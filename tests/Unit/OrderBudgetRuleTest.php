<?php

use App\Domain\Exceptions\BudgetExceededException;
use App\Domain\Exceptions\ConsultantNotAssignedException;
use App\Models\Consultant;
use App\Models\CostCentre;
use App\Models\PurchaseOrder;
use App\Models\Supplier;

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
        'name' => 'IT Customer Systems',
        'department' => 'Corporate IT',
    ]);

    $this->order = PurchaseOrder::query()->create([
        'order_number' => '4500012345',
        'title' => 'Customer portal migration',
        'supplier_id' => $this->supplier->id,
        'cost_centre_id' => $this->costCentre->id,
        'ordered_budget_pd' => 10.0, // 10 PD = 80 hours
        'hourly_rate_eur' => 120.00,
        'start_date' => '2026-10-01',
        'end_date' => '2026-12-31',
        'status' => 'active',
    ]);

    $this->order->consultants()->attach($this->consultant->id);
});

test('it calculates remaining budget in hours and person days accurately', function () {
    expect($this->order->remainingBudgetHours())->toBe(80.0)
        ->and($this->order->remainingBudgetPD())->toBe(10.0)
        ->and($this->order->calculateRemainingBudget())->toBe(10.0)
        ->and($this->order->usedHours())->toBe(0.0)
        ->and($this->order->usedPD())->toBe(0.0)
        ->and($this->order->budgetUsagePercent())->toBe(0.0);
});

test('it accepts booking within free budget', function () {
    $booking = $this->order->book(
        consultant: $this->consultant,
        hours: 8.0,
        date: '2026-10-15',
        description: 'Frontend component migration',
    );

    expect($booking)->not->toBeNull()
        ->and($booking->duration_hours)->toBe(8.0)
        ->and($this->order->fresh()->usedHours())->toBe(8.0)
        ->and($this->order->fresh()->usedPD())->toBe(1.0)
        ->and($this->order->fresh()->remainingBudgetHours())->toBe(72.0)
        ->and($this->order->fresh()->remainingBudgetPD())->toBe(9.0)
        ->and($this->order->fresh()->budgetUsagePercent())->toBe(10.0);
});

test('it accepts booking that consumes exactly the remaining free budget', function () {
    // Total budget is 80 hours
    $booking = $this->order->book(
        consultant: $this->consultant,
        hours: 80.0,
        date: '2026-10-15',
        description: 'Exhaust entire budget',
    );

    expect($booking)->not->toBeNull()
        ->and($this->order->fresh()->remainingBudgetHours())->toBe(0.0)
        ->and($this->order->fresh()->remainingBudgetPD())->toBe(0.0)
        ->and($this->order->fresh()->budgetUsagePercent())->toBe(100.0);
});

test('it rejects booking beyond free budget with clear message', function () {
    // Order budget is 80 hours (10 PD). Attempt 80.5 hours.
    expect(function () {
        $this->order->book(
            consultant: $this->consultant,
            hours: 80.5,
            date: '2026-10-15',
            description: 'Over budget attempt',
        );
    })->toThrow(
        BudgetExceededException::class,
        'Booking rejected: only 10 PD (80 h) left on order 4500012345.'
    );
});

test('it rejects booking from consultant not assigned to the order', function () {
    $unassignedConsultant = Consultant::query()->create([
        'supplier_id' => $this->supplier->id,
        'name' => 'Foreign Consultant',
        'email' => 'foreign@other.de',
        'daily_rate_eur' => 800.00,
    ]);

    expect(function () use ($unassignedConsultant) {
        $this->order->book(
            consultant: $unassignedConsultant,
            hours: 8.0,
            date: '2026-10-15',
            description: 'Unassigned work',
        );
    })->toThrow(
        ConsultantNotAssignedException::class,
        'Consultant Foreign Consultant is not assigned to Purchase Order 4500012345.'
    );
});
