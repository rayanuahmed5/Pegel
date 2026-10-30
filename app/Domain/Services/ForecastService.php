<?php

namespace App\Domain\Services;

use App\Models\PlannedAbsence;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ForecastService
{
    public const DEFAULT_PURCHASING_LEAD_WEEKS = 4;

    public const HOURS_PER_PERSON_DAY = 8.0;

    /**
     * Calculate comprehensive forecast and burn-rate metrics for a purchase order.
     *
     * @return array<string, mixed>
     */
    public function calculateForecast(PurchaseOrder $order, ?Carbon $asOfDate = null): array
    {
        $asOf = $asOfDate ? $asOfDate->copy() : Carbon::parse('2026-10-23'); // Week 42 Friday anchor
        $orderedPD = (float) $order->ordered_budget_pd;
        $usedHours = $order->usedHours();
        $usedPD = $order->usedPD();
        $remainingHours = $order->remainingBudgetHours();
        $remainingPD = $order->remainingBudgetPD();
        $usagePercent = $order->budgetUsagePercent();

        // 1. Calculate historical weekly pace (velocity in PD / week)
        $weeklyPacePD = $this->calculateWeeklyPace($order, $asOf);

        // 2. Unadjusted pace forecast (baseline linear projection)
        // From current week, how many weeks of pace until budget runs out
        $asOfStartOfWeek = $asOf->copy()->startOfWeek();
        $unadjustedWeeksCount = $weeklyPacePD > 0 ? (int) floor($remainingPD / $weeklyPacePD) : 0;
        $unadjustedDepletionDate = (clone $asOfStartOfWeek)->addWeeks($unadjustedWeeksCount);
        $unadjustedDepletionWeek = (int) $unadjustedDepletionDate->isoWeek();

        // 3. Adjusted forecast factoring in planned vacations, client shutdowns and holidays
        $adjustedProjection = $this->projectWithAbsences($order, $remainingPD, $asOf, $weeklyPacePD);
        $adjustedDepletionDate = $adjustedProjection['depletion_date'];
        $adjustedDepletionWeek = (int) $adjustedDepletionDate->isoWeek();
        $weeklyBurnSchedule = $adjustedProjection['schedule'];

        // 4. Project end gap & purchasing lead time calculation
        $projectEndDate = Carbon::parse($order->end_date);
        $projectEndWeek = (int) $projectEndDate->isoWeek();

        // Missing person days calculation
        $missingPD = 0.0;
        if ($adjustedDepletionDate->lessThan($projectEndDate)) {
            $workingDaysGap = $this->countWorkingDaysBetween(
                $adjustedDepletionDate,
                $projectEndDate,
                $order
            );
            // Daily burn rate = 6 PD / 5 working days = 1.2 PD/day
            $dailyPacePD = $weeklyPacePD / 5.0;
            $missingPD = round($workingDaysGap * $dailyPacePD, 1);
        }

        // Purchasing deadline (must order X weeks prior to depletion)
        $purchasingLeadWeeks = self::DEFAULT_PURCHASING_LEAD_WEEKS;
        $reorderDeadlineDate = (clone $adjustedDepletionDate)->subWeeks($purchasingLeadWeeks);
        $reorderDeadlineWeek = (int) $reorderDeadlineDate->isoWeek();

        // Traffic Light Status:
        // GREEN: Budget reaches project end with buffer
        // YELLOW: Budget exhausts before end, but re-order deadline is > 2 weeks away
        // RED: Budget exhausts before end AND re-order deadline is <= 2 weeks away or overdue
        $daysUntilReorderDeadline = (int) $asOf->diffInDays($reorderDeadlineDate, false);

        if ($adjustedDepletionDate->greaterThanOrEqualTo($projectEndDate)) {
            $trafficLight = 'green';
            $statusMessage = 'Budget is healthy and reaches scheduled project completion.';
        } elseif ($daysUntilReorderDeadline > 14) {
            $trafficLight = 'yellow';
            $statusMessage = "Budget will deplete in Week {$adjustedDepletionWeek}. Re-order required by Week {$reorderDeadlineWeek}.";
        } else {
            $trafficLight = 'red';
            $statusMessage = "CRITICAL: Follow-up purchase order required immediately (Deadline: Week {$reorderDeadlineWeek})!";
        }

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'title' => $order->title,
            'as_of_date' => $asOf->toDateString(),
            'as_of_week' => $asOf->isoWeek(),
            'ordered_budget_pd' => $orderedPD,
            'used_pd' => $usedPD,
            'used_hours' => $usedHours,
            'remaining_pd' => $remainingPD,
            'remaining_hours' => $remainingHours,
            'budget_usage_percent' => $usagePercent,
            'weekly_pace_pd' => round($weeklyPacePD, 1),
            'unadjusted_depletion_date' => $unadjustedDepletionDate->toDateString(),
            'unadjusted_depletion_week' => $unadjustedDepletionWeek,
            'adjusted_depletion_date' => $adjustedDepletionDate->toDateString(),
            'adjusted_depletion_week' => $adjustedDepletionWeek,
            'project_end_date' => $projectEndDate->toDateString(),
            'project_end_week' => $projectEndWeek,
            'missing_pd_until_end' => $missingPD,
            'purchasing_lead_weeks' => $purchasingLeadWeeks,
            'reorder_deadline_date' => $reorderDeadlineDate->toDateString(),
            'reorder_deadline_week' => $reorderDeadlineWeek,
            'days_until_reorder_deadline' => $daysUntilReorderDeadline,
            'traffic_light' => $trafficLight,
            'status_message' => $statusMessage,
            'recommended_followup_order_pd' => max($missingPD, 0.0),
            'weekly_burn_schedule' => $weeklyBurnSchedule,
        ];
    }

    /**
     * Compute average weekly velocity (Person-Days / week) from historical approved bookings.
     */
    public function calculateWeeklyPace(PurchaseOrder $order, ?Carbon $asOf = null): float
    {
        $uniqueWeeks = $order->timeBookings()
            ->where('status', '!=', 'rejected')
            ->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('o-W');
            })
            ->unique()
            ->count();

        if ($uniqueWeeks === 0) {
            return 6.0; // Default baseline in challenge
        }

        return round($order->usedPD() / $uniqueWeeks, 1);
    }

    /**
     * Simulate week-by-week burn rate accounting for consultant absences and shutdowns.
     *
     * @return array{depletion_date: Carbon, schedule: array<int, array<string, mixed>>}
     */
    protected function projectWithAbsences(
        PurchaseOrder $order,
        float $remainingPD,
        Carbon $asOf,
        float $standardWeeklyPace
    ): array {
        $consultantIds = $order->consultants()->pluck('consultants.id')->all();
        $teamSize = max(1, count($consultantIds));
        $nominalDailyPerPerson = ($standardWeeklyPace / $teamSize) / 5.0; // Daily PD per person

        $currentBudget = $remainingPD;
        $currentDate = $asOf->copy()->startOfWeek()->addWeek(); // Begin next calendar week
        $schedule = [];
        $maxWeeksSimulated = 52;
        $weekCount = 0;

        while ($currentBudget > 0 && $weekCount < $maxWeeksSimulated) {
            $weekNumber = $currentDate->isoWeek();
            $weekYear = $currentDate->isoWeekYear();
            $weekStart = $currentDate->copy()->startOfWeek();
            $weekEnd = $currentDate->copy()->endOfWeek();

            // Calculate active working capacity in this week
            $weekWorkingDays = 5; // Mon-Fri
            $weekActualPD = 0.0;
            $absenceNotes = [];

            // Check each working day for holidays/vacations/shutdowns
            for ($d = 0; $d < 5; $d++) {
                $day = $weekStart->copy()->addDays($d);
                $dayDate = $day->toDateString();

                foreach ($consultantIds as $cId) {
                    $absence = PlannedAbsence::query()
                        ->where('consultant_id', $cId)
                        ->where('start_date', '<=', $dayDate)
                        ->where('end_date', '>=', $dayDate)
                        ->first();

                    if ($absence) {
                        $absenceNotes[] = "{$absence->type} ({$absence->description})";
                    } else {
                        // Consultant works this day
                        $weekActualPD += $nominalDailyPerPerson;
                    }
                }
            }

            // Cap burn at remaining budget
            $burnedThisWeek = min($currentBudget, $weekActualPD);
            $currentBudget = max(0.0, round($currentBudget - $burnedThisWeek, 2));

            $schedule[] = [
                'calendar_week' => $weekNumber,
                'year' => $weekYear,
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'planned_burn_pd' => round($burnedThisWeek, 1),
                'remaining_budget_pd' => round($currentBudget, 1),
                'absences' => array_values(array_unique($absenceNotes)),
                'is_depleted' => $currentBudget <= 0,
            ];

            if ($currentBudget <= 0) {
                return [
                    'depletion_date' => $weekStart->copy()->addDays((int) min(4, round(($burnedThisWeek / max(0.1, $weekActualPD)) * 5))),
                    'schedule' => $schedule,
                ];
            }

            $currentDate->addWeek();
            $weekCount++;
        }

        return [
            'depletion_date' => $currentDate,
            'schedule' => $schedule,
        ];
    }

    /**
     * Count business working days between two dates, excluding weekends and shutdowns.
     */
    protected function countWorkingDaysBetween(
        Carbon $from,
        Carbon $to,
        PurchaseOrder $order
    ): int {
        if ($from->greaterThanOrEqualTo($to)) {
            return 0;
        }

        $period = CarbonPeriod::create($from, $to);
        $consultantIds = $order->consultants()->pluck('consultants.id')->all();
        $workingDays = 0;

        foreach ($period as $date) {
            if ($date->isWeekend()) {
                continue;
            }

            $dateStr = $date->toDateString();
            // Check if full shutdown exists
            $isShutdown = PlannedAbsence::query()
                ->whereIn('consultant_id', $consultantIds)
                ->where('type', 'shutdown')
                ->where('start_date', '<=', $dateStr)
                ->where('end_date', '>=', $dateStr)
                ->exists();

            if (! $isShutdown) {
                $workingDays++;
            }
        }

        return $workingDays;
    }
}
