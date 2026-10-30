<?php

use App\Http\Controllers\DemoRoleController;
use App\Models\Consultant;
use App\Models\CostCentre;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\TimeBooking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

test('demo role switch route is disabled outside local and test environments', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => app(DemoRoleController::class)->switchRole(
        Request::create('/demo/switch-role', 'POST', ['role' => 'consultant'])
    ))->toThrow(NotFoundHttpException::class);
});

test('consultant dashboard only includes assigned orders and own booking data', function () {
    $otherLead = User::query()->create([
        'name' => 'Other Project Lead',
        'email' => 'other-lead@pegel.test',
        'password' => Hash::make('password'),
        'role' => 'project_lead',
    ]);
    $otherConsultant = Consultant::query()->create([
        'supplier_id' => $this->supplier->id,
        'name' => 'Other Consultant',
        'email' => 'other-consultant@pegel.test',
    ]);
    $otherOrder = PurchaseOrder::query()->create([
        'order_number' => '4500099999',
        'title' => 'Unrelated order',
        'supplier_id' => $this->supplier->id,
        'cost_centre_id' => $this->costCentre->id,
        'project_lead_id' => $otherLead->id,
        'ordered_budget_pd' => 20.0,
        'hourly_rate_eur' => 120.00,
        'start_date' => '2026-07-06',
        'end_date' => '2027-02-05',
        'status' => 'active',
    ]);
    $otherOrder->consultants()->attach($otherConsultant->id);

    $this->actingAs($this->consultantUser)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('orders', 1)
            ->where('orders.0.order_number', '4500012345')
            ->has('pending_approvals', 0)
            ->has('my_bookings', 0)
            ->has('my_absences', 0)
        );
});

test('purchasing sees order aggregates without consultant or timesheet details', function () {
    $purchasingUser = User::query()->create([
        'name' => 'Purchasing User',
        'email' => 'purchasing@pegel.test',
        'password' => Hash::make('password'),
        'role' => 'purchasing',
    ]);

    $this->actingAs($purchasingUser)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('orders', 1)
            ->where('orders.0.order_number', '4500012345')
            ->where('orders.0.description', null)
            ->missing('orders.0.consultants')
            ->has('pending_approvals', 0)
            ->has('my_bookings', 0)
            ->has('my_absences', 0)
        );
});

test('project lead only receives approval entries for managed orders', function () {
    $otherLead = User::query()->create([
        'name' => 'Other Project Lead',
        'email' => 'other-lead@pegel.test',
        'password' => Hash::make('password'),
        'role' => 'project_lead',
    ]);
    $otherOrder = PurchaseOrder::query()->create([
        'order_number' => '4500099999',
        'title' => 'Other lead order',
        'supplier_id' => $this->supplier->id,
        'cost_centre_id' => $this->costCentre->id,
        'project_lead_id' => $otherLead->id,
        'ordered_budget_pd' => 20.0,
        'hourly_rate_eur' => 120.00,
        'start_date' => '2026-07-06',
        'end_date' => '2027-02-05',
        'status' => 'active',
    ]);
    $otherOrder->consultants()->attach($this->consultant->id);
    $booking = TimeBooking::query()->create([
        'purchase_order_id' => $otherOrder->id,
        'consultant_id' => $this->consultant->id,
        'date' => '2026-10-27',
        'duration_hours' => 8.0,
        'description' => 'Other project work',
        'status' => 'pending',
    ]);

    $this->actingAs($this->leadUser)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders', 1)
            ->where('orders.0.order_number', '4500012345')
            ->has('pending_approvals', 0)
        );

    $this->post("/bookings/{$booking->id}/approve")->assertForbidden();
    expect($booking->fresh()->status)->toBe('pending');
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

test('JSON clients receive a created response when a valid booking is submitted', function () {
    $this->actingAs($this->consultantUser)
        ->postJson('/bookings', [
            'purchase_order_id' => $this->order->id,
            'date' => '2026-10-28',
            'duration_hours' => 8.0,
            'description' => 'Developed the portal search page',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'Booking logged successfully on order 4500012345.');
});

test('JSON clients receive a descriptive HTTP 400 for a rejected booking', function () {
    $this->order->update(['ordered_budget_pd' => 0.5]);

    $this->actingAs($this->consultantUser)
        ->postJson('/bookings', [
            'purchase_order_id' => $this->order->id,
            'date' => '2026-10-28',
            'duration_hours' => 8.0,
            'description' => 'Attempted booking over the order budget',
        ])
        ->assertBadRequest()
        ->assertJsonPath('message', 'Booking rejected: only 0.5 PD (4 h) left on order 4500012345.');

    $this->assertDatabaseCount('time_bookings', 0);
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

test('purchasing can request a forecast using a validated as of date', function () {
    $purchasingUser = User::query()->create([
        'name' => 'Purchasing User',
        'email' => 'purchasing@pegel.test',
        'password' => Hash::make('password'),
        'role' => 'purchasing',
    ]);

    $this->actingAs($purchasingUser)
        ->getJson("/purchase-orders/{$this->order->id}/forecast?as_of_date=2026-10-23")
        ->assertOk()
        ->assertJsonPath('order_number', '4500012345')
        ->assertJsonPath('as_of_date', '2026-10-23');
});

test('consultant cannot request a purchase order forecast', function () {
    $this->actingAs($this->consultantUser)
        ->getJson("/purchase-orders/{$this->order->id}/forecast")
        ->assertForbidden();
});

test('project lead cannot request a forecast for an order they do not manage', function () {
    $otherLead = User::query()->create([
        'name' => 'Other Project Lead',
        'email' => 'other-lead@pegel.test',
        'password' => Hash::make('password'),
        'role' => 'project_lead',
    ]);
    $otherOrder = PurchaseOrder::query()->create([
        'order_number' => '4500099999',
        'title' => 'Other lead order',
        'supplier_id' => $this->supplier->id,
        'cost_centre_id' => $this->costCentre->id,
        'project_lead_id' => $otherLead->id,
        'ordered_budget_pd' => 20.0,
        'hourly_rate_eur' => 120.00,
        'start_date' => '2026-07-06',
        'end_date' => '2027-02-05',
        'status' => 'active',
    ]);

    $this->actingAs($this->leadUser)
        ->getJson("/purchase-orders/{$otherOrder->id}/forecast")
        ->assertForbidden();
});

test('forecast request rejects an invalid as of date', function () {
    $purchasingUser = User::query()->create([
        'name' => 'Purchasing User',
        'email' => 'purchasing@pegel.test',
        'password' => Hash::make('password'),
        'role' => 'purchasing',
    ]);

    $this->actingAs($purchasingUser)
        ->getJson("/purchase-orders/{$this->order->id}/forecast?as_of_date=not-a-date")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('as_of_date');
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
