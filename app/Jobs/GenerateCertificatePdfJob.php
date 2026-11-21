<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Services\Certificate\CertificateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCertificatePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public int $certificateId)
    {
        $this->onQueue('certificates');
    }

    public function handle(CertificateService $certificateService): void
    {
        $certificateService->generateApprovedPdf($this->certificateId);
    }
}
