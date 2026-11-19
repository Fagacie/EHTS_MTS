@props([
    'status',
    'label' => null,
    'variant' => null,
])

@php
    $normalized = $status instanceof \BackedEnum ? (string) $status->value : strtolower((string) $status);

    $resolvedVariant = $variant ?? match ($normalized) {
        'active', 'published', 'confirmed', 'paid', 'successful', 'issued', 'present', 'verified', 'completed', 'ready', 'valid', 'customer' => 'success',
        'pending', 'pending_payment', 'pending_review', 'suspended', 'full', 'requested', 'queued', 'sending', 'excused' => 'warning',
        'deactivated', 'cancelled', 'failed', 'rejected', 'revoked', 'absent', 'correction_requested', 'rejected_invalid_signature', 'rejected_mismatch', 'not_found' => 'danger',
        'in_progress', 'processing', 'generating', 'submitted', 'approved', 'scheduled', 'partially_refunded', 'refunded', 'received', 'administrator' => 'info',
        'instructor', 'unmatched_pending' => 'teal',
        default => 'neutral',
    };

    $resolvedLabel = $label ?? ($status instanceof \BackedEnum && method_exists($status, 'label')
        ? $status->label()
        : \Illuminate\Support\Str::of($normalized)->replace('_', ' ')->title()->toString());

    $styles = match ($resolvedVariant) {
        'success' => 'bg-status-success-bg text-status-success-text border-status-success-border',
        'warning' => 'bg-status-warning-bg text-status-warning-text border-status-warning-border',
        'danger' => 'bg-status-danger-bg text-status-danger-text border-status-danger-border',
        'info' => 'bg-status-info-bg text-status-info-text border-status-info-border',
        'teal' => 'bg-eht-teal-50 text-eht-teal-700 border-eht-teal-200',
        default => 'bg-status-neutral-bg text-status-neutral-text border-status-neutral-border',
    };

    $dotColor = match ($resolvedVariant) {
        'success' => 'bg-eht-emerald-600',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-rose-600',
        'info' => 'bg-sky-600',
        'teal' => 'bg-eht-teal-600',
        default => 'bg-slate-500',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold {$styles}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dotColor }}" aria-hidden="true"></span>
    {{ $resolvedLabel }}
</span>
