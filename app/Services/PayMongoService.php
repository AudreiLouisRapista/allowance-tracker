<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoService
{
    /**
     * PayMongo is mocked when explicitly requested or when no secret key is set.
     * This keeps the payout flow usable locally until the real key is added.
     */
    public function isMocked(): bool
    {
        $mock = filter_var(config('services.paymongo.mock', true), FILTER_VALIDATE_BOOLEAN);

        return $mock || trim((string) config('services.paymongo.secret_key')) === '';
    }

    /**
     * Create a hosted checkout session and return its id and checkout URL.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{id: string, checkout_url: string, status: string}
     */
    public function createCheckoutSession(array $attributes): array
    {
        $response = $this->request()->post($this->url('/checkout_sessions'), [
            'data' => ['attributes' => $attributes],
        ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response));
        }

        $data = $response->json('data');

        if (! is_array($data) || ! is_string($data['id'] ?? null)) {
            throw new RuntimeException('PayMongo did not return a checkout session id.');
        }

        $checkoutUrl = data_get($data, 'attributes.checkout_url');

        if (! is_string($checkoutUrl) || $checkoutUrl === '') {
            throw new RuntimeException('PayMongo did not return a checkout URL.');
        }

        return [
            'id' => $data['id'],
            'checkout_url' => $checkoutUrl,
            'status' => (string) data_get($data, 'attributes.status', 'active'),
        ];
    }

    /**
     * Retrieve a checkout session so the return URL can confirm payment.
     *
     * @return array<string, mixed>
     */
    public function retrieveCheckoutSession(string $checkoutId): array
    {
        $response = $this->request()->get($this->url('/checkout_sessions/'.$checkoutId));

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response));
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            throw new RuntimeException('PayMongo returned an invalid checkout session.');
        }

        return $data;
    }

    /**
     * Check whether PayMongo reports a successful payment for this session.
     *
     * @param  array<string, mixed>  $session
     */
    public function checkoutIsPaid(array $session): bool
    {
        $payments = data_get($session, 'attributes.payments', []);

        if (is_array($payments)) {
            foreach ($payments as $payment) {
                if (is_array($payment) && data_get($payment, 'attributes.status') === 'paid') {
                    return true;
                }
            }
        }

        // Retrieving a session with a secret key also exposes the payment intent.
        return data_get($session, 'attributes.payment_intent.attributes.status') === 'succeeded';
    }

    /**
     * Read the checkout session status (active, paid, expired).
     *
     * @param  array<string, mixed>  $session
     */
    public function checkoutStatus(array $session): string
    {
        return (string) data_get($session, 'attributes.status', 'active');
    }

    /**
     * Return the PayMongo payment id for the first paid payment in the session.
     *
     * @param  array<string, mixed>  $session
     */
    public function paidPaymentId(array $session): ?string
    {
        $payments = data_get($session, 'attributes.payments', []);

        if (! is_array($payments)) {
            return null;
        }

        foreach ($payments as $payment) {
            if (! is_array($payment) || data_get($payment, 'attributes.status') !== 'paid') {
                continue;
            }

            $id = $payment['id'] ?? null;

            if (is_string($id)) {
                return $id;
            }
        }

        return null;
    }

    /**
     * PayMongo sends a signature header shaped like t=...,te=...,li=....
     * Both the test and live signatures are accepted because the mode is
     * selected by the configured key.
     */
    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        $secret = trim((string) config('services.paymongo.webhook_secret'));

        if ($secret === '' || $signature === null || trim($signature) === '') {
            return false;
        }

        $parts = [];

        foreach (explode(',', $signature) as $piece) {
            $pair = explode('=', trim($piece), 2);

            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }

        $timestamp = $parts['t'] ?? null;

        if ($timestamp === null) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach (['te', 'li'] as $key) {
            $provided = $parts[$key] ?? null;

            if (is_string($provided) && hash_equals($expected, $provided)) {
                return true;
            }
        }

        return false;
    }

    // Build an authenticated, retrying HTTP client for PayMongo API calls.
    private function request(): PendingRequest
    {
        return Http::timeout(20)
            ->retry(2, 250)
            ->withBasicAuth(trim((string) config('services.paymongo.secret_key')), '')
            ->acceptJson()
            ->asJson();
    }

    // Join the configured PayMongo base URL with an endpoint path.
    private function url(string $path): string
    {
        return rtrim((string) config('services.paymongo.base_url', 'https://api.paymongo.com/v1'), '/').$path;
    }

    // Turn a failed PayMongo response into a readable exception message.
    private function errorMessage(Response $response): string
    {
        $detail = $response->json('errors.0.detail');

        if (is_string($detail) && $detail !== '') {
            return 'PayMongo: '.$detail;
        }

        return 'PayMongo returned HTTP '.$response->status().'.';
    }
}
