<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\WebhookProcessingStatus;
use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentOrchestrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillplzWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentOrchestrationService $paymentService): JsonResponse
    {
        $payload = $request->post();

        $sanitizedHeaders = collect($request->headers->all())
            ->only(['content-type', 'user-agent', 'x-forwarded-for'])
            ->toArray();

        $event = $paymentService->processBillplzWebhook(
            payload: is_array($payload) ? $payload : [],
            headers: $sanitizedHeaders,
            sourceIp: $request->ip(),
        );

        if ($event->processing_status === WebhookProcessingStatus::RejectedInvalidSignature) {
            return response()->json([
                'status' => $event->processing_status->value,
                'message' => 'Invalid X-Signature.',
            ], 400);
        }

        return response()->json([
            'status' => $event->processing_status->value,
            'webhook_event_id' => $event->id,
        ], 200);
    }
}
