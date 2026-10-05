<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Tampilkan data guru.
     */
    public function index()
    {
        $teachers = Teacher::with(['user', 'classes'])->latest()->get();

        return view('admin.master-data.data-guru', compact('teachers'));
    }

    /**
     * Tambah data guru.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'nip' => 'required|string|max:50|unique:teachers,nip',
            'phone' => 'required|string|max:30',
        ]);

        if ($validate->fails()) {
            return back()->withErrors($validate)->withInput();
        }

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            $user->assignRole('teacher');

            Teacher::create([
                'user_id' => $user->id,
                'nip' => $request->nip,
                'phone' => $request->phone,
            ]);
        });

        return redirect()->route('admin.teacher.index')->with('success', 'Data guru berhasil ditambahkan.');
    }

    /**
     * Update data guru.
     */
    public function update(Request $request, Teacher $teacher)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$teacher->user_id,
            'password' => 'nullable|min:8',
            'nip' => 'required|string|max:50|unique:teachers,nip,'.$teacher->id,
            'phone' => 'required|string|max:30',
        ]);

        if ($validate->fails()) {
            return back()->withErrors($validate)->withInput();
        }

        DB::transaction(function () use ($request, $teacher) {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            if ($request->filled('password')) {
                $userData['password'] = bcrypt($request->password);
            }

            $teacher->user->update($userData);

            $teacher->update([
                'nip' => $request->nip,
                'phone' => $request->phone,
            ]);
        });

        return redirect()->route('admin.teacher.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    /**
     * Hapus data guru.
     */
    public function destroy(Teacher $teacher)
    {
        DB::transaction(function () use ($teacher) {
            $user = $teacher->user;
            $teacher->delete();
            if ($user) {
                $user->delete();
            }
        });

        return redirect()->route('admin.teacher.index')->with('success', 'Data guru berhasil dihapus.');
    }
}
