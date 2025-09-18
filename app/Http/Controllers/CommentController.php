<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function index($lessonId)
    {
        $comments = Comment::where('lesson_id', $lessonId)
            ->with('student:id,full_name')
            ->latest()
            ->get();

        return response()->json($comments);
    }

    public function store(Request $request, $lessonId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        if (!$user->student) {
            return response()->json(['error' => 'Only students can comment'], 403);
        }

        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'lesson_id' => $lessonId,
            'student_id' => $user->student->id,
            'body' => $request->body,
        ]);

        return response()->json($comment, 201);
    }

    public function update(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        if (!$user->student) {
            return response()->json(['error' => 'Only students can update comments'], 403);
        }

        $comment = Comment::findOrFail($id);

        if ($comment->student_id !== $user->student->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $comment->update(['body' => $request->body]);

        return response()->json($comment);
    }

    public function destroy($id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        if (!$user->student) {
            return response()->json(['error' => 'Only students can delete comments'], 403);
        }

        $comment = Comment::findOrFail($id);

        if ($comment->student_id !== $user->student->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}
