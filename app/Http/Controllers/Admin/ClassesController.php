<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClassesController extends Controller
{
    /**
     * Tampilkan data kelas dalam bentuk tab navigasi per kelas beserta detail kelas dan daftar siswa.
     */
    public function index(Request $request)
    {
        // Pastikan ada tahun ajaran aktif, buat default jika belum ada
        $activeYear = AcademicYear::getActive();
        if (! $activeYear) {
            $activeYear = AcademicYear::create([
                'name' => '2025/2026',
                'semester' => 'Ganjil',
                'is_active' => true,
            ]);
        }

        $academicYears = AcademicYear::orderByDesc('name')->orderBy('semester')->get();
        $teachers = Teacher::with('user')->get();

        // Urutkan kelas berdasarkan jenjang tingkat X, XI, XII dan nama kelas
        $classes = Classes::with(['teacher.user'])
            ->withCount(['students' => function ($q) {
                $q->where('status', 'aktif');
            }])
            ->orderByRaw("CASE
                WHEN grade_level = 'X' THEN 1
                WHEN grade_level = 'XI' THEN 2
                WHEN grade_level = 'XII' THEN 3
                ELSE 4 END")
            ->orderBy('name')
            ->get();

        // Tentukan kelas yang sedang aktif/dipilih
        $selectedClassId = $request->query('class_id');
        $selectedClass = null;

        if ($selectedClassId) {
            $selectedClass = $classes->firstWhere('id', $selectedClassId);
        }

        // Jika tidak ada parameter atau tidak ditemukan, pilih kelas pertama
        if (! $selectedClass && $classes->isNotEmpty()) {
            $selectedClass = $classes->first();
        }

        $students = collect();
        $availableStudents = collect();

        if ($selectedClass) {
            $queryStudents = Student::with(['faces'])
                ->where('class_id', $selectedClass->id);

            if ($request->filled('search_student')) {
                $search = $request->search_student;
                $queryStudents->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            }

            $students = $queryStudents->orderBy('name')->get();

            // Daftar siswa aktif yang belum masuk ke kelas ini (untuk modal tambah siswa)
            $availableStudents = Student::where(function ($q) use ($selectedClass) {
                $q->whereNull('class_id')
                    ->orWhere('class_id', '!=', $selectedClass->id);
            })
                ->where('status', 'aktif')
                ->orderBy('name')
                ->get();
        }

        return view('admin.master-data.classes', compact(
            'classes',
            'selectedClass',
            'students',
            'teachers',
            'activeYear',
            'academicYears',
            'availableStudents'
        ));
    }

    /**
     * Simpan data kelas baru.
     */
    public function store(Request $request)
    {
        $cleanedName = trim($request->name ?? '');
        $request->merge(['name' => $cleanedName]);

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:classes,name',
            ],
            'grade_level' => 'required|in:X,XI,XII',
            'teacher_id' => 'nullable|exists:teachers,id',
        ], [
            'name.required' => 'Nama kelas wajib diisi.',
            'name.unique' => 'Nama kelas sudah ada, silakan gunakan nama yang berbeda.',
            'grade_level.required' => 'Tingkat kelas wajib dipilih.',
            'grade_level.in' => 'Tingkat kelas harus berupa X, XI, atau XII.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Pengecekan case-insensitive jika ada perbedaan huruf besar/kecil
        $alreadyExists = Classes::whereRaw('LOWER(name) = ?', [strtolower($cleanedName)])->exists();
        if ($alreadyExists) {
            return back()->withErrors(['name' => 'Nama kelas sudah ada, silakan gunakan nama yang berbeda.'])->withInput();
        }

        $class = Classes::create([
            'name' => $cleanedName,
            'grade_level' => $request->grade_level,
            'teacher_id' => $request->teacher_id ?: null,
        ]);

        return redirect()->route('admin.classes.index', ['class_id' => $class->id])
            ->with('success', "Kelas {$class->name} berhasil ditambahkan.");
    }

    /**
     * Update data kelas.
     */
    public function update(Request $request, Classes $class)
    {
        $cleanedName = trim($request->name ?? '');
        $request->merge(['name' => $cleanedName]);

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('classes', 'name')->ignore($class->id),
            ],
            'grade_level' => 'required|in:X,XI,XII',
            'teacher_id' => 'nullable|exists:teachers,id',
        ], [
            'name.required' => 'Nama kelas wajib diisi.',
            'name.unique' => 'Nama kelas sudah ada, silakan gunakan nama yang berbeda.',
            'grade_level.required' => 'Tingkat kelas wajib dipilih.',
            'grade_level.in' => 'Tingkat kelas harus berupa X, XI, atau XII.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Pengecekan case-insensitive jika ada perbedaan huruf besar/kecil pada kelas lain
        $alreadyExists = Classes::where('id', '!=', $class->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($cleanedName)])
            ->exists();
        if ($alreadyExists) {
            return back()->withErrors(['name' => 'Nama kelas sudah ada, silakan gunakan nama yang berbeda.'])->withInput();
        }

        $class->update([
            'name' => $cleanedName,
            'grade_level' => $request->grade_level,
            'teacher_id' => $request->teacher_id ?: null,
        ]);

        return redirect()->route('admin.classes.index', ['class_id' => $class->id])
            ->with('success', "Data kelas {$class->name} berhasil diperbarui.");
    }

    /**
     * Update cepat wali kelas.
     */
    public function updateTeacher(Request $request, Classes $class)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $class->update([
            'teacher_id' => $request->teacher_id ?: null,
        ]);

        $teacherName = $class->teacher ? $class->teacher->user->name : 'Tanpa Wali Kelas';

        return redirect()->route('admin.classes.index', ['class_id' => $class->id])
            ->with('success', "Wali kelas {$class->name} berhasil diubah menjadi {$teacherName}.");
    }

    /**
     * Tambahkan siswa ke dalam kelas terpilih.
     */
    public function addStudents(Request $request, Classes $class)
    {
        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ], [
            'student_ids.required' => 'Pilih minimal satu siswa untuk dimasukkan ke dalam kelas.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $activeYear = AcademicYear::getActive();

        DB::transaction(function () use ($request, $class, $activeYear) {
            foreach ($request->student_ids as $studentId) {
                $student = Student::find($studentId);
                if ($student) {
                    $student->update([
                        'class_id' => $class->id,
                        'status' => 'aktif',
                    ]);

                    if ($activeYear) {
                        StudentClassHistory::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'academic_year_id' => $activeYear->id,
                            ],
                            [
                                'class_id' => $class->id,
                                'status' => 'aktif',
                            ]
                        );
                    }
                }
            }
        });

        $count = count($request->student_ids);

        return redirect()->route('admin.classes.index', ['class_id' => $class->id])
            ->with('success', "Berhasil menambahkan {$count} siswa ke kelas {$class->name}.");
    }

    /**
     * Keluarkan siswa dari kelas terpilih.
     */
    public function removeStudent(Classes $class, Student $student)
    {
        $student->update([
            'class_id' => null,
        ]);

        return redirect()->route('admin.classes.index', ['class_id' => $class->id])
            ->with('success', "Siswa {$student->name} berhasil dikeluarkan dari kelas {$class->name}.");
    }

    /**
     * Hapus data kelas.
     */
    public function destroy(Classes $class)
    {
        $className = $class->name;

        // Lepaskan siswa dari kelas ini agar tidak terhapus cascade
        Student::where('class_id', $class->id)->update(['class_id' => null]);

        $class->delete();

        return redirect()->route('admin.classes.index')
            ->with('success', "Data kelas {$className} berhasil dihapus.");
    }

    /**
     * Simpan tahun ajaran baru.
     */
    public function storeAcademicYear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'semester' => 'required|in:Ganjil,Genap',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $isActive = $request->boolean('is_active');

        if ($isActive) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        $academicYear = AcademicYear::create([
            'name' => $request->name,
            'semester' => $request->semester,
            'is_active' => $isActive,
        ]);

        return back()->with('success', "Tahun ajaran {$academicYear->name} ({$academicYear->semester}) berhasil ditambahkan.");
    }

    /**
     * Jadikan tahun ajaran terpilih sebagai tahun ajaran aktif.
     */
    public function setActiveAcademicYear(AcademicYear $academicYear)
    {
        AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
        $academicYear->update(['is_active' => true]);

        return back()->with('success', "Tahun ajaran aktif berhasil diubah menjadi {$academicYear->name} ({$academicYear->semester}).");
    }

    /**
     * Halaman / Tampilan Preview Kenaikan Kelas Massal.
     */
    public function promotionPreview(Request $request)
    {
        $activeYear = AcademicYear::getActive();
        $academicYears = AcademicYear::orderByDesc('name')->orderBy('semester')->get();

        $classes = Classes::with('teacher.user')
            ->orderByRaw("CASE
                WHEN grade_level = 'X' THEN 1
                WHEN grade_level = 'XI' THEN 2
                WHEN grade_level = 'XII' THEN 3
                ELSE 4 END")
            ->orderBy('name')
            ->get();

        $sourceClassId = $request->query('source_class_id');
        $targetAcademicYearId = $request->query('target_academic_year_id');

        // Target academic year default adalah tahun berikutnya jika ada
        $targetAcademicYear = null;
        if ($targetAcademicYearId) {
            $targetAcademicYear = AcademicYear::find($targetAcademicYearId);
        } else {
            $targetAcademicYear = AcademicYear::where('id', '!=', $activeYear?->id)->orderBy('id')->first();
        }

        // Ambil siswa berdasarkan kelas asal yang dipilih
        $studentsQuery = Student::with(['classes'])
            ->whereNotNull('class_id')
            ->where('status', 'aktif');

        if ($sourceClassId && $sourceClassId !== 'all') {
            $studentsQuery->where('class_id', $sourceClassId);
        }

        $students = $studentsQuery->orderBy('class_id')->orderBy('name')->get();

        // Pisahkan daftar kelas berdasarkan jenjang untuk kemudahan rekomendasi & select option
        $classesByGrade = [
            'X' => $classes->where('grade_level', 'X'),
            'XI' => $classes->where('grade_level', 'XI'),
            'XII' => $classes->where('grade_level', 'XII'),
        ];

        // Buat data preview dengan rekomendasi aksi otomatis
        $previewData = $students->map(function ($student) use ($classesByGrade) {
            $currentGrade = $student->classes?->grade_level;
            $currentClassName = $student->classes?->name ?? '-';

            $defaultAction = 'naik';
            $recommendedTargetClassId = null;

            if ($currentGrade === 'XII') {
                // Sesuai butir 10: Siswa kelas XII tidak boleh dipindahkan ke kelas berikutnya, sediakan status Lulus
                $defaultAction = 'lulus';
                $recommendedTargetClassId = null;
            } elseif ($currentGrade === 'XI') {
                $defaultAction = 'naik';
                // Rekomendasikan kelas tingkat XII yang namanya bersesuaian jika ada
                $targetGradeClasses = $classesByGrade['XII'];
                $match = $targetGradeClasses->first(function ($cls) use ($currentClassName) {
                    $suffix = trim(str_replace('XI', '', $currentClassName));

                    return str_contains($cls->name, $suffix);
                });
                $recommendedTargetClassId = $match ? $match->id : $targetGradeClasses->first()?->id;
            } elseif ($currentGrade === 'X') {
                $defaultAction = 'naik';
                // Rekomendasikan kelas tingkat XI yang namanya bersesuaian jika ada
                $targetGradeClasses = $classesByGrade['XI'];
                $match = $targetGradeClasses->first(function ($cls) use ($currentClassName) {
                    $suffix = trim(str_replace('X', '', $currentClassName));

                    return str_contains($cls->name, $suffix);
                });
                $recommendedTargetClassId = $match ? $match->id : $targetGradeClasses->first()?->id;
            }

            return [
                'student' => $student,
                'current_grade' => $currentGrade,
                'current_class_name' => $currentClassName,
                'default_action' => $defaultAction,
                'recommended_target_class_id' => $recommendedTargetClassId,
            ];
        });

        return view('admin.master-data.classes-promotion', compact(
            'activeYear',
            'academicYears',
            'targetAcademicYear',
            'classes',
            'classesByGrade',
            'sourceClassId',
            'previewData'
        ));
    }

    /**
     * Proses eksekusi kenaikan kelas massal secara aman dengan histori data lengkap.
     */
    public function promoteProcess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_academic_year_id' => 'required|exists:academic_years,id',
            'promotions' => 'required|array',
            'promotions.*.student_id' => 'required|exists:students,id',
            'promotions.*.action' => 'required|in:naik,tinggal,lulus',
            'promotions.*.target_class_id' => 'nullable|exists:classes,id',
        ], [
            'target_academic_year_id.required' => 'Tahun ajaran tujuan wajib dipilih.',
            'promotions.required' => 'Tidak ada siswa yang dipilih untuk diproses kenaikan kelas.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $targetAcademicYear = AcademicYear::findOrFail($request->target_academic_year_id);
        $activeYear = AcademicYear::getActive();

        $processedCount = 0;

        DB::transaction(function () use ($request, $targetAcademicYear, $activeYear, &$processedCount) {
            foreach ($request->promotions as $item) {
                // Periksa apakah checkbox siswa ini dicentang (jika ada flag selected)
                if (isset($item['selected']) && ! $item['selected']) {
                    continue;
                }

                $student = Student::find($item['student_id']);
                if (! $student) {
                    continue;
                }

                $action = $item['action'];
                $targetClassId = ! empty($item['target_class_id']) ? $item['target_class_id'] : null;
                $oldClassId = $student->class_id;

                // 1. Amankan histori tahun ajaran lama agar histori absensi lama tetap valid
                if ($activeYear && $oldClassId) {
                    $historyStatus = match ($action) {
                        'lulus' => 'lulus',
                        'naik' => 'naik_kelas',
                        'tinggal' => 'tinggal_kelas',
                        default => 'aktif',
                    };

                    StudentClassHistory::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'academic_year_id' => $activeYear->id,
                        ],
                        [
                            'class_id' => $oldClassId,
                            'status' => $historyStatus,
                        ]
                    );
                }

                // 2. Terapkan status baru pada siswa dan buat histori tahun ajaran baru
                if ($action === 'lulus') {
                    // Kelas XII menjadi lulus
                    $student->update([
                        'status' => 'lulus',
                        'class_id' => null,
                    ]);
                } elseif ($action === 'naik') {
                    // Naik ke kelas berikutnya
                    $student->update([
                        'status' => 'aktif',
                        'class_id' => $targetClassId,
                    ]);

                    if ($targetClassId) {
                        StudentClassHistory::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'academic_year_id' => $targetAcademicYear->id,
                            ],
                            [
                                'class_id' => $targetClassId,
                                'status' => 'aktif',
                            ]
                        );
                    }
                } elseif ($action === 'tinggal') {
                    // Tinggal di kelas saat ini
                    $student->update([
                        'status' => 'aktif',
                        'class_id' => $oldClassId,
                    ]);

                    if ($oldClassId) {
                        StudentClassHistory::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'academic_year_id' => $targetAcademicYear->id,
                            ],
                            [
                                'class_id' => $oldClassId,
                                'status' => 'aktif',
                            ]
                        );
                    }
                }

                $processedCount++;
            }

            // Jika opsi aktifkan tahun ajaran dicentang
            if ($request->boolean('set_target_as_active')) {
                AcademicYear::where('id', '!=', $targetAcademicYear->id)->update(['is_active' => false]);
                $targetAcademicYear->update(['is_active' => true]);
            }
        });

        return redirect()->route('admin.classes.index')
            ->with('success', "Proses kenaikan kelas berhasil diproses untuk {$processedCount} siswa ke Tahun Ajaran {$targetAcademicYear->name} ({$targetAcademicYear->semester}).");
    }
}
