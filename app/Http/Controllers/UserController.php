<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users with search, filters, and statistics.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $roleFilter = $request->input('role');
        $statusFilter = $request->input('status');

        $query = User::query();

        if (!empty($search)) {
            $query->search($search);
        }

        if (!empty($roleFilter) && in_array($roleFilter, ['admin', 'member'])) {
            $query->where('role', $roleFilter);
        }

        if (!empty($statusFilter) && in_array($statusFilter, ['Active', 'Inactive'])) {
            $query->where('status', $statusFilter);
        }

        $users = $query->latest('id')->paginate(10)->withQueryString();

        // Statistics
        $totalUsers = User::count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalActive = User::where('status', 'Active')->count();
        $totalInactive = User::where('status', '!=', 'Active')->count();

        return view('pages.users.index', compact(
            'users',
            'search',
            'roleFilter',
            'statusFilter',
            'totalUsers',
            'totalAdmins',
            'totalActive',
            'totalInactive'
        ));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role'     => ['required', Rule::in(['admin', 'member'])],
            'status'   => ['required', Rule::in(['Active', 'Inactive'])],
        ], [
            'name.required'     => 'Nama pengguna wajib diisi.',
            'email.required'    => 'Alamat email wajib diisi.',
            'email.unique'      => 'Email ini sudah terdaftar oleh pengguna lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal harus 6 karakter.',
            'password.confirmed'=> 'Konfirmasi password tidak cocok.',
            'role.in'           => 'Role yang dipilih tidak valid.',
            'status.in'         => 'Status akun tidak valid.',
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
            'status'   => $validated['status'],
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna baru '{$validated['name']}' berhasil ditambahkan!");
    }

    /**
     * Display the specified user data as JSON for edit modal.
     */
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'user'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'role'   => $user->role ?? 'member',
                'status' => $user->status,
            ],
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'role'     => ['nullable', Rule::in(['admin', 'member'])],
            'status'   => ['nullable', Rule::in(['Active', 'Inactive'])],
        ];

        $validated = $request->validate($rules, [
            'name.required'     => 'Nama pengguna wajib diisi.',
            'email.required'    => 'Alamat email wajib diisi.',
            'email.unique'      => 'Email ini sudah terdaftar oleh pengguna lain.',
            'password.min'      => 'Password baru minimal harus 6 karakter.',
            'password.confirmed'=> 'Konfirmasi password baru tidak cocok.',
            'role.in'           => 'Role yang dipilih tidak valid.',
            'status.in'         => 'Status akun tidak valid.',
        ]);

        $roleToSet = $validated['role'] ?? $user->role;
        $statusToSet = $validated['status'] ?? $user->status;

        // Safety checks for current authenticated user
        if ($user->id === Auth::id()) {
            if ($roleToSet !== 'admin') {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['role' => 'Demi keamanan, Anda tidak dapat mencabut hak Administrator dari akun Anda sendiri.']);
            }
            if ($statusToSet !== 'Active') {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['status' => 'Anda tidak dapat menonaktifkan akun Anda sendiri saat sedang login.']);
            }
        }

        // Safety check: ensure at least one admin remains
        if ($user->isAdmin() && $roleToSet !== 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['role' => 'Tidak dapat mengubah role ini. Sistem harus memiliki minimal satu akun Administrator.']);
            }
        }

        $dataToUpdate = [
            'name'   => $validated['name'],
            'email'  => $validated['email'],
            'role'   => $roleToSet,
            'status' => $statusToSet,
        ];

        if (!empty($validated['password'])) {
            $dataToUpdate['password'] = Hash::make($validated['password']);
        }

        $user->update($dataToUpdate);

        return redirect()->route('admin.users.index')
            ->with('success', "Data pengguna '{$user->name}' berhasil diperbarui!");
    }

    /**
     * Toggle or set the user role (Admin <-> Member).
     */
    public function toggleRole(Request $request, User $user)
    {
        // Safety: Cannot demote self
        if ($user->id === Auth::id()) {
            $msg = 'Anda tidak dapat mencabut hak Administrator dari akun Anda sendiri.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : redirect()->back()->withErrors(['role' => $msg]);
        }

        $newRole = $request->input('role');
        if (empty($newRole)) {
            $newRole = $user->isAdmin() ? 'member' : 'admin';
        }

        // Safety: Ensure system has at least one admin
        if ($user->isAdmin() && $newRole !== 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                $msg = 'Sistem harus memiliki minimal satu akun Administrator.';
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => $msg], 422)
                    : redirect()->back()->withErrors(['role' => $msg]);
            }
        }

        $user->update(['role' => $newRole]);

        $statusText = $newRole === 'admin' ? 'Administrator' : 'Member';
        $msg = "Role pengguna '{$user->name}' berhasil diubah menjadi {$statusText}.";

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'role' => $newRole])
            : redirect()->route('admin.users.index')->with('success', $msg);
    }

    /**
     * Toggle or set the user status (Active <-> Inactive).
     */
    public function toggleStatus(Request $request, User $user)
    {
        // Safety: Cannot deactivate self
        if ($user->id === Auth::id()) {
            $msg = 'Anda tidak dapat menonaktifkan akun Anda sendiri.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : redirect()->back()->withErrors(['status' => $msg]);
        }

        $newStatus = $request->input('status');
        if (empty($newStatus)) {
            $newStatus = $user->status === 'Active' ? 'Inactive' : 'Active';
        }

        $user->update(['status' => $newStatus]);

        $msg = "Status akun '{$user->name}' diubah menjadi {$newStatus}.";

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'status' => $newStatus])
            : redirect()->route('admin.users.index')->with('success', $msg);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user)
    {
        // Safety: Cannot delete self
        if ($user->id === Auth::id()) {
            return redirect()->back()
                ->withErrors(['delete' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
        }

        // Safety: Cannot delete the last admin
        if ($user->isAdmin()) {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return redirect()->back()
                    ->withErrors(['delete' => 'Tidak dapat menghapus akun Administrator satu-satunya di sistem.']);
            }
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna '{$userName}' berhasil dihapus.");
    }
}
