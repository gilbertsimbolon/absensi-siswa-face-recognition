<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Student;
use App\Models\StudentFace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Tampilkan daftar data siswa beserta relasi foto wajah, filter, dan pagination.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isTeacher = $user && $user->hasRole('teacher') && ! $user->hasRole('admin');
        $teacherRecord = $isTeacher ? $user->teacher : null;
        $myClass = $teacherRecord ? Classes::where('teacher_id', $teacherRecord->id)->first() : null;

        $query = Student::with(['classes', 'faces']);

        if ($isTeacher) {
            if ($myClass) {
                $query->where('class_id', $myClass->id);
                $classes = Classes::where('id', $myClass->id)->get();
            } else {
                $query->whereRaw('1 = 0');
                $classes = collect();
            }
        } else {
            // Filter berdasarkan kelas
            if ($request->filled('class_id')) {
                $query->where('class_id', $request->class_id);
            }
            $classes = Classes::orderBy('grade_level')->orderBy('name')->get();
        }

        // Filter pencarian nama atau NISN
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $sort = $request->query('sort', 'name_asc');
        match ($sort) {
            'name_desc' => $query->orderByDesc('name'),
            'nisn_asc' => $query->orderBy('nisn'),
            'nisn_desc' => $query->orderByDesc('nisn'),
            'latest' => $query->latest(),
            default => $query->orderBy('name'),
        };

        $students = $query->paginate(10)->withQueryString();

        return view('admin.master-data.data-siswa', compact('students', 'classes', 'isTeacher', 'myClass'));
    }

    /**
     * Simpan data siswa baru beserta minimal 3 sampel foto wajah.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user && $user->hasRole('teacher') && ! $user->hasRole('admin')) {
            $teacher = $user->teacher;
            $myClass = $teacher ? Classes::where('teacher_id', $teacher->id)->first() : null;
            if (! $myClass) {
                abort(403, 'Anda belum ditugaskan sebagai wali kelas.');
            }
            $request->merge(['class_id' => $myClass->id]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nisn' => 'required|string|max:30|unique:students,nisn',
            'class_id' => 'required|exists:classes,id',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:30',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:30',
            'photo_depan' => 'required|image|mimes:jpeg,jpg,png|max:5120',
            'photo_kanan' => 'required|image|mimes:jpeg,jpg,png|max:5120',
            'photo_kiri' => 'required|image|mimes:jpeg,jpg,png|max:5120',
        ], [
            'photo_depan.required' => 'Foto wajah tampak depan wajib diunggah.',
            'photo_kanan.required' => 'Foto wajah serong kanan wajib diunggah.',
            'photo_kiri.required' => 'Foto wajah serong kiri wajib diunggah.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $student = Student::create([
            'name' => $request->name,
            'nisn' => $request->nisn,
            'class_id' => $request->class_id,
            'gender' => $request->gender,
            'phone' => $request->phone,
            'parent_name' => $request->parent_name,
            'parent_phone' => $request->parent_phone,
        ]);

        // Simpan 3 sampel foto wajah
        $this->saveStudentPhotos($request, $student);

        return redirect()->route('admin.student.index')->with('success', 'Berhasil');
    }

    /**
     * Update data siswa dan foto wajah (jika ada pembaruan).
     */
    public function update(Request $request, Student $student)
    {
        $user = Auth::user();
        if ($user && $user->hasRole('teacher') && ! $user->hasRole('admin')) {
            $teacher = $user->teacher;
            $myClass = $teacher ? Classes::where('teacher_id', $teacher->id)->first() : null;
            if (! $myClass || $student->class_id !== $myClass->id) {
                abort(403, 'Anda hanya dapat mengubah data siswa di kelas Anda sendiri.');
            }
            $request->merge(['class_id' => $myClass->id]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nisn' => 'required|string|max:30|unique:students,nisn,'.$student->id,
            'class_id' => 'required|exists:classes,id',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:30',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:30',
            'photo_depan' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'photo_kanan' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'photo_kiri' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
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

        // Update foto jika diunggah baru
        $this->saveStudentPhotos($request, $student, true);

        return redirect()->route('admin.student.index')->with('success', 'Berhasil');
    }

    /**
     * Hapus data siswa beserta berkas foto di storage dan folder python dataset.
     */
    public function destroy(Student $student)
    {
        $user = Auth::user();
        if ($user && $user->hasRole('teacher') && ! $user->hasRole('admin')) {
            $teacher = $user->teacher;
            $myClass = $teacher ? Classes::where('teacher_id', $teacher->id)->first() : null;
            if (! $myClass || $student->class_id !== $myClass->id) {
                abort(403, 'Anda hanya dapat menghapus data siswa di kelas Anda sendiri.');
            }
        }
        // 1. Hapus setiap berkas foto fisik di storage jika ada
        foreach ($student->faces as $face) {
            if ($face->file_path && Storage::disk('public')->exists($face->file_path)) {
                Storage::disk('public')->delete($face->file_path);
            }
        }

        // 2. Hapus direktori faces/{nisn} di storage public
        $storagePath = 'faces/'.$student->nisn;
        if (Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->deleteDirectory($storagePath);
        }

        // 3. Hapus folder dataset siswa di python-service jika ada
        $pythonDatasetPath = base_path('python-service/dataset/'.$student->nisn);
        if (File::exists($pythonDatasetPath)) {
            File::deleteDirectory($pythonDatasetPath);
        }

        // 4. Hapus relasi record student_faces di database
        $student->faces()->delete();

        // 5. Hapus record siswa
        $student->delete();

        return redirect()->route('admin.student.index')->with('success', 'Berhasil');
    }

    /**
     * Helper penyimpanan foto ke storage public dan sinkronisasi ke python-service/dataset.
     */
    private function saveStudentPhotos(Request $request, Student $student, bool $isUpdate = false): void
    {
        $photoInputs = [
            'photo_depan' => 'Tampak Depan',
            'photo_kanan' => 'Serong Kanan',
            'photo_kiri' => 'Serong Kiri',
        ];

        // Folder tujuan sinkronisasi DeepFace Python
        $pythonDatasetDir = base_path('python-service/dataset/'.$student->nisn);
        if (! File::exists($pythonDatasetDir)) {
            File::makeDirectory($pythonDatasetDir, 0755, true);
        }

        foreach ($photoInputs as $inputKey => $label) {
            if ($request->hasFile($inputKey)) {
                $file = $request->file($inputKey);
                $fileName = time().'_'.$inputKey.'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('faces/'.$student->nisn, $fileName, 'public');

                // Salin juga langsung ke folder dataset Python DeepFace
                $pythonFilePath = $pythonDatasetDir.'/'.$fileName;
                File::copy(storage_path('app/public/'.$path), $pythonFilePath);

                if ($isUpdate) {
                    $existingFace = StudentFace::where('student_id', $student->id)->where('label', $label)->first();
                    if ($existingFace) {
                        Storage::disk('public')->delete($existingFace->file_path);
                        $existingFace->update(['file_path' => $path]);

                        continue;
                    }
                }

                StudentFace::create([
                    'student_id' => $student->id,
                    'file_path' => $path,
                    'label' => $label,
                ]);
            }
        }
    }
}
