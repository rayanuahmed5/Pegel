<?php

namespace App\Domain\Services;

use App\Domain\Exceptions\ArbzgViolationException;
use App\Domain\Exceptions\BudgetExceededException;
use App\Domain\Exceptions\ConsultantNotAssignedException;
use App\Domain\Exceptions\OverlappingBookingException;
use App\Domain\Exceptions\PlannedAbsenceConflictException;
use App\Models\Consultant;
use App\Models\PurchaseOrder;
use App\Models\TimeBooking;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        protected ArbzgValidationService $arbzgValidator
    ) {}

    /**
     * Book hours on a purchase order with full domain validation.
     *
     * @throws BudgetExceededException
     * @throws ConsultantNotAssignedException
     * @throws ArbzgViolationException
     * @throws OverlappingBookingException
     * @throws PlannedAbsenceConflictException
     */
    public function book(
        PurchaseOrder $order,
        Consultant $consultant,
        string $date,
        float $durationHours,
        string $description,
        ?string $accountingObject = null,
        ?string $activity = null,
        ?string $startTime = null,
        ?string $endTime = null,
        string $status = 'pending'
    ): TimeBooking {
        return DB::transaction(function () use (
            $order,
            $consultant,
            $date,
            $durationHours,
            $description,
            $accountingObject,
            $activity,
            $startTime,
            $endTime,
            $status
        ) {
            // 1. Validate compliance with German Working Time Act (ArbZG) & absence conflicts
            $this->arbzgValidator->validate(
                $consultant,
                $date,
                $durationHours,
                $startTime,
                $endTime
            );

            // 2. Enforce Purchase Order budget & assignment rules
            return $order->book(
                $consultant,
                $durationHours,
                $date,
                $description,
                $accountingObject,
                $activity,
                $startTime,
                $endTime,
                $status
            );
        });
    }
}
