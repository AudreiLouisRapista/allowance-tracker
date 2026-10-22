<?php

namespace App\Http\Controllers;

use App\Models\AllowancePayout;
use App\Models\AllowancePlan;
use App\Models\FamilyMember;
use App\Services\AllowancePlanService;
use App\Services\TenantConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AllowancePlanController extends Controller
{
    /**
     * Show the allowance plans this parent may manage.
     */
    public function index(Request $request): Response
    {
        $user = $request->attributes->get('auth_user');
        $isCreator = $user !== null && (int) $user->is_creator === 1;

        // Only active students are eligible for a plan.
        $studentsQuery = FamilyMember::active()->onlyStudents()->orderBy('name');

        // A co-parent only manages the students linked to them.
        if (! $isCreator) {
            $studentsQuery->whereIn('id', DB::connection(TenantConnection::CONNECTION)
                ->table('parent_student')
                ->where('parent_id', (int) ($user->id ?? 0))
                ->select('student_id'));
        }

        $students = $studentsQuery->get(['id', 'name']);

        $plans = AllowancePlan::query()
            ->with(['student:id,name', 'status:id,status'])
            ->whereIn('student_id', $students->pluck('id')->all())
            ->orderBy('next_payout_date')
            ->get(['id', 'student_id', 'plan_status_id', 'amount', 'frequency', 'start_date', 'next_payout_date']);

        // Read the last three payouts per plan in one query (payouts are recorded in a later phase).
        $payoutsByPlan = AllowancePayout::query()
            ->whereIn('plan_id', $plans->pluck('id')->all())
            ->orderByDesc('cycle_date')
            ->get(['plan_id', 'amount', 'cycle_date', 'paid_at'])
            ->groupBy('plan_id');

        $plans->each(function (AllowancePlan $plan) use ($payoutsByPlan) {
            $plan->setAttribute('recent_payouts', $payoutsByPlan->get($plan->id, collect())->take(3)->values());
        });

        $activePlans = $plans->filter(fn (AllowancePlan $plan) => $plan->status?->status === 'active')->values();

        return Inertia::render('parent/allowance-plans', [
            'plans' => $plans,
            'students' => $students,
            // Students who already have an active plan cannot be picked again.
            'activeStudentIds' => $activePlans->pluck('student_id')->unique()->values(),
            'nextPayout' => $activePlans->sortBy('next_payout_date')->first(),
        ]);
    }

    /**
     * Create an active plan for one linked student.
     */
    public function store(Request $request, AllowancePlanService $planService): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'frequency' => ['required', Rule::in(AllowancePlan::FREQUENCIES)],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ], [
            'student_id.required' => 'Choose a student.',
            'student_id.integer' => 'Choose a student.',
            'amount.required' => 'Enter an allowance amount.',
            'amount.numeric' => 'Enter a valid amount.',
            'amount.decimal' => 'Enter an amount with up to 2 decimals.',
            'frequency.in' => 'Choose weekly, biweekly, or monthly.',
            'start_date.after_or_equal' => 'Choose today or a later date.',
        ]);

        $this->assertCanManageStudent($request, (int) $validated['student_id']);

        $user = $request->attributes->get('auth_user');
        $planService->create((int) $user->id, $validated);

        return to_route('allowance-plans.index');
    }

    /**
     * Update a plan's amount, frequency, or start date.
     */
    public function update(Request $request, int $id, AllowancePlanService $planService): RedirectResponse
    {
        $plan = AllowancePlan::findOrFail($id);
        $this->assertCanManageStudent($request, (int) $plan->student_id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'frequency' => ['required', Rule::in(AllowancePlan::FREQUENCIES)],
            // No after_or_equal here: an existing plan may keep its original start date.
            'start_date' => ['required', 'date_format:Y-m-d'],
        ], [
            'amount.required' => 'Enter an allowance amount.',
            'amount.numeric' => 'Enter a valid amount.',
            'amount.decimal' => 'Enter an amount with up to 2 decimals.',
            'frequency.in' => 'Choose weekly, biweekly, or monthly.',
        ]);

        $planService->update($plan, $validated);

        return to_route('allowance-plans.index');
    }

    /**
     * Activate or deactivate a plan.
     */
    public function setStatus(Request $request, int $id, AllowancePlanService $planService): RedirectResponse
    {
        $plan = AllowancePlan::findOrFail($id);
        $this->assertCanManageStudent($request, (int) $plan->student_id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $planService->setStatus($plan, $validated['status']);

        return to_route('allowance-plans.index');
    }

    /**
     * The creator may manage every student; a co-parent only the students linked to them.
     */
    private function assertCanManageStudent(Request $request, int $studentId): void
    {
        $user = $request->attributes->get('auth_user');

        if ($user === null) {
            abort(403, 'You cannot manage this allowance plan.');
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

        abort_unless($linked, 403, 'You can only manage plans for your linked students.');
    }
}
