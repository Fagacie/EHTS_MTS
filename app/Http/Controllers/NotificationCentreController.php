<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationCentreController extends Controller
{
    public function portalIndex(Request $request): View
    {
        return $this->renderCentre($request, 'portal.notifications', 'portal');
    }

    public function instructorIndex(Request $request): View
    {
        return $this->renderCentre($request, 'instructor.notifications', 'instructor');
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        if ($request->boolean('open') && is_string($notification->data['action_url'] ?? null)) {
            $target = $notification->data['action_url'];
            if (str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
                return redirect()->to($target);
            }
        }

        return back()->with('status', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $updated = $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return back()->with(
            'status',
            $updated > 0 ? "{$updated} notification(s) marked as read." : 'All notifications are already read.'
        );
    }

    protected function renderCentre(Request $request, string $view, string $portalContext): View
    {
        $user = $request->user();
        $filter = $request->string('filter')->toString() === 'unread' ? 'unread' : 'all';

        $query = $user->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        return view($view, [
            'user' => $user,
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
            'notifications' => $query->paginate(15)->withQueryString(),
            'portalContext' => $portalContext,
        ]);
    }
}
