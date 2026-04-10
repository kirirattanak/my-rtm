<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(30)
            ->through(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type'] ?? 'unknown',
                'data'       => $n->data,
                'read_at'    => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        return Inertia::render('Notifications', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
