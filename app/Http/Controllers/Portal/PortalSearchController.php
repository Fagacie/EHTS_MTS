<?php

namespace App\Http\Controllers\Portal;

use App\Enums\EntitlementStatus;
use App\Enums\ModuleStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $timezone = (string) config('etms.display_timezone', 'Asia/Kuala_Lumpur');

        $rawQuery = trim($request->string('q')->toString());
        $queryText = mb_substr($rawQuery, 0, 80);

        if (mb_strlen($queryText) < 2) {
            return response()
                ->json([
                    'query' => $queryText,
                    'totalCount' => 0,
                    'results' => [
                        'bookings' => [],
                        'orders' => [],
                        'certificates' => [],
                        'modules' => [],
                        'catalogue' => [],
                    ],
                ])
                ->header('Cache-Control', 'no-store, private');
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $queryText);
        $like = "%{$escaped}%";

        $bookings = Booking::query()
            ->with('trainingSession:id,module_id,session_code,title,starts_at,timezone,venue_name,venue_city')
            ->where('user_id', $user->id)
            ->where(function ($query) use ($like): void {
                $query->where('booking_reference', 'like', $like)
                    ->orWhereHas('trainingSession', function ($sessionQuery) use ($like): void {
                        $sessionQuery->where('title', 'like', $like)
                            ->orWhere('session_code', 'like', $like)
                            ->orWhere('venue_name', 'like', $like)
                            ->orWhere('venue_city', 'like', $like);
                    });
            })
            ->latest('created_at')
            ->limit(4)
            ->get()
            ->map(function (Booking $booking) use ($timezone): array {
                $session = $booking->trainingSession;
                $sessionTz = $session?->timezone ?: $timezone;
                $start = $session?->starts_at?->copy()->timezone($sessionTz);
                $venue = $session?->venue_city ?: $session?->venue_name;

                return [
                    'id' => $booking->id,
                    'title' => $session?->title ?? 'Physical Training Session',
                    'subtitle' => "Booking \u{00B7} {$booking->booking_reference}",
                    'meta' => trim(implode(" \u{00B7} ", array_filter([
                        $start ? $start->format('d M Y') : null,
                        $venue,
                    ]))) ?: 'Physical training',
                    'statusValue' => $booking->status->value,
                    'statusLabel' => $booking->status->label(),
                    'url' => route('portal.bookings.show', $booking),
                ];
            })
            ->values()
            ->all();

        $orders = Order::query()
            ->with('items:id,order_id,item_title_snapshot')
            ->where('user_id', $user->id)
            ->where(function ($query) use ($like): void {
                $query->where('order_number', 'like', $like)
                    ->orWhereHas('items', function ($itemQuery) use ($like): void {
                        $itemQuery->where('item_title_snapshot', 'like', $like);
                    });
            })
            ->latest('created_at')
            ->limit(4)
            ->get()
            ->map(function (Order $order): array {
                $firstTitle = $order->items->first()?->item_title_snapshot;

                return [
                    'id' => $order->id,
                    'title' => $firstTitle ?: $order->order_type->label(),
                    'subtitle' => "Order \u{00B7} {$order->order_number}",
                    'meta' => $order->formattedTotal(),
                    'statusValue' => $order->status->value,
                    'statusLabel' => $order->status->label(),
                    'url' => route('portal.orders.show', $order),
                ];
            })
            ->values()
            ->all();

        $certificates = Certificate::query()
            ->select([
                'id',
                'certificate_number',
                'user_id',
                'record_type',
                'status',
                'training_title_snapshot',
                'issued_at',
            ])
            ->where('user_id', $user->id)
            ->where(function ($query) use ($like): void {
                $query->where('certificate_number', 'like', $like)
                    ->orWhere('training_title_snapshot', 'like', $like);
            })
            ->orderByDesc('issued_at')
            ->limit(4)
            ->get()
            ->map(function (Certificate $certificate) use ($timezone): array {
                $issued = $certificate->issued_at?->copy()->timezone($timezone)->format('d M Y');

                return [
                    'id' => $certificate->id,
                    'title' => $certificate->training_title_snapshot,
                    'subtitle' => "Certificate \u{00B7} {$certificate->certificate_number}",
                    'meta' => $issued ? "Issued {$issued}" : $certificate->recordTypeLabel(),
                    'statusValue' => $certificate->status->value,
                    'statusLabel' => $certificate->status->label(),
                    'url' => route('portal.certificates.show', $certificate),
                ];
            })
            ->values()
            ->all();

        $modules = ModuleEntitlement::query()
            ->with('module.category:id,name')
            ->where('user_id', $user->id)
            ->where('status', EntitlementStatus::Active->value)
            ->whereHas('module', function ($moduleQuery) use ($like): void {
                $moduleQuery->where('title', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('summary', 'like', $like);
            })
            ->latest('granted_at')
            ->limit(4)
            ->get()
            ->filter(static fn (ModuleEntitlement $entitlement): bool => $entitlement->module !== null)
            ->map(function (ModuleEntitlement $entitlement): array {
                $module = $entitlement->module;

                return [
                    'id' => $module->id,
                    'title' => $module->title,
                    'subtitle' => "My Learning \u{00B7} {$module->code}",
                    'meta' => $module->category?->name ?? 'Self-paced module',
                    'statusValue' => 'active',
                    'statusLabel' => 'Active',
                    'url' => route('portal.modules.show', $module),
                ];
            })
            ->values()
            ->all();

        $catalogue = Module::query()
            ->with('category:id,name')
            ->where('status', ModuleStatus::Published->value)
            ->where(function ($moduleQuery) use ($like): void {
                $moduleQuery->where('title', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('summary', 'like', $like);
            })
            ->orderBy('title')
            ->limit(4)
            ->get()
            ->map(function (Module $module): array {
                $priceMeta = $module->is_available_independently && $module->independent_price !== null
                    ? 'RM '.number_format((float) $module->independent_price, 2)
                    : ($module->category?->name ?? 'Training programme');

                return [
                    'id' => $module->id,
                    'title' => $module->title,
                    'subtitle' => "Catalogue \u{00B7} {$module->code}",
                    'meta' => $priceMeta,
                    'statusValue' => null,
                    'statusLabel' => null,
                    'url' => route('catalogue.modules.show', $module),
                ];
            })
            ->values()
            ->all();

        $totalCount = count($bookings) + count($orders) + count($certificates) + count($modules) + count($catalogue);

        return response()
            ->json([
                'query' => $queryText,
                'totalCount' => $totalCount,
                'results' => [
                    'bookings' => $bookings,
                    'orders' => $orders,
                    'certificates' => $certificates,
                    'modules' => $modules,
                    'catalogue' => $catalogue,
                ],
            ])
            ->header('Cache-Control', 'no-store, private');
    }
}
