<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class StaffAdminController extends Controller
{
    public function index(): Response
    {
        $staffMembers = User::whereHas('roles')
            ->with('roles')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'is_active' => $u->is_active,
                'roles' => $u->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'display_name' => $r->display_name]),
                'created_at' => $u->created_at->format('Y-m-d'),
            ]);

        $roles = Role::all(['id', 'name', 'display_name']);

        return Inertia::render('Admin/Staff/Index', [
            'staffMembers' => $staffMembers,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'password' => ['required', Password::defaults()],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->roles()->sync($validated['role_ids']);

        return redirect()->back()->with('success', 'Staff member created successfully.');
    }

    public function updateRoles(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['exists:roles,id'],
        ]);

        $user = User::findOrFail($id);

        // Safeguard: Check if removing super_admin from last super_admin
        if ($user->hasRole('super_admin')) {
            $superAdminRoleId = Role::where('name', 'super_admin')->value('id');
            if (!in_array($superAdminRoleId, $validated['role_ids'], false)) {
                $count = User::whereHas('roles', fn ($r) => $r->where('name', 'super_admin'))->count();
                if ($count <= 1) {
                    return redirect()->back()->withErrors(['roles' => 'Cannot remove super_admin from the sole remaining Super Administrator.']);
                }
            }
        }

        $user->roles()->sync($validated['role_ids']);

        return redirect()->back()->with('success', 'Staff roles updated.');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->is_active && $user->hasRole('super_admin')) {
            $count = User::where('is_active', true)
                ->whereHas('roles', fn ($r) => $r->where('name', 'super_admin'))
                ->count();
            if ($count <= 1) {
                return redirect()->back()->withErrors(['user' => 'Cannot deactivate the sole active Super Administrator.']);
            }
        }

        $user->update(['is_active' => !$user->is_active]);

        return redirect()->back()->with('success', 'Staff active status changed.');
    }
}
