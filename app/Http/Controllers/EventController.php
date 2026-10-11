<?php

namespace App\Http\Controllers;

use App\Models\Event;
use DB;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'sometimes|integer|exists:categories,id',
            'search' => 'sometimes|string|max:100'
        ]);
        $query = Event::query()
            ->where('status', 'published')
            ->with(['category', 'creator:id,name,username'])
            ->withCount('activeRegistration as participants_count');

        if (isset($data['category_id'])) {
            $query->where('category_id', $data['category_id']);
        }
        if (isset($data['search'])) {
            $search = $data['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }
        return response()->json([
            'success' => true,
            'message' => 'Get all event success',
            'data' => $query->orderBy('event_date')->paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'event_date' => 'required|date',
            'capacity' => 'required|integer|min:1'
        ]);
        $event = $request->user()->events()->create($data);
        return response()->json([
            'success' => true,
            'message' => 'Create event success',
            'data' => $event->load(['category', 'creator:id,name,username'])
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Event $event)
    {
        if ($event->status !== 'published' && $request->user()->id !== $event->user_id) {
            abort(404, 'Event not found');
        }
        return response()->json([
            'success' => true,
            'message' => 'Get event success',
            'data' => $event->load(['category', 'creator:id,name,username'])
                ->loadCount('activeRegistrations as participants_count')
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $this->ensureOwner($request, $event);
        $data = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'event_date' => 'required|date',
            'capacity' => 'required|integer|min:1'
        ]);
        if (isset($data['capacity'])) {
            $activeCount = $event->activeRegistrations()->count();
            if ($data['capacity'] < $activeCount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Capacity cannot be less than the active participants ({$activeCount})',
                ], 422);
            }
        }
        $event->update($data);
        return response()->json([
            'success' => true,
            'message' => 'Update event success',
            'data' => $event->refresh()->load(['category', 'creator:id,name,username'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Event $event)
    {
        $this->ensureOwner($request, $event);
        DB::transaction(fn() => $event->delete());
        return response()->json([
            'success' => true,
            'message' => 'Delete event success'
        ]);
    }

    public function updateStatus(Request $request, Event $event)
    {
        $this->ensureOwner($request, $event);
        $data = $request->validate(['status' => 'required|in:draft,published,cancelled,finished']);
        $event->update(['status' => $data['status']]);
        return response()->json([
            'success' => true,
            'message' => "Update status success",
            'data' => $event->refresh()
        ]);
    }
    private function ensureOwner(Request $request, Event $event): void
    {
        abort_unless($request->user() && $request->user()->id === $event->user_id, 403, 'You do not have access to manage this event');
    }
}
