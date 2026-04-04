<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\BusinessRequirement;
use App\Models\Comment;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function storeBr(Request $request, Project $project, BusinessRequirement $businessRequirement): RedirectResponse
    {
        $this->authorize('view', $businessRequirement);

        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $businessRequirement->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function storeTr(Request $request, Project $project, TechnicalRequirement $technicalRequirement): RedirectResponse
    {
        $this->authorize('view', $technicalRequirement);

        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $technicalRequirement->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        if ($comment->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }
}
