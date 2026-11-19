<x-layouts.portal title="Certificate {{ $certificate->certificate_number }}">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <a
                href="{{ route('portal.certificates.index') }}"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-portal-muted transition hover:text-portal-primary"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                <span>Back to Certificates</span>
            </a>

            <x-portal.status-badge
                :status="$certificate->isIssued() ? 'issued' : 'revoked'"
                :label="$certificate->isIssued() ? 'Official Issued' : 'Revoked'"
            />
        </div>

        <x-portal.page-header
            :title="$certificate->training_title_snapshot"
            :description="'Certificate '.$certificate->certificate_number.' · '.$certificate->recordTypeLabel()"
        >
            <x-slot:actions>
                @if ($certificate->isIssued())
                    <x-portal.button :href="route('certificates.download', $certificate)" variant="primary">
                        Download Official PDF
                    </x-portal.button>
                @endif
            </x-slot:actions>
        </x-portal.page-header>

        @if ($certificate->isRevoked())
            <x-portal.alert variant="danger" title="This Certificate Has Been Revoked">
                <p>
                    {{ $certificate->revocation_reason ?: 'This certificate has been invalidated by Ecohydrotech administration and is no longer valid for download or verification.' }}
                </p>
                @if ($certificate->revoked_at)
                    <p class="mt-1 font-medium">
                        Revoked on {{ $certificate->revoked_at->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT
                    </p>
                @endif
            </x-portal.alert>
        @endif

        {{-- Certificate Preview Card --}}
        <x-portal.card :padding="false">
            <div class="p-5 sm:p-8">
                <div class="rounded-xl border border-portal-border bg-white p-6 ring-2 ring-portal-primary/15 sm:p-10">
                    <div class="border-b border-portal-border pb-6 text-center">
                        @if ($logoDataUri)
                            <div class="mx-auto mb-4 flex justify-center">
                                <img src="{{ $logoDataUri }}" alt="{{ $certificate->issuing_organisation_snapshot }}" class="h-12 w-auto object-contain">
                            </div>
                        @endif

                        <p class="text-sm font-semibold uppercase tracking-wider text-portal-primary">
                            {{ $certificate->issuing_organisation_snapshot }}
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-portal-text sm:text-3xl">
                            Certificate of Completion
                        </h2>
                        <p class="mt-2 text-[15px] text-portal-muted">
                            This certifies that the participant named below has successfully completed the prescribed technical programme.
                        </p>
                    </div>

                    <div class="py-8 text-center">
                        <p class="text-sm font-medium text-portal-muted">Awarded to</p>
                        <p class="mt-2 text-2xl font-bold text-portal-text sm:text-3xl">
                            {{ $certificate->trainee_name_snapshot }}
                        </p>

                        <p class="mt-6 text-sm font-medium text-portal-muted">Programme Completed</p>
                        <p class="mt-1.5 text-xl font-semibold text-portal-primary">
                            {{ $certificate->training_title_snapshot }}
                        </p>
                        @if ($certificate->training_code_snapshot)
                            <p class="mt-1 text-sm font-medium text-portal-muted">
                                Code: {{ $certificate->training_code_snapshot }} · {{ $certificate->recordTypeLabel() }}
                            </p>
                        @endif
                    </div>

                    <div class="grid gap-6 border-t border-portal-border pt-6 sm:grid-cols-3">
                        <div>
                            <p class="text-sm font-medium text-portal-muted">Completion Date</p>
                            <p class="mt-1 text-[15px] font-semibold text-portal-text">
                                {{ $certificate->completion_date?->format('d M Y') ?? '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-portal-muted">Issued On</p>
                            <p class="mt-1 text-[15px] font-semibold text-portal-text">
                                {{ $certificate->issued_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y') ?? '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-portal-muted">Authorised Signatory</p>
                            <p class="mt-1 text-[15px] font-semibold text-portal-text">
                                {{ $certificate->signatory_name_snapshot }}
                            </p>
                            @if ($certificate->signatory_title_snapshot)
                                <p class="text-sm text-portal-muted">{{ $certificate->signatory_title_snapshot }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-portal-muted">
                    <div>
                        Certificate Reference:
                        <span class="font-mono font-semibold text-portal-text">{{ $certificate->certificate_number }}</span>
                    </div>
                    @if ($certificate->isIssued() && $certificate->pdf_file_size_bytes)
                        <div>PDF Document Size: {{ $certificate->formattedFileSize() }}</div>
                    @endif
                </div>
            </x-slot:footer>
        </x-portal.card>
    </div>
</x-layouts.portal>
