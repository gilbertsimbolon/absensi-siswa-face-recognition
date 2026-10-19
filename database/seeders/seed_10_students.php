<?php

require 'c:/laravel/absensi-siswa/vendor/autoload.php';
$app = require_once 'c:/laravel/absensi-siswa/bootstrap/app.php';

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$class = Classes::first() ?? Classes::create([
    'name' => 'X MIPA 1',
    'grade_level' => 'X',
    'jam_masuk' => '07:00:00',
    'batas_terlambat' => '07:15:00',
    'batas_alpa' => '08:30:00',
]);

$academicYear = AcademicYear::getActive() ?? AcademicYear::first();

$dataSiswa = [
    [
        'nama' => 'Rian Runtuwene',
        'gender' => 'L',
        'nisn' => '0021000101',
        'email' => 'rian.runtuwene@gmail.com',
        'phone' => '082190100101',
        'parent_name' => 'Johan Runtuwene',
        'parent_phone' => '081245100101',
        'parent_email' => 'johan.runtuwene@gmail.com',
        'skor' => 94.37,
        'waktu_detik' => 0.45,
        'jam_masuk' => '07:02:15',
        'status' => Attendance::STATUS_HADIR,
    ],
    [
        'nama' => 'Kezia Tambayong',
        'gender' => 'P',
        'nisn' => '0021000102',
        'email' => 'kezia.tambayong@gmail.com',
        'phone' => '082190100102',
        'parent_name' => 'Frans Tambayong',
        'parent_phone' => '081245100102',
        'parent_email' => 'frans.tambayong@gmail.com',
        'skor' => 92.10,
        'waktu_detik' => 0.48,
        'jam_masuk' => '07:18:20',
        'status' => Attendance::STATUS_TERLAMBAT,
    ],
    [
        'nama' => 'Rezky Kembuan',
        'gender' => 'L',
        'nisn' => '0021000103',
        'email' => 'rezky.kembuan@gmail.com',
        'phone' => '082190100103',
        'parent_name' => 'Ronald Kembuan',
        'parent_phone' => '081245100103',
        'parent_email' => 'ronald.kembuan@gmail.com',
        'skor' => 95.80,
        'waktu_detik' => 0.51,
        'jam_masuk' => '06:54:10',
        'status' => Attendance::STATUS_HADIR,
    ],
    [
        'nama' => 'Priscilla Walukow',
        'gender' => 'P',
        'nisn' => '0021000104',
        'email' => 'priscilla.walukow@gmail.com',
        'phone' => '082190100104',
        'parent_name' => 'Max Walukow',
        'parent_phone' => '081245100104',
        'parent_email' => 'max.walukow@gmail.com',
        'skor' => 93.25,
        'waktu_detik' => 0.46,
        'jam_masuk' => '07:08:18',
        'status' => Attendance::STATUS_HADIR,
    ],
    [
        'nama' => 'Christo Polii',
        'gender' => 'L',
        'nisn' => '0021000105',
        'email' => 'christo.polii@gmail.com',
        'phone' => '082190100105',
        'parent_name' => 'Donny Polii',
        'parent_phone' => '081245100105',
        'parent_email' => 'donny.polii@gmail.com',
        'skor' => 91.40,
        'waktu_detik' => 0.53,
        'jam_masuk' => '07:22:45',
        'status' => Attendance::STATUS_TERLAMBAT,
    ],
    [
        'nama' => 'Stela Mangundap',
        'gender' => 'P',
        'nisn' => '0021000106',
        'email' => 'stela.mangundap@gmail.com',
        'phone' => '082190100106',
        'parent_name' => 'Bobby Mangundap',
        'parent_phone' => '081245100106',
        'parent_email' => 'bobby.mangundap@gmail.com',
        'skor' => 96.15,
        'waktu_detik' => 0.44,
        'jam_masuk' => '06:51:24',
        'status' => Attendance::STATUS_HADIR,
    ],
    [
        'nama' => 'Farel Sumendap',
        'gender' => 'L',
        'nisn' => '0021000107',
        'email' => 'farel.sumendap@gmail.com',
        'phone' => '082190100107',
        'parent_name' => 'Jimmy Sumendap',
        'parent_phone' => '081245100107',
        'parent_email' => 'jimmy.sumendap@gmail.com',
        'skor' => 92.80,
        'waktu_detik' => 0.49,
        'jam_masuk' => '07:11:55',
        'status' => Attendance::STATUS_HADIR,
    ],
    [
        'nama' => 'Angelica Lapian',
        'gender' => 'P',
        'nisn' => '0021000108',
        'email' => 'angelica.lapian@gmail.com',
        'phone' => '082190100108',
        'parent_name' => 'Royke Lapian',
        'parent_phone' => '081245100108',
        'parent_email' => 'royke.lapian@gmail.com',
        'skor' => 94.90,
        'waktu_detik' => 0.47,
        'jam_masuk' => '06:58:33',
        'status' => Attendance::STATUS_HADIR,
    ],
    [
        'nama' => 'Devan Rorong',
        'gender' => 'L',
        'nisn' => '0021000109',
        'email' => 'devan.rorong@gmail.com',
        'phone' => '082190100109',
        'parent_name' => 'Stenly Rorong',
        'parent_phone' => '081245100109',
        'parent_email' => 'stenly.rorong@gmail.com',
        'skor' => 93.60,
        'waktu_detik' => 0.50,
        'jam_masuk' => '07:05:40',
        'status' => Attendance::STATUS_HADIR,
    ],
    [
        'nama' => 'Gisela Senduk',
        'gender' => 'P',
        'nisn' => '0021000110',
        'email' => 'gisela.senduk@gmail.com',
        'phone' => '082190100110',
        'parent_name' => 'Ferry Senduk',
        'parent_phone' => '081245100110',
        'parent_email' => 'ferry.senduk@gmail.com',
        'skor' => 97.00,
        'waktu_detik' => 0.42,
        'jam_masuk' => '06:48:15',
        'status' => Attendance::STATUS_HADIR,
    ],
];

echo "[*] Memulai pembuatan data 10 siswa dan riwayat presensi...\n";

DB::transaction(function () use ($dataSiswa, $class, $academicYear) {
    $today = Carbon::today()->format('Y-m-d');
    $yesterday1 = Carbon::today()->subDays(1)->format('Y-m-d'); // 2026-10-18 (Minggu atau Sabtu)
    $yesterday2 = Carbon::today()->subDays(2)->format('Y-m-d'); // 2026-10-17
    $yesterday3 = Carbon::today()->subDays(3)->format('Y-m-d'); // 2026-10-16

    foreach ($dataSiswa as $item) {
        // 1. Buat atau update data siswa
        $student = Student::updateOrCreate(
            ['nisn' => $item['nisn']],
            [
                'name' => $item['nama'],
                'class_id' => $class->id,
                'gender' => $item['gender'],
                'email' => $item['email'],
                'phone' => $item['phone'],
                'parent_name' => $item['parent_name'],
                'parent_phone' => $item['parent_phone'],
                'parent_email' => $item['parent_email'],
                'status' => 'aktif',
            ]
        );

        $notes = "Presensi wajah Face AI (Akurasi: {$item['skor']}%, Waktu: {$item['waktu_detik']}s) - Sesuai";

        // 2. Presensi Hari Ini (Today: 2026-10-19)
        Attendance::updateOrCreate(
            [
                'student_id' => $student->id,
                'date' => $today,
            ],
            [
                'class_id' => $class->id,
                'academic_year_id' => $academicYear?->id,
                'check_in_time' => $item['jam_masuk'],
                'status' => $item['status'],
                'method' => Attendance::METHOD_FACE,
                'confidence_score' => $item['skor'],
                'notes' => $notes,
            ]
        );

        // 3. Riwayat presensi hari-hari sebelumnya agar grafik dan rekapitulasi memiliki histori
        $historicalDays = [
            ['date' => $yesterday3, 'status' => Attendance::STATUS_HADIR, 'time' => $item['jam_masuk']],
            ['date' => $yesterday2, 'status' => ($item['gender'] === 'L' && $item['skor'] < 93 ? Attendance::STATUS_TERLAMBAT : Attendance::STATUS_HADIR), 'time' => $item['jam_masuk']],
        ];

        foreach ($historicalDays as $hist) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'date' => $hist['date'],
                ],
                [
                    'class_id' => $class->id,
                    'academic_year_id' => $academicYear?->id,
                    'check_in_time' => $hist['time'],
                    'status' => $hist['status'],
                    'method' => Attendance::METHOD_FACE,
                    'confidence_score' => $item['skor'],
                    'notes' => $notes,
                ]
            );
        }

        echo "  [+] Berhasil memproses siswa: {$student->name} (NISN: {$student->nisn})\n";
    }
});

echo "[✓] Selesai! Sebanyak 10 siswa dan histori presensi berhasil dimasukkan ke sistem.\n";
