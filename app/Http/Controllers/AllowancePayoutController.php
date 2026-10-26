<?php

namespace App\Http\Controllers;

use App\Models\AllowancePayout;
use App\Models\AllowancePlan;
use App\Models\FamilyMember;
use App\Models\PayoutCheckout;
use App\Models\StudentBalance;
use App\Services\AllowancePayoutService;
use App\Services\PayMongoService;
use App\Services\TenantConnection;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class AllowancePayoutController extends Controller
{
    /**
     * Show due allowances, student balances, and recent payouts.
     */
    public function index(Request $request, PayMongoService $paymongo): Response
    {
        $user = $request->attributes->get('auth_user');
        $isCreator = $user !== null && (int) $user->is_creator === 1;

        $studentsQuery = FamilyMember::active()->onlyStudents()->orderBy('name');

        // A co-parent only sees the students linked to them.
        if (! $isCreator) {
            $studentsQuery->whereIn('id', DB::connection(TenantConnection::CONNECTION)
                ->table('parent_student')
                ->where('parent_id', (int) ($user->id ?? 0))
                ->select('student_id'));
        }

        $students = $studentsQuery->get(['id', 'name']);
        $studentIds = $students->pluck('id')->all();

        $plans = AllowancePlan::query()
            ->with(['student:id,name', 'status:id,status'])
            ->whereIn('student_id', $studentIds)
            ->orderBy('next_payout_date')
            ->get(['id', 'student_id', 'plan_status_id', 'amount', 'frequency', 'next_payout_date']);

        $pendingByCycle = PayoutCheckout::query()
            ->whereIn('plan_id', $plans->pluck('id')->all())
            ->where('status', PayoutCheckout::STATUS_PENDING)
            ->latest('id')
            ->get()
            ->unique(fn (PayoutCheckout $checkout) => $this->cycleKey($checkout->plan_id, $checkout->cycle_date))
            ->keyBy(fn (PayoutCheckout $checkout) => $this->cycleKey($checkout->plan_id, $checkout->cycle_date));

        $duePlans = $plans
            ->filter(function (AllowancePlan $plan): bool {
                if ($plan->status?->status !== 'active') {
                    return false;
                }

                return CarbonImmutable::instance($plan->next_payout_date)->startOfDay()->lessThanOrEqualTo(today());
            })
            ->map(function (AllowancePlan $plan) use ($pendingByCycle) {
                $cycle = CarbonImmutable::instance($plan->next_payout_date)->startOfDay();
                $checkout = $pendingByCycle->get($this->cycleKey($plan->id, $cycle));

                $plan->setAttribute('days_overdue', max(0, (int) $cycle->diffInDays(today(), false)));
                $plan->setAttribute('pending_checkout', $checkout === null ? null : [
                    'id' => $checkout->id,
                    'checkout_url' => $checkout->checkout_url,
                    'status' => $checkout->status,
                ]);

                return $plan;
            })
            ->values();

        $recentPayouts = AllowancePayout::query()
            ->with(['student:id,name', 'plan:id,frequency'])
            ->whereIn('student_id', $studentIds)
            ->orderByDesc('paid_at')
            ->limit(20)
            ->get(['id', 'plan_id', 'student_id', 'amount', 'cycle_date', 'paid_at', 'created_at']);

        $balanceRows = StudentBalance::query()
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'balance', 'updated_at'])
            ->keyBy('student_id');

        $balances = $students->map(function (FamilyMember $student) use ($balanceRows) {
            $row = $balanceRows->get($student->id);

            return [
                'student_id' => $student->id,
                'name' => $student->name,
                'balance' => $row === null ? '0.00' : $row->balance,
            ];
        });

        $paidThisMonth = (float) AllowancePayout::query()
            ->whereIn('student_id', $studentIds)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        return Inertia::render('parent/payouts', [
            'duePlans' => $duePlans,
            'recentPayouts' => $recentPayouts,
            'balances' => $balances,
            'stats' => [
                'due_count' => $duePlans->count(),
                'due_total' => number_format((float) $duePlans->sum(fn (AllowancePlan $plan) => (float) $plan->amount), 2, '.', ''),
                'paid_this_month' => number_format($paidThisMonth, 2, '.', ''),
                'total_balance' => number_format((float) $balances->sum(fn (array $balance) => (float) $balance['balance']), 2, '.', ''),
            ],
            'paymongoMode' => $paymongo->isMocked() ? 'mock' : 'live',
        ]);
    }

    /**
     * Start a checkout for one due allowance.
     */
    public function store(Request $request, AllowancePayoutService $payoutService): SymfonyResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'min:1'],
        ], [
            'plan_id.required' => 'Choose an allowance plan.',
            'plan_id.integer' => 'Choose an allowance plan.',
        ]);

        $plan = AllowancePlan::query()
            ->with(['student', 'status'])
            ->findOrFail((int) $validated['plan_id']);

        $this->assertCanManageStudent($request, (int) $plan->student_id);

        try {
            $result = $payoutService->start($plan, (int) $request->attributes->get('auth_family_id'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'plan_id' => 'PayMongo could not start the checkout. Please try again.',
            ]);
        }

        if ($result['redirect'] !== null) {
            return Inertia::location($result['redirect']);
        }

        return to_route('payouts.index')->with(
            'payout_success',
            $result['mock']
                ? 'Test payout recorded. The student balance was updated.'
                : 'Allowance paid. The student balance was updated.'
        );
    }

    /**
     * Confirm a checkout after PayMongo redirects back to the app.
     */
    public function returnFromCheckout(Request $request, int $checkout, AllowancePayoutService $payoutService): RedirectResponse
    {
        $record = PayoutCheckout::query()->findOrFail($checkout);

        $this->assertCanManageStudent($request, (int) $record->student_id);

        try {
            $status = $payoutService->sync($record);
        } catch (Throwable $exception) {
            report($exception);

            return to_route('payouts.index')->with(
                'payout_error',
                'We could not confirm the PayMongo payment yet. Please try again.'
            );
        }

        if ($status === PayoutCheckout::STATUS_PAID) {
            return to_route('payouts.index')->with(
                'payout_success',
                'Allowance paid. The student balance was updated.'
            );
        }

        if ($status === PayoutCheckout::STATUS_EXPIRED) {
            return to_route('payouts.index')->with(
                'payout_error',
                'That PayMongo checkout expired. Start a new payout when you are ready.'
            );
        }

        return to_route('payouts.index')->with(
            'payout_error',
            'PayMongo is still processing this payment. It will update after confirmation.'
        );
    }

    // Build a stable key that matches a plan to one payout cycle.
    private function cycleKey(int $planId, mixed $cycleDate): string
    {
        return $planId.'|'.CarbonImmutable::instance($cycleDate)->startOfDay()->toDateString();
    }

    /**
     * The creator may manage every student; a co-parent only the students linked to them.
     */
    private function assertCanManageStudent(Request $request, int $studentId): void
    {
        $user = $request->attributes->get('auth_user');

        if ($user === null) {
            abort(403, 'You cannot manage this payout.');
        }

        $student = FamilyMember::active()->onlyStudents()->find($studentId);

        if ($student === null) {
            abort(404);
        }

        if ((int) $user->is_creator === 1) {
            return;
        }

        $linked = DB::connection(TenantConnection::CONNECTION)
            ->table('parent_student')
            ->where('parent_id', (int) $user->id)
            ->where('student_id', $studentId)
            ->exists();

        abort_unless($linked, 403, 'You can only manage payouts for your linked students.');
    }
}
