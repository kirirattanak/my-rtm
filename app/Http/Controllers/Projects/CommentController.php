<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\CommentRequest;
use App\Models\ActivityLog;
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

        $data = $request->validated();

        $businessRequirement->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        ActivityLog::create([
            'subject_type' => BusinessRequirement::class,
            'subject_id'   => $businessRequirement->id,
            'user_id'      => $request->user()->id,
            'action'       => 'commented',
            'data'         => ['title' => $businessRequirement->title],
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function storeTr(CommentRequest $request, Project $project, TechnicalRequirement $technicalRequirement): RedirectResponse
    {
        $this->authorize('view', $technicalRequirement);

        $data = $request->validated();

        $technicalRequirement->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        ActivityLog::create([
            'subject_type' => TechnicalRequirement::class,
            'subject_id'   => $technicalRequirement->id,
            'user_id'      => $request->user()->id,
            'action'       => 'commented',
            'data'         => ['title' => $technicalRequirement->title],
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }
}
