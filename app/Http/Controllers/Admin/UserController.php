<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Tampilkan daftar user beserta relasi guru dan role.
     */
    public function index(Request $request): View
    {
        $query = User::with(['roles', 'teacher']);

        // Filter pencarian berdasarkan nama atau email
        $search = $request->input('cari', $request->input('search'));
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter role jika ada
        $roleFilter = $request->input('role');
        if (! empty($roleFilter)) {
            $query->role($roleFilter);
        }

        // Urutkan (default: nama asc)
        $users = $query->orderBy('name')->paginate(10)->withQueryString();

        // Data guru untuk opsi pemilihan nama
        $teachers = Teacher::with('user')->orderBy('id')->get();
        $roles = Role::whereIn('name', ['admin', 'teacher', 'headmaster'])->get();

        return view('admin.manajemen-user.index', compact('users', 'teachers', 'roles'));
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
            'teacher_id' => 'nullable|exists:teachers,id',
        ], [
            'name.required' => 'Nama user wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar di sistem.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'role.required' => 'Role wajib dipilih.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->assignRole($request->role);

            // Jika memilih guru yang belum terhubung dengan akun user
            if ($request->filled('teacher_id')) {
                $teacher = Teacher::find($request->teacher_id);
                if ($teacher && ! $teacher->user_id) {
                    $teacher->update(['user_id' => $user->id]);
                }
            }
        });

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Perbarui data user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|exists:roles,name',
        ], [
            'name.required' => 'Nama user wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan oleh user lain.',
            'password.min' => 'Password baru minimal terdiri dari 8 karakter.',
            'role.required' => 'Role wajib dipilih.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::transaction(function () use ($request, $user) {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);
            $user->syncRoles([$request->role]);
        });

        return redirect()->route('admin.users.index')->with('success', 'Data user berhasil diperbarui.');
    }

    /**
     * Hapus data user.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Proteksi: tidak boleh menghapus akun yang sedang login
        if (Auth::id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        // Proteksi: jangan sampai tidak ada administrator sama sekali
        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus. Sistem membutuhkan minimal satu akun Administrator.');
        }

        DB::transaction(function () use ($user) {
            // Jika user terkait dengan teacher, bersihkan relasi/file terkait
            if ($user->teacher) {
                foreach ($user->teacher->faces as $face) {
                    if ($face->file_path && Storage::disk('public')->exists($face->file_path)) {
                        Storage::disk('public')->delete($face->file_path);
                    }
                }
                $user->teacher->faces()->delete();
                $user->teacher->delete();
            }

            $user->delete();
        });

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }
}
