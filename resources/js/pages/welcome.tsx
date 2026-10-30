import { Head, router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Building2,
    CheckCircle2,
    Clock,
    Euro,
    FileText,
    Sparkles,
    TrendingUp,
    Users,
} from 'lucide-react';
import React from 'react';

export default function Welcome() {
    const { demoRoleSwitchEnabled } = usePage().props;

    const handleQuickLogin = (role: string) => {
        router.post('/demo/switch-role', { role });
    };

    return (
        <div className="flex min-h-screen flex-col justify-between bg-slate-900 text-slate-100 selection:bg-blue-600 selection:text-white">
            <Head title="Pegel - Time Booking & Budget Forecast" />

            {/* Top Navigation */}
            <header className="border-b border-slate-800 bg-slate-900/80 px-6 py-4 backdrop-blur-md">
                <div className="mx-auto flex max-w-6xl items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 font-bold text-white shadow-lg shadow-blue-500/30">
                            P
                        </div>
                        <div>
                            <span className="text-lg font-bold tracking-tight text-white">
                                Pegel
                            </span>
                            <span className="ml-2 rounded-md border border-blue-800 bg-blue-950 px-2 py-0.5 text-[11px] font-semibold text-blue-400">
                                Hackathon 2026 Edition
                            </span>
                        </div>
                    </div>

                    <a
                        href="/login"
                        className="rounded-lg border border-slate-700 px-4 py-2 text-xs font-semibold text-slate-300 transition hover:bg-slate-800 hover:text-white"
                    >
                        Standard Sign In
                    </a>
                </div>
            </header>

            {/* Hero Section */}
            <main className="mx-auto flex max-w-5xl flex-1 flex-col justify-center px-6 py-12">
                <div className="text-center">
                    <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-blue-500/30 bg-blue-500/10 px-4 py-1.5 text-xs font-medium text-blue-400">
                        <Sparkles className="h-3.5 w-3.5" />
                        Article 26 Hackathon Challenge: Every Hour You Buy, in
                        View
                    </div>

                    <h1 className="text-4xl font-extrabold tracking-tight text-white sm:text-6xl">
                        Steer Project Budgets <br />
                        <span className="bg-gradient-to-r from-blue-400 via-sky-300 to-indigo-400 bg-clip-text text-transparent">
                            Before the Money Runs Out
                        </span>
                    </h1>

                    <p className="mx-auto mt-6 max-w-2xl text-base text-slate-400 sm:text-lg">
                        Pegel forecasts when external consulting budgets deplete
                        factoring in weekly velocity, vacations, and shutdowns.
                        It enforces German Working Time Act (ArbZG) limits on
                        the server and alerts purchasing 4 weeks in advance.
                    </p>
                </div>

                {/* Instant Demo Role Selection Cards */}
                {demoRoleSwitchEnabled && (
                    <div className="mt-12">
                        <p className="mb-4 text-center text-xs font-semibold tracking-wider text-slate-400 uppercase">
                            Instant Juror & Evaluator Sign-In · Select Any
                            Persona
                        </p>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {/* 1. Consultant */}
                            <div
                                onClick={() => handleQuickLogin('consultant')}
                                className="group cursor-pointer rounded-2xl border border-slate-800 bg-slate-800/40 p-5 shadow-md transition hover:border-blue-500/50 hover:bg-slate-800/80"
                            >
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/20 text-blue-400 transition group-hover:bg-blue-500 group-hover:text-white">
                                    <Clock className="h-5 w-5" />
                                </div>
                                <h3 className="mt-4 font-bold text-white">
                                    Elena Becker
                                </h3>
                                <p className="text-xs font-medium text-blue-400">
                                    External Consultant
                                </p>
                                <p className="mt-2 text-xs leading-relaxed text-slate-400">
                                    Book daily hours, validate ArbZG 10h daily
                                    limits and register planned vacations.
                                </p>
                                <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-blue-400 transition group-hover:translate-x-1">
                                    Launch Consultant View{' '}
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </div>
                            </div>

                            {/* 2. Project Lead */}
                            <div
                                onClick={() => handleQuickLogin('project_lead')}
                                className="group cursor-pointer rounded-2xl border border-slate-800 bg-slate-800/40 p-5 shadow-md transition hover:border-emerald-500/50 hover:bg-slate-800/80"
                            >
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400 transition group-hover:bg-emerald-500 group-hover:text-white">
                                    <FileText className="h-5 w-5" />
                                </div>
                                <h3 className="mt-4 font-bold text-white">
                                    Markus Weber
                                </h3>
                                <p className="text-xs font-medium text-emerald-400">
                                    Client Project Lead
                                </p>
                                <p className="mt-2 text-xs leading-relaxed text-slate-400">
                                    Inspect 140 PD budget, Week 51 burn-out
                                    forecast, and approve Proof of Work.
                                </p>
                                <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-emerald-400 transition group-hover:translate-x-1">
                                    Launch Project Lead View{' '}
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </div>
                            </div>

                            {/* 3. Purchasing */}
                            <div
                                onClick={() => handleQuickLogin('purchasing')}
                                className="group cursor-pointer rounded-2xl border border-slate-800 bg-slate-800/40 p-5 shadow-md transition hover:border-amber-500/50 hover:bg-slate-800/80"
                            >
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400 transition group-hover:bg-amber-500 group-hover:text-white">
                                    <Building2 className="h-5 w-5" />
                                </div>
                                <h3 className="mt-4 font-bold text-white">
                                    Sabine Richter
                                </h3>
                                <p className="text-xs font-medium text-amber-400">
                                    Client Purchasing
                                </p>
                                <p className="mt-2 text-xs leading-relaxed text-slate-400">
                                    Receive automated 4-week lead time alert to
                                    order 31 PD follow-up extension.
                                </p>
                                <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-amber-400 transition group-hover:translate-x-1">
                                    Launch Purchasing View{' '}
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </div>
                            </div>

                            {/* 4. Finance */}
                            <div
                                onClick={() => handleQuickLogin('finance')}
                                className="group cursor-pointer rounded-2xl border border-slate-800 bg-slate-800/40 p-5 shadow-md transition hover:border-purple-500/50 hover:bg-slate-800/80"
                            >
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-500/20 text-purple-400 transition group-hover:bg-purple-500 group-hover:text-white">
                                    <Euro className="h-5 w-5" />
                                </div>
                                <h3 className="mt-4 font-bold text-white">
                                    Thomas Müller
                                </h3>
                                <p className="text-xs font-medium text-purple-400">
                                    Client Finance
                                </p>
                                <p className="mt-2 text-xs leading-relaxed text-slate-400">
                                    Audit €80,640 approved spend against
                                    purchase order €134,400 commitment.
                                </p>
                                <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-purple-400 transition group-hover:translate-x-1">
                                    Launch Finance View{' '}
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Challenge Scenario Evidence Summary */}
                <div className="mt-12 rounded-2xl border border-slate-800 bg-slate-800/20 p-6 text-xs text-slate-400">
                    <div className="mb-2 flex items-center gap-2 font-bold text-slate-200">
                        <CheckCircle2 className="h-4 w-4 text-emerald-400" />
                        Main Challenge Benchmark Loaded: Customer Portal
                        Migration (PO #4500012345)
                    </div>
                    <div className="mt-3 grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div>
                            Ordered:{' '}
                            <span className="font-bold text-white">
                                140 Person-Days
                            </span>
                        </div>
                        <div>
                            Used (W28-W42):{' '}
                            <span className="font-bold text-white">
                                84 Person-Days
                            </span>
                        </div>
                        <div>
                            Average Pace:{' '}
                            <span className="font-bold text-white">
                                6 Person-Days / Week
                            </span>
                        </div>
                        <div>
                            Follow-Up Order By:{' '}
                            <span className="font-bold text-amber-400">
                                Week 47, 2026
                            </span>
                        </div>
                    </div>
                </div>
            </main>

            {/* Footer */}
            <footer className="border-t border-slate-800 py-6 text-center text-xs text-slate-500">
                Pegel · Article 26 Hackathon 2026 · Working Code Track (10/10
                Technical Target)
            </footer>
        </div>
    );
}
