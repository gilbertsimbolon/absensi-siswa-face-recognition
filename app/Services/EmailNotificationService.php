<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * Menghasilkan subjek dan konten email statis sesuai status (Hadir, Terlambat, Alpa).
     *
     * @return array{subject: string, html: string}
     */
    public static function generateStaticEmail(Attendance $attendance): array
    {
        $student = $attendance->student;
        $studentClass = $student?->classes;
        $parentName = ! empty($student?->parent_name) ? $student->parent_name : 'Bapak/Ibu Orang Tua / Wali';

        $checkInTime = $attendance->check_in_time
            ? Carbon::parse($attendance->check_in_time)->format('H:i')
            : Carbon::now()->format('H:i');

        $dateFormatted = $attendance->date
            ? Carbon::parse($attendance->date)->translatedFormat('l, d F Y')
            : Carbon::today()->translatedFormat('l, d F Y');

        $batasTerlambat = $studentClass?->batas_terlambat
            ? Carbon::parse($studentClass->batas_terlambat)->format('H:i')
            : '07:15';

        $batasAlpa = $studentClass?->batas_alpa
            ? Carbon::parse($studentClass->batas_alpa)->format('H:i')
            : '08:30';

        $status = $attendance->status;

        if ($status === Attendance::STATUS_TERLAMBAT) {
            $subject = "[Pemberitahuan Keterlambatan] {$student->name} ({$studentClass?->name}) - Terlambat";
            $statusBadge = '<span style="display: inline-block; padding: 4px 12px; background-color: #fef3c7; color: #92400e; font-weight: 700; border-radius: 4px; font-size: 13px;">TERLAMBAT</span>';
            $pesanPengantar = "Diberitahukan bahwa ananda <strong>{$student->name}</strong> tercatat hadir <strong>TERLAMBAT</strong> pada presensi hari ini di SMAN 2 Tondano.";
            $catatanKhusus = "Waktu kedatangan siswa melewati batas toleransi keterlambatan ({$batasTerlambat} WITA). Mohon perhatian dan pendampingan Bapak/Ibu agar ananda dapat hadir tepat waktu pada kegiatan belajar selanjutnya.";
            $headerColor = '#d97706';
        } elseif ($status === Attendance::STATUS_ALPA) {
            $subject = "[Pemberitahuan Ketidakhadiran] {$student->name} ({$studentClass?->name}) - Alpa";
            $statusBadge = '<span style="display: inline-block; padding: 4px 12px; background-color: #fee2e2; color: #991b1b; font-weight: 700; border-radius: 4px; font-size: 13px;">ALPA</span>';
            $pesanPengantar = "Diberitahukan bahwa ananda <strong>{$student->name}</strong> tercatat berstatus <strong>ALPA</strong> pada presensi hari ini di SMAN 2 Tondano.";
            $catatanKhusus = "Siswa melakukan pemindaian melewati batas toleransi alpa ({$batasAlpa} WITA). Mohon konfirmasi dan klarifikasi kehadiran ananda kepada pihak sekolah atau wali kelas.";
            $headerColor = '#dc2626';
        } else {
            // Default: HADIR
            $subject = "[Presensi Kehadiran] {$student->name} ({$studentClass?->name}) - Hadir Tepat Waktu";
            $statusBadge = '<span style="display: inline-block; padding: 4px 12px; background-color: #dcfce7; color: #166534; font-weight: 700; border-radius: 4px; font-size: 13px;">HADIR</span>';
            $pesanPengantar = "Diberitahukan bahwa ananda <strong>{$student->name}</strong> telah hadir dan tercatat presensi tepat waktu di SMAN 2 Tondano.";
            $catatanKhusus = 'Terima kasih atas kedisiplinan ananda dan kerja sama Bapak/Ibu dalam mendukung ketertiban belajar di sekolah.';
            $headerColor = '#0284c7';
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$subject}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <!-- Header -->
        <tr>
            <td style="background-color: {$headerColor}; padding: 20px 24px; text-align: left;">
                <h2 style="color: #ffffff; margin: 0; font-size: 18px; font-weight: 700;">Presensi Siswa SMAN 2 Tondano</h2>
                <p style="color: #ffffff; opacity: 0.9; margin: 4px 0 0 0; font-size: 13px;">Sistem Informasi Kehadiran & Pemindaian Wajah</p>
            </td>
        </tr>

        <!-- Isi Konten -->
        <tr>
            <td style="padding: 24px; font-size: 14px; line-height: 1.6; color: #334155;">
                <p style="margin-top: 0;">Yth. <strong>{$parentName}</strong>,</p>
                <p>{$pesanPengantar}</p>

                <!-- Rincian Data Presensi -->
                <table width="100%" border="0" cellspacing="0" cellpadding="8" style="margin: 16px 0; background-color: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 13.5px;">
                    <tr>
                        <td width="35%" style="color: #64748b; border-bottom: 1px solid #f1f5f9;">Nama Siswa</td>
                        <td style="font-weight: 600; color: #0f172a; border-bottom: 1px solid #f1f5f9;">{$student->name}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; border-bottom: 1px solid #f1f5f9;">NISN</td>
                        <td style="color: #0f172a; border-bottom: 1px solid #f1f5f9;">{$student->nisn}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; border-bottom: 1px solid #f1f5f9;">Kelas</td>
                        <td style="color: #0f172a; border-bottom: 1px solid #f1f5f9;">{$studentClass?->name}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; border-bottom: 1px solid #f1f5f9;">Hari, Tanggal</td>
                        <td style="color: #0f172a; border-bottom: 1px solid #f1f5f9;">{$dateFormatted}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; border-bottom: 1px solid #f1f5f9;">Waktu Presensi</td>
                        <td style="color: #0f172a; border-bottom: 1px solid #f1f5f9;">{$checkInTime} WITA</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;">Status Kehadiran</td>
                        <td>{$statusBadge}</td>
                    </tr>
                </table>

                <p style="margin-bottom: 0; color: #475569; font-size: 13.5px;">{$catatanKhusus}</p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f8fafc; padding: 16px 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9;">
                Email ini dibuat secara otomatis oleh Sistem Presensi SMAN 2 Tondano.<br>
                Jl. Siswa, Tondano, Minahasa, Sulawesi Utara
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        return [
            'subject' => $subject,
            'html' => $html,
        ];
    }

    /**
     * Mengirim pesan email ke satu alamat penerima menggunakan konfigurasi SMTP di .env.
     *
     * @return array{status: string, pesan: string}
     */
    public static function sendEmail(string $recipientEmail, string $subject, string $htmlContent): array
    {
        $recipientEmail = trim($recipientEmail);

        if (! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'pesan' => "Alamat email tidak valid: {$recipientEmail}",
            ];
        }

        $fromAddress = config('mail.from.address', 'presensi@sman2tondano.sch.id');
        $fromName = config('mail.from.name', 'SMAN 2 Tondano');

        try {
            Mail::html($htmlContent, function ($message) use ($recipientEmail, $subject, $fromAddress, $fromName) {
                $message->to($recipientEmail)
                    ->from($fromAddress, $fromName)
                    ->subject($subject);
            });

            return [
                'status' => 'sukses',
                'pesan' => "Email berhasil dikirim ke {$recipientEmail}",
            ];
        } catch (\Exception $e) {
            Log::error("Gagal mengirim email ke {$recipientEmail}: {$e->getMessage()}");

            return [
                'status' => 'error',
                'pesan' => "Gagal mengirim email ke {$recipientEmail}: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Mengirim email uji coba konfigurasi server SMTP.
     *
     * @return array{status: string, pesan: string}
     */
    public static function sendTestEmail(string $recipientEmail): array
    {
        $subject = '[Uji Coba] Konfigurasi Email Presensi - SMAN 2 Tondano';
        $timeNow = Carbon::now()->format('H:i:s');
        $dateNow = Carbon::today()->translatedFormat('l, d F Y');

        $htmlContent = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{$subject}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
        <tr>
            <td style="background-color: #16a34a; padding: 20px 24px; text-align: left;">
                <h2 style="color: #ffffff; margin: 0; font-size: 18px; font-weight: 700;">Uji Coba Email Berhasil</h2>
                <p style="color: #dcfce7; margin: 4px 0 0 0; font-size: 13px;">Sistem Presensi SMAN 2 Tondano</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 24px; font-size: 14px; line-height: 1.6; color: #334155;">
                <p>Halo,</p>
                <p>Ini adalah pesan uji coba dari <strong>Sistem Presensi SMAN 2 Tondano</strong>. Konfigurasi server email (SMTP) pada file lingkungan (.env) Anda telah terpasang dengan baik dan siap digunakan.</p>
                <div style="background-color: #f1f5f9; padding: 12px 16px; border-radius: 6px; margin: 16px 0; font-size: 13px; color: #475569;">
                    <strong>Waktu Uji Coba:</strong> {$dateNow}, {$timeNow} WITA<br>
                    <strong>Status:</strong> Terhubung & Berfungsi Normal
                </div>
            </td>
        </tr>
        <tr>
            <td style="background-color: #f8fafc; padding: 16px 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9;">
                Email uji coba otomatis - SMAN 2 Tondano
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        return self::sendEmail($recipientEmail, $subject, $htmlContent);
    }

    /**
     * Mengirim notifikasi email presensi otomatis dengan template statis (Hadir, Terlambat, Alpa).
     *
     * @return array{status: string, pesan: string, detail?: array}
     */
    public static function sendAttendanceNotification(Attendance $attendance): array
    {
        // 1. Cek apakah notifikasi diaktifkan di .env (default: true)
        if (! config('mail.notification_enabled', env('MAIL_NOTIFICATION_ENABLED', true))) {
            return [
                'status' => 'skipped',
                'pesan' => 'Notifikasi email dinonaktifkan dalam konfigurasi lingkungan (.env).',
            ];
        }

        // 2. Kumpulkan daftar email tujuan (Orang Tua & Siswa)
        $student = $attendance->student;
        $targetEmails = [];

        if (! empty($student?->parent_email)) {
            $targetEmails[] = $student->parent_email;
        }

        if (! empty($student?->email)) {
            $targetEmails[] = $student->email;
        }

        // Tembusan email admin/sekolah dari konfigurasi .env (jika disetel)
        $adminCopy = env('MAIL_ADMIN_COPY');
        if (! empty($adminCopy)) {
            $targetEmails[] = $adminCopy;
        }

        $targetEmails = array_unique(array_filter($targetEmails));

        if (empty($targetEmails)) {
            return [
                'status' => 'skipped',
                'pesan' => 'Tidak ada alamat email yang terdaftar untuk siswa atau orang tua.',
            ];
        }

        // 3. Generate template email statis untuk status ini
        $emailData = self::generateStaticEmail($attendance);
        $subject = $emailData['subject'];
        $htmlContent = $emailData['html'];

        // 4. Kirim email ke seluruh target
        $results = [];
        foreach ($targetEmails as $email) {
            $results[$email] = self::sendEmail($email, $subject, $htmlContent);
        }

        return [
            'status' => 'sukses',
            'pesan' => 'Notifikasi email berhasil diproses.',
            'detail' => $results,
        ];
    }
}
