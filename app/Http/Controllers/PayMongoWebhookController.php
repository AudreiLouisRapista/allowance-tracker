<?php

namespace App\Http\Controllers;

use App\Models\PayoutCheckout;
use App\Services\AllowancePayoutService;
use App\Services\PayMongoService;
use App\Services\TenantConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PayMongoWebhookController extends Controller
{
    // Verify PayMongo's signature, switch to the right family database, and mark the payout paid.
    public function __invoke(
        Request $request,
        PayMongoService $paymongo,
        AllowancePayoutService $payoutService,
        TenantConnection $tenants
    ): JsonResponse {
        $payload = $request->getContent();

        if (! $paymongo->verifyWebhookSignature($payload, $request->header('Paymongo-Signature'))) {
            return response()->json(['message' => 'Invalid webhook signature.'], 400);
        }

        $event = json_decode($payload, true);

        if (! is_array($event)) {
            return response()->json(['message' => 'Invalid payload.'], 400);
        }

        $type = data_get($event, 'data.attributes.type');

        if (! in_array($type, ['checkout_session.payment.paid', 'payment.paid'], true)) {
            return response()->json(['received' => true]);
        }

        $resource = data_get($event, 'data.attributes.data');
        $metadata = data_get($resource, 'attributes.metadata', []);
        $familyId = (int) data_get($metadata, 'family_id', 0);

        if (! is_array($resource) || $familyId <= 0) {
            return response()->json(['received' => true]);
        }

        $sessionId = $resource['id'] ?? null;
        $reference = data_get($metadata, 'checkout_reference');

        if (! is_string($sessionId) && ! is_string($reference)) {
            return response()->json(['received' => true]);
        }

        try {
            $tenants->switchToFamily($familyId);
        } catch (Throwable) {
            // Not one of our families, or the master row is gone.
            return response()->json(['received' => true]);
        }

        $checkout = PayoutCheckout::query()
            ->where(function ($query) use ($sessionId, $reference) {
                if (is_string($sessionId)) {
                    $query->where('paymongo_checkout_id', $sessionId);
                }

                if (is_string($reference)) {
                    $query->orWhere('reference', $reference);
                }
            })
            ->first();

        if ($checkout === null) {
            return response()->json(['received' => true]);
        }

        $paymentId = $type === 'payment.paid'
            ? (string) ($resource['id'] ?? '')
            : $paymongo->paidPaymentId($resource);

        try {
            $payoutService->finalize($checkout, $paymentId === '' ? null : $paymentId);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }

        return response()->json(['received' => true]);
    }
}
