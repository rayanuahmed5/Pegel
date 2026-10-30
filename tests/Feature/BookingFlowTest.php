<?php

use App\Models\Consultant;
use App\Models\CostCentre;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\TimeBooking;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->supplier = Supplier::query()->create([
        'name' => 'Nova Consulting GmbH',
        'contact_email' => 'contracts@novaconsulting.de',
    ]);

    $this->consultantUser = User::query()->create([
        'name' => 'Elena Becker',
        'email' => 'consultant@pegel.test',
        'password' => Hash::make('password'),
        'role' => 'consultant',
    ]);

    $this->leadUser = User::query()->create([
        'name' => 'Markus Weber',
        'email' => 'lead@pegel.test',
        'password' => Hash::make('password'),
        'role' => 'project_lead',
    ]);

    $this->consultant = Consultant::query()->create([
        'user_id' => $this->consultantUser->id,
        'supplier_id' => $this->supplier->id,
        'name' => 'Elena Becker',
        'email' => 'consultant@pegel.test',
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
        'project_lead_id' => $this->leadUser->id,
        'ordered_budget_pd' => 140.0,
        'hourly_rate_eur' => 120.00,
        'start_date' => '2026-07-06',
        'end_date' => '2027-02-05',
        'status' => 'active',
    ]);

    $this->order->consultants()->attach($this->consultant->id);
});

test('welcome landing page renders successfully', function () {
    $response = $this->get('/');
    $response->assertOk();
});

test('guest is redirected to login when accessing dashboard', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('demo role switch route logs in as requested role', function () {
    $response = $this->post('/demo/switch-role', [
        'role' => 'consultant',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($this->consultantUser);
});

test('consultant can book working hours on assigned purchase order', function () {
    $this->actingAs($this->consultantUser);

    $response = $this->post('/bookings', [
        'purchase_order_id' => $this->order->id,
        'date' => '2026-10-28',
        'duration_hours' => 8.0,
        'description' => 'Developed OAuth authentication flow and unit tests',
        'accounting_object' => 'PSP: P-2026-0815.03',
        'activity' => 'Security & Identity',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('time_bookings', [
        'purchase_order_id' => $this->order->id,
        'consultant_id' => $this->consultant->id,
        'duration_hours' => 8.0,
        'status' => 'pending',
    ]);

    expect($this->order->fresh()->usedHours())->toBe(8.0);
});

test('non-consultant cannot create a booking on a consultant account', function () {
    $this->actingAs($this->leadUser);

    $response = $this->post('/bookings', [
        'purchase_order_id' => $this->order->id,
        'date' => '2026-10-28',
        'duration_hours' => 8.0,
        'description' => 'Booking created by a non-consultant',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('time_bookings', 0);
});

test('server rejects booking exceeding ArbZG 10 hours daily cap', function () {
    $this->actingAs($this->consultantUser);

    $response = $this->post('/bookings', [
        'purchase_order_id' => $this->order->id,
        'date' => '2026-10-28',
        'duration_hours' => 10.5,
        'description' => 'Overtime shift exceeding legal limit',
    ]);

    $response->assertSessionHasErrors(['duration_hours']);
});

test('project lead can approve proof of work booking', function () {
    $booking = TimeBooking::query()->create([
        'purchase_order_id' => $this->order->id,
        'consultant_id' => $this->consultant->id,
        'date' => '2026-10-27',
        'duration_hours' => 8.0,
        'description' => 'API microservice development',
        'status' => 'pending',
    ]);

    $this->actingAs($this->leadUser);

    $response = $this->post("/bookings/{$booking->id}/approve");

    $response->assertRedirect();
    expect($booking->fresh()->status)->toBe('approved')
        ->and($booking->fresh()->approved_by)->toBe($this->leadUser->id);
});

test('superadmin can approve proof of work booking', function () {
    $booking = TimeBooking::query()->create([
        'purchase_order_id' => $this->order->id,
        'consultant_id' => $this->consultant->id,
        'date' => '2026-10-27',
        'duration_hours' => 8.0,
        'description' => 'API microservice development',
        'status' => 'pending',
    ]);

    $superadmin = User::query()->create([
        'name' => 'Super Admin',
        'username' => 'superadmin',
        'email' => 'superadmin@pegel.test',
        'password' => Hash::make('password'),
        'role' => 'superadmin',
    ]);
    $this->actingAs($superadmin);

    $response = $this->post("/bookings/{$booking->id}/approve");

    $response->assertRedirect();
    expect($booking->fresh()->status)->toBe('approved')
        ->and($booking->fresh()->approved_by)->toBe($superadmin->id);
});

test('project lead can reject booking with feedback', function () {
    $booking = TimeBooking::query()->create([
        'purchase_order_id' => $this->order->id,
        'consultant_id' => $this->consultant->id,
        'date' => '2026-10-27',
        'duration_hours' => 8.0,
        'description' => 'Unclear description without deliverables',
        'status' => 'pending',
    ]);

    $this->actingAs($this->leadUser);

    $response = $this->post("/bookings/{$booking->id}/reject", [
        'reason' => 'Please detail the ticket numbers worked on.',
    ]);

    $response->assertRedirect();
    expect($booking->fresh()->status)->toBe('rejected')
        ->and($booking->fresh()->rejection_reason)->toBe('Please detail the ticket numbers worked on.');
});
