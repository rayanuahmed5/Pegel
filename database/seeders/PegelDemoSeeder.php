<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Consultant;
use App\Models\CostCentre;
use App\Models\PlannedAbsence;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\TimeBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PegelDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Demo Users for each role
        $consultantUser = User::query()->firstOrCreate(
            ['email' => 'consultant@pegel.test'],
            [
                'name' => 'Elena Becker',
                'password' => Hash::make('password'),
                'role' => 'consultant',
                'email_verified_at' => now(),
            ]
        );

        $leadUser = User::query()->firstOrCreate(
            ['email' => 'lead@pegel.test'],
            [
                'name' => 'Markus Weber',
                'password' => Hash::make('password'),
                'role' => 'project_lead',
                'email_verified_at' => now(),
            ]
        );

        $purchasingUser = User::query()->firstOrCreate(
            ['email' => 'purchasing@pegel.test'],
            [
                'name' => 'Sabine Richter',
                'password' => Hash::make('password'),
                'role' => 'purchasing',
                'email_verified_at' => now(),
            ]
        );

        $financeUser = User::query()->firstOrCreate(
            ['email' => 'finance@pegel.test'],
            [
                'name' => 'Thomas Müller',
                'password' => Hash::make('password'),
                'role' => 'finance',
                'email_verified_at' => now(),
            ]
        );

        $superadminUser = User::query()->firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@pegel.test',
                'password' => Hash::make('password'),
                'role' => 'superadmin',
                'email_verified_at' => now(),
            ]
        );

        $superadminTeam = Team::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin Team',
                'is_personal' => true,
            ]
        );
        $superadminTeam->members()->syncWithoutDetaching([
            $superadminUser->id => ['role' => TeamRole::Owner->value],
        ]);
        $superadminUser->switchTeam($superadminTeam);

        // 2. Create Supplier
        $supplier = Supplier::query()->firstOrCreate(
            ['name' => 'Nova Consulting GmbH'],
            [
                'contact_email' => 'contracts@novaconsulting.de',
                'city' => 'Frankfurt am Main',
            ]
        );

        // 3. Create Consultants
        $elena = Consultant::query()->firstOrCreate(
            ['email' => 'consultant@pegel.test'],
            [
                'user_id' => $consultantUser->id,
                'supplier_id' => $supplier->id,
                'name' => 'Elena Becker',
                'daily_rate_eur' => 960.00,
            ]
        );

        $david = Consultant::query()->firstOrCreate(
            ['email' => 'david.koch@novaconsulting.de'],
            [
                'supplier_id' => $supplier->id,
                'name' => 'David Koch',
                'daily_rate_eur' => 960.00,
            ]
        );

        $jonas = Consultant::query()->firstOrCreate(
            ['email' => 'jonas.vogel@novaconsulting.de'],
            [
                'supplier_id' => $supplier->id,
                'name' => 'Jonas Vogel',
                'daily_rate_eur' => 880.00,
            ]
        );

        // 4. Create Cost Centre
        $costCentre = CostCentre::query()->firstOrCreate(
            ['code' => 'CC-4100'],
            [
                'name' => 'IT Customer Systems & Digital Channels',
                'department' => 'Corporate IT',
            ]
        );

        // 5. Create Purchase Orders
        // PO 1: "Customer portal migration", 140 PD (Challenge main scenario)
        $portalOrder = PurchaseOrder::query()->firstOrCreate(
            ['order_number' => '4500012345'],
            [
                'title' => 'Customer portal migration',
                'description' => 'Architecture refactoring, cloud microservices and React web portal migration.',
                'supplier_id' => $supplier->id,
                'cost_centre_id' => $costCentre->id,
                'project_lead_id' => $leadUser->id,
                'ordered_budget_pd' => 140.0,
                'hourly_rate_eur' => 120.00,
                'start_date' => '2026-07-06',
                'end_date' => '2027-02-05', // Week 5, 2027
                'status' => 'active',
            ]
        );

        $portalOrder->consultants()->syncWithoutDetaching([$elena->id, $david->id]);

        // PO 2: "Legacy data reconciliation", 40 PD
        $reconciliationOrder = PurchaseOrder::query()->firstOrCreate(
            ['order_number' => '4500012346'],
            [
                'title' => 'Legacy data reconciliation',
                'description' => 'Data cleansing, customer records audit and mapping for ERP cutover.',
                'supplier_id' => $supplier->id,
                'cost_centre_id' => $costCentre->id,
                'project_lead_id' => $leadUser->id,
                'ordered_budget_pd' => 40.0,
                'hourly_rate_eur' => 110.00,
                'start_date' => '2026-09-01',
                'end_date' => '2026-12-18',
                'status' => 'active',
            ]
        );

        $reconciliationOrder->consultants()->syncWithoutDetaching([$jonas->id]);

        // 6. Planned Absences
        PlannedAbsence::query()->firstOrCreate([
            'consultant_id' => $elena->id,
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-16',
        ], [
            'type' => 'vacation',
            'description' => 'Autumn Vacation (Elena)',
        ]);

        PlannedAbsence::query()->firstOrCreate([
            'consultant_id' => $david->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
        ], [
            'type' => 'vacation',
            'description' => 'Autumn Vacation (David)',
        ]);

        PlannedAbsence::query()->firstOrCreate([
            'consultant_id' => $elena->id,
            'start_date' => '2026-12-24',
            'end_date' => '2027-01-01',
        ], [
            'type' => 'shutdown',
            'description' => 'Year-End Client Plant Shutdown',
        ]);

        PlannedAbsence::query()->firstOrCreate([
            'consultant_id' => $david->id,
            'start_date' => '2026-12-24',
            'end_date' => '2027-01-01',
        ], [
            'type' => 'shutdown',
            'description' => 'Year-End Client Plant Shutdown',
        ]);

        // 7. Seed Historical Bookings matching the Challenge:
        // "Two external consultants have worked on a migration project since early July.
        // The order 'Customer portal migration' covers 140 person-days. 84 are used.
        // On average 6 person-days are used per week."
        // 84 PD = 672 hours = 14 weeks * 6 PD/week (Elena: Mon, Tue, Wed; David: Wed, Thu, Fri)
        if ($portalOrder->timeBookings()->count() === 0) {
            $startDate = Carbon::parse('2026-07-06'); // Monday of Week 28

            for ($week = 0; $week < 14; $week++) {
                $weekStart = (clone $startDate)->addWeeks($week);

                // Elena: Monday, Tuesday, Wednesday (3 days = 24h = 3 PD)
                foreach ([0, 1, 2] as $dayOffset) {
                    $day = (clone $weekStart)->addDays($dayOffset);
                    TimeBooking::query()->create([
                        'purchase_order_id' => $portalOrder->id,
                        'consultant_id' => $elena->id,
                        'date' => $day->toDateString(),
                        'start_time' => '08:30:00',
                        'end_time' => '17:00:00',
                        'duration_hours' => 8.0,
                        'description' => 'Customer portal frontend components and API orchestration',
                        'accounting_object' => 'PSP: P-2026-0815.03',
                        'activity' => 'Portal UI Development',
                        'status' => 'approved',
                        'approved_at' => (clone $day)->addDays(4)->setTime(16, 0),
                        'approved_by' => $leadUser->id,
                    ]);
                }

                // David: Wednesday, Thursday, Friday (3 days = 24h = 3 PD)
                foreach ([2, 3, 4] as $dayOffset) {
                    $day = (clone $weekStart)->addDays($dayOffset);
                    TimeBooking::query()->create([
                        'purchase_order_id' => $portalOrder->id,
                        'consultant_id' => $david->id,
                        'date' => $day->toDateString(),
                        'start_time' => '09:00:00',
                        'end_time' => '17:30:00',
                        'duration_hours' => 8.0,
                        'description' => 'Microservice endpoints, security token gateway and automated tests',
                        'accounting_object' => 'PSP: P-2026-0815.03',
                        'activity' => 'Backend Services Architecture',
                        'status' => 'approved',
                        'approved_at' => (clone $day)->addDays(3)->setTime(16, 0),
                        'approved_by' => $leadUser->id,
                    ]);
                }
            }
        }

        // Seed reconciliation historical bookings (14 PD = 112 hours)
        if ($reconciliationOrder->timeBookings()->count() === 0) {
            $recStartDate = Carbon::parse('2026-09-07');
            for ($i = 0; $i < 14; $i++) {
                $day = (clone $recStartDate)->addDays($i < 7 ? $i : $i + 2);
                if ($day->isWeekend()) {
                    continue;
                }
                TimeBooking::query()->create([
                    'purchase_order_id' => $reconciliationOrder->id,
                    'consultant_id' => $jonas->id,
                    'date' => $day->toDateString(),
                    'start_time' => '08:30:00',
                    'end_time' => '17:00:00',
                    'duration_hours' => 8.0,
                    'description' => 'Legacy CRM database audit and mapping transformation',
                    'accounting_object' => 'CC-4100',
                    'activity' => 'Data Cleansing',
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => $leadUser->id,
                ]);
            }
        }
    }
}
