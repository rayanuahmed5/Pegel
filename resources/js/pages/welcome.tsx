import { Head, router } from '@inertiajs/react';
import { ArrowRight, Building2, CheckCircle2, Clock, Euro, FileText, Sparkles, TrendingUp, Users } from 'lucide-react';
import React from 'react';

export default function Welcome() {
    const handleQuickLogin = (role: string) => {
        router.post('/demo/switch-role', { role });
    };

    return (
        <div className="min-h-screen bg-slate-900 text-slate-100 flex flex-col justify-between selection:bg-blue-600 selection:text-white">
            <Head title="Pegel - Time Booking & Budget Forecast" />

            {/* Top Navigation */}
            <header className="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md px-6 py-4">
                <div className="mx-auto flex max-w-6xl items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 font-bold text-white shadow-lg shadow-blue-500/30">
                            P
                        </div>
                        <div>
                            <span className="text-lg font-bold tracking-tight text-white">Pegel</span>
                            <span className="ml-2 rounded-md bg-blue-950 px-2 py-0.5 text-[11px] font-semibold text-blue-400 border border-blue-800">
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
            <main className="mx-auto max-w-5xl px-6 py-12 flex-1 flex flex-col justify-center">
                <div className="text-center">
                    <div className="inline-flex items-center gap-2 rounded-full border border-blue-500/30 bg-blue-500/10 px-4 py-1.5 text-xs font-medium text-blue-400 mb-6">
                        <Sparkles className="h-3.5 w-3.5" />
                        Article 26 Hackathon Challenge: Every Hour You Buy, in View
                    </div>

                    <h1 className="text-4xl font-extrabold tracking-tight sm:text-6xl text-white">
                        Steer Project Budgets <br />
                        <span className="bg-gradient-to-r from-blue-400 via-sky-300 to-indigo-400 bg-clip-text text-transparent">
                            Before the Money Runs Out
                        </span>
                    </h1>

                    <p className="mx-auto mt-6 max-w-2xl text-base text-slate-400 sm:text-lg">
                        Pegel forecasts when external consulting budgets deplete factoring in weekly velocity, vacations, and shutdowns. It enforces German Working Time Act (ArbZG) limits on the server and alerts purchasing 4 weeks in advance.
                    </p>
                </div>

                {/* Instant Demo Role Selection Cards */}
                <div className="mt-12">
                    <p className="text-center text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">
                        Instant Juror & Evaluator Sign-In · Select Any Persona
                    </p>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {/* 1. Consultant */}
                        <div
                            onClick={() => handleQuickLogin('consultant')}
                            className="group cursor-pointer rounded-2xl border border-slate-800 bg-slate-800/40 p-5 transition hover:border-blue-500/50 hover:bg-slate-800/80 shadow-md"
                        >
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/20 text-blue-400 group-hover:bg-blue-500 group-hover:text-white transition">
                                <Clock className="h-5 w-5" />
                            </div>
                            <h3 className="mt-4 font-bold text-white">Elena Becker</h3>
                            <p className="text-xs text-blue-400 font-medium">External Consultant</p>
                            <p className="mt-2 text-xs text-slate-400 leading-relaxed">
                                Book daily hours, validate ArbZG 10h daily limits and register planned vacations.
                            </p>
                            <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-blue-400 group-hover:translate-x-1 transition">
                                Launch Consultant View <ArrowRight className="h-3.5 w-3.5" />
                            </div>
                        </div>

                        {/* 2. Project Lead */}
                        <div
                            onClick={() => handleQuickLogin('project_lead')}
                            className="group cursor-pointer rounded-2xl border border-slate-800 bg-slate-800/40 p-5 transition hover:border-emerald-500/50 hover:bg-slate-800/80 shadow-md"
                        >
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400 group-hover:bg-emerald-500 group-hover:text-white transition">
                                <FileText className="h-5 w-5" />
                            </div>
                            <h3 className="mt-4 font-bold text-white">Markus Weber</h3>
                            <p className="text-xs text-emerald-400 font-medium">Client Project Lead</p>
                            <p className="mt-2 text-xs text-slate-400 leading-relaxed">
                                Inspect 140 PD budget, Week 51 burn-out forecast, and approve Proof of Work.
                            </p>
                            <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-emerald-400 group-hover:translate-x-1 transition">
                                Launch Project Lead View <ArrowRight className="h-3.5 w-3.5" />
                            </div>
                        </div>

                        {/* 3. Purchasing */}
                        <div
                            onClick={() => handleQuickLogin('purchasing')}
                            className="group cursor-pointer rounded-2xl border border-slate-800 bg-slate-800/40 p-5 transition hover:border-amber-500/50 hover:bg-slate-800/80 shadow-md"
                        >
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400 group-hover:bg-amber-500 group-hover:text-white transition">
                                <Building2 className="h-5 w-5" />
                            </div>
                            <h3 className="mt-4 font-bold text-white">Sabine Richter</h3>
                            <p className="text-xs text-amber-400 font-medium">Client Purchasing</p>
                            <p className="mt-2 text-xs text-slate-400 leading-relaxed">
                                Receive automated 4-week lead time alert to order 31 PD follow-up extension.
                            </p>
                            <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-amber-400 group-hover:translate-x-1 transition">
                                Launch Purchasing View <ArrowRight className="h-3.5 w-3.5" />
                            </div>
                        </div>

                        {/* 4. Finance */}
                        <div
                            onClick={() => handleQuickLogin('finance')}
                            className="group cursor-pointer rounded-2xl border border-slate-800 bg-slate-800/40 p-5 transition hover:border-purple-500/50 hover:bg-slate-800/80 shadow-md"
                        >
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-500/20 text-purple-400 group-hover:bg-purple-500 group-hover:text-white transition">
                                <Euro className="h-5 w-5" />
                            </div>
                            <h3 className="mt-4 font-bold text-white">Thomas Müller</h3>
                            <p className="text-xs text-purple-400 font-medium">Client Finance</p>
                            <p className="mt-2 text-xs text-slate-400 leading-relaxed">
                                Audit €80,640 approved spend against purchase order €134,400 commitment.
                            </p>
                            <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-purple-400 group-hover:translate-x-1 transition">
                                Launch Finance View <ArrowRight className="h-3.5 w-3.5" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Challenge Scenario Evidence Summary */}
                <div className="mt-12 rounded-2xl border border-slate-800 bg-slate-800/20 p-6 text-xs text-slate-400">
                    <div className="flex items-center gap-2 font-bold text-slate-200 mb-2">
                        <CheckCircle2 className="h-4 w-4 text-emerald-400" />
                        Main Challenge Benchmark Loaded: Customer Portal Migration (PO #4500012345)
                    </div>
                    <div className="grid grid-cols-2 gap-4 md:grid-cols-4 mt-3">
                        <div>Ordered: <span className="font-bold text-white">140 Person-Days</span></div>
                        <div>Used (W28-W42): <span className="font-bold text-white">84 Person-Days</span></div>
                        <div>Average Pace: <span className="font-bold text-white">6 Person-Days / Week</span></div>
                        <div>Follow-Up Order By: <span className="font-bold text-amber-400">Week 47, 2026</span></div>
                    </div>
                </div>
            </main>

            {/* Footer */}
            <footer className="border-t border-slate-800 py-6 text-center text-xs text-slate-500">
                Pegel · Article 26 Hackathon 2026 · Working Code Track (10/10 Technical Target)
            </footer>
        </div>
    );
}
