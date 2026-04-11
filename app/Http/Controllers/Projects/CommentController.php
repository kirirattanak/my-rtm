<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\CommentRequest;
use App\Models\BusinessRequirement;
use App\Models\Comment;
use App\Models\Project;
use App\Models\TechnicalRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function storeBr(CommentRequest $request, Project $project, BusinessRequirement $businessRequirement): RedirectResponse
    {
        $this->authorize('view', $businessRequirement);

        $businessRequirement->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $request->validated('body'),
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function storeTr(CommentRequest $request, Project $project, TechnicalRequirement $technicalRequirement): RedirectResponse
    {
        $this->authorize('view', $technicalRequirement);

        $technicalRequirement->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $request->validated('body'),
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function destroy(Request $request, Project $project, Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }
}
