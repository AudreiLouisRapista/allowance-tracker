<?php

namespace App\Services;

use App\Models\AllowancePayout;
use App\Models\AllowancePlan;
use App\Models\PayoutCheckout;
use App\Models\StudentBalance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AllowancePayoutService
{
    // Inject the PayMongo gateway used to create and verify checkouts.
    public function __construct(private PayMongoService $paymongo) {}

    /**
     * Start (or resume) a PayMongo checkout for the plan's current cycle.
     *
     * @return array{checkout: PayoutCheckout, redirect: string|null, mock: bool}
     */
    public function start(AllowancePlan $plan, int $familyId): array
    {
        $plan->loadMissing(['student', 'status']);
        $this->assertDue($plan);

        $cycle = CarbonImmutable::instance($plan->next_payout_date)->startOfDay();

        if ($this->payoutExists($plan, $cycle)) {
            throw ValidationException::withMessages([
                'plan_id' => 'This allowance cycle has already been paid.',
            ]);
        }

        $pending = $this->pendingCheckout($plan, $cycle);

        // Without a key, finish immediately so the payout flow can still be tested.
        if ($this->paymongo->isMocked()) {
            $checkout = $pending ?? $this->createCheckoutRecord($plan, $cycle);

            if ($checkout->paymongo_checkout_id === null) {
                $checkout->update(['paymongo_checkout_id' => 'cs_mock_'.Str::lower(Str::random(24))]);
            }

            $this->finalize($checkout, 'pay_mock_'.Str::lower(Str::random(24)));

            return [
                'checkout' => $checkout->fresh() ?? $checkout,
                'redirect' => null,
                'mock' => true,
            ];
        }

        if ($pending !== null) {
            $resumed = $this->resumePending($pending);

            if ($resumed !== null) {
                return $resumed;
            }
        }

        $checkout = $this->createCheckoutRecord($plan, $cycle);

        try {
            $session = $this->paymongo->createCheckoutSession(
                $this->checkoutAttributes($plan, $cycle, $checkout, $familyId)
            );
        } catch (Throwable $exception) {
            $checkout->update(['status' => PayoutCheckout::STATUS_FAILED]);

            throw $exception;
        }

        $checkout->update([
            'paymongo_checkout_id' => $session['id'],
            'checkout_url' => $session['checkout_url'],
        ]);

        return [
            'checkout' => $checkout->fresh() ?? $checkout,
            'redirect' => $session['checkout_url'],
            'mock' => false,
        ];
    }

    /**
     * Confirm a checkout after the user returns from PayMongo.
     */
    public function sync(PayoutCheckout $checkout): string
    {
        if ($checkout->status === PayoutCheckout::STATUS_PAID) {
            return PayoutCheckout::STATUS_PAID;
        }

        if ($this->paymongo->isMocked() || $checkout->paymongo_checkout_id === null) {
            return $checkout->status;
        }

        $session = $this->paymongo->retrieveCheckoutSession($checkout->paymongo_checkout_id);

        if ($this->paymongo->checkoutIsPaid($session)) {
            $this->finalize($checkout, $this->paymongo->paidPaymentId($session));

            return PayoutCheckout::STATUS_PAID;
        }

        if ($this->paymongo->checkoutStatus($session) === 'expired') {
            $checkout->update(['status' => PayoutCheckout::STATUS_EXPIRED]);

            return PayoutCheckout::STATUS_EXPIRED;
        }

        return $checkout->status;
    }

    /**
     * Record the payout and credit the student. Safe to call more than once.
     */
    public function finalize(PayoutCheckout $checkout, ?string $paymentId = null): AllowancePayout
    {
        return DB::connection(TenantConnection::CONNECTION)->transaction(function () use ($checkout, $paymentId) {
            $locked = PayoutCheckout::query()->whereKey($checkout->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === PayoutCheckout::STATUS_PAID && $locked->allowance_payout_id !== null) {
                return AllowancePayout::query()->findOrFail($locked->allowance_payout_id);
            }

            $plan = AllowancePlan::query()->whereKey($locked->plan_id)->lockForUpdate()->firstOrFail();
            $cycleDate = CarbonImmutable::instance($locked->cycle_date)->startOfDay();

            $payout = AllowancePayout::query()
                ->where('plan_id', $plan->id)
                ->whereDate('cycle_date', $cycleDate->toDateString())
                ->first();

            if ($payout === null) {
                $payout = AllowancePayout::create([
                    'plan_id' => $plan->id,
                    'student_id' => $locked->student_id,
                    'amount' => $locked->amount,
                    'cycle_date' => $cycleDate->toDateString(),
                    'paid_at' => now(),
                    'paymongo_checkout_id' => $locked->paymongo_checkout_id,
                    'paymongo_payment_id' => $paymentId,
                    'created_at' => now(),
                ]);

                $this->creditBalance((int) $locked->student_id, (string) $locked->amount);
                $this->advancePlan($plan, $cycleDate);
            } elseif (blank($payout->paymongo_checkout_id)) {
                $payout->update([
                    'paymongo_checkout_id' => $locked->paymongo_checkout_id,
                    'paymongo_payment_id' => $paymentId,
                ]);
            }

            $locked->update([
                'status' => PayoutCheckout::STATUS_PAID,
                'paymongo_payment_id' => $paymentId,
                'allowance_payout_id' => $payout->id,
                'paid_at' => now(),
            ]);

            return $payout;
        });
    }

    /**
     * Try to reuse an existing checkout, finalizing it if PayMongo already charged it.
     *
     * @return array{checkout: PayoutCheckout, redirect: string|null, mock: bool}|null
     */
    private function resumePending(PayoutCheckout $checkout): ?array
    {
        if ($checkout->paymongo_checkout_id === null) {
            $checkout->update(['status' => PayoutCheckout::STATUS_FAILED]);

            return null;
        }

        $session = $this->paymongo->retrieveCheckoutSession($checkout->paymongo_checkout_id);

        if ($this->paymongo->checkoutIsPaid($session)) {
            $this->finalize($checkout, $this->paymongo->paidPaymentId($session));

            return [
                'checkout' => $checkout->fresh() ?? $checkout,
                'redirect' => null,
                'mock' => false,
            ];
        }

        if ($this->paymongo->checkoutStatus($session) === 'expired') {
            $checkout->update(['status' => PayoutCheckout::STATUS_EXPIRED]);

            return null;
        }

        if (! is_string($checkout->checkout_url) || $checkout->checkout_url === '') {
            $checkout->update(['status' => PayoutCheckout::STATUS_FAILED]);

            return null;
        }

        return [
            'checkout' => $checkout,
            'redirect' => $checkout->checkout_url,
            'mock' => false,
        ];
    }

    // Reject inactive plans and plans whose next payout date is still in the future.
    private function assertDue(AllowancePlan $plan): void
    {
        if ($plan->status?->status !== 'active') {
            throw ValidationException::withMessages([
                'plan_id' => 'Activate this allowance plan before sending a payout.',
            ]);
        }

        if (CarbonImmutable::instance($plan->next_payout_date)->startOfDay()->greaterThan(today())) {
            throw ValidationException::withMessages([
                'plan_id' => 'This allowance is not due yet.',
            ]);
        }
    }

    // Check whether this plan cycle already has a recorded payout.
    private function payoutExists(AllowancePlan $plan, CarbonImmutable $cycle): bool
    {
        return AllowancePayout::query()
            ->where('plan_id', $plan->id)
            ->whereDate('cycle_date', $cycle->toDateString())
            ->exists();
    }

    // Find the latest unfinished checkout for this plan cycle.
    private function pendingCheckout(AllowancePlan $plan, CarbonImmutable $cycle): ?PayoutCheckout
    {
        return PayoutCheckout::query()
            ->where('plan_id', $plan->id)
            ->whereDate('cycle_date', $cycle->toDateString())
            ->where('status', PayoutCheckout::STATUS_PENDING)
            ->latest('id')
            ->first();
    }

    // Create the local checkout row before calling PayMongo.
    private function createCheckoutRecord(AllowancePlan $plan, CarbonImmutable $cycle): PayoutCheckout
    {
        return PayoutCheckout::create([
            'reference' => (string) Str::ulid(),
            'plan_id' => $plan->id,
            'student_id' => $plan->student_id,
            'amount' => $plan->amount,
            'cycle_date' => $cycle->toDateString(),
            'status' => PayoutCheckout::STATUS_PENDING,
        ]);
    }

    /**
     * Build the PayMongo Checkout Session payload for one allowance payout.
     *
     * @return array<string, mixed>
     */
    private function checkoutAttributes(
        AllowancePlan $plan,
        CarbonImmutable $cycle,
        PayoutCheckout $checkout,
        int $familyId
    ): array {
        $studentName = (string) data_get($plan, 'student.name', 'Student');

        return [
            'line_items' => [
                [
                    'amount' => (int) round(((float) $plan->amount) * 100),
                    'currency' => 'PHP',
                    'name' => 'Allowance for '.$studentName,
                    'quantity' => 1,
                    'description' => ucfirst($plan->frequency).' allowance for '.$cycle->format('M j, Y'),
                ],
            ],
            'payment_method_types' => ['card', 'gcash'],
            'success_url' => route('payouts.return', ['checkout' => $checkout->id]),
            'cancel_url' => route('payouts.index'),
            'description' => 'Allowance payout for '.$studentName,
            'reference_number' => $checkout->reference,
            'send_email_receipt' => false,
            'show_description' => true,
            'show_line_items' => true,
            'metadata' => [
                'family_id' => (string) $familyId,
                'checkout_reference' => $checkout->reference,
                'plan_id' => (string) $plan->id,
                'cycle_date' => $cycle->toDateString(),
            ],
        ];
    }

    // Add the payout amount to the student's stored balance.
    private function creditBalance(int $studentId, string $amount): void
    {
        // The first payout for a student creates the balance row.
        DB::connection(TenantConnection::CONNECTION)
            ->table('student_balance')
            ->insertOrIgnore([
                'student_id' => $studentId,
                'balance' => 0,
            ]);

        $balance = StudentBalance::query()->whereKey($studentId)->lockForUpdate()->firstOrFail();

        if (! is_numeric($amount)) {
            throw new \InvalidArgumentException('The payout amount is not numeric.');
        }

        $balance->balance = bcadd((string) $balance->balance, $amount, 2);
        $balance->updated_at = now();
        $balance->save();
    }

    // Move the plan to its next payout date after a successful payout.
    private function advancePlan(AllowancePlan $plan, CarbonImmutable $cycle): void
    {
        $next = CarbonImmutable::instance($plan->next_payout_date)->startOfDay();

        if (! $next->equalTo($cycle)) {
            return;
        }

        $plan->next_payout_date = AllowancePlanService::advanceDate($next, $plan->frequency);
        $plan->save();
    }
}
