<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\RefundMethod;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\Payment\PaymentOrchestrationService;
use App\Services\Payment\RefundService;
use DomainException;
use RuntimeException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminFinanceController extends Controller
{
    public function verifyPayment(Payment $payment, PaymentOrchestrationService $service, Request $request): RedirectResponse
    {
        try {
            $service->verifyPaymentViaApi($payment, $request->user());
        } catch (DomainException|RuntimeException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return back()->with('success', 'Payment verified against Billplz.');
    }

    public function recordRefund(Request $request, Payment $payment, RefundService $service): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$payment->refundableBalance()],
            'refund_method' => ['required', 'in:'.implode(',', array_column(RefundMethod::cases(), 'value'))],
            'disbursement_reference' => ['nullable', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $service->recordAndCompleteAdminRefund(
                payment: $payment,
                admin: $request->user(),
                amount: (float) $data['amount'],
                method: $data['refund_method'],
                reason: $data['reason'],
                disbursementReference: $data['disbursement_reference'] ?? null,
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return back()->with('success', 'Refund recorded and completed.');
    }

    public function completeRefund(Request $request, Refund $refund, RefundService $service): RedirectResponse
    {
        $data = $request->validate([
            'refund_method' => ['required', 'in:'.implode(',', array_column(RefundMethod::cases(), 'value'))],
            'disbursement_reference' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $service->approveAndCompleteRequestedRefund(
                refund: $refund,
                admin: $request->user(),
                method: $data['refund_method'],
                disbursementReference: $data['disbursement_reference'] ?? null,
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['refund' => $exception->getMessage()]);
        }

        return back()->with('success', 'Refund approved and completed.');
    }

    public function rejectRefund(Request $request, Refund $refund, RefundService $service): RedirectResponse
    {
        $data = $request->validate(['failure_reason' => ['required', 'string', 'max:500']]);

        try {
            $service->rejectRefund($refund, $request->user(), $data['failure_reason']);
        } catch (DomainException $exception) {
            return back()->withErrors(['refund' => $exception->getMessage()]);
        }

        return back()->with('success', 'Refund request rejected.');
    }

    public function payments(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $payments = Payment::query()
            ->with(['user:id,name,email', 'order:id,order_number'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', "%{$search}%"));
            }))
            ->when($status !== '' && PaymentStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(static fn (Payment $payment): array => [
                'id' => $payment->id,
                'reference' => $payment->payment_reference,
                'orderNumber' => $payment->order?->order_number,
                'customer' => $payment->user?->name ?? 'Unknown',
                'email' => $payment->user?->email,
                'amount' => number_format((float) $payment->amount, 2),
                'refundedAmount' => number_format((float) $payment->refunded_amount, 2),
                'status' => $payment->status instanceof PaymentStatus ? $payment->status->label() : (string) $payment->status,
                'paidAt' => $payment->paid_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Finance/Payments', [
            'payments' => $payments,
            'filters' => ['q' => $search, 'status' => $status],
            'statuses' => collect(PaymentStatus::cases())->map(static fn (PaymentStatus $item): array => ['value' => $item->value, 'label' => $item->label()])->values()->all(),
        ]);
    }

    public function refunds(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $refunds = Refund::query()
            ->with(['user:id,name,email', 'order:id,order_number', 'payment:id,payment_reference'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('refund_reference', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', "%{$search}%"));
            }))
            ->when($status !== '' && RefundStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->latest('requested_at')
            ->paginate(15)
            ->withQueryString()
            ->through(static fn (Refund $refund): array => [
                'id' => $refund->id,
                'reference' => $refund->refund_reference,
                'orderNumber' => $refund->order?->order_number,
                'customer' => $refund->user?->name ?? 'Unknown',
                'amount' => number_format((float) $refund->amount, 2),
                'method' => $refund->refund_method?->label() ?? 'Pending selection',
                'status' => $refund->status instanceof RefundStatus ? $refund->status->label() : (string) $refund->status,
                'reason' => $refund->reason,
                'requestedAt' => $refund->requested_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Finance/Refunds', [
            'refunds' => $refunds,
            'filters' => ['q' => $search, 'status' => $status],
            'statuses' => collect(RefundStatus::cases())->map(static fn (RefundStatus $item): array => ['value' => $item->value, 'label' => $item->label()])->values()->all(),
        ]);
    }
}
