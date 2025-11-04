<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query()->withCount('connectedPlatforms');

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by admin status
        if ($request->has('filter') && $request->filter !== 'all') {
            $query->where('is_admin', $request->filter === 'admin');
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->toArray(),
            'filters' => [
                'search' => $request->get('search', ''),
                'filter' => $request->get('filter', 'all'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_admin' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_admin'] = $validated['is_admin'] ?? false;

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'Benutzer erfolgreich erstellt.');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->only(['id', 'name', 'email', 'is_admin', 'email_verified_at', 'created_at']),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_admin' => 'boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Benutzer erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Du kannst dich nicht selbst löschen.');
        }

        // Delete user's connected platforms
        $user->connectedPlatforms()->delete();

        // Delete user
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Benutzer erfolgreich gelöscht.');
    }

    /**
     * Toggle admin status.
     */
    public function toggleAdmin(User $user)
    {
        // Prevent removing your own admin status
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Du kannst deinen eigenen Admin-Status nicht ändern.');
        }

        $user->update([
            'is_admin' => !$user->is_admin,
        ]);

        $message = $user->is_admin
            ? 'Benutzer wurde zum Admin ernannt.'
            : 'Admin-Rechte wurden entzogen.';

        return back()->with('success', $message);
    }
}
