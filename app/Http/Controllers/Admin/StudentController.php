<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Student;
use App\Models\StudentFace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Tampilkan daftar data siswa beserta relasi foto wajah.
     */
    public function index()
    {
        $students = Student::with(['classes', 'faces'])->latest()->get();
        $classes = Classes::orderBy('grade_level')->orderBy('name')->get();

        return view('admin.master-data.data-siswa', compact('students', 'classes'));
    }

    /**
     * Simpan data siswa baru beserta minimal 3 sampel foto wajah.
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

        return redirect()->route('admin.student.index')->with('success', 'Data siswa dan 3 sampel foto wajah berhasil disimpan.');
    }

    /**
     * Update data siswa dan foto wajah (jika ada pembaruan).
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

        return redirect()->route('admin.student.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Hapus data siswa beserta berkas foto di storage dan folder python dataset.
     */
    public function destroy(Student $student)
    {
        // Hapus folder dataset di python-service jika ada
        $pythonDatasetPath = base_path('python-service/dataset/'.$student->nisn);
        if (File::exists($pythonDatasetPath)) {
            File::deleteDirectory($pythonDatasetPath);
        }

        // Hapus folder di storage/app/public/faces/{nisn}
        $storagePath = 'faces/'.$student->nisn;
        if (Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->deleteDirectory($storagePath);
        }

        $student->delete();

        return redirect()->route('admin.student.index')->with('success', 'Data siswa dan dataset foto berhasil dihapus.');
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
