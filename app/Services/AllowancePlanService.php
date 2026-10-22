<?php

namespace App\Services;

use App\Models\AllowancePlan;
use App\Models\AllowancePlanStatus;
use App\Models\FamilyMember;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class AllowancePlanService
{
    /**
     * Create an active plan. A student may only have one active plan at a time.
     */
    public function create(int $creatorId, array $data): AllowancePlan
    {
        $student = FamilyMember::active()->onlyStudents()->find((int) $data['student_id']);

        if ($student === null) {
            throw ValidationException::withMessages([
                'student_id' => 'This student was not found in your family.',
            ]);
        }

        $this->assertNoActivePlan($student->id);

        $startDate = CarbonImmutable::parse($data['start_date'])->startOfDay();

        return AllowancePlan::create([
            'student_id' => $student->id,
            'created_by' => $creatorId,
            'plan_status_id' => $this->statusId('active'),
            'amount' => $data['amount'],
            'frequency' => $data['frequency'],
            'start_date' => $startDate,
            // The first cycle is the start date; later cycles advance after each payout.
            'next_payout_date' => $startDate,
        ]);
    }

    /**
     * Update the editable fields. The next payout date only moves when the
     * start date changes and no payout has been recorded yet.
     */
    public function update(AllowancePlan $plan, array $data): AllowancePlan
    {
        $startDate = CarbonImmutable::parse($data['start_date'])->startOfDay();
        $startChanged = ! $startDate->equalTo($plan->start_date->copy()->startOfDay());

        if ($startChanged) {
            if ($plan->payouts()->exists()) {
                // Once payouts exist, keep the running schedule intact.
                if ($startDate->greaterThan($plan->next_payout_date)) {
                    throw ValidationException::withMessages([
                        'start_date' => 'The start date cannot be after the next payout date.',
                    ]);
                }
            } else {
                if ($startDate->lessThan(today())) {
                    throw ValidationException::withMessages([
                        'start_date' => 'Choose today or a later date.',
                    ]);
                }

                $plan->next_payout_date = $startDate;
            }
        }

        $plan->fill([
            'amount' => $data['amount'],
            'frequency' => $data['frequency'],
            'start_date' => $startDate,
        ]);

        $plan->save();

        return $plan;
    }

    /**
     * Activate or deactivate a plan. Reactivating skips missed cycles so the
     * plan does not open with a backlog of overdue payouts.
     */
    public function setStatus(AllowancePlan $plan, string $status): AllowancePlan
    {
        if (! in_array($status, ['active', 'inactive'], true)) {
            throw new InvalidArgumentException("Unsupported allowance plan status: {$status}");
        }

        $statusId = $this->statusId($status);

        if ((int) $plan->plan_status_id === $statusId) {
            return $plan;
        }

        if ($status === 'active') {
            $this->assertNoActivePlan($plan->student_id, $plan->id);

            $next = CarbonImmutable::instance($plan->next_payout_date)->startOfDay();
            $guard = 0;

            while ($next->lessThan(today()) && $guard < 5200) {
                $next = self::advanceDate($next, $plan->frequency);
                $guard++;
            }

            // Safety net for a date that is still in the past after the loop.
            $plan->next_payout_date = $next->lessThan(today()) ? CarbonImmutable::parse(today()) : $next;
        }

        $plan->plan_status_id = $statusId;
        $plan->save();

        return $plan;
    }

    /**
     * The date one cycle after the given date.
     */
    public static function advanceDate(CarbonInterface $date, string $frequency): CarbonImmutable
    {
        $next = CarbonImmutable::instance($date);

        return match ($frequency) {
            'weekly' => $next->addWeek(),
            'biweekly' => $next->addWeeks(2),
            // No overflow: Jan 31 becomes Feb 28 instead of Mar 3.
            'monthly' => $next->addMonthNoOverflow(),
            default => throw new InvalidArgumentException("Unsupported allowance frequency: {$frequency}"),
        };
    }

    /**
     * Reject a second active plan for the same student.
     */
    private function assertNoActivePlan(int $studentId, ?int $ignorePlanId = null): void
    {
        $exists = AllowancePlan::query()
            ->where('student_id', $studentId)
            ->when($ignorePlanId !== null, fn ($query) => $query->where('id', '!=', $ignorePlanId))
            ->active()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'student_id' => 'This student already has an active allowance plan. Deactivate it first.',
            ]);
        }
    }

    /**
     * The id of a status row, for example the id of "active".
     */
    private function statusId(string $status): int
    {
        $id = AllowancePlanStatus::query()->where('status', $status)->value('id');

        if ($id === null) {
            throw new RuntimeException("The allowance plan status \"{$status}\" is missing.");
        }

        return (int) $id;
    }
}
