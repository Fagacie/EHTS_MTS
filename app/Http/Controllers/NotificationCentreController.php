<?php

namespace App\Http\Controllers;

use App\Support\InstructorPresenter;
use App\Support\PortalPresenter;
use App\Support\PublicCataloguePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class NotificationCentreController extends Controller
{
    public function portalIndex(Request $request): InertiaResponse
    {
        $user = $request->user();
        $filter = $request->string('filter')->toString() === 'unread' ? 'unread' : 'all';

        $query = $user->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $paginator = $query->paginate(15)->withQueryString();

        return Inertia::render('Portal/Notifications/Index', [
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
            'markAllReadUrl' => route('portal.notifications.read-all'),
            'notifications' => $paginator->getCollection()
                ->map(static fn (DatabaseNotification $notification): array => PortalPresenter::notificationItem($notification))
                ->values()
                ->all(),
            'pagination' => PublicCataloguePresenter::paginationMeta($paginator),
        ]);
    }

    public function instructorIndex(Request $request): InertiaResponse
    {
        $user = $request->user();
        $filter = $request->string('filter')->toString() === 'unread' ? 'unread' : 'all';

        $query = $user->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $paginator = $query->paginate(15)->withQueryString();

        return Inertia::render('Instructor/Notifications/Index', [
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
            'markAllReadUrl' => route('instructor.notifications.read-all'),
            'notifications' => $paginator->getCollection()
                ->map(static fn (DatabaseNotification $notification): array => InstructorPresenter::notificationItem($notification))
                ->values()
                ->all(),
            'pagination' => PublicCataloguePresenter::paginationMeta($paginator),
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse|SymfonyResponse
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
                if ($request->header('X-Inertia') && ! PortalPresenter::isInertiaPath($target)) {
                    return Inertia::location($target);
                }

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
