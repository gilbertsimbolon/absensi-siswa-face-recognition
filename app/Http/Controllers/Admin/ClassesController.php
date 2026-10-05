<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassesController extends Controller
{
    /**
     * Tampilkan data kelas.
     */
    public function index()
    {
        $classes = Classes::with('teacher.user')->latest()->get();
        $teachers = Teacher::with('user')->get();

        return view('admin.master-data.classes', compact('classes', 'teachers'));
    }

    /**
     * Simpan data kelas baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'grade_level' => 'required|string|max:20',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        Classes::create([
            'name' => $request->name,
            'grade_level' => $request->grade_level,
            'teacher_id' => $request->teacher_id ?: null,
        ]);

        return redirect()->route('admin.classes.index')->with('success', 'Data kelas berhasil ditambahkan.');
    }

    /**
     * Update data kelas.
     */
    public function update(Request $request, Classes $class)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'grade_level' => 'required|string|max:20',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $class->update([
            'name' => $request->name,
            'grade_level' => $request->grade_level,
            'teacher_id' => $request->teacher_id ?: null,
        ]);

        return redirect()->route('admin.classes.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    /**
     * Hapus data kelas.
     */
    public function destroy(Classes $class)
    {
        $class->delete();

        return redirect()->route('admin.classes.index')->with('success', 'Data kelas berhasil dihapus.');
    }
}
