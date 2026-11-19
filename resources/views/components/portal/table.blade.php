<div {{ $attributes->merge(['class' => 'portal-table-wrap overflow-hidden rounded-xl bg-portal-surface shadow-xs ring-1 ring-gray-950/5']) }}>
    <table class="portal-table">
        @isset($head)
            <thead>
                {{ $head }}
            </thead>
        @endisset
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
