@props([
    'status',
    'label' => null,
    'variant' => null,
])

@php
    $normalized = $status instanceof \BackedEnum ? (string) $status->value : strtolower((string) $status);

    // Semantic status colors consistent with Filament Admin (.fi-badge)
    $resolvedVariant = $variant ?? match ($normalized) {
        'active', 'published', 'confirmed', 'paid', 'successful', 'issued', 'present', 'verified', 'completed', 'ready', 'valid', 'success' => 'success',
        'pending', 'pending_payment', 'pending_review', 'full', 'requested', 'queued', 'sending', 'excused', 'warning' => 'warning',
        'cancelled', 'failed', 'rejected', 'revoked', 'absent', 'suspended', 'deactivated', 'correction_requested', 'rejected_invalid_signature', 'rejected_mismatch', 'not_found', 'danger' => 'danger',
        'in_progress', 'processing', 'generating', 'submitted', 'approved', 'scheduled', 'partially_refunded', 'refunded', 'received', 'customer', 'instructor', 'administrator', 'primary', 'info' => 'primary',
        default => 'neutral',
    };

    $resolvedLabel = $label ?? ($status instanceof \BackedEnum && method_exists($status, 'label')
        ? $status->label()
        : \Illuminate\Support\Str::of($normalized)->replace('_', ' ')->title()->toString());

    $styles = match ($resolvedVariant) {
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'warning' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-600/15',
        'primary' => 'bg-portal-primary-soft text-portal-primary ring-portal-primary/15',
        default => 'bg-gray-100 text-gray-700 ring-gray-500/15',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center whitespace-nowrap rounded-md px-2.5 py-1 text-[13px] leading-none font-medium ring-1 ring-inset {$styles}"]) }}>
    {{ $resolvedLabel }}
</span>
