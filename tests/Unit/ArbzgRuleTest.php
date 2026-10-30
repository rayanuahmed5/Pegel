<?php

use App\Domain\Exceptions\ArbzgViolationException;
use App\Domain\Exceptions\OverlappingBookingException;
use App\Domain\Exceptions\PlannedAbsenceConflictException;
use App\Domain\Services\ArbzgValidationService;
use App\Domain\Services\BookingService;
use App\Models\Consultant;
use App\Models\CostCentre;
use App\Models\PlannedAbsence;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\TimeBooking;

beforeEach(function () {
    $this->supplier = Supplier::query()->create([
        'name' => 'Nova Consulting GmbH',
        'contact_email' => 'contracts@novaconsulting.de',
    ]);

    $this->consultant = Consultant::query()->create([
        'supplier_id' => $this->supplier->id,
        'name' => 'Elena Becker',
        'email' => 'elena@novaconsulting.de',
        'daily_rate_eur' => 960.00,
    ]);

    $this->costCentre = CostCentre::query()->create([
        'code' => 'CC-4100',
        'name' => 'IT Systems',
    ]);

    $this->order = PurchaseOrder::query()->create([
        'order_number' => '4500012345',
        'title' => 'Customer portal migration',
        'supplier_id' => $this->supplier->id,
        'cost_centre_id' => $this->costCentre->id,
        'ordered_budget_pd' => 140.0,
        'hourly_rate_eur' => 120.00,
        'start_date' => '2026-07-06',
        'end_date' => '2027-02-05',
        'status' => 'active',
    ]);

    $this->order->consultants()->attach($this->consultant->id);
    $this->validator = new ArbzgValidationService;
    $this->bookingService = new BookingService($this->validator);
});

test('it accepts booking up to 10 hours per day', function () {
    // Valid 8 hours booking
    $this->validator->validate(
        consultant: $this->consultant,
        date: '2026-10-20',
        hours: 8.0,
    );

    // Valid 10 hours boundary
    $this->validator->validate(
        consultant: $this->consultant,
        date: '2026-10-21',
        hours: 10.0,
    );

    expect(true)->toBeTrue();
});

test('it rejects booking exceeding 10 hours daily limit (ArbZG § 3)', function () {
    expect(function () {
        $this->validator->validate(
            consultant: $this->consultant,
            date: '2026-10-20',
            hours: 10.5,
        );
    })->toThrow(
        ArbzgViolationException::class,
        'German Working Time Act (ArbZG § 3) limits daily work to 10 hours'
    );
});

test('it rejects cumulative bookings on same day that exceed 10 hours', function () {
    // Book 7 hours on 2026-10-20
    TimeBooking::query()->create([
        'purchase_order_id' => $this->order->id,
        'consultant_id' => $this->consultant->id,
        'date' => '2026-10-20',
        'duration_hours' => 7.0,
        'description' => 'Morning shift',
    ]);

    // Attempting 4 hours on the same day (7 + 4 = 11h > 10h)
    expect(function () {
        $this->validator->validate(
            consultant: $this->consultant,
            date: '2026-10-20',
            hours: 4.0,
        );
    })->toThrow(
        ArbzgViolationException::class,
        'Already booked on 2026-10-20: 7h, attempted: 4h (total: 11h)'
    );
});

test('it rejects booking with duration less than or equal to zero hours', function () {
    expect(function () {
        $this->validator->validate(
            consultant: $this->consultant,
            date: '2026-10-20',
            hours: 0.0,
        );
    })->toThrow(
        ArbzgViolationException::class,
        'Duration must be greater than 0 hours'
    );
});

test('it rejects booking during consultant planned vacation', function () {
    PlannedAbsence::query()->create([
        'consultant_id' => $this->consultant->id,
        'type' => 'vacation',
        'start_date' => '2026-10-12',
        'end_date' => '2026-10-16',
        'description' => 'Autumn Family Trip',
    ]);

    expect(function () {
        $this->validator->validate(
            consultant: $this->consultant,
            date: '2026-10-14',
            hours: 8.0,
        );
    })->toThrow(
        PlannedAbsenceConflictException::class,
        'Consultant has scheduled Vacation on 2026-10-14 - Autumn Family Trip'
    );
});

test('it rejects booking during company shutdown', function () {
    PlannedAbsence::query()->create([
        'consultant_id' => $this->consultant->id,
        'type' => 'shutdown',
        'start_date' => '2026-12-24',
        'end_date' => '2027-01-01',
        'description' => 'Plant Year-End Shutdown',
    ]);

    expect(function () {
        $this->validator->validate(
            consultant: $this->consultant,
            date: '2026-12-29',
            hours: 8.0,
        );
    })->toThrow(
        PlannedAbsenceConflictException::class,
        'Consultant has scheduled Shutdown on 2026-12-29'
    );
});

test('it rejects overlapping booking for same consultant on same date', function () {
    TimeBooking::query()->create([
        'purchase_order_id' => $this->order->id,
        'consultant_id' => $this->consultant->id,
        'date' => '2026-10-22',
        'start_time' => '09:00:00',
        'end_time' => '13:00:00',
        'duration_hours' => 4.0,
        'description' => 'Morning session',
    ]);

    // Overlapping booking: 11:00 - 15:00
    expect(function () {
        $this->validator->validate(
            consultant: $this->consultant,
            date: '2026-10-22',
            hours: 4.0,
            startTime: '11:00:00',
            endTime: '15:00:00',
        );
    })->toThrow(
        OverlappingBookingException::class,
        'Consultant already has an overlapping booking on 2026-10-22 during 11:00:00 - 15:00:00'
    );
});

test('it rejects booking violating 11-hour rest period between shifts (ArbZG § 5)', function () {
    // Shift ends at 23:00 on 2026-10-20
    TimeBooking::query()->create([
        'purchase_order_id' => $this->order->id,
        'consultant_id' => $this->consultant->id,
        'date' => '2026-10-20',
        'start_time' => '15:00:00',
        'end_time' => '23:00:00',
        'duration_hours' => 8.0,
        'description' => 'Late deployment',
    ]);

    // Next shift starts at 07:00 on 2026-10-21 (only 8 hours rest instead of required 11h)
    expect(function () {
        $this->validator->validate(
            consultant: $this->consultant,
            date: '2026-10-21',
            hours: 8.0,
            startTime: '07:00:00',
            endTime: '15:30:00',
        );
    })->toThrow(
        ArbzgViolationException::class,
        'German Working Time Act (ArbZG § 5) requires an 11-hour rest period between shifts. Only 8h between previous shift end (23:00:00) and new start (07:00:00)'
    );
});

test('BookingService orchestrates compliance and books hours cleanly', function () {
    $booking = $this->bookingService->book(
        order: $this->order,
        consultant: $this->consultant,
        date: '2026-10-25',
        durationHours: 8.0,
        description: 'Sprint planning and architecture design',
        accountingObject: 'PSP: P-2026-0815.03',
        activity: 'Architecture',
        startTime: '09:00:00',
        endTime: '17:30:00',
    );

    expect($booking)->not->toBeNull()
        ->and($booking->duration_hours)->toBe(8.0)
        ->and($booking->purchase_order_id)->toBe($this->order->id)
        ->and($booking->consultant_id)->toBe($this->consultant->id)
        ->and($booking->status)->toBe('pending');
});
