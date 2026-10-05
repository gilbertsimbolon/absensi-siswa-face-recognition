<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Tampilkan daftar data siswa.
     */
    public function index()
    {
        $students = Student::with('classes')->latest()->get();
        $classes = Classes::orderBy('grade_level')->orderBy('name')->get();

        return view('admin.master-data.data-siswa', compact('students', 'classes'));
    }

    /**
     * Simpan data siswa baru beserta informasi WhatsApp siswa & orang tua.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nisn' => 'required|string|max:30|unique:students,nisn',
            'class_id' => 'required|exists:classes,id',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:30',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        Student::create([
            'name' => $request->name,
            'nisn' => $request->nisn,
            'class_id' => $request->class_id,
            'gender' => $request->gender,
            'phone' => $request->phone,
            'parent_name' => $request->parent_name,
            'parent_phone' => $request->parent_phone,
        ]);

        return redirect()->route('admin.student.index')->with('success', 'Data siswa dan nomor WhatsApp berhasil ditambahkan.');
    }

    /**
     * Update data siswa dan kontak orang tua.
     */
    public function update(Request $request, Student $student)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nisn' => 'required|string|max:30|unique:students,nisn,'.$student->id,
            'class_id' => 'required|exists:classes,id',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:30',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $student->update([
            'name' => $request->name,
            'nisn' => $request->nisn,
            'class_id' => $request->class_id,
            'gender' => $request->gender,
            'phone' => $request->phone,
            'parent_name' => $request->parent_name,
            'parent_phone' => $request->parent_phone,
        ]);

        return redirect()->route('admin.student.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Hapus data siswa.
     */
    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('admin.student.index')->with('success', 'Data siswa berhasil dihapus.');
    }
}
