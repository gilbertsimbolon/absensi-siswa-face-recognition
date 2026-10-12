<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationSettingController extends Controller
{
    /**
     * Menampilkan halaman konfigurasi notifikasi WhatsApp.
     */
    public function index(): View
    {
        $setting = NotificationSetting::getSettings();
        $gatewayStatus = WhatsAppService::getGatewayStatus($setting);

        $placeholders = [
            '{nama_siswa}' => 'Nama lengkap siswa (contoh: Josep Gilbert)',
            '{nisn}' => 'Nomor Induk Siswa Nasional (contoh: 0025625634)',
            '{kelas}' => 'Nama kelas siswa saat ini (contoh: XII MIPA 1)',
            '{nama_ortu}' => 'Nama orang tua/wali siswa (contoh: Bpk. Simbolon)',
            '{status}' => 'Status absensi (HADIR / TERLAMBAT)',
            '{waktu}' => 'Jam siswa melakukan presensi (contoh: 07:10)',
            '{tanggal}' => 'Tanggal presensi (contoh: 13 Oktober 2026)',
            '{sekolah}' => 'Nama sekolah (SMAN 2 Tondano)',
        ];

        return view('admin.pengaturan.notifikasi', compact('setting', 'gatewayStatus', 'placeholders'));
    }

    /**
     * Memperbarui konfigurasi notifikasi WhatsApp.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'wa_gateway_url' => 'required|url',
            'wa_api_key' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'send_on_hadir' => 'nullable|boolean',
            'send_on_terlambat' => 'nullable|boolean',
            'recipient_target' => 'required|in:parent,student,both',
            'admin_phone' => 'nullable|string|max:20',
            'template_hadir' => 'required|string',
            'template_terlambat' => 'required|string',
        ]);

        $setting = NotificationSetting::getSettings();

        $setting->update([
            'wa_gateway_url' => rtrim($validated['wa_gateway_url'], '/'),
            'wa_api_key' => $validated['wa_api_key'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'send_on_hadir' => $request->boolean('send_on_hadir'),
            'send_on_terlambat' => $request->boolean('send_on_terlambat'),
            'recipient_target' => $validated['recipient_target'],
            'admin_phone' => $validated['admin_phone'] ?? null,
            'template_hadir' => $validated['template_hadir'],
            'template_terlambat' => $validated['template_terlambat'],
        ]);

        return redirect()->route('admin.settings.notification')
            ->with('success', 'Pengaturan notifikasi WhatsApp berhasil disimpan.');
    }

    /**
     * Menguji pengiriman pesan WhatsApp langsung ke nomor tujuan.
     */
    public function testSend(Request $request): RedirectResponse
    {
        $request->validate([
            'test_phone' => 'required|string',
            'test_message' => 'required|string',
        ]);

        $result = WhatsAppService::sendMessage($request->input('test_phone'), $request->input('test_message'));

        if ($result['status'] === 'sukses') {
            return redirect()->route('admin.settings.notification')
                ->with('success', 'Uji coba berhasil: '.$result['pesan']);
        }

        return redirect()->route('admin.settings.notification')
            ->with('error', 'Gagal mengirim pesan uji coba: '.$result['pesan']);
    }

    /**
     * Mendapatkan status gateway Baileys via AJAX (untuk QR Code real-time).
     */
    public function status(): JsonResponse
    {
        $setting = NotificationSetting::getSettings();
        $status = WhatsAppService::getGatewayStatus($setting);

        return response()->json($status);
    }
}
