<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntitlementStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\NotificationDelivery;
use App\Models\Order;
use App\Models\User;
use App\Services\Notification\EtmsNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminCommerceGovernanceController extends Controller
{
    public function orders(Request $request): Response
    {
        Gate::authorize('viewAny', Order::class);

        $search = trim((string) $request->query('q', ''));
        $orderType = (string) $request->query('order_type', '');
        $status = (string) $request->query('status', '');

        $orders = Order::query()
            ->withCount(['items', 'payments', 'refunds'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            }))
            ->when($orderType !== '' && OrderType::tryFrom($orderType), fn ($query) => $query->where('order_type', $orderType))
            ->when($status !== '' && OrderStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(static fn (Order $order): array => [
                'id' => $order->id,
                'number' => $order->order_number,
                'customer' => $order->customer_name,
                'email' => $order->customer_email,
                'company' => $order->company_name,
                'orderType' => $order->order_type instanceof OrderType ? $order->order_type->label() : (string) $order->order_type,
                'total' => number_format((float) $order->total_amount, 2),
                'status' => $order->status instanceof OrderStatus ? $order->status->label() : (string) $order->status,
                'paidAt' => $order->paid_at?->toIso8601String(),
                'createdAt' => $order->created_at?->toIso8601String(),
                'itemsCount' => (int) $order->items_count,
            ]);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => ['q' => $search, 'order_type' => $orderType, 'status' => $status],
            'orderTypes' => collect(OrderType::cases())->map(static fn (OrderType $type): array => ['value' => $type->value, 'label' => $type->label()])->values()->all(),
            'statuses' => collect(OrderStatus::cases())->map(static fn (OrderStatus $item): array => ['value' => $item->value, 'label' => $item->label()])->values()->all(),
        ]);
    }

    public function showOrder(Order $order): Response
    {
        Gate::authorize('view', $order);
        $order->load(['items', 'payments', 'bookings', 'moduleEntitlements.module', 'refunds']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'customer' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
                'company' => $order->company_name,
                'orderType' => $order->order_type instanceof OrderType ? $order->order_type->label() : (string) $order->order_type,
                'status' => $order->status instanceof OrderStatus ? $order->status->label() : (string) $order->status,
                'subtotal' => number_format((float) $order->subtotal_amount, 2),
                'total' => number_format((float) $order->total_amount, 2),
                'billingAddress' => collect([
                    $order->billing_address_line_1,
                    $order->billing_address_line_2,
                    $order->billing_city,
                    $order->billing_state,
                    $order->billing_postcode,
                    $order->billing_country,
                ])->filter()->implode(', '),
                'paidAt' => $order->paid_at?->toIso8601String(),
                'createdAt' => $order->created_at?->toIso8601String(),
                'items' => $order->items->map(static fn ($item): array => [
                    'code' => $item->item_code_snapshot,
                    'title' => $item->item_title_snapshot,
                    'quantity' => (int) $item->quantity,
                    'total' => number_format((float) $item->line_total, 2),
                ])->values()->all(),
                'payments' => $order->payments->map(static fn ($payment): array => [
                    'reference' => $payment->payment_reference,
                    'amount' => number_format((float) $payment->amount, 2),
                    'status' => $payment->status instanceof \App\Enums\PaymentStatus ? $payment->status->label() : (string) $payment->status,
                ])->values()->all(),
                'entitlements' => $order->moduleEntitlements->map(static fn (ModuleEntitlement $entitlement): array => [
                    'module' => $entitlement->module?->title,
                    'status' => $entitlement->status instanceof EntitlementStatus ? $entitlement->status->label() : (string) $entitlement->status,
                ])->values()->all(),
                'refunds' => $order->refunds->map(static fn ($refund): array => [
                    'reference' => $refund->refund_reference,
                    'amount' => number_format((float) $refund->amount, 2),
                    'status' => $refund->status instanceof \App\Enums\RefundStatus ? $refund->status->label() : (string) $refund->status,
                ])->values()->all(),
            ],
        ]);
    }

    public function entitlements(Request $request): Response
    {
        Gate::authorize('viewAny', ModuleEntitlement::class);

        $search = trim((string) $request->query('q', ''));
        $moduleId = (string) $request->query('module_id', '');
        $status = (string) $request->query('status', '');

        $entitlements = ModuleEntitlement::query()
            ->with(['user:id,name,email', 'module:id,code,title', 'order:id,order_number'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('module', fn ($module) => $module->where('code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', "%{$search}%"));
            }))
            ->when($moduleId !== '' && ctype_digit($moduleId), fn ($query) => $query->where('module_id', (int) $moduleId))
            ->when($status !== '' && EntitlementStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->latest('granted_at')
            ->paginate(15)
            ->withQueryString()
            ->through(static fn (ModuleEntitlement $entitlement): array => [
                'id' => $entitlement->id,
                'customer' => $entitlement->user?->name,
                'email' => $entitlement->user?->email,
                'moduleCode' => $entitlement->module?->code,
                'moduleTitle' => $entitlement->module?->title,
                'source' => $entitlement->grant_source === 'admin_grant' ? 'Admin Grant' : 'Purchased',
                'orderNumber' => $entitlement->order?->order_number,
                'status' => $entitlement->status instanceof EntitlementStatus ? $entitlement->status->label() : (string) $entitlement->status,
                'grantedAt' => $entitlement->granted_at?->toIso8601String(),
                'canRevoke' => $entitlement->status === EntitlementStatus::Active,
            ]);

        return Inertia::render('Admin/ModuleEntitlements/Index', [
            'entitlements' => $entitlements,
            'filters' => ['q' => $search, 'module_id' => $moduleId, 'status' => $status],
            'modules' => Module::query()->orderBy('code')->get(['id', 'code', 'title'])->map(static fn (Module $module): array => [
                'id' => $module->id,
                'label' => "{$module->code} — {$module->title}",
            ])->all(),
            'statuses' => collect(EntitlementStatus::cases())->map(static fn (EntitlementStatus $item): array => ['value' => $item->value, 'label' => $item->label()])->values()->all(),
            'customers' => User::query()->whereHas('roles', fn ($query) => $query->where('slug', RoleSlug::Customer->value))->orderBy('name')->get(['id', 'name', 'email'])->map(static fn (User $user): array => [
                'id' => $user->id,
                'label' => "{$user->name} ({$user->email})",
            ])->all(),
        ]);
    }

    public function grantEntitlement(Request $request, EtmsNotificationService $notifications): RedirectResponse
    {
        Gate::authorize('create', ModuleEntitlement::class);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'grant_reason' => ['required', 'string', 'max:500'],
        ]);

        $active = ModuleEntitlement::query()->where('user_id', $data['user_id'])->where('module_id', $data['module_id'])->where('status', EntitlementStatus::Active->value)->exists();
        if ($active) {
            return back()->withErrors(['entitlement' => 'This customer already has active access to the selected module.']);
        }

        $entitlement = ModuleEntitlement::query()->create([
            'user_id' => $data['user_id'],
            'module_id' => $data['module_id'],
            'order_id' => null,
            'order_item_id' => null,
            'grant_source' => 'admin_grant',
            'status' => EntitlementStatus::Active,
            'granted_at' => now(),
            'granted_by' => $request->user()->id,
            'grant_reason' => $data['grant_reason'],
        ]);

        AuditLog::record(
            event: 'module_entitlement.admin_granted',
            auditableType: ModuleEntitlement::class,
            auditableId: $entitlement->id,
            oldValues: null,
            newValues: ['user_id' => $entitlement->user_id, 'module_id' => $entitlement->module_id, 'grant_source' => 'admin_grant'],
            reason: $data['grant_reason'],
            actor: $request->user(),
        );

        $entitlement->loadMissing(['user', 'module']);
        if ($entitlement->user && $entitlement->module) {
            $notifications->notifyUser(
                user: $entitlement->user,
                eventType: 'module.access_granted',
                title: "Module Learning Access Granted: {$entitlement->module->title}",
                message: "You now have access to learning materials for {$entitlement->module->title} ({$entitlement->module->code}).",
                actionUrl: route('portal.modules.show', $entitlement->module, false),
                actionLabel: 'Open Learning Materials',
                details: ['Module' => "{$entitlement->module->code} — {$entitlement->module->title}", 'Grant Type' => 'Administrator Grant'],
                reference: $entitlement->module->code,
            );
        }

        return back()->with('success', 'Module entitlement granted.');
    }

    public function revokeEntitlement(Request $request, ModuleEntitlement $entitlement): RedirectResponse
    {
        Gate::authorize('update', $entitlement);
        $data = $request->validate(['revocation_reason' => ['required', 'string', 'max:500']]);

        if ($entitlement->status !== EntitlementStatus::Active) {
            return back()->withErrors(['entitlement' => 'This entitlement is already revoked.']);
        }

        $entitlement->update([
            'status' => EntitlementStatus::Revoked,
            'revoked_at' => now(),
            'revoked_by' => $request->user()->id,
            'revocation_reason' => $data['revocation_reason'],
        ]);

        AuditLog::record(
            event: 'module_entitlement.revoked',
            auditableType: ModuleEntitlement::class,
            auditableId: $entitlement->id,
            oldValues: ['status' => EntitlementStatus::Active->value],
            newValues: ['status' => EntitlementStatus::Revoked->value],
            reason: $data['revocation_reason'],
            actor: $request->user(),
        );

        return back()->with('success', 'Module entitlement revoked.');
    }

    public function auditLogs(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('audit_logs.view'), 403);

        $event = (string) $request->query('event', '');
        $search = trim((string) $request->query('q', ''));

        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('event', 'like', "%{$search}%")
                    ->orWhere('auditable_type', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('actor', fn ($actor) => $actor->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            }))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(static fn (AuditLog $log): array => [
                'id' => $log->id,
                'createdAt' => $log->created_at?->toIso8601String(),
                'event' => $log->event,
                'actor' => $log->actor?->name,
                'actorRole' => $log->actor_role,
                'targetType' => $log->auditable_type,
                'targetId' => $log->auditable_id,
                'reason' => $log->reason,
                'ipAddress' => $log->ip_address,
                'oldValues' => $log->old_values,
                'newValues' => $log->new_values,
            ]);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => ['q' => $search, 'event' => $event],
            'events' => AuditLog::query()->distinct()->whereNotNull('event')->orderBy('event')->pluck('event')->values()->all(),
        ]);
    }

    public function notificationDeliveries(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('announcements.manage'), 403);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $eventType = (string) $request->query('event_type', '');

        $deliveries = NotificationDelivery::query()
            ->with('user:id,name,email')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('recipient_email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('provider_message_id', 'like', "%{$search}%");
            }))
            ->when($status !== '' && NotificationDeliveryStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->when($eventType !== '', fn ($query) => $query->where('event_type', $eventType))
            ->latest('queued_at')
            ->paginate(20)
            ->withQueryString()
            ->through(static fn (NotificationDelivery $delivery): array => [
                'id' => $delivery->id,
                'queuedAt' => $delivery->queued_at?->toIso8601String(),
                'eventType' => $delivery->event_type,
                'recipient' => $delivery->recipient_email,
                'subject' => $delivery->subject,
                'mailer' => $delivery->mailer,
                'status' => $delivery->status instanceof NotificationDeliveryStatus ? $delivery->status->label() : (string) $delivery->status,
                'sentAt' => $delivery->sent_at?->toIso8601String(),
                'errorMessage' => $delivery->error_message,
            ]);

        return Inertia::render('Admin/NotificationDeliveries/Index', [
            'deliveries' => $deliveries,
            'filters' => ['q' => $search, 'status' => $status, 'event_type' => $eventType],
            'statuses' => collect(NotificationDeliveryStatus::cases())->map(static fn (NotificationDeliveryStatus $item): array => ['value' => $item->value, 'label' => $item->label()])->values()->all(),
            'eventTypes' => NotificationDelivery::query()->distinct()->whereNotNull('event_type')->orderBy('event_type')->pluck('event_type')->values()->all(),
        ]);
    }
}
