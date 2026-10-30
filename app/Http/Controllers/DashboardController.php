<?php

namespace App\Http\Controllers;

use App\Domain\Services\ForecastService;
use App\Models\PurchaseOrder;
use App\Models\TeamInvitation;
use App\Models\TimeBooking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected ForecastService $forecastService
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $consultant = $user->consultant;
        $canViewIndividualBookings = in_array($user->role, ['project_lead', 'superadmin'], true);
        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $ordersQuery = PurchaseOrder::query()
            ->with(['supplier', 'costCentre'])
            ->when($canViewIndividualBookings, fn ($query) => $query->with('consultants'));

        if ($user->isConsultant() && $consultant) {
            $ordersQuery->whereHas('consultants', fn ($query) => $query->whereKey($consultant->id));
        } elseif ($user->isProjectLead()) {
            $ordersQuery->where('project_lead_id', $user->id);
        } elseif (! in_array($user->role, ['purchasing', 'finance', 'superadmin'], true)) {
            $ordersQuery->whereRaw('1 = 0');
        }

        $orders = $ordersQuery
            ->get()
            ->map(function (PurchaseOrder $order) use ($canViewIndividualBookings) {
                $forecast = $this->forecastService->calculateForecast($order);

                $orderData = [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'title' => $order->title,
                    'description' => $canViewIndividualBookings ? $order->description : null,
                    'supplier_name' => $order->supplier->name,
                    'cost_centre_code' => $order->costCentre->code,
                    'cost_centre_name' => $order->costCentre->name,
                    'ordered_budget_pd' => $order->ordered_budget_pd,
                    'hourly_rate_eur' => $order->hourly_rate_eur,
                    'total_budget_eur' => round($order->ordered_budget_pd * 8.0 * $order->hourly_rate_eur, 2),
                    'used_pd' => $order->usedPD(),
                    'used_hours' => $order->usedHours(),
                    'used_amount_eur' => round($order->usedHours() * $order->hourly_rate_eur, 2),
                    'remaining_pd' => $order->remainingBudgetPD(),
                    'remaining_hours' => $order->remainingBudgetHours(),
                    'usage_percent' => $order->budgetUsagePercent(),
                    'start_date' => $order->start_date,
                    'end_date' => $order->end_date,
                    'status' => $order->status,
                    'forecast' => $forecast,
                ];

                if ($canViewIndividualBookings) {
                    $orderData['consultants'] = $order->consultants->map(fn ($consultant) => [
                        'id' => $consultant->id,
                        'name' => $consultant->name,
                        'email' => $consultant->email,
                    ]);
                }

                return $orderData;
            });

        // Consultant view props
        $assignedOrders = [];
        $myBookings = [];
        $myAbsences = [];

        if ($user->isConsultant() && $consultant) {
            $assignedOrders = $consultant->purchaseOrders()
                ->get()
                ->map(fn (PurchaseOrder $po) => [
                    'id' => $po->id,
                    'order_number' => $po->order_number,
                    'title' => $po->title,
                    'remaining_pd' => $po->remainingBudgetPD(),
                ]);

            $myBookings = $consultant->timeBookings()
                ->with('purchaseOrder')
                ->latest('date')
                ->take(30)
                ->get()
                ->map(fn (TimeBooking $b) => [
                    'id' => $b->id,
                    'order_number' => $b->purchaseOrder->order_number,
                    'order_title' => $b->purchaseOrder->title,
                    'date' => $b->date,
                    'duration_hours' => $b->duration_hours,
                    'description' => $b->description,
                    'activity' => $b->activity,
                    'status' => $b->status,
                    'rejection_reason' => $b->rejection_reason,
                ]);

            $myAbsences = $consultant->plannedAbsences()
                ->orderBy('start_date')
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'type' => $a->type,
                    'start_date' => $a->start_date,
                    'end_date' => $a->end_date,
                    'description' => $a->description,
                ]);
        }

        $pendingApprovalsQuery = TimeBooking::query()
            ->with(['purchaseOrder', 'consultant'])
            ->where('status', 'pending')
            ->when(
                $user->isProjectLead(),
                fn ($query) => $query->whereHas(
                    'purchaseOrder',
                    fn ($purchaseOrders) => $purchaseOrders->where('project_lead_id', $user->id)
                )
            );

        if (! $user->isProjectLead() && ! $user->isSuperAdmin()) {
            $pendingApprovalsQuery->whereRaw('1 = 0');
        }

        $pendingApprovals = $pendingApprovalsQuery
            ->latest('date')
            ->get()
            ->map(fn (TimeBooking $b) => [
                'id' => $b->id,
                'order_number' => $b->purchaseOrder->order_number,
                'consultant_name' => $b->consultant->name,
                'date' => $b->date,
                'duration_hours' => $b->duration_hours,
                'description' => $b->description,
                'activity' => $b->activity,
                'accounting_object' => $b->accounting_object,
            ]);

        // Purchasing alerts
        $reorderAlerts = in_array($user->role, ['project_lead', 'purchasing', 'superadmin'], true)
            ? $orders->filter(function ($order) {
                return in_array($order['forecast']['traffic_light'], ['yellow', 'red']);
            })->values()
            : collect();

        // High-level KPIs
        $totalOrderedPD = $orders->sum('ordered_budget_pd');
        $totalUsedPD = $orders->sum('used_pd');
        $totalRemainingPD = $orders->sum('remaining_pd');
        $totalSpentEur = $orders->sum('used_amount_eur');
        $totalBudgetEur = $orders->sum('total_budget_eur');

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'current_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'kpis' => [
                'total_ordered_pd' => $totalOrderedPD,
                'total_used_pd' => $totalUsedPD,
                'total_remaining_pd' => $totalRemainingPD,
                'total_budget_eur' => $totalBudgetEur,
                'total_spent_eur' => $totalSpentEur,
                'active_orders_count' => $orders->count(),
                'pending_approvals_count' => $pendingApprovals->count(),
                'reorder_alerts_count' => $reorderAlerts->count(),
            ],
            'orders' => $orders,
            'assigned_orders' => $assignedOrders,
            'my_bookings' => $myBookings,
            'my_absences' => $myAbsences,
            'pending_approvals' => $pendingApprovals,
            'reorder_alerts' => $reorderAlerts,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }
}
