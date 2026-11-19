@props([
    'title' => 'Account Access',
    'heading' => null,
    'subheading' => null,
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ config('etms.brand_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased flex flex-col justify-center py-10 px-4 sm:px-6">
    <div class="mx-auto w-full max-w-md">
        <div class="mb-6 flex justify-center">
            <a href="{{ route('home') }}" class="rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-eht-teal-600">
                <x-eht.logo />
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @if ($heading)
                <div class="mb-6">
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ $heading }}</h1>
                    @if ($subheading)
                        <p class="mt-1 text-sm text-slate-600">{{ $subheading }}</p>
                    @endif
                </div>
            @endif

            @if (session('status'))
                <div class="mb-5 rounded-lg border border-status-success-border bg-status-success-bg px-4 py-3 text-xs font-medium text-status-success-text" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-status-danger-border bg-status-danger-bg px-4 py-3 text-xs text-status-danger-text" role="alert">
                    <p class="font-semibold">Please check the highlighted details:</p>
                    <ul class="mt-1 list-disc pl-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">
            <a href="{{ route('home') }}" class="font-medium text-eht-navy-700 hover:underline">
                &larr; Return to Ecohydrotech Home
            </a>
        </p>
    </div>

    @livewireScripts
</body>
</html>
