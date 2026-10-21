<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Student;
use App\Models\StudentFace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            // Filter berdasarkan kelas (dukung 'kelas' dan 'class_id')
            $filterClassId = $request->input('kelas', $request->input('class_id'));
            if (! empty($filterClassId)) {
                $query->where('class_id', $filterClassId);
            }
            $classes = Classes::orderBy('grade_level')->orderBy('name')->get();
        }

        // Filter pencarian nama atau NISN (dukung 'cari' dan 'search')
        $search = $request->input('cari', $request->input('search'));
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $sort = $request->query('urutkan', $request->query('sort', 'nama_asc'));
        match ($sort) {
            'nama_desc', 'name_desc' => $query->orderByDesc('name'),
            'nisn_asc' => $query->orderBy('nisn'),
            'nisn_desc' => $query->orderByDesc('nisn'),
            'terbaru', 'latest' => $query->latest(),
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

        $rules = [
            'name' => 'required|string|max:255',
            'nisn' => 'required|string|max:30|unique:students,nisn',
            'class_id' => 'required|exists:classes,id',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:30',
            'parent_email' => 'nullable|email|max:255',
        ];

        if ($request->hasFile('photos')) {
            $rules['photos'] = 'required|array|min:1';
            $rules['photos.*'] = 'image|mimes:jpeg,jpg,png|max:10240';
        } elseif ($request->hasFile('photo')) {
            $rules['photo'] = 'required|image|mimes:jpeg,jpg,png|max:10240';
        } elseif ($request->hasFile('photo_depan')) {
            $rules['photo_depan'] = 'required|image|mimes:jpeg,jpg,png|max:10240';
        } else {
            $rules['photos'] = 'required|array|min:1';
        }

        $validator = Validator::make($request->all(), $rules, [
            'photos.required' => 'Minimal 1 foto wajah siswa wajib diunggah.',
            'photos.*.image' => 'Berkas yang diunggah harus berupa gambar (JPG/PNG).',
            'photo.required' => 'Foto wajah siswa wajib diunggah.',
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
            'email' => $request->email,
            'parent_name' => $request->parent_name,
            'parent_phone' => $request->parent_phone,
            'parent_email' => $request->parent_email,
        ]);

        // Simpan foto wajah siswa dan ekstrak embedding ke database
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
            'email' => 'nullable|email|max:255',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:30',
            'parent_email' => 'nullable|email|max:255',
            'photos.*' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
            'photo_depan' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
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
            'email' => $request->email,
            'parent_name' => $request->parent_name,
            'parent_phone' => $request->parent_phone,
            'parent_email' => $request->parent_email,
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

        // Hapus embedding di memori dan cache Python DeepFace FaceNet
        try {
            $pythonUrl = config('services.deepface.base_url', 'http://127.0.0.1:5000').'/hapus-siswa/'.$student->nisn;
            Http::timeout(5)->delete($pythonUrl);
        } catch (\Throwable $e) {
            Log::warning("Gagal menghapus embedding FaceNet di Python untuk {$student->nisn}: ".$e->getMessage());
        }

        // 4. Hapus relasi record student_faces di database
        $student->faces()->delete();

        // 5. Hapus record siswa
        $student->delete();

        return redirect()->route('admin.student.index')->with('success', 'Berhasil');
    }

    /**
     * Helper penyimpanan foto ke storage public (faces/{nisn}) dan ekstraksi embedding di microservice Python.
     */
    private function saveStudentPhotos(Request $request, Student $student, bool $isUpdate = false): void
    {
        // Kumpulkan semua file gambar yang diunggah
        $uploadedFiles = [];
        if ($request->hasFile('photos')) {
            $uploadedFiles = $request->file('photos');
        } elseif ($request->hasFile('photo')) {
            $uploadedFiles = [$request->file('photo')];
        } else {
            foreach (['photo_depan', 'photo_kanan', 'photo_kiri'] as $key) {
                if ($request->hasFile($key)) {
                    $uploadedFiles[] = $request->file($key);
                }
            }
        }

        if (empty($uploadedFiles)) {
            return;
        }

        foreach ($uploadedFiles as $idx => $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $fileName = time().'_'.($idx + 1).'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('faces/'.$student->nisn, $fileName, 'public');

            StudentFace::create([
                'student_id' => $student->id,
                'file_path' => $path,
            ]);
        }

        // Otomatis sinkronisasi, deteksi auto-crop zoom wajah, dan ekstraksi embedding FaceNet di microservice Python
        $this->triggerFaceExtraction($student);
    }

    /**
     * Memicu microservice Python untuk mengekstrak vektor FaceNet 128-D dan menyimpannya ke MySQL.
     */
    private function triggerFaceExtraction(Student $student): bool
    {
        try {
            $pythonUrl = config('services.deepface.base_url', 'http://127.0.0.1:5000').'/sinkronisasi-siswa/'.$student->nisn;
            $response = Http::timeout(120)->post($pythonUrl, [
                'nama' => $student->name,
            ]);

            if ($response->successful()) {
                $aiData = $response->json();
                $embeddingsMap = $aiData['embeddings'] ?? [];

                $savedCount = 0;
                foreach ($embeddingsMap as $fn => $vector) {
                    $updated = StudentFace::where('student_id', $student->id)
                        ->where('file_path', 'like', "%{$fn}")
                        ->update(['embedding' => $vector]);
                    if ($updated) {
                        $savedCount++;
                    }
                }
                Log::info("Ekstraksi FaceNet sukses untuk {$student->nisn}: {$savedCount} vektor tersimpan.");

                return $savedCount > 0;
            }

            Log::error("Microservice Python gagal mengekstrak wajah {$student->nisn}: ".$response->body());
        } catch (\Throwable $e) {
            Log::warning("Gagal memicu ekstraksi embedding FaceNet untuk {$student->nisn}: ".$e->getMessage());
        }

        return false;
    }
}
