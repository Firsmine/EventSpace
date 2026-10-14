<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Event;
use Illuminate\Http\Request;
use League\Config\Exception\ValidationException;
use Throwable;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Event $event)
    {
        $this->ensureCanView($request, $event);
        return response()->json([
            'success' => true,
            'message' => 'Get all comments success',
            'data' => $event->comments()->with('user:id,name,username')->latest()->paginate(20)
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
    public function store(Request $request, Event $event)
    {
        try {
            $this->ensureCanView($request, $event);
            $data = $request->validate([
                'comment' => 'required|string|max:5000'
            ]);
            $comment = $event->comments()->create([
                'user_id' => $request->user()->id,
                'comment' => $data['comment']
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Create comment success',
                'data' => $comment->load('user:id,name,username')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Comment $comment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comment $comment)
    {
        try {
            $this->ensureCommentOwner($request, $comment);
            $data = $request->validate(['comment' => 'required|string|max:5000']);
            $comment->update($data);
            return response()->json([
                'success' => true,
                'message' => 'Update comment success',
                'data' => $comment->refresh()->load('user:id,name,username')
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Comment $comment)
    {
        try {
            $this->ensureCommentOwner($request, $comment);
            $comment->delete();
            return response()->json([
                'success' => true,
                'message' => 'Delete comment success'
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment.',
            ], 500);
        }
    }

    private function ensureCommentOwner(Request $request, Comment $comment): void
    {
        abort_unless(
            $request->user()->id === $comment->user_id,
            403,
            'You only can manage your own comment'
        );
    }
    private function ensureCanView(Request $request, Event $event): void
    {
        // event draft atau non-published hanya bisa dilihat oleh pemiliknya
        abort_unless($event->status === 'published' || $request->user()->id === $event->user_id, 404, 'Event not found');
    }
}
