@props([
    'label' => null,
    'id' => null,
    'name' => null,
    'hint' => null,
    'error' => null,
    'rows' => 3,
    'required' => false,
    'optional' => false,
])

@php
    $textareaId = $id ?? ($name ? 'portal-field-'.str_replace(['[', ']'], ['-', ''], $name) : null);
    $resolvedError = $error ?? ($name && isset($errors) && $errors->has($name) ? $errors->first($name) : null);
    $hintId = $textareaId ? $textareaId.'-hint' : null;
    $errorId = $textareaId ? $textareaId.'-error' : null;
    $describedBy = $resolvedError ? $errorId : ($hint ? $hintId : null);
@endphp

<div>
    @if ($label)
        <label @if ($textareaId) for="{{ $textareaId }}" @endif class="portal-label mb-1.5">
            <span>{{ $label }}</span>
            @if ($required)
                <span class="text-rose-600" aria-hidden="true">*</span>
            @elseif ($optional)
                <span class="ml-1 text-[13px] font-normal text-portal-muted">(Optional)</span>
            @endif
        </label>
    @endif

    <textarea
        @if ($textareaId) id="{{ $textareaId }}" @endif
        @if ($name) name="{{ $name }}" @endif
        rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($resolvedError) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->merge([
            'class' => 'portal-textarea'.($resolvedError ? ' border-rose-400 focus:border-rose-500 focus:ring-rose-500/15' : ''),
        ]) }}
    >{{ $slot }}</textarea>

    @if ($resolvedError)
        <p @if ($errorId) id="{{ $errorId }}" @endif class="mt-1.5 text-sm text-rose-700">{{ $resolvedError }}</p>
    @elseif ($hint)
        <p @if ($hintId) id="{{ $hintId }}" @endif class="mt-1.5 text-[13px] text-portal-muted">{{ $hint }}</p>
    @endif
</div>
