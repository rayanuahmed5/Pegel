<?php

use App\Domain\Services\ForecastService;
use App\Models\Consultant;
use App\Models\CostCentre;
use App\Models\PlannedAbsence;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\TimeBooking;
use Carbon\Carbon;

beforeEach(function () {
    $this->supplier = Supplier::query()->create([
        'name' => 'Nova Consulting GmbH',
        'contact_email' => 'contracts@novaconsulting.de',
    ]);

    $this->elena = Consultant::query()->create([
        'supplier_id' => $this->supplier->id,
        'name' => 'Elena Becker',
        'email' => 'elena@novaconsulting.de',
        'daily_rate_eur' => 960.00,
    ]);

    $this->david = Consultant::query()->create([
        'supplier_id' => $this->supplier->id,
        'name' => 'David Koch',
        'email' => 'david@novaconsulting.de',
        'daily_rate_eur' => 960.00,
    ]);

    $this->costCentre = CostCentre::query()->create([
        'code' => 'CC-4100',
        'name' => 'IT Customer Systems',
        'department' => 'Corporate IT',
    ]);

    // Main challenge order: 140 PD, ends in Week 5, 2027
    $this->order = PurchaseOrder::query()->create([
        'order_number' => '4500012345',
        'title' => 'Customer portal migration',
        'supplier_id' => $this->supplier->id,
        'cost_centre_id' => $this->costCentre->id,
        'ordered_budget_pd' => 140.0,
        'hourly_rate_eur' => 120.00,
        'start_date' => '2026-07-06',
        'end_date' => '2027-02-05', // Friday of Week 5, 2027
        'status' => 'active',
    ]);

    $this->order->consultants()->attach([$this->elena->id, $this->david->id]);

    // Seed 14 weeks of bookings @ 6 PD/week = 84 PD used
    $startDate = Carbon::parse('2026-07-06');
    for ($w = 0; $w < 14; $w++) {
        $weekStart = (clone $startDate)->addWeeks($w);
        // Elena: 3 days (24h)
        foreach ([0, 1, 2] as $offset) {
            TimeBooking::query()->create([
                'purchase_order_id' => $this->order->id,
                'consultant_id' => $this->elena->id,
                'date' => (clone $weekStart)->addDays($offset)->toDateString(),
                'duration_hours' => 8.0,
                'description' => 'Frontend components',
                'status' => 'approved',
            ]);
        }
        // David: 3 days (24h)
        foreach ([2, 3, 4] as $offset) {
            TimeBooking::query()->create([
                'purchase_order_id' => $this->order->id,
                'consultant_id' => $this->david->id,
                'date' => (clone $weekStart)->addDays($offset)->toDateString(),
                'duration_hours' => 8.0,
                'description' => 'Backend microservices',
                'status' => 'approved',
            ]);
        }
    }

    $this->forecastService = new ForecastService;
});

test('it calculates historical velocity matching the challenge pace of 6 PD per week', function () {
    $asOf = Carbon::parse('2026-10-16');
    $pace = $this->forecastService->calculateWeeklyPace($this->order, $asOf);

    expect($this->order->usedPD())->toBe(84.0)
        ->and($this->order->remainingBudgetPD())->toBe(56.0)
        ->and($pace)->toBe(6.0);
});

test('it projects unadjusted linear depletion around week 50-51', function () {
    $asOf = Carbon::parse('2026-10-16');
    $forecast = $this->forecastService->calculateForecast($this->order, $asOf);

    expect($forecast['unadjusted_depletion_week'])->toBeGreaterThanOrEqual(50)
        ->and($forecast['unadjusted_depletion_week'])->toBeLessThanOrEqual(52);
});

test('it factors in vacations to extend calendar depletion date', function () {
    // Register autumn vacation blocks
    PlannedAbsence::query()->create([
        'consultant_id' => $this->elena->id,
        'type' => 'vacation',
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-30',
        'description' => 'Autumn Vacation A',
    ]);

    PlannedAbsence::query()->create([
        'consultant_id' => $this->david->id,
        'type' => 'vacation',
        'start_date' => '2026-11-09',
        'end_date' => '2026-11-13',
        'description' => 'Autumn Vacation B',
    ]);

    $asOf = Carbon::parse('2026-10-16');
    $forecast = $this->forecastService->calculateForecast($this->order, $asOf);

    $unadjustedDate = Carbon::parse($forecast['unadjusted_depletion_date']);
    $adjustedDate = Carbon::parse($forecast['adjusted_depletion_date']);

    // Vacations push the budget depletion date further into the future
    expect($adjustedDate->greaterThan($unadjustedDate))->toBeTrue();
});

test('forecast schedule reports reduced capacity without exposing absence descriptions', function () {
    PlannedAbsence::query()->create([
        'consultant_id' => $this->elena->id,
        'type' => 'vacation',
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-30',
        'description' => 'Private travel plans',
    ]);

    $forecast = $this->forecastService->calculateForecast(
        $this->order,
        Carbon::parse('2026-10-16')
    );
    $weeklyAbsences = collect($forecast['weekly_burn_schedule'])
        ->flatMap(fn (array $week) => $week['absences'])
        ->all();

    expect($weeklyAbsences)
        ->toContain('Reduced capacity')
        ->not->toContain('Private travel plans');
});

test('it calculates missing person days until project completion in week 5 of 2027', function () {
    // Register autumn vacation blocks and year-end shutdown as per challenge scenario
    PlannedAbsence::query()->create([
        'consultant_id' => $this->elena->id,
        'type' => 'vacation',
        'start_date' => '2026-10-26',
        'end_date' => '2026-10-30',
        'description' => 'Autumn Vacation A',
    ]);

    PlannedAbsence::query()->create([
        'consultant_id' => $this->david->id,
        'type' => 'vacation',
        'start_date' => '2026-11-09',
        'end_date' => '2026-11-13',
        'description' => 'Autumn Vacation B',
    ]);

    PlannedAbsence::query()->create([
        'consultant_id' => $this->elena->id,
        'type' => 'shutdown',
        'start_date' => '2026-12-24',
        'end_date' => '2027-01-01',
        'description' => 'Plant Shutdown',
    ]);

    $asOf = Carbon::parse('2026-10-16');
    $forecast = $this->forecastService->calculateForecast($this->order, $asOf);

    // Challenge specifies ≈ 31 Person-Days missing until week 5, 2027
    expect($forecast['missing_pd_until_end'])->toBeGreaterThanOrEqual(25.0)
        ->and($forecast['missing_pd_until_end'])->toBeLessThanOrEqual(35.0)
        ->and($forecast['project_end_week'])->toBe(5);
});

test('it calculates purchasing re-order lead time deadline 4 weeks before depletion', function () {
    $asOf = Carbon::parse('2026-10-16');
    $forecast = $this->forecastService->calculateForecast($this->order, $asOf);

    $depletion = Carbon::parse($forecast['adjusted_depletion_date']);
    $reorder = Carbon::parse($forecast['reorder_deadline_date']);

    // Re-order date is exactly 4 weeks (28 days) before depletion
    expect($forecast['purchasing_lead_weeks'])->toBe(4)
        ->and((int) $reorder->diffInDays($depletion))->toBe(28);
});

test('it sets traffic light to yellow or red when budget depletes before project completion', function () {
    $asOf = Carbon::parse('2026-10-16');
    $forecast = $this->forecastService->calculateForecast($this->order, $asOf);

    expect(in_array($forecast['traffic_light'], ['yellow', 'red']))->toBeTrue()
        ->and($forecast['recommended_followup_order_pd'])->toBeGreaterThan(0);
});
