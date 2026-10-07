<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    //
    /**
     * Dapatkan daftar kelas yang berhak diakses oleh user (Admin: semua, Guru: hanya kelas binaannya).
     */
    protected function getAccessibleClasses()
    {
        $user = Auth::user();
        $isTeacher = $user && $user->hasRole('teacher') && ! $user->hasRole('admin');
        $teacherRecord = $isTeacher ? $user->teacher : null;

        $classesQuery = Classes::with(['teacher.user']);

        if ($isTeacher) {
            if ($teacherRecord) {
                $classesQuery->where('teacher_id', $teacherRecord->id);
            } else {
                $classesQuery->whereRaw('1 = 0');
            }
        } else {
            $classesQuery->orderByRaw("CASE
                WHEN grade_level = 'X' THEN 1
                WHEN grade_level = 'XI' THEN 2
                WHEN grade_level = 'XII' THEN 3
                ELSE 4 END")
                ->orderBy('name');
        }

        return $classesQuery->get();
    }

    /**
     * Halaman Presensi Harian.
     */
    public function index(Request $request)
    {
        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        $classes = $this->getAccessibleClasses();

        // Tentukan kelas yang dipilih
        $selectedClassId = $request->query('class_id', $classes->first()?->id);
        $selectedClass = $classes->firstWhere('id', (int) $selectedClassId);

        // Tentukan tanggal yang dipilih (default: hari ini)
        $selectedDate = $request->query('date', now()->format('Y-m-d'));

        // Parameter sorting dan filter status
        $sortBy = $request->query('sort_by', 'name');
        $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $statusFilter = $request->query('status', 'all');

        $studentsData = collect();
        $summary = [
            'total' => 0,
            'hadir' => 0,
            'terlambat' => 0,
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
            'belum' => 0,
        ];

        if ($selectedClass) {
            // Ambil semua siswa aktif di kelas ini
            $studentsQuery = Student::where('class_id', $selectedClass->id)
                ->where('status', 'aktif');

            if ($sortBy === 'nisn') {
                $studentsQuery->orderBy('nisn', $sortDir);
            } else {
                $studentsQuery->orderBy('name', $sortDir);
            }

            $students = $studentsQuery->get();
            $summary['total'] = $students->count();

            // Ambil record presensi pada tanggal tersebut untuk kelas ini
            $attendances = Attendance::where('class_id', $selectedClass->id)
                ->whereDate('date', $selectedDate)
                ->get()
                ->keyBy('student_id');

            foreach ($students as $student) {
                $att = $attendances->get($student->id);
                $status = $att ? $att->status : 'belum';

                if ($att) {
                    if (isset($summary[$status])) {
                        $summary[$status]++;
                    }
                } else {
                    $summary['belum']++;
                }

                $student->attendance = $att;
                $student->daily_status = $status;

                // Terapkan filter status jika ada
                if ($statusFilter === 'all' || $statusFilter === $status) {
                    $studentsData->push($student);
                }
            }
        }

        return view('admin.attendance.index', compact(
            'activeYear',
            'classes',
            'selectedClass',
            'selectedDate',
            'studentsData',
            'summary',
            'sortBy',
            'sortDir',
            'statusFilter'
        ));
    }

    /**
     * Halaman Rekapitulasi Presensi (Matriks Bulanan & Statistik Kehadiran).
     */
    public function recap(Request $request)
    {
        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        $classes = $this->getAccessibleClasses();

        $selectedClassId = $request->query('class_id', $classes->first()?->id);
        $selectedClass = $classes->firstWhere('id', (int) $selectedClassId);

        // Periode: 'weekly' (default), 'monthly', 'yearly'
        $period = $request->query('period', 'weekly');
        if (! in_array($period, ['weekly', 'monthly', 'yearly'])) {
            $period = 'weekly';
        }

        $selectedDate = $request->query('date', now()->format('Y-m-d'));
        $selectedMonth = (int) $request->query('month', now()->month);
        $selectedYear = (int) $request->query('year', now()->year);

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $daysInfo = [];
        $monthsInfo = [];
        $effectiveDaysCount = 0;
        $today = now();
        $weekStart = null;
        $weekEnd = null;

        if ($period === 'weekly') {
            // Mode Mingguan: Tampilkan minggu yang sedang berjalan (Senin s/d Sabtu)
            $refCarbon = Carbon::parse($selectedDate);
            $weekStart = $refCarbon->copy()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $refCarbon->copy()->startOfWeek(Carbon::MONDAY)->addDays(5); // Senin s/d Sabtu (6 hari)

            for ($i = 0; $i < 6; $i++) {
                $curDate = $weekStart->copy()->addDays($i);
                $isPastOrToday = $curDate->lte($today);

                if ($isPastOrToday) {
                    $effectiveDaysCount++;
                }

                $daysInfo[] = [
                    'key' => $curDate->format('Y-m-d'),
                    'day_name' => $curDate->locale('id')->translatedFormat('l'), // Full: Senin, Selasa, Rabu, dll
                    'date_label' => $curDate->locale('id')->translatedFormat('d F Y'),
                    'short_label' => $curDate->locale('id')->translatedFormat('d M'),
                    'is_today' => $curDate->isToday(),
                    'is_past' => $isPastOrToday,
                ];
            }
        } elseif ($period === 'monthly') {
            // Mode Bulanan: Tampilkan hari-hari dalam bulan yang dipilih
            $monthCarbon = Carbon::createFromDate($selectedYear, $selectedMonth, 1);
            $daysInMonth = $monthCarbon->daysInMonth;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dayDate = Carbon::createFromDate($selectedYear, $selectedMonth, $d);
                $isWeekend = $dayDate->isSunday();
                $isPastOrToday = $dayDate->lte($today);

                if (! $isWeekend && $isPastOrToday) {
                    $effectiveDaysCount++;
                }

                $daysInfo[] = [
                    'key' => $dayDate->format('Y-m-d'),
                    'day' => $d,
                    'day_name' => $dayDate->locale('id')->translatedFormat('l'), // Full: Senin, Selasa, dst
                    'date_label' => $dayDate->locale('id')->translatedFormat('d F Y'),
                    'is_weekend' => $isWeekend,
                    'is_today' => $dayDate->isToday(),
                    'is_past' => $isPastOrToday,
                ];
            }
        } else {
            // Mode Tahunan: Tampilkan rekap 12 bulan
            for ($m = 1; $m <= 12; $m++) {
                $monthsInfo[] = [
                    'month_num' => $m,
                    'name' => $monthNames[$m],
                ];
            }
        }

        $recapStudents = collect();
        $classTotalHadir = 0;
        $classTotalTerlambat = 0;
        $classTotalSakit = 0;
        $classTotalIzin = 0;
        $classTotalAlpa = 0;

        if ($selectedClass) {
            $students = Student::where('class_id', $selectedClass->id)
                ->orderBy('name')
                ->get();

            $attendancesQuery = Attendance::where('class_id', $selectedClass->id);

            if ($period === 'weekly') {
                $attendancesQuery->whereBetween('date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')]);
            } elseif ($period === 'monthly') {
                $attendancesQuery->whereYear('date', $selectedYear)->whereMonth('date', $selectedMonth);
            } else {
                $attendancesQuery->whereYear('date', $selectedYear);
            }

            $attendances = $attendancesQuery->get()->groupBy('student_id');

            foreach ($students as $student) {
                $studentAtts = $attendances->get($student->id, collect());
                $matrix = [];
                $monthlyCounts = [];
                $h = 0;
                $t = 0;
                $s = 0;
                $i = 0;
                $a = 0;

                if ($period === 'weekly' || $period === 'monthly') {
                    foreach ($studentAtts as $att) {
                        $dateStr = Carbon::parse($att->date)->format('Y-m-d');
                        $matrix[$dateStr] = $att;

                        match ($att->status) {
                            Attendance::STATUS_HADIR => $h++,
                            Attendance::STATUS_TERLAMBAT => $t++,
                            Attendance::STATUS_SAKIT => $s++,
                            Attendance::STATUS_IZIN => $i++,
                            Attendance::STATUS_ALPA => $a++,
                            default => null,
                        };
                    }
                } else {
                    for ($m = 1; $m <= 12; $m++) {
                        $monthlyCounts[$m] = ['h' => 0, 't' => 0, 's' => 0, 'i' => 0, 'a' => 0, 'total' => 0];
                    }

                    foreach ($studentAtts as $att) {
                        $mNum = (int) Carbon::parse($att->date)->format('n');
                        match ($att->status) {
                            Attendance::STATUS_HADIR => [$h++, $monthlyCounts[$mNum]['h']++],
                            Attendance::STATUS_TERLAMBAT => [$t++, $monthlyCounts[$mNum]['t']++],
                            Attendance::STATUS_SAKIT => [$s++, $monthlyCounts[$mNum]['s']++],
                            Attendance::STATUS_IZIN => [$i++, $monthlyCounts[$mNum]['i']++],
                            Attendance::STATUS_ALPA => [$a++, $monthlyCounts[$mNum]['a']++],
                            default => null,
                        };
                        $monthlyCounts[$mNum]['total']++;
                    }
                }

                $totalPresence = $h + $t;
                $percent = 0;
                if ($period === 'weekly' && $effectiveDaysCount > 0) {
                    $percent = round(($totalPresence / $effectiveDaysCount) * 100, 1);
                } elseif ($period === 'monthly' && $effectiveDaysCount > 0) {
                    $percent = round(($totalPresence / $effectiveDaysCount) * 100, 1);
                } elseif ($period === 'yearly') {
                    $totalRecords = $h + $t + $s + $i + $a;
                    $percent = $totalRecords > 0 ? round(($totalPresence / $totalRecords) * 100, 1) : 0;
                }

                $classTotalHadir += $h;
                $classTotalTerlambat += $t;
                $classTotalSakit += $s;
                $classTotalIzin += $i;
                $classTotalAlpa += $a;

                $student->matrix = $matrix;
                $student->monthly_counts = $monthlyCounts;
                $student->count_h = $h;
                $student->count_t = $t;
                $student->count_s = $s;
                $student->count_i = $i;
                $student->count_a = $a;
                $student->presence_percent = min($percent, 100);

                $recapStudents->push($student);
            }
        }

        $classAvgPercent = $recapStudents->isNotEmpty()
            ? round($recapStudents->avg('presence_percent'), 1)
            : 0;

        return view('admin.attendance.recap', compact(
            'activeYear',
            'classes',
            'selectedClass',
            'period',
            'selectedDate',
            'selectedMonth',
            'selectedYear',
            'weekStart',
            'weekEnd',
            'daysInfo',
            'monthsInfo',
            'effectiveDaysCount',
            'recapStudents',
            'classAvgPercent',
            'classTotalHadir',
            'classTotalTerlambat',
            'classTotalSakit',
            'classTotalIzin',
            'classTotalAlpa',
            'monthNames'
        ));
    }

    /**
     * Tampilan Cetak / Print Rekapitulasi Presensi Bulanan.
     */
    public function printRecap(Request $request)
    {
        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        $classes = $this->getAccessibleClasses();

        $selectedClassId = $request->query('class_id', $classes->first()?->id);
        $selectedClass = $classes->firstWhere('id', (int) $selectedClassId);

        abort_if(! $selectedClass, 404, 'Kelas tidak ditemukan.');

        $period = $request->query('period', 'weekly');
        if (! in_array($period, ['weekly', 'monthly', 'yearly'])) {
            $period = 'weekly';
        }

        $selectedDate = $request->query('date', now()->format('Y-m-d'));
        $selectedMonth = (int) $request->query('month', now()->month);
        $selectedYear = (int) $request->query('year', now()->year);

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $daysInfo = [];
        $monthsInfo = [];
        $effectiveDaysCount = 0;
        $today = now();
        $weekStart = null;
        $weekEnd = null;

        if ($period === 'weekly') {
            $refCarbon = Carbon::parse($selectedDate);
            $weekStart = $refCarbon->copy()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $refCarbon->copy()->startOfWeek(Carbon::MONDAY)->addDays(5);

            for ($i = 0; $i < 6; $i++) {
                $curDate = $weekStart->copy()->addDays($i);
                $isPastOrToday = $curDate->lte($today);

                if ($isPastOrToday) {
                    $effectiveDaysCount++;
                }

                $daysInfo[] = [
                    'key' => $curDate->format('Y-m-d'),
                    'day_name' => $curDate->locale('id')->translatedFormat('l'),
                    'short_label' => $curDate->locale('id')->translatedFormat('d M'),
                ];
            }
        } elseif ($period === 'monthly') {
            $monthCarbon = Carbon::createFromDate($selectedYear, $selectedMonth, 1);
            $daysInMonth = $monthCarbon->daysInMonth;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dayDate = Carbon::createFromDate($selectedYear, $selectedMonth, $d);
                $isWeekend = $dayDate->isSunday();
                $isPastOrToday = $dayDate->lte($today);

                if (! $isWeekend && $isPastOrToday) {
                    $effectiveDaysCount++;
                }

                $daysInfo[] = [
                    'key' => $dayDate->format('Y-m-d'),
                    'day' => $d,
                    'day_name' => $dayDate->locale('id')->translatedFormat('l'),
                    'is_weekend' => $isWeekend,
                ];
            }
        } else {
            for ($m = 1; $m <= 12; $m++) {
                $monthsInfo[] = [
                    'month_num' => $m,
                    'name' => $monthNames[$m],
                ];
            }
        }

        $students = Student::where('class_id', $selectedClass->id)
            ->orderBy('name')
            ->get();

        $attendancesQuery = Attendance::where('class_id', $selectedClass->id);
        if ($period === 'weekly') {
            $attendancesQuery->whereBetween('date', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')]);
        } elseif ($period === 'monthly') {
            $attendancesQuery->whereYear('date', $selectedYear)->whereMonth('date', $selectedMonth);
        } else {
            $attendancesQuery->whereYear('date', $selectedYear);
        }

        $attendances = $attendancesQuery->get()->groupBy('student_id');

        $recapStudents = collect();
        foreach ($students as $student) {
            $studentAtts = $attendances->get($student->id, collect());
            $matrix = [];
            $monthlyCounts = [];
            $h = 0;
            $t = 0;
            $s = 0;
            $i = 0;
            $a = 0;

            if ($period === 'weekly' || $period === 'monthly') {
                foreach ($studentAtts as $att) {
                    $dateStr = Carbon::parse($att->date)->format('Y-m-d');
                    $matrix[$dateStr] = $att;

                    match ($att->status) {
                        Attendance::STATUS_HADIR => $h++,
                        Attendance::STATUS_TERLAMBAT => $t++,
                        Attendance::STATUS_SAKIT => $s++,
                        Attendance::STATUS_IZIN => $i++,
                        Attendance::STATUS_ALPA => $a++,
                        default => null,
                    };
                }
            } else {
                for ($m = 1; $m <= 12; $m++) {
                    $monthlyCounts[$m] = ['h' => 0, 't' => 0, 's' => 0, 'i' => 0, 'a' => 0, 'total' => 0];
                }

                foreach ($studentAtts as $att) {
                    $mNum = (int) Carbon::parse($att->date)->format('n');
                    match ($att->status) {
                        Attendance::STATUS_HADIR => [$h++, $monthlyCounts[$mNum]['h']++],
                        Attendance::STATUS_TERLAMBAT => [$t++, $monthlyCounts[$mNum]['t']++],
                        Attendance::STATUS_SAKIT => [$s++, $monthlyCounts[$mNum]['s']++],
                        Attendance::STATUS_IZIN => [$i++, $monthlyCounts[$mNum]['i']++],
                        Attendance::STATUS_ALPA => [$a++, $monthlyCounts[$mNum]['a']++],
                        default => null,
                    };
                    $monthlyCounts[$mNum]['total']++;
                }
            }

            $totalPresence = $h + $t;
            $percent = 0;
            if ($period === 'weekly' && $effectiveDaysCount > 0) {
                $percent = round(($totalPresence / $effectiveDaysCount) * 100, 1);
            } elseif ($period === 'monthly' && $effectiveDaysCount > 0) {
                $percent = round(($totalPresence / $effectiveDaysCount) * 100, 1);
            } elseif ($period === 'yearly') {
                $totalRecords = $h + $t + $s + $i + $a;
                $percent = $totalRecords > 0 ? round(($totalPresence / $totalRecords) * 100, 1) : 0;
            }

            $student->matrix = $matrix;
            $student->monthly_counts = $monthlyCounts;
            $student->count_h = $h;
            $student->count_t = $t;
            $student->count_s = $s;
            $student->count_i = $i;
            $student->count_a = $a;
            $student->presence_percent = min($percent, 100);

            $recapStudents->push($student);
        }

        return view('admin.attendance.print-recap', compact(
            'activeYear',
            'selectedClass',
            'period',
            'selectedDate',
            'selectedMonth',
            'selectedYear',
            'weekStart',
            'weekEnd',
            'daysInfo',
            'monthsInfo',
            'effectiveDaysCount',
            'recapStudents',
            'monthNames'
        ));
    }

    /**
     * Catat atau Perbarui Presensi Siswa Secara Manual.
     */
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'status' => 'required|in:hadir,terlambat,sakit,izin,alpa',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'Gagal validasi data presensi.');
        }

        $student = Student::findOrFail($request->student_id);

        // Validasi hak akses guru
        $user = Auth::user();
        if ($user && $user->hasRole('teacher') && ! $user->hasRole('admin')) {
            $teacher = $user->teacher;
            $class = Classes::where('teacher_id', $teacher?->id)->first();
            if (! $class || $student->class_id !== $class->id) {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk siswa ini.');
            }
        }

        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        if (! $activeYear) {
            return redirect()->back()->with('error', 'Tahun ajaran aktif belum ditentukan.');
        }

        $checkInTime = $request->check_in_time ? "{$request->check_in_time}:00" : null;
        if (! $checkInTime && in_array($request->status, ['hadir', 'terlambat'])) {
            $checkInTime = now()->format('H:i:s');
        }

        $checkOutTime = $request->check_out_time ? "{$request->check_out_time}:00" : null;

        $targetDate = Carbon::parse($request->date)->format('Y-m-d');

        Attendance::updateOrCreate(
            [
                'student_id' => $student->id,
                'date' => $targetDate,
            ],
            [
                'class_id' => $student->class_id,
                'academic_year_id' => $activeYear->id,
                'status' => $request->status,
                'method' => Attendance::METHOD_MANUAL,
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'notes' => $request->notes,
            ]
        );

        return redirect()->back()->with('success', 'Berhasil');
    }

    /**
     * Tandai semua siswa yang belum absen pada tanggal tertentu (misal ditandai Alpa atau Hadir).
     */
    public function bulkMark(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'target_status' => 'required|in:alpa,hadir',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'Gagal memproses presensi massal.');
        }

        $class = Classes::findOrFail($request->class_id);

        // Validasi hak akses guru
        $user = Auth::user();
        if ($user && $user->hasRole('teacher') && ! $user->hasRole('admin')) {
            $teacher = $user->teacher;
            if ($class->teacher_id !== $teacher?->id) {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk kelas ini.');
            }
        }

        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        $students = Student::where('class_id', $class->id)->where('status', 'aktif')->get();

        $targetDate = Carbon::parse($request->date)->format('Y-m-d');
        $existingStudentIds = Attendance::where('class_id', $class->id)
            ->whereDate('date', $targetDate)
            ->pluck('student_id')
            ->toArray();

        $targetStatus = $request->target_status;
        $nowTime = now()->format('H:i:s');

        $insertedCount = 0;
        foreach ($students as $student) {
            if (! in_array($student->id, $existingStudentIds)) {
                Attendance::create([
                    'student_id' => $student->id,
                    'class_id' => $class->id,
                    'academic_year_id' => $activeYear->id,
                    'date' => $targetDate,
                    'status' => $targetStatus,
                    'method' => Attendance::METHOD_MANUAL,
                    'check_in_time' => $targetStatus === 'hadir' ? $nowTime : null,
                    'notes' => $targetStatus === 'alpa' ? 'Ditandai alpa massal' : 'Presensi massal',
                ]);
                $insertedCount++;
            }
        }

        return redirect()->back()->with('success', 'Berhasil');
    }

    /**
     * Hapus / Reset data presensi siswa pada tanggal tertentu.
     */
    public function destroy(Attendance $attendance)
    {
        $user = Auth::user();
        if ($user && $user->hasRole('teacher') && ! $user->hasRole('admin')) {
            $teacher = $user->teacher;
            $class = Classes::where('teacher_id', $teacher?->id)->first();
            if (! $class || $attendance->class_id !== $class->id) {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak akses.');
            }
        }

        $attendance->delete();

        return redirect()->back()->with('success', 'Berhasil');
    }
}
