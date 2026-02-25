<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::where('organization_id', auth()->user()->organization_id)
            ->with('role')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role' => $user->role?->name,
                    'created_at' => $user->created_at->format('M d, Y'),
                ];
            });

        $roles = Role::all();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        // Check if user has permission to manage users
        if (!auth()->user()->canManageUsers()) {
            abort(403, 'You do not have permission to create users.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => 'required|exists:roles,id',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'organization_id' => auth()->user()->organization_id,
            'role_id' => $validated['role_id'],
        ];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        User::create($data);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        // Check if user has permission to manage users
        if (!auth()->user()->canManageUsers()) {
            abort(403, 'You do not have permission to update users.');
        }

        // Ensure user belongs to the same organization
        if ($user->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role_id' => 'required|exists:roles,id',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ];

        // Only update password if provided
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                \Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } elseif (!$request->has('avatar')) {
            // Don't update avatar field if no new file is uploaded
            unset($data['avatar']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Check if user has permission to manage users
        if (!auth()->user()->canManageUsers()) {
            abort(403, 'You do not have permission to delete users.');
        }

        // Ensure user belongs to the same organization
        if ($user->organization_id !== auth()->user()->organization_id) {
            abort(403);
        }

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete yourself.');
        }

        // Delete avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
