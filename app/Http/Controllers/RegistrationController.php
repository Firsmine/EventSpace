<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use DB;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function register(Request $request, Event $event)
    {
        $user = $request->user();

        return DB::transaction(function () use ($event, $user) {
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($lockedEvent->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration unavailable'
                ], 422);
            }
            $registration = Registration::where('event_id', $lockedEvent->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()->first();
            if ($registration && $registration->status !== 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'You can not register twice'
                ], 409);
            }
            $participantsCount = $lockedEvent->activeRegistration()->count();
            if ($participantsCount >= $lockedEvent->capacity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event is full'
                ], 409);
            }
            if ($registration) {
                $registration->update([
                    'status' => 'registered',
                    'registered_at' => now(),
                ]);
            } else {
                $registration = Registration::create([
                    'event_id' => $lockedEvent->id,
                    'user_id' => $user->id,
                    'registered_at' => now(),
                    'status' => 'registered',
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => 'Registration success',
                'data' => $registration->load(['event:id,title,event_date', 'user:id,name,username']),
            ], 201);
        });
    }

    public function unregister(Request $request, Event $event)
    {
        $registration = Registration::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['registered', 'attended'])
            ->first();
        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'You have not registered',
            ], 404);
        }
        $registration->update(['status' => 'canceled']);
        return response()->json([
            'success' => true,
            'message' => 'Unregister success',
            'data' => $registration->refresh()
        ]);
    }

    public function participants(Request $request, Event $event)
    {
        $owner = $this->ensureOwner($request, $event);
        if (!$owner)
            return response()->json([
                'success' => false,
                'message' => 'You do not have access'
            ], 403);
        $participants = $event->activeRegistration()
            ->with('user:id,name,username')
            ->orderBy('created_at')
            ->paginate(20);
        return response()->json([
            'success' => true,
            'message' => 'Get participants success',
            'data' => $participants
        ]);
    }

    public function myEvents(Request $request)
    {
        try {
            $registrations = Registration::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['registered', 'attended'])
                ->with(['event.category', 'event.creator:id,name,username'])
                ->orderByDesc('created_at')
                ->paginate(10);

            if ($registrations->isEmpty())
                return response()->json([
                    'success' => false,
                    'message' => 'You have not registered for any events'
                ], 404);

            return response()->json([
                'success' => true,
                'message' => 'Get registration success',
                'data' => $registrations
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get your events.',
            ], 500);
        }
    }
    public function status(Request $request, Event $event)
    {
        try {
            $registered = Registration::query()
                ->where('event_id', $event->id)
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['registered', 'attended'])
                ->first();
            if (!$registered) {
                return response()->json([
                    'success' => true,
                    'message' => 'You are not registered for this event.',
                ], 200);
            }
            return response()->json([
                'success' => true,
                'message' => 'Get status success',
                'data' => $registered
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get registration status',
            ], 500);
        }
    }

    // private function ensureOwner(Request $request, Event $event): void
    // {
    //     abort_unless($request->user()->id === $event->user_id, 403, 'You do not have access');
    // }

    private function ensureOwner(Request $request, Event $event): bool
    {
        return $request->user()->id === $event->created_by;
    }
}
