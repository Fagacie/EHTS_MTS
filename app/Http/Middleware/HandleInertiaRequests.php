<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use App\Models\User;
use App\Support\InstructorPresenter;
use App\Support\PortalPresenter;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default across Inertia responses.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => fn (): array => [
                'brandName' => (string) SystemSetting::getValue('system.organisation_name', config('etms.brand_name', 'Ecohydrotech Solution')),
                'shortName' => (string) SystemSetting::getValue('system.short_name', config('etms.short_name', 'ETMS')),
                'displayTimezone' => (string) SystemSetting::getValue('system.display_timezone', config('etms.display_timezone', 'Asia/Kuala_Lumpur')),
                'currency' => (string) SystemSetting::getValue('system.currency', config('etms.currency', 'MYR')),
                'year' => (int) now()->year,
                'logoUrl' => asset('images/ecohydrotech-logo.png'),
            ],
            'auth' => fn (): array => [
                'user' => $this->resolveAuthUser($request),
            ],
            'flash' => [
                'status' => fn (): ?string => $request->session()->get('status'),
                'error' => fn (): ?string => $request->session()->get('error'),
            ],
            'routes' => fn (): array => [
                'home' => route('home'),
                'catalogue' => route('catalogue.index'),
                'catalogueIndependent' => route('catalogue.index', ['availability' => 'independent']),
                'sessions' => route('sessions.index'),
                'instructors' => route('instructors.index'),
                'login' => route('login'),
                'register' => route('register'),
                'logout' => route('logout'),
                'portalDashboard' => route('portal.dashboard'),
                'portalSearch' => route('portal.search'),
                'portalModules' => route('portal.modules.index'),
                'portalBookings' => route('portal.bookings.index'),
                'portalRecordings' => route('portal.recordings.index'),
                'portalCertificates' => route('portal.certificates.index'),
                'portalOrders' => route('portal.orders.index'),
                'portalNotifications' => route('portal.notifications.index'),
                'portalProfile' => route('portal.profile.edit'),
                'portalSecurity' => route('portal.security.edit'),
                'instructorDashboard' => route('instructor.dashboard'),
                'instructorSessions' => route('instructor.sessions.index'),
                'instructorProfile' => route('instructor.profile.edit'),
                'instructorNotifications' => route('instructor.notifications.index'),
                'adminDashboard' => route('admin-vue.dashboard'),
            ],
            'portal' => fn (): ?array => $this->resolvePortalShellData($request),
            'instructorShell' => fn (): ?array => $this->resolveInstructorShellData($request),
            'currentPath' => fn (): string => $this->normalizeCurrentPath($request),
        ];
    }

    /**
     * Return a minimal, non-sensitive public session DTO for the authenticated user.
     *
     * @return array<string, mixed>|null
     */
    private function resolveAuthUser(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        $user->loadMissing('roles');

        $portals = array_map(
            static fn (array $portal): array => [
                'slug' => $portal['slug'],
                'label' => $portal['label'],
                'description' => $portal['description'],
                'url' => $portal['url'],
            ],
            $user->availablePortals(),
        );

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'photoUrl' => $user->photoUrl(),
            'isCustomer' => $user->isCustomer(),
            'isInstructor' => $user->isInstructor(),
            'isAdministrator' => $user->isAdministrator(),
            'defaultDashboardUrl' => $user->defaultDashboardUrl(),
            'availablePortals' => $portals,
            'unreadNotificationsCount' => $user->unreadNotifications()->count(),
        ];
    }

    /**
     * Return shared Trainee Portal shell metrics and latest notifications on /portal routes only.
     *
     * @return array<string, mixed>|null
     */
    private function resolvePortalShellData(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isCustomer()) {
            return null;
        }

        if (! $request->is('portal') && ! $request->is('portal/*')) {
            return null;
        }

        $recentNotifications = $user->notifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(static fn (DatabaseNotification $notification): array => PortalPresenter::notificationItem($notification))
            ->values()
            ->all();

        return [
            'recentNotifications' => $recentNotifications,
            'markAllReadUrl' => route('portal.notifications.read-all'),
        ];
    }

    /**
     * Return shared Instructor Portal shell metrics and latest notifications on /instructor routes only.
     *
     * @return array<string, mixed>|null
     */
    private function resolveInstructorShellData(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isInstructor()) {
            return null;
        }

        if (! $request->is('instructor') && ! $request->is('instructor/*')) {
            return null;
        }

        $recentNotifications = $user->notifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(static fn (DatabaseNotification $notification): array => InstructorPresenter::notificationItem($notification))
            ->values()
            ->all();

        return [
            'recentNotifications' => $recentNotifications,
            'markAllReadUrl' => route('instructor.notifications.read-all'),
        ];
    }

    private function normalizeCurrentPath(Request $request): string
    {
        $path = trim($request->path(), '/');

        return $path === '' ? '/' : "/{$path}";
    }
}
