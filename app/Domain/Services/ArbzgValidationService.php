<?php

namespace App\Domain\Services;

use App\Domain\Exceptions\ArbzgViolationException;
use App\Domain\Exceptions\OverlappingBookingException;
use App\Domain\Exceptions\PlannedAbsenceConflictException;
use App\Models\Consultant;
use App\Models\TimeBooking;
use Carbon\Carbon;

class ArbzgValidationService
{
    public const MAX_DAILY_HOURS = 10.0;

    public const MIN_REST_HOURS = 11.0;

    /**
     * Validate all ArbZG compliance rules and absence conflicts for a proposed booking.
     *
     * @throws ArbzgViolationException
     * @throws OverlappingBookingException
     * @throws PlannedAbsenceConflictException
     */
    public function validate(
        Consultant $consultant,
        string $date,
        float $hours,
        ?string $startTime = null,
        ?string $endTime = null
    ): void {
        $this->validateHoursRange($hours);
        $this->validateNoAbsenceConflict($consultant, $date);
        $this->validateDailyCap($consultant, $date, $hours);

        if ($startTime !== null && $endTime !== null) {
            $this->validateTimeOrder($startTime, $endTime);
            $this->validateNoOverlap($consultant, $date, $startTime, $endTime);
            $this->validateRestPeriod($consultant, $date, $startTime);
        }
    }

    /**
     * Basic check: positive hours and reasonable single entry limit.
     */
    public function validateHoursRange(float $hours): void
    {
        if ($hours <= 0) {
            throw new ArbzgViolationException('Booking rejected: Duration must be greater than 0 hours.');
        }

        if ($hours > self::MAX_DAILY_HOURS) {
            throw new ArbzgViolationException(
                "Booking rejected: German Working Time Act (ArbZG § 3) limits daily work to 10 hours. Attempted: {$hours}h."
            );
        }
    }

    /**
     * ArbZG § 3: Daily working time may not exceed 10 hours.
     */
    public function validateDailyCap(Consultant $consultant, string $date, float $newHours): void
    {
        $existingHours = (float) TimeBooking::query()
            ->where('consultant_id', $consultant->id)
            ->where('date', $date)
            ->where('status', '!=', 'rejected')
            ->sum('duration_hours');

        $totalHours = $existingHours + $newHours;

        if ($totalHours > self::MAX_DAILY_HOURS) {
            throw new ArbzgViolationException(
                "Booking rejected: German Working Time Act (ArbZG § 3) limits daily work to 10 hours. Already booked on {$date}: {$existingHours}h, attempted: {$newHours}h (total: {$totalHours}h)."
            );
        }
    }

    /**
     * Planned Absences check: vacations, company shutdowns, holidays.
     */
    public function validateNoAbsenceConflict(Consultant $consultant, string $date): void
    {
        $absence = $consultant->plannedAbsences()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if ($absence !== null) {
            $label = ucfirst($absence->type);
            $note = $absence->description ? " - {$absence->description}" : '';
            throw new PlannedAbsenceConflictException(
                "Booking rejected: Consultant has scheduled {$label} on {$date}{$note}."
            );
        }
    }

    /**
     * Validate that start time precedes end time.
     */
    public function validateTimeOrder(string $startTime, string $endTime): void
    {
        if (strtotime($endTime) <= strtotime($startTime)) {
            throw new ArbzgViolationException(
                "Booking rejected: End time ({$endTime}) must be after start time ({$startTime})."
            );
        }
    }

    /**
     * Overlapping booking check: avoid double-booking the same consultant.
     */
    public function validateNoOverlap(
        Consultant $consultant,
        string $date,
        string $startTime,
        string $endTime
    ): void {
        $overlapExists = TimeBooking::query()
            ->where('consultant_id', $consultant->id)
            ->where('date', $date)
            ->where('status', '!=', 'rejected')
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->exists();

        if ($overlapExists) {
            throw new OverlappingBookingException(
                "Booking rejected: Consultant already has an overlapping booking on {$date} during {$startTime} - {$endTime}."
            );
        }
    }

    /**
     * ArbZG § 5: Uninterrupted rest period of at least 11 hours between shifts.
     */
    public function validateRestPeriod(
        Consultant $consultant,
        string $date,
        string $startTime
    ): void {
        $currentStart = Carbon::parse("{$date} {$startTime}");
        $previousDay = Carbon::parse($date)->subDay()->toDateString();

        $previousBooking = TimeBooking::query()
            ->where('consultant_id', $consultant->id)
            ->where('date', $previousDay)
            ->where('status', '!=', 'rejected')
            ->whereNotNull('end_time')
            ->orderByDesc('end_time')
            ->first();

        if ($previousBooking && $previousBooking->end_time) {
            $previousEnd = Carbon::parse("{$previousDay} {$previousBooking->end_time}");
            $restHours = $previousEnd->diffInMinutes($currentStart) / 60.0;

            if ($restHours < self::MIN_REST_HOURS) {
                $formattedRest = round($restHours, 1);
                throw new ArbzgViolationException(
                    "Booking rejected: German Working Time Act (ArbZG § 5) requires an 11-hour rest period between shifts. Only {$formattedRest}h between previous shift end ({$previousBooking->end_time}) and new start ({$startTime})."
                );
            }
        }
    }
}
