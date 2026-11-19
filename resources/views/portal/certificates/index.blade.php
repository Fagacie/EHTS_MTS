<x-layouts.portal title="Certificates">
    <div class="space-y-6">
        <x-portal.page-header
            title="Certificates"
            description="View your verified Ecohydrotech training certificates and download official PDF copies."
        >
            <x-slot:actions>
                <x-portal.button :href="route('portal.modules.index')" variant="secondary">
                    My Learning
                </x-portal.button>
            </x-slot:actions>
        </x-portal.page-header>

        @if ($issuedCertificates->isEmpty() && $revokedCertificates->isEmpty())
            <x-portal.empty-state
                title="No certificates issued yet"
                description="Certificates become available here once your session attendance is verified or your eligible module training record is issued by Ecohydrotech administration."
            >
                <x-slot:action>
                    <x-portal.button :href="route('portal.bookings.index')" variant="secondary">
                        View My Bookings
                    </x-portal.button>
                    <x-portal.button :href="route('catalogue.index')" variant="primary">
                        Browse Training
                    </x-portal.button>
                </x-slot:action>
            </x-portal.empty-state>
        @else
            {{-- Issued Certificates --}}
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-portal-text">
                        Issued Certificates
                        <span class="ml-1.5 text-base font-normal text-portal-muted">({{ $issuedCertificates->count() }})</span>
                    </h2>
                </div>

                @if ($issuedCertificates->isEmpty())
                    <x-portal.card>
                        <p class="text-[15px] text-portal-muted">You do not currently have any active issued certificates.</p>
                    </x-portal.card>
                @else
                    <div class="grid gap-5 md:grid-cols-2">
                        @foreach ($issuedCertificates as $certificate)
                            <x-portal.card :padding="false" class="flex h-full flex-col justify-between">
                                <div class="space-y-4 p-5 sm:p-6">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="inline-flex items-center rounded-md bg-portal-primary-soft px-2.5 py-1 text-[13px] font-semibold text-portal-primary">
                                            {{ $certificate->recordTypeLabel() }}
                                        </span>
                                        <x-portal.status-badge status="issued" label="Official Issued" />
                                    </div>

                                    <div>
                                        <p class="font-mono text-sm font-medium text-portal-primary">
                                            {{ $certificate->certificate_number }}
                                        </p>
                                        <h3 class="mt-1 text-lg font-semibold leading-snug text-portal-text">
                                            {{ $certificate->training_title_snapshot }}
                                        </h3>
                                        @if ($certificate->training_code_snapshot)
                                            <p class="mt-1 text-sm text-portal-muted">
                                                Programme Code: {{ $certificate->training_code_snapshot }}
                                            </p>
                                        @endif
                                    </div>

                                    <dl class="grid gap-3 border-t border-portal-border pt-4 text-sm sm:grid-cols-2">
                                        <div>
                                            <dt class="text-portal-muted">Completion Date</dt>
                                            <dd class="mt-0.5 font-medium text-portal-text">
                                                {{ $certificate->completion_date?->format('d M Y') ?? '—' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-portal-muted">Issued Date</dt>
                                            <dd class="mt-0.5 font-medium text-portal-text">
                                                {{ $certificate->issued_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y') ?? '—' }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 rounded-b-xl border-t border-portal-border bg-portal-bg px-5 py-4 sm:px-6">
                                    <x-portal.button :href="route('portal.certificates.show', $certificate)" variant="secondary" size="sm">
                                        Preview Record
                                    </x-portal.button>
                                    <x-portal.button :href="route('certificates.download', $certificate)" variant="primary" size="sm">
                                        Download Official PDF
                                    </x-portal.button>
                                </div>
                            </x-portal.card>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Revoked Certificates (if any) --}}
            @if ($revokedCertificates->isNotEmpty())
                <section class="space-y-4">
                    <h2 class="text-lg font-semibold text-portal-text">
                        Revoked Certificates
                        <span class="ml-1.5 text-base font-normal text-portal-muted">({{ $revokedCertificates->count() }})</span>
                    </h2>

                    <div class="grid gap-5 md:grid-cols-2">
                        @foreach ($revokedCertificates as $certificate)
                            <x-portal.card :padding="false" class="flex h-full flex-col justify-between">
                                <div class="space-y-4 p-5 sm:p-6">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-mono text-sm font-medium text-portal-muted">
                                            {{ $certificate->certificate_number }}
                                        </span>
                                        <x-portal.status-badge status="revoked" label="Revoked" />
                                    </div>

                                    <div>
                                        <h3 class="text-lg font-semibold leading-snug text-portal-text">
                                            {{ $certificate->training_title_snapshot }}
                                        </h3>
                                        @if ($certificate->revocation_reason)
                                            <p class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-900 ring-1 ring-inset ring-rose-600/20">
                                                {{ $certificate->revocation_reason }}
                                            </p>
                                        @endif
                                    </div>

                                    <p class="text-sm text-portal-muted">
                                        Revoked on {{ $certificate->revoked_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') ?? '—' }} MYT
                                    </p>
                                </div>

                                <div class="rounded-b-xl border-t border-portal-border bg-portal-bg px-5 py-4 sm:px-6">
                                    <x-portal.button :href="route('portal.certificates.show', $certificate)" variant="secondary" size="sm">
                                        View Record Notice
                                    </x-portal.button>
                                </div>
                            </x-portal.card>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
</x-layouts.portal>
