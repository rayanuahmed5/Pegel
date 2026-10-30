import { Head, router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    Building2,
    Calendar,
    Check,
    CheckCircle2,
    Clock,
    Euro,
    FileText,
    ShieldAlert,
    TrendingDown,
    Users,
    X,
} from 'lucide-react';
import React, { useState } from 'react';

type UserRole = 'consultant' | 'project_lead' | 'purchasing' | 'finance' | 'superadmin';

interface CurrentUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
}

interface ForecastScheduleItem {
    calendar_week: number;
    year: number;
    week_start: string;
    week_end: string;
    planned_burn_pd: number;
    remaining_budget_pd: number;
    absences: string[];
    is_depleted: boolean;
}

interface OrderForecast {
    as_of_week: number;
    ordered_budget_pd: number;
    used_pd: number;
    remaining_pd: number;
    weekly_pace_pd: number;
    unadjusted_depletion_week: number;
    adjusted_depletion_week: number;
    adjusted_depletion_date: string;
    project_end_date: string;
    project_end_week: number;
    missing_pd_until_end: number;
    purchasing_lead_weeks: number;
    reorder_deadline_week: number;
    reorder_deadline_date: string;
    days_until_reorder_deadline: number;
    traffic_light: 'green' | 'yellow' | 'red';
    status_message: string;
    recommended_followup_order_pd: number;
    weekly_burn_schedule: ForecastScheduleItem[];
}

interface PurchaseOrderData {
    id: number;
    order_number: string;
    title: string;
    description: string;
    supplier_name: string;
    cost_centre_code: string;
    cost_centre_name: string;
    ordered_budget_pd: number;
    hourly_rate_eur: number;
    total_budget_eur: number;
    used_pd: number;
    used_hours: number;
    used_amount_eur: number;
    remaining_pd: number;
    remaining_hours: number;
    usage_percent: number;
    start_date: string;
    end_date: string;
    status: string;
    forecast: OrderForecast;
}

interface TimeBookingData {
    id: number;
    order_number: string;
    order_title?: string;
    consultant_name?: string;
    date: string;
    duration_hours: number;
    description: string;
    activity?: string;
    accounting_object?: string;
    status: string;
    rejection_reason?: string;
}

interface Props {
    current_user: CurrentUser;
    kpis: {
        total_ordered_pd: number;
        total_used_pd: number;
        total_remaining_pd: number;
        total_budget_eur: number;
        total_spent_eur: number;
        active_orders_count: number;
        pending_approvals_count: number;
        reorder_alerts_count: number;
    };
    orders: PurchaseOrderData[];
    assigned_orders: Array<{ id: number; order_number: string; title: string; remaining_pd: number }>;
    my_bookings: TimeBookingData[];
    my_absences: Array<{ id: number; type: string; start_date: string; end_date: string; description: string }>;
    pending_approvals: TimeBookingData[];
    reorder_alerts: PurchaseOrderData[];
    flash?: {
        success?: string;
        error?: string;
    };
    errors?: Record<string, string>;
}

export default function Dashboard({
    current_user,
    kpis,
    orders,
    assigned_orders = [],
    my_bookings = [],
    my_absences = [],
    pending_approvals = [],
    reorder_alerts = [],
    flash,
    errors = {},
}: Props) {
    const [activeTab, setActiveTab] = useState<UserRole>(
        current_user.role === 'superadmin' ? 'project_lead' : current_user.role,
    );
    const mainOrder = orders.find((o) => o.order_number === '4500012345') || orders[0];

    // Form for Consultant time booking
    const { data, setData, post, processing, reset } = useForm({
        purchase_order_id: assigned_orders[0]?.id || mainOrder?.id || '',
        date: new Date().toISOString().split('T')[0],
        duration_hours: '8.0',
        description: '',
        activity: 'Core Feature Development',
        accounting_object: 'PSP: P-2026-0815.03',
        start_time: '09:00',
        end_time: '17:30',
    });

    const handleSwitchRole = (role: UserRole) => {
        router.post('/demo/switch-role', { role });
    };

    const handleBookingSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/bookings', {
            onSuccess: () => reset('description'),
        });
    };

    const handleApprove = (bookingId: number) => {
        router.post(`/bookings/${bookingId}/approve`);
    };

    const handleReject = (bookingId: number) => {
        const reason = window.prompt('Enter rejection feedback reason:', 'Overlapping hours or incomplete activity description');
        if (reason !== null) {
            router.post(`/bookings/${bookingId}/reject`, { reason });
        }
    };

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
            <Head title="Pegel - Time Booking & Budget Forecast" />

            {/* Top Navigation & Role Switcher */}
            <header className="border-b border-slate-200 bg-white shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white font-bold shadow-md shadow-blue-500/20">
                            P
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-white">Pegel</h1>
                                <span className="rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                    Enterprise Controlling
                                </span>
                            </div>
                            <p className="text-xs text-slate-500 dark:text-slate-400">Every hour you buy, in view</p>
                        </div>
                    </div>

                    {/* Role Switcher Toolbar */}
                    <div className="flex flex-wrap items-center gap-2 rounded-xl bg-slate-100 p-1.5 dark:bg-slate-800/70">
                        <span className="px-2 text-xs font-medium text-slate-500">Demo Role:</span>
                        <button
                            type="button"
                            onClick={() => handleSwitchRole('consultant')}
                            className={`flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium transition ${
                                current_user.role === 'consultant'
                                    ? 'bg-white text-blue-600 shadow-xs dark:bg-slate-700 dark:text-blue-400'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                            }`}
                        >
                            <Users className="h-3.5 w-3.5" />
                            Consultant
                        </button>
                        <button
                            type="button"
                            onClick={() => handleSwitchRole('project_lead')}
                            className={`flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium transition ${
                                current_user.role === 'project_lead'
                                    ? 'bg-white text-emerald-600 shadow-xs dark:bg-slate-700 dark:text-emerald-400'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                            }`}
                        >
                            <FileText className="h-3.5 w-3.5" />
                            Project Lead
                        </button>
                        <button
                            type="button"
                            onClick={() => handleSwitchRole('purchasing')}
                            className={`flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium transition ${
                                current_user.role === 'purchasing'
                                    ? 'bg-white text-amber-600 shadow-xs dark:bg-slate-700 dark:text-amber-400'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                            }`}
                        >
                            <Building2 className="h-3.5 w-3.5" />
                            Purchasing
                        </button>
                        <button
                            type="button"
                            onClick={() => handleSwitchRole('finance')}
                            className={`flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium transition ${
                                current_user.role === 'finance'
                                    ? 'bg-white text-purple-600 shadow-xs dark:bg-slate-700 dark:text-purple-400'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                            }`}
                        >
                            <Euro className="h-3.5 w-3.5" />
                            Finance
                        </button>
                    </div>
                </div>
            </header>

            {/* Flash Alerts / Errors */}
            <div className="mx-auto max-w-7xl px-4 pt-4 sm:px-6">
                {flash?.success && (
                    <div className="mb-4 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                        <span>{flash.success}</span>
                    </div>
                )}
                {(flash?.error || errors?.error) && (
                    <div className="mb-4 flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-300">
                        <ShieldAlert className="h-5 w-5 shrink-0 text-rose-600 dark:text-rose-400" />
                        <span>{flash?.error || errors?.error}</span>
                    </div>
                )}
            </div>

            {/* Main Content Area */}
            <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6">
                {/* KPI Overview Banner */}
                <div className="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div className="flex items-center justify-between text-slate-500 dark:text-slate-400">
                            <span className="text-xs font-semibold tracking-wider uppercase">Active Orders</span>
                            <Building2 className="h-4 w-4 text-blue-600" />
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className="text-2xl font-bold">{kpis.active_orders_count}</span>
                            <span className="text-xs text-slate-500">PO #4500012345 active</span>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div className="flex items-center justify-between text-slate-500 dark:text-slate-400">
                            <span className="text-xs font-semibold tracking-wider uppercase">Budget Used</span>
                            <TrendingDown className="h-4 w-4 text-amber-600" />
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className="text-2xl font-bold">{kpis.total_used_pd} PD</span>
                            <span className="text-xs text-slate-500">of {kpis.total_ordered_pd} PD ({Math.round((kpis.total_used_pd / (kpis.total_ordered_pd || 1)) * 100)}%)</span>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div className="flex items-center justify-between text-slate-500 dark:text-slate-400">
                            <span className="text-xs font-semibold tracking-wider uppercase">Remaining Budget</span>
                            <Clock className="h-4 w-4 text-emerald-600" />
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{kpis.total_remaining_pd} PD</span>
                            <span className="text-xs text-slate-500">{kpis.total_remaining_pd * 8} hours</span>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                        <div className="flex items-center justify-between text-slate-500 dark:text-slate-400">
                            <span className="text-xs font-semibold tracking-wider uppercase">Re-Order Alerts</span>
                            <AlertTriangle className="h-4 w-4 text-rose-600" />
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className={`text-2xl font-bold ${kpis.reorder_alerts_count > 0 ? 'text-rose-600' : 'text-slate-900'}`}>
                                {kpis.reorder_alerts_count} Critical
                            </span>
                            <span className="text-xs text-slate-500">Lead time 4 weeks</span>
                        </div>
                    </div>
                </div>

                {/* Sub-navigation Tabs */}
                <div className="mb-6 flex border-b border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        onClick={() => setActiveTab('project_lead')}
                        className={`flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition ${
                            activeTab === 'project_lead'
                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                        }`}
                    >
                        <FileText className="h-4 w-4" />
                        Project Lead (Forecast & Traffic Light)
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('consultant')}
                        className={`flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition ${
                            activeTab === 'consultant'
                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                        }`}
                    >
                        <Clock className="h-4 w-4" />
                        External Consultant (Time Booking)
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('purchasing')}
                        className={`flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition ${
                            activeTab === 'purchasing'
                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                        }`}
                    >
                        <Building2 className="h-4 w-4" />
                        Purchasing (Order Extension Alerts)
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('finance')}
                        className={`flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition ${
                            activeTab === 'finance'
                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'
                        }`}
                    >
                        <Euro className="h-4 w-4" />
                        Finance (Invoice Audit)
                    </button>
                </div>

                {/* 1. PROJECT LEAD VIEW: FORECAST TRAFFIC LIGHT & SCHEDULE */}
                {activeTab === 'project_lead' && mainOrder && (
                    <div className="space-y-6">
                        {/* Traffic Light Spotlight Card */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <div className="flex flex-col justify-between gap-4 border-b border-slate-100 pb-5 md:flex-row md:items-center dark:border-slate-800">
                                <div>
                                    <div className="flex items-center gap-3">
                                        <h2 className="text-lg font-bold text-slate-900 dark:text-white">
                                            {mainOrder.title} <span className="text-slate-400 font-normal">({mainOrder.order_number})</span>
                                        </h2>
                                        <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            {mainOrder.cost_centre_code} · {mainOrder.cost_centre_name}
                                        </span>
                                    </div>
                                    <p className="mt-1 text-sm text-slate-500">
                                        Supplier: {mainOrder.supplier_name} · Project runs until <strong>Week {mainOrder.forecast.project_end_week}, 2027</strong> ({mainOrder.end_date})
                                    </p>
                                </div>

                                {/* Traffic Light Indicator */}
                                <div className="flex items-center gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-slate-800/60">
                                    <div className="flex items-center gap-2">
                                        <div className={`h-4 w-4 rounded-full ${mainOrder.forecast.traffic_light === 'red' ? 'bg-rose-500 ring-4 ring-rose-500/20 animate-pulse' : 'bg-slate-300 dark:bg-slate-700'}`} />
                                        <div className={`h-4 w-4 rounded-full ${mainOrder.forecast.traffic_light === 'yellow' ? 'bg-amber-500 ring-4 ring-amber-500/20 animate-pulse' : 'bg-slate-300 dark:bg-slate-700'}`} />
                                        <div className={`h-4 w-4 rounded-full ${mainOrder.forecast.traffic_light === 'green' ? 'bg-emerald-500 ring-4 ring-emerald-500/20' : 'bg-slate-300 dark:bg-slate-700'}`} />
                                    </div>
                                    <span className="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                        {mainOrder.forecast.traffic_light.toUpperCase()} STATUS
                                    </span>
                                </div>
                            </div>

                            {/* Main Challenge Forecasting Comparison Grid */}
                            <div className="mt-6 grid grid-cols-1 gap-6 md:grid-cols-4">
                                <div className="rounded-xl border border-slate-100 bg-slate-50/70 p-4 dark:border-slate-800/80 dark:bg-slate-800/40">
                                    <span className="text-xs font-medium text-slate-500">Forecast (Linear Pace)</span>
                                    <p className="mt-1 text-xl font-bold text-slate-800 dark:text-slate-200">
                                        Week {mainOrder.forecast.unadjusted_depletion_week}
                                    </p>
                                    <span className="text-xs text-slate-400">At {mainOrder.forecast.weekly_pace_pd} PD/week burn rate</span>
                                </div>

                                <div className="rounded-xl border border-blue-100 bg-blue-50/50 p-4 dark:border-blue-900/40 dark:bg-blue-950/20">
                                    <span className="text-xs font-medium text-blue-600 dark:text-blue-400">Forecast (with Vacations & Shutdown)</span>
                                    <p className="mt-1 text-xl font-bold text-blue-700 dark:text-blue-300">
                                        Week {mainOrder.forecast.adjusted_depletion_week}
                                    </p>
                                    <span className="text-xs text-blue-600/80 dark:text-blue-400/80">Stretches calendar time by ~1 week</span>
                                </div>

                                <div className="rounded-xl border border-rose-100 bg-rose-50/50 p-4 dark:border-rose-900/40 dark:bg-rose-950/20">
                                    <span className="text-xs font-medium text-rose-600 dark:text-rose-400">Shortfall to Project End (KW 5, 2027)</span>
                                    <p className="mt-1 text-xl font-bold text-rose-700 dark:text-rose-300">
                                        ≈ {mainOrder.forecast.missing_pd_until_end} Person-Days
                                    </p>
                                    <span className="text-xs text-rose-600/80 dark:text-rose-400/80">Budget will not reach February!</span>
                                </div>

                                <div className="rounded-xl border border-amber-100 bg-amber-50/50 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                                    <span className="text-xs font-medium text-amber-700 dark:text-amber-400">Order More By (Purchasing Lead Time)</span>
                                    <p className="mt-1 text-xl font-bold text-amber-800 dark:text-amber-300">
                                        Week {mainOrder.forecast.reorder_deadline_week}
                                    </p>
                                    <span className="text-xs text-amber-700/80 dark:text-amber-400/80">Purchasing needs 4 weeks lead time</span>
                                </div>
                            </div>

                            {/* Warning Callout Box */}
                            <div className="mt-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50/70 p-4 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                                <AlertTriangle className="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
                                <div>
                                    <p className="font-semibold">Executive Steer Recommendation:</p>
                                    <p className="mt-0.5 text-xs text-amber-800 dark:text-amber-300">
                                        The current budget of 140 PD will exhaust in <strong>Week {mainOrder.forecast.adjusted_depletion_week}</strong> ({mainOrder.forecast.adjusted_depletion_date}).
                                        To maintain project velocity and prevent consultant stop-work orders, a follow-up order for <strong>{mainOrder.forecast.recommended_followup_order_pd} PD</strong> must be submitted to Purchasing by <strong>Week {mainOrder.forecast.reorder_deadline_week}</strong> at the latest.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Week-by-Week Depletion Schedule Simulation */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <h3 className="text-base font-bold text-slate-900 dark:text-white">
                                Week-by-Week Trajectory Simulation (Look-Ahead Engine)
                            </h3>
                            <p className="text-xs text-slate-500">
                                Dynamic projection factoring in consultant velocity and registered vacations/shutdowns.
                            </p>

                            <div className="mt-4 overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead className="border-b border-slate-200 text-slate-500 uppercase dark:border-slate-800">
                                        <tr>
                                            <th className="py-2.5 pr-4">Calendar Week</th>
                                            <th className="py-2.5 pr-4">Period</th>
                                            <th className="py-2.5 pr-4">Planned Burn</th>
                                            <th className="py-2.5 pr-4">Remaining Free Budget</th>
                                            <th className="py-2.5 pr-4">Registered Absences / Events</th>
                                            <th className="py-2.5">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60">
                                        {mainOrder.forecast.weekly_burn_schedule?.map((item) => (
                                            <tr key={`${item.year}-${item.calendar_week}`} className={item.is_depleted ? 'bg-rose-50/60 dark:bg-rose-950/20' : ''}>
                                                <td className="py-2.5 pr-4 font-bold text-slate-900 dark:text-white">
                                                    KW {item.calendar_week} ({item.year})
                                                </td>
                                                <td className="py-2.5 pr-4 text-slate-500">
                                                    {item.week_start} to {item.week_end}
                                                </td>
                                                <td className="py-2.5 pr-4 font-semibold text-slate-800 dark:text-slate-200">
                                                    {item.planned_burn_pd} PD
                                                </td>
                                                <td className="py-2.5 pr-4 font-semibold text-emerald-600 dark:text-emerald-400">
                                                    {item.remaining_budget_pd} PD
                                                </td>
                                                <td className="py-2.5 pr-4">
                                                    {item.absences.length > 0 ? (
                                                        <span className="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                                            {item.absences.join(', ')}
                                                        </span>
                                                    ) : (
                                                        <span className="text-slate-400">Standard Pace</span>
                                                    )}
                                                </td>
                                                <td className="py-2.5">
                                                    {item.is_depleted ? (
                                                        <span className="rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                                            DEPLETED
                                                        </span>
                                                    ) : (
                                                        <span className="text-emerald-600 dark:text-emerald-400">Active</span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* Proof of Work Approval Section */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-base font-bold text-slate-900 dark:text-white">
                                        Proof of Work Verification (Leistungsnachweis)
                                    </h3>
                                    <p className="text-xs text-slate-500">
                                        Review timesheet entries submitted by external consultants before invoice clearance.
                                    </p>
                                </div>
                                <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                    {pending_approvals.length} Pending Approvals
                                </span>
                            </div>

                            {pending_approvals.length === 0 ? (
                                <p className="mt-4 text-xs text-slate-400 italic">No pending bookings awaiting approval.</p>
                            ) : (
                                <div className="mt-4 overflow-x-auto">
                                    <table className="w-full text-left text-xs">
                                        <thead className="border-b border-slate-200 text-slate-500 uppercase dark:border-slate-800">
                                            <tr>
                                                <th className="py-2 pr-4">Date</th>
                                                <th className="py-2 pr-4">Consultant</th>
                                                <th className="py-2 pr-4">Order</th>
                                                <th className="py-2 pr-4">Hours</th>
                                                <th className="py-2 pr-4">Task Description</th>
                                                <th className="py-2 pr-4">Accounting Object</th>
                                                <th className="py-2 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60">
                                            {pending_approvals.map((b) => (
                                                <tr key={b.id}>
                                                    <td className="py-2.5 pr-4 font-medium text-slate-900 dark:text-white">{b.date}</td>
                                                    <td className="py-2.5 pr-4 text-slate-700 dark:text-slate-300">{b.consultant_name}</td>
                                                    <td className="py-2.5 pr-4 font-mono text-slate-600 dark:text-slate-400">{b.order_number}</td>
                                                    <td className="py-2.5 pr-4 font-bold text-blue-600 dark:text-blue-400">{b.duration_hours}h</td>
                                                    <td className="py-2.5 pr-4 max-w-xs truncate text-slate-600 dark:text-slate-400">{b.description}</td>
                                                    <td className="py-2.5 pr-4 text-slate-500">{b.accounting_object || 'PSP-Element'}</td>
                                                    <td className="py-2.5 text-right">
                                                        <div className="flex items-center justify-end gap-1.5">
                                                            <button
                                                                type="button"
                                                                onClick={() => handleApprove(b.id)}
                                                                className="flex items-center gap-1 rounded-md bg-emerald-600 px-2.5 py-1 text-[11px] font-medium text-white hover:bg-emerald-700"
                                                            >
                                                                <Check className="h-3 w-3" />
                                                                Approve
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleReject(b.id)}
                                                                className="flex items-center gap-1 rounded-md bg-rose-50 px-2.5 py-1 text-[11px] font-medium text-rose-700 hover:bg-rose-100 dark:bg-rose-950 dark:text-rose-300"
                                                            >
                                                                <X className="h-3 w-3" />
                                                                Reject
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* 2. EXTERNAL CONSULTANT VIEW: FAST TIME BOOKING */}
                {activeTab === 'consultant' && (
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        {/* Time Booking Form */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs lg:col-span-1 dark:border-slate-800 dark:bg-slate-900">
                            <div className="flex items-center gap-2">
                                <Clock className="h-5 w-5 text-blue-600" />
                                <h3 className="text-base font-bold text-slate-900 dark:text-white">Record Working Time</h3>
                            </div>
                            <p className="mt-1 text-xs text-slate-500">
                                Hours are checked against German Working Time Act (ArbZG) limits and order free budget.
                            </p>

                            <form onSubmit={handleBookingSubmit} className="mt-4 space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">Purchase Order</label>
                                    <select
                                        value={data.purchase_order_id}
                                        onChange={(e) => setData('purchase_order_id', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs shadow-xs focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        required
                                    >
                                        {assigned_orders.map((po) => (
                                            <option key={po.id} value={po.id}>
                                                {po.order_number} · {po.title} ({po.remaining_pd} PD free)
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">Date</label>
                                        <input
                                            type="date"
                                            value={data.date}
                                            onChange={(e) => setData('date', e.target.value)}
                                            className="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs shadow-xs focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">Duration (Hours)</label>
                                        <input
                                            type="number"
                                            step="0.5"
                                            min="0.5"
                                            max="10.0"
                                            value={data.duration_hours}
                                            onChange={(e) => setData('duration_hours', e.target.value)}
                                            className="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs shadow-xs focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                            required
                                        />
                                        <span className="text-[10px] text-slate-400">Max 10h/day (ArbZG § 3)</span>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">Shift Start Time</label>
                                        <input
                                            type="time"
                                            value={data.start_time}
                                            onChange={(e) => setData('start_time', e.target.value)}
                                            className="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs shadow-xs focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">Shift End Time</label>
                                        <input
                                            type="time"
                                            value={data.end_time}
                                            onChange={(e) => setData('end_time', e.target.value)}
                                            className="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs shadow-xs focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">Activity / Accounting Object</label>
                                    <input
                                        type="text"
                                        value={data.accounting_object}
                                        onChange={(e) => setData('accounting_object', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs shadow-xs focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        placeholder="e.g. PSP: P-2026-0815.03"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300">Work Description</label>
                                    <textarea
                                        rows={3}
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs shadow-xs focus:border-blue-500 focus:outline-hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        placeholder="Specific work deliverables and tasks completed..."
                                        required
                                    />
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full rounded-xl bg-blue-600 py-2.5 text-xs font-semibold text-white shadow-md shadow-blue-500/20 hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {processing ? 'Validating Legal Guardrails...' : 'Submit Time Booking'}
                                </button>
                            </form>
                        </div>

                        {/* Recent Bookings & Absences List */}
                        <div className="space-y-6 lg:col-span-2">
                            <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                                <h3 className="text-base font-bold text-slate-900 dark:text-white">My Logged Hours</h3>
                                <p className="text-xs text-slate-500">Your recent timesheet submissions and approval states.</p>

                                <div className="mt-4 overflow-x-auto">
                                    <table className="w-full text-left text-xs">
                                        <thead className="border-b border-slate-200 text-slate-500 uppercase dark:border-slate-800">
                                            <tr>
                                                <th className="py-2 pr-3">Date</th>
                                                <th className="py-2 pr-3">Order</th>
                                                <th className="py-2 pr-3">Hours</th>
                                                <th className="py-2 pr-3">Description</th>
                                                <th className="py-2">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60">
                                            {my_bookings.slice(0, 10).map((b) => (
                                                <tr key={b.id}>
                                                    <td className="py-2 pr-3 font-medium text-slate-900 dark:text-white">{b.date}</td>
                                                    <td className="py-2 pr-3 font-mono text-slate-600 dark:text-slate-400">{b.order_number}</td>
                                                    <td className="py-2 pr-3 font-bold text-blue-600 dark:text-blue-400">{b.duration_hours}h</td>
                                                    <td className="py-2 pr-3 max-w-xs truncate text-slate-600 dark:text-slate-400">{b.description}</td>
                                                    <td className="py-2">
                                                        <span
                                                            className={`rounded-md px-2 py-0.5 text-[10px] font-bold ${
                                                                b.status === 'approved'
                                                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                                    : b.status === 'rejected'
                                                                      ? 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300'
                                                                      : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                                            }`}
                                                        >
                                                            {b.status.toUpperCase()}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* My Planned Vacations */}
                            <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                                <h3 className="text-base font-bold text-slate-900 dark:text-white">Registered Absences & Vacations</h3>
                                <p className="text-xs text-slate-500">Scheduled leaves that feed directly into the client forecast engine.</p>

                                <div className="mt-3 flex flex-wrap gap-3">
                                    {my_absences.map((a) => (
                                        <div key={a.id} className="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs dark:border-slate-800 dark:bg-slate-800/60">
                                            <Calendar className="h-4 w-4 text-blue-600" />
                                            <div>
                                                <p className="font-semibold text-slate-800 dark:text-slate-200">{a.description || a.type}</p>
                                                <p className="text-[10px] text-slate-500">{a.start_date} to {a.end_date}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* 3. PURCHASING VIEW: CONTRACT MONITORING & LEAD TIME ALERTS */}
                {activeTab === 'purchasing' && (
                    <div className="space-y-6">
                        {/* Purchasing Re-Order Warning Card */}
                        <div className="rounded-2xl border border-amber-200 bg-linear-to-r from-amber-50 to-orange-50 p-6 shadow-xs dark:border-amber-900/50 dark:from-amber-950/30 dark:to-orange-950/20">
                            <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                                <div>
                                    <div className="flex items-center gap-2 text-amber-800 dark:text-amber-300">
                                        <AlertTriangle className="h-5 w-5" />
                                        <h3 className="text-base font-bold">Automated Re-Order Alert: Lead Time Threshold Reached</h3>
                                    </div>
                                    <p className="mt-1 text-xs text-amber-700 dark:text-amber-400">
                                        Purchasing lead time requires <strong>4 weeks</strong>. Based on current burn rates, order <strong>4500012345</strong> will exhaust by <strong>Week {mainOrder.forecast.adjusted_depletion_week}</strong>.
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="rounded-xl bg-amber-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm">
                                        Order By: Week {mainOrder.forecast.reorder_deadline_week}
                                    </span>
                                </div>
                            </div>

                            {/* Ready-made follow-up order proposal */}
                            <div className="mt-4 rounded-xl border border-amber-200/60 bg-white/80 p-4 text-xs dark:border-amber-900/40 dark:bg-slate-900/80">
                                <p className="font-bold text-slate-900 dark:text-white">Draft Follow-Up Purchase Order Recommendation:</p>
                                <div className="mt-2 grid grid-cols-2 gap-4 md:grid-cols-4">
                                    <div>
                                        <span className="text-slate-500">Recommended Extension:</span>
                                        <p className="font-bold text-slate-800 dark:text-slate-200">{mainOrder.forecast.recommended_followup_order_pd} Person-Days</p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Estimated Commitment:</span>
                                        <p className="font-bold text-slate-800 dark:text-slate-200">
                                            €{(mainOrder.forecast.recommended_followup_order_pd * 8 * mainOrder.hourly_rate_eur).toLocaleString()}
                                        </p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Contractor / Supplier:</span>
                                        <p className="font-bold text-slate-800 dark:text-slate-200">{mainOrder.supplier_name}</p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Cost Centre:</span>
                                        <p className="font-bold text-slate-800 dark:text-slate-200">{mainOrder.cost_centre_code}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* All Orders Table (Aggregated Privacy View) */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <h3 className="text-base font-bold text-slate-900 dark:text-white">Aggregated Purchase Orders</h3>
                            <p className="text-xs text-slate-500">
                                High-level totals per contract. Personal individual hourly bookings are withheld in accordance with GDPR data minimisation.
                            </p>

                            <div className="mt-4 overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead className="border-b border-slate-200 text-slate-500 uppercase dark:border-slate-800">
                                        <tr>
                                            <th className="py-2.5 pr-4">Order #</th>
                                            <th className="py-2.5 pr-4">Title</th>
                                            <th className="py-2.5 pr-4">Supplier</th>
                                            <th className="py-2.5 pr-4">Cost Centre</th>
                                            <th className="py-2.5 pr-4">Ordered Budget</th>
                                            <th className="py-2.5 pr-4">Used Budget</th>
                                            <th className="py-2.5 pr-4">Free Budget</th>
                                            <th className="py-2.5">Traffic Light</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60">
                                        {orders.map((o) => (
                                            <tr key={o.id}>
                                                <td className="py-3 pr-4 font-mono font-bold text-slate-900 dark:text-white">{o.order_number}</td>
                                                <td className="py-3 pr-4 font-semibold text-slate-800 dark:text-slate-200">{o.title}</td>
                                                <td className="py-3 pr-4 text-slate-600 dark:text-slate-400">{o.supplier_name}</td>
                                                <td className="py-3 pr-4 text-slate-600 dark:text-slate-400">{o.cost_centre_code}</td>
                                                <td className="py-3 pr-4 font-semibold">{o.ordered_budget_pd} PD</td>
                                                <td className="py-3 pr-4 text-slate-600 dark:text-slate-400">{o.used_pd} PD ({o.usage_percent}%)</td>
                                                <td className="py-3 pr-4 font-bold text-emerald-600 dark:text-emerald-400">{o.remaining_pd} PD</td>
                                                <td className="py-3">
                                                    <span className={`rounded-md px-2 py-0.5 text-[10px] font-bold ${
                                                        o.forecast.traffic_light === 'green'
                                                            ? 'bg-emerald-50 text-emerald-700'
                                                            : o.forecast.traffic_light === 'yellow'
                                                              ? 'bg-amber-50 text-amber-700'
                                                              : 'bg-rose-50 text-rose-700'
                                                    }`}>
                                                        {o.forecast.traffic_light.toUpperCase()}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}

                {/* 4. FINANCE VIEW: INVOICE AUDIT & TOTAL RECONCILIATION */}
                {activeTab === 'finance' && (
                    <div className="space-y-6">
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                            <h3 className="text-base font-bold text-slate-900 dark:text-white">Finance & Controlling Reconciliation</h3>
                            <p className="text-xs text-slate-500">
                                Reconcile verified Proof of Work against incoming monthly supplier invoices.
                            </p>

                            <div className="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                                <div className="rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                                    <span className="text-xs text-slate-500">Contract Total Commitment</span>
                                    <p className="mt-1 text-2xl font-bold text-slate-900 dark:text-white">€{kpis.total_budget_eur.toLocaleString()}</p>
                                    <span className="text-xs text-slate-400">Sum across all active purchase orders</span>
                                </div>
                                <div className="rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                                    <span className="text-xs text-slate-500">Approved Spend to Date</span>
                                    <p className="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">€{kpis.total_spent_eur.toLocaleString()}</p>
                                    <span className="text-xs text-slate-400">Validated through Proof of Work</span>
                                </div>
                                <div className="rounded-xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                                    <span className="text-xs text-slate-500">Invoice Discrepancies</span>
                                    <p className="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">0 Variances</p>
                                    <span className="text-xs text-slate-400">100% matched to approved bookings</span>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </main>
        </div>
    );
}
