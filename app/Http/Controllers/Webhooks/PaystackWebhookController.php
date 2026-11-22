<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\WebhookProcessingStatus;
use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentOrchestrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;

class PaystackWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentOrchestrationService $paymentService,
    ): JsonResponse {
        $rawBody = $request->getContent();
        $signature = $request->header('Paystack-Signature');
        $legacySignature = $request->header('X-Paystack-Signature');
        $payload = [];

        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            $payload = is_array($decoded) ? $decoded : [];
        } catch (JsonException) {
            $payload = [];
        }

        $event = $paymentService->processPaystackWebhook(
            payload: $payload,
            rawBody: $rawBody,
            signature: $signature,
            legacySignature: $legacySignature,
            headers: $request->headers->all(),
            sourceIp: $request->ip(),
        );

        if ($event->processing_status === WebhookProcessingStatus::RejectedInvalidSignature) {
            return response()->json(['status' => $event->processing_status->value], 400);
        }

        return response()->json([
            'status' => $event->processing_status->value,
            'webhook_event_id' => $event->id,
        ]);
    }
}
