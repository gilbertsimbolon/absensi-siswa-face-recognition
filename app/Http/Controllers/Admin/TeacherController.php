<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherFace;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Tampilkan data guru beserta relasi foto wajah, pencarian, dan pagination.
     */
    public function index(Request $request)
    {
        $query = Teacher::with(['user', 'classes', 'faces'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $teachers = $query->paginate(10)->withQueryString();

        return view('admin.master-data.data-guru', compact('teachers'));
    }

    /**
     * Tambah data guru beserta 3 sampel foto wajah.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'nip' => 'required|string|max:50|unique:teachers,nip',
            'phone' => 'required|string|max:30',
            'photo_depan' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'photo_kanan' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'photo_kiri' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        ]);

        if ($validate->fails()) {
            return back()->withErrors($validate)->withInput();
        }

        $teacher = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            $user->assignRole('teacher');

            return Teacher::create([
                'user_id' => $user->id,
                'nip' => $request->nip,
                'phone' => $request->phone,
            ]);
        });

        // Simpan foto wajah jika diunggah
        $this->saveTeacherPhotos($request, $teacher);

        return redirect()->route('admin.teacher.index')->with('success', 'Data guru dan sampel foto wajah berhasil ditambahkan.');
    }

    /**
     * Update data guru dan foto wajah.
     */
    public function update(Request $request, Teacher $teacher)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$teacher->user_id,
            'password' => 'nullable|min:8',
            'nip' => 'required|string|max:50|unique:teachers,nip,'.$teacher->id,
            'phone' => 'required|string|max:30',
            'photo_depan' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'photo_kanan' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'photo_kiri' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
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

        // Update foto wajah jika diunggah baru
        $this->saveTeacherPhotos($request, $teacher, true);

        return redirect()->route('admin.teacher.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    /**
     * Hapus data guru beserta dataset foto di storage dan folder python.
     */
    public function destroy(Teacher $teacher)
    {
        // 1. Hapus setiap berkas fisik foto guru di storage jika ada
        foreach ($teacher->faces as $face) {
            if ($face->file_path && Storage::disk('public')->exists($face->file_path)) {
                Storage::disk('public')->delete($face->file_path);
            }
        }

        // 2. Hapus direktori teacher_faces/{nip} di storage
        $storagePath = 'teacher_faces/'.$teacher->nip;
        if (Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->deleteDirectory($storagePath);
        }

        // 3. Hapus folder dataset siswa/guru di python-service jika ada
        $pythonDatasetPath = base_path('python-service/dataset/'.$teacher->nip);
        if (File::exists($pythonDatasetPath)) {
            File::deleteDirectory($pythonDatasetPath);
        }

        // 4. Hapus record relasi teacher_faces
        $teacher->faces()->delete();

        // 5. Hapus teacher dan user akun
        DB::transaction(function () use ($teacher) {
            $user = $teacher->user;
            $teacher->delete();
            if ($user) {
                $user->delete();
            }
        });

        return redirect()->route('admin.teacher.index')->with('success', 'Data guru dan seluruh dataset foto berhasil dihapus.');
    }

    /**
     * Helper penyimpanan foto wajah guru ke storage public dan sinkronisasi ke python-service/dataset.
     */
    private function saveTeacherPhotos(Request $request, Teacher $teacher, bool $isUpdate = false): void
    {
        $photoInputs = [
            'photo_depan' => 'Tampak Depan',
            'photo_kanan' => 'Serong Kanan',
            'photo_kiri' => 'Serong Kiri',
        ];

        // Folder tujuan sinkronisasi DeepFace Python
        $pythonDatasetDir = base_path('python-service/dataset/'.$teacher->nip);
        if (! File::exists($pythonDatasetDir)) {
            File::makeDirectory($pythonDatasetDir, 0755, true);
        }

        foreach ($photoInputs as $inputKey => $label) {
            if ($request->hasFile($inputKey)) {
                $file = $request->file($inputKey);
                $fileName = time().'_'.$inputKey.'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('teacher_faces/'.$teacher->nip, $fileName, 'public');

                // Salin ke folder dataset Python DeepFace
                $pythonFilePath = $pythonDatasetDir.'/'.$fileName;
                File::copy(storage_path('app/public/'.$path), $pythonFilePath);

                if ($isUpdate) {
                    $existingFace = TeacherFace::where('teacher_id', $teacher->id)->where('label', $label)->first();
                    if ($existingFace) {
                        Storage::disk('public')->delete($existingFace->file_path);
                        $existingFace->update(['file_path' => $path]);

                        continue;
                    }
                }

                TeacherFace::create([
                    'teacher_id' => $teacher->id,
                    'file_path' => $path,
                    'label' => $label,
                ]);
            }
        }
    }
}
