<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman Dashboard/Beranda.
     */
    public function index()
    {
        $user = Auth::user();
        $userName = $user?->name ?? 'Pengguna';

        // 1. Ucapan Salam Berdasarkan Waktu Lokal (WITA)
        $hour = Carbon::now()->hour;
        if ($hour >= 5 && $hour < 11) {
            $greeting = 'Selamat Pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            $greeting = 'Selamat Siang';
        } elseif ($hour >= 15 && $hour < 18) {
            $greeting = 'Selamat Sore';
        } else {
            $greeting = 'Selamat Malam';
        }

        // 2. Jam Masuk & Batas Absensi (Toleransi Masuk)
        $scheduleClass = null;
        if ($user && $user->hasRole('teacher') && $user->teacher) {
            $scheduleClass = Classes::where('teacher_id', $user->teacher->id)->first();
        }
        if (! $scheduleClass) {
            $scheduleClass = Classes::whereNotNull('jam_masuk')->first() ?? Classes::first();
        }

        $jamMasuk = $scheduleClass?->jam_masuk
            ? Carbon::parse($scheduleClass->jam_masuk)->format('H:i')
            : '07:00';

        $batasAbsensi = $scheduleClass?->batas_terlambat
            ? Carbon::parse($scheduleClass->batas_terlambat)->format('H:i')
            : '07:15';

        // 3. Statistik Jumlah (Siswa, Guru, Kelas)
        $totalStudents = Student::count();
        $totalTeachers = Teacher::count();
        $totalClasses = Classes::count();

        // 4. Data Presensi Hari Ini
        $today = Carbon::today()->format('Y-m-d');
        $todayAttendances = Attendance::whereDate('date', $today)->get();

        $hadirToday = $todayAttendances->where('status', Attendance::STATUS_HADIR)->count();
        $terlambatToday = $todayAttendances->where('status', Attendance::STATUS_TERLAMBAT)->count();
        $sakitToday = $todayAttendances->where('status', Attendance::STATUS_SAKIT)->count();
        $izinToday = $todayAttendances->where('status', Attendance::STATUS_IZIN)->count();
        $alpaToday = $todayAttendances->where('status', Attendance::STATUS_ALPA)->count();
        $totalPresensiToday = $todayAttendances->count();

        // Persentase Kehadiran (Hadir + Terlambat terhadap Total Siswa)
        $attendancePercentage = $totalStudents > 0
            ? round((($hadirToday + $terlambatToday) / $totalStudents) * 100, 1)
            : 0;

        // 5. Data Grafik Kehadiran (7 Hari Kalender Terakhir - Dioptimalkan dalam 1 kueri)
        $chartCategories = [];
        $chartHadir = [];
        $chartTerlambat = [];
        $chartTidakHadir = [];

        $startDate = Carbon::today()->subDays(6)->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        $chartAttendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('date, status, count(*) as total')
            ->groupBy('date', 'status')
            ->get();

        $attendancesGrouped = [];
        foreach ($chartAttendances as $record) {
            $dateKey = Carbon::parse($record->date)->format('Y-m-d');
            $attendancesGrouped[$dateKey][$record->status] = (int) $record->total;
        }

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $dateLabel = $date->translatedFormat('D, d M');

            $chartCategories[] = $dateLabel;

            $dayStats = $attendancesGrouped[$dateString] ?? [];
            $h = $dayStats[Attendance::STATUS_HADIR] ?? 0;
            $t = $dayStats[Attendance::STATUS_TERLAMBAT] ?? 0;
            $th = ($dayStats[Attendance::STATUS_ALPA] ?? 0)
                + ($dayStats[Attendance::STATUS_SAKIT] ?? 0)
                + ($dayStats[Attendance::STATUS_IZIN] ?? 0);

            $chartHadir[] = $h;
            $chartTerlambat[] = $t;
            $chartTidakHadir[] = $th;
        }

        // 6. Aktivitas Absensi Terbaru (10 Siswa Terkini)
        $recentAttendances = Attendance::with(['student.classes', 'student.faces'])
            ->whereDate('date', $today)
            ->latest('id')
            ->limit(10)
            ->get();

        // Fallback jika belum ada absensi pada hari ini
        if ($recentAttendances->isEmpty()) {
            $recentAttendances = Attendance::with(['student.classes', 'student.faces'])
                ->latest('id')
                ->limit(10)
                ->get();
        }

        return view('admin.beranda.index', compact(
            'greeting',
            'userName',
            'jamMasuk',
            'batasAbsensi',
            'totalStudents',
            'totalTeachers',
            'totalClasses',
            'hadirToday',
            'terlambatToday',
            'sakitToday',
            'izinToday',
            'alpaToday',
            'totalPresensiToday',
            'attendancePercentage',
            'chartCategories',
            'chartHadir',
            'chartTerlambat',
            'chartTidakHadir',
            'recentAttendances'
        ));
    }
}
