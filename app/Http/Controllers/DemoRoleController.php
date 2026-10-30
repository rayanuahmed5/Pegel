<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoRoleController extends Controller
{
    /**
     * Instantly switch authenticated session to any demo role.
     */
    public function switchRole(Request $request): RedirectResponse
    {
        $role = $request->input('role', 'consultant');

        $emailMap = [
            'consultant' => 'consultant@pegel.test',
            'project_lead' => 'lead@pegel.test',
            'purchasing' => 'purchasing@pegel.test',
            'finance' => 'finance@pegel.test',
        ];

        $email = $emailMap[$role] ?? 'lead@pegel.test';

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return back()->withErrors(['error' => "Demo user for role {$role} not found."]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/dashboard')->with('success', "Switched session to role: {$user->name} ({$role})");
    }
}
