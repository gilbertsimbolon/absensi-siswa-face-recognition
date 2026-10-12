@extends('layouts.admin.app')

@section('title', 'Pengaturan Notifikasi WhatsApp | SMAN 2 Tondano')

@section('content')
    <div class="d-flex flex-column flex-grow-1 h-100" style="min-height: 0; overflow-y: auto;">

        <!-- Notifikasi Flash Message -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show flex-shrink-0" role="alert">
                <i class="icon-base bx bx-check-circle me-1"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show flex-shrink-0" role="alert">
                <i class="icon-base bx bx-error-circle me-1"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show flex-shrink-0" role="alert">
                <i class="icon-base bx bx-error-circle me-1"></i>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Header Halaman -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-shrink-0">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="icon-base bx bxl-whatsapp text-success me-2"></i>Pengaturan Notifikasi WhatsApp
                </h4>
                <span class="text-muted small">
                    Konfigurasi integrasi WhatsApp Baileys, template teks dinamis, dan penentuan target penerima presensi siswa SMAN 2 Tondano.
                </span>
            </div>
            <div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="periksaStatusGateway()">
                    <i class="bx bx-refresh me-1"></i> Cek Koneksi Gateway
                </button>
            </div>
        </div>

        <form action="{{ route('admin.settings.notification.update') }}" method="POST">
            @csrf

            <div class="row g-4 mb-4">
                
                <!-- KOLOM KIRI: Status Gateway Baileys & Konfigurasi Koneksi -->
                <div class="col-lg-5 col-md-12">
                    
                    <!-- Card Status Gateway -->
                    <div class="card border-0 shadow-sm rounded-3 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">
                                <i class="bx bx-broadcast text-primary me-1"></i> Status Gateway Baileys
                            </h6>
                            <span id="gateway-badge" class="badge {{ $gatewayStatus['connected'] ? 'bg-success' : ($gatewayStatus['online'] ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ $gatewayStatus['connected'] ? 'TERHUBUNG' : ($gatewayStatus['online'] ? 'MENUNGGU SCAN QR' : 'OFFLINE') }}
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="text-center py-2" id="gateway-status-content">
                                @if ($gatewayStatus['connected'])
                                    <div class="text-success mb-2" style="font-size: 2.5rem;">
                                        <i class="bx bxs-check-shield"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">WhatsApp Terhubung Aktif</h6>
                                    <p class="text-muted small mb-0">Nomor Pengirim: <strong>{{ $gatewayStatus['phone'] ?? '-' }}</strong></p>
                                    <small class="text-success d-block mt-1">Siap mengirimkan notifikasi presensi otomatis.</small>
                                @elseif ($gatewayStatus['online'] && !empty($gatewayStatus['qr']))
                                    <h6 class="fw-semibold text-dark mb-2">Scan QR Code dengan WhatsApp</h6>
                                    <div class="d-inline-block p-2 bg-white border rounded shadow-sm mb-2">
                                        <img src="{{ $gatewayStatus['qr'] }}" alt="QR Code WhatsApp" id="qr-image" style="width: 180px; height: 180px;">
                                    </div>
                                    <p class="text-muted small mb-0">Buka WA di HP &gt; Perangkat Tertaut &gt; Tautkan Perangkat</p>
                                @else
                                    <div class="text-secondary mb-2" style="font-size: 2.5rem;">
                                        <i class="bx bx-server"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Layanan Baileys Belum Terhubung</h6>
                                    <p class="text-muted small mb-2">Pastikan gateway WhatsApp (Port 3000) sedang berjalan.</p>
                                    <span class="badge bg-label-secondary small">URL: {{ $setting->wa_gateway_url }}</span>
                                @endif
                            </div>

                            <hr class="my-3">

                            <!-- Pengaturan URL & API Key Gateway -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold small" for="wa_gateway_url">URL Endpoint Gateway Baileys <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bx bx-link"></i></span>
                                    <input type="url" name="wa_gateway_url" id="wa_gateway_url" class="form-control" 
                                           value="{{ old('wa_gateway_url', $setting->wa_gateway_url) }}" placeholder="http://127.0.0.1:3000" required>
                                </div>
                                <div class="form-text small" style="font-size: 11px;">Alamat microservice Baileys Node.js (default: <code>http://127.0.0.1:3000</code>).</div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-semibold small" for="wa_api_key">API Key / Token Rahasia (Opsional)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bx bx-key"></i></span>
                                    <input type="password" name="wa_api_key" id="wa_api_key" class="form-control" 
                                           value="{{ old('wa_api_key', $setting->wa_api_key) }}" placeholder="Kosongkan jika tanpa API Key">
                                </div>
                                <div class="form-text small" style="font-size: 11px;">Gunakan jika gateway WA Anda dilindungi token autentikasi.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Target Penerima & Aturan Pengiriman -->
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="fw-bold mb-0">
                                <i class="bx bx-user-check text-success me-1"></i> Target Penerima & Aturan
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <!-- Toggle Aktifkan Notifikasi -->
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $setting->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_active">
                                    Aktifkan Notifikasi WhatsApp Otomatis
                                </label>
                                <div class="form-text small" style="font-size: 11px;">
                                    Jika diaktifkan, pesan akan langsung terkirim otomatis saat siswa berhasil scan wajah di kamera.
                                </div>
                            </div>

                            <hr class="my-3">

                            <!-- Siapa Penerimanya -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Siapa Penerima Pesan? <span class="text-danger">*</span></label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recipient_target" id="target_parent" value="parent" 
                                            {{ old('recipient_target', $setting->recipient_target) === 'parent' ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="target_parent">
                                            <strong>Orang Tua / Wali Saja</strong> (Disarankan untuk presensi sekolah)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recipient_target" id="target_student" value="student" 
                                            {{ old('recipient_target', $setting->recipient_target) === 'student' ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="target_student">
                                            <strong>Siswa Saja</strong> (Ke nomor WhatsApp pribadi siswa)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recipient_target" id="target_both" value="both" 
                                            {{ old('recipient_target', $setting->recipient_target) === 'both' ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="target_both">
                                            <strong>Keduanya</strong> (Kirim ke Orang Tua DAN Siswa sekaligus)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            <!-- Kapan Dikirim -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Kapan Pesan Dikirim?</label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="send_on_hadir" id="send_on_hadir" value="1" 
                                            {{ old('send_on_hadir', $setting->send_on_hadir) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="send_on_hadir">
                                            Kirim saat siswa hadir tepat waktu (Status: <strong>HADIR</strong>)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="send_on_terlambat" id="send_on_terlambat" value="1" 
                                            {{ old('send_on_terlambat', $setting->send_on_terlambat) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="send_on_terlambat">
                                            Kirim saat siswa hadir lewat batas 07:15 (Status: <strong>TERLAMBAT</strong>)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Nomor Tembusan Sekolah (Opsional) -->
                            <div class="mb-0">
                                <label class="form-label fw-semibold small" for="admin_phone">Nomor Tembusan Tambahan (Opsional)</label>
                                <input type="text" name="admin_phone" id="admin_phone" class="form-control form-control-sm" 
                                       value="{{ old('admin_phone', $setting->admin_phone) }}" placeholder="Contoh: 081234567890">
                                <div class="form-text small" style="font-size: 11px;">Nomor Guru Piket / Admin yang juga menerima tembusan notifikasi presensi.</div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- KOLOM KANAN: Template Pesan Dinamis & Variabel -->
                <div class="col-lg-7 col-md-12">
                    
                    <div class="card border-0 shadow-sm rounded-3 mb-4">
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">
                                <i class="bx bx-message-detail text-warning me-1"></i> Template Teks Dinamis
                            </h6>
                            <span class="badge bg-label-info small">Format WhatsApp Markdown</span>
                        </div>
                        <div class="card-body p-3">
                            
                            <!-- Variabel Dinamis Helper -->
                            <div class="p-3 bg-light rounded-3 mb-3 border">
                                <h6 class="fw-bold small text-dark mb-2">
                                    <i class="bx bx-code-alt text-primary me-1"></i> Variabel Dinamis yang Tersedia:
                                </h6>
                                <p class="text-muted small mb-2" style="font-size: 12px;">
                                    Klik salah satu variabel di bawah untuk menyalin dan menempelkannya ke template pesan Anda:
                                </p>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($placeholders as $var => $desc)
                                        <button type="button" class="btn btn-sm btn-white border shadow-xs py-0 px-2 text-dark font-monospace" 
                                                style="font-size: 11px;" onclick="sisipkanVariabel('{{ $var }}')" title="{{ $desc }}">
                                            <span class="text-primary fw-bold">{{ $var }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Template Status Hadir -->
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-dark d-flex justify-content-between align-items-center" for="template_hadir">
                                    <span>Template Pesan: Hadir Tepat Waktu</span>
                                    <span class="badge bg-label-success small" style="font-size: 10px;">Status: HADIR</span>
                                </label>
                                <textarea name="template_hadir" id="template_hadir" rows="6" class="form-control font-monospace" 
                                          style="font-size: 13px;" required>{{ old('template_hadir', $setting->template_hadir) }}</textarea>
                                <div class="form-text small" style="font-size: 11px;">Gunakan <code>*teks*</code> untuk tebal, <code>_teks_</code> untuk miring.</div>
                            </div>

                            <!-- Template Status Terlambat -->
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-dark d-flex justify-content-between align-items-center" for="template_terlambat">
                                    <span>Template Pesan: Hadir Terlambat (&gt; 07:15 WITA)</span>
                                    <span class="badge bg-label-warning text-dark small" style="font-size: 10px;">Status: TERLAMBAT</span>
                                </label>
                                <textarea name="template_terlambat" id="template_terlambat" rows="6" class="form-control font-monospace" 
                                          style="font-size: 13px;" required>{{ old('template_terlambat', $setting->template_terlambat) }}</textarea>
                                <div class="form-text small" style="font-size: 11px;">Pesan khusus keterlambatan untuk memberikan himbauan kepada orang tua.</div>
                            </div>

                            <!-- Tombol Simpan Konfigurasi -->
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bx bx-save me-1"></i> Simpan Seluruh Pengaturan
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- Card Simulasi Preview Chat WhatsApp -->
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white border-bottom py-2 px-3">
                            <h6 class="fw-bold mb-0 text-muted small">
                                <i class="bx bxl-whatsapp text-success me-1"></i> Simulasi Tampilan Pesan WhatsApp (Live Preview)
                            </h6>
                        </div>
                        <div class="card-body p-3" style="background-color: #efeae2;">
                            <div class="d-flex flex-column align-items-start">
                                <div class="bg-white p-3 rounded-3 shadow-xs border" style="max-width: 90%; position: relative;">
                                    <div class="fw-bold text-success small mb-1">SMAN 2 Tondano (Presensi Otomatis)</div>
                                    <div id="preview-text" class="small text-dark" style="white-space: pre-wrap; font-size: 12.5px; line-height: 1.45;"></div>
                                    <div class="text-end text-muted mt-1" style="font-size: 10px;">
                                        {{ now()->format('H:i') }} WITA <i class="bx bx-check-double text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </form>

        <!-- Card Uji Coba Pengiriman Pesan Mandiri -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0">
                    <i class="bx bx-paper-plane text-info me-1"></i> Uji Coba Pengiriman Pesan WhatsApp (Test Message)
                </h6>
            </div>
            <div class="card-body p-3">
                <form action="{{ route('admin.settings.notification.test') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small" for="test_phone">Nomor WhatsApp Tujuan <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bx bx-phone"></i></span>
                                <input type="text" name="test_phone" id="test_phone" class="form-control" 
                                       placeholder="081234567890" value="{{ auth()->user()->phone ?? '' }}" required>
                            </div>
                            <div class="form-text small" style="font-size: 11px;">Format 08... atau 628... akan disesuaikan otomatis.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small" for="test_message">Isi Pesan Uji Coba <span class="text-danger">*</span></label>
                            <input type="text" name="test_message" id="test_message" class="form-control form-control-sm" 
                                   value="Halo, ini adalah pesan uji coba integrasi WhatsApp Baileys dari Sistem Presensi SMAN 2 Tondano." required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success btn-sm w-100 py-2">
                                <i class="bx bx-send me-1"></i> Kirim Tes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Script Live Preview, Variabel Insertion, & Polling Gateway Status -->
    <script>
        let textareaAktif = 'template_hadir';

        document.getElementById('template_hadir').addEventListener('focus', () => { textareaAktif = 'template_hadir'; });
        document.getElementById('template_terlambat').addEventListener('focus', () => { textareaAktif = 'template_terlambat'; });

        function sisipkanVariabel(variabel) {
            const el = document.getElementById(textareaAktif);
            if (!el) return;
            const start = el.selectionStart;
            const end = el.selectionEnd;
            const text = el.value;
            el.value = text.substring(0, start) + variabel + text.substring(end);
            el.selectionStart = el.selectionEnd = start + variabel.length;
            el.focus();
            perbaruiPreview();
        }

        function perbaruiPreview() {
            const template = document.getElementById('template_hadir').value;
            const contohData = {
                '{nama_siswa}': 'Josep Gilbert',
                '{nisn}': '0025625634',
                '{kelas}': 'XII MIPA 1',
                '{nama_ortu}': 'Bpk. Simbolon',
                '{status}': 'HADIR',
                '{waktu}': '07:05',
                '{tanggal}': '13 Oktober 2026',
                '{sekolah}': 'SMAN 2 Tondano'
            };

            let hasil = template;
            for (const [key, val] of Object.entries(contohData)) {
                hasil = hasil.replaceAll(key, val);
            }

            const previewEl = document.getElementById('preview-text');
            if (previewEl) previewEl.textContent = hasil;
        }

        document.getElementById('template_hadir').addEventListener('input', perbaruiPreview);
        perbaruiPreview();

        // AJAX Polling Status Gateway
        async function periksaStatusGateway() {
            try {
                const res = await fetch("{{ route('admin.settings.notification.status') }}");
                if (!res.ok) return;
                const data = await res.json();

                const badge = document.getElementById('gateway-badge');
                const content = document.getElementById('gateway-status-content');

                if (data.connected) {
                    badge.className = 'badge bg-success';
                    badge.textContent = 'TERHUBUNG';
                    content.innerHTML = `
                        <div class="text-success mb-2" style="font-size: 2.5rem;"><i class="bx bxs-check-shield"></i></div>
                        <h6 class="fw-bold text-dark mb-1">WhatsApp Terhubung Aktif</h6>
                        <p class="text-muted small mb-0">Nomor Pengirim: <strong>${data.phone || '-'}</strong></p>
                        <small class="text-success d-block mt-1">Siap mengirimkan notifikasi presensi otomatis.</small>
                    `;
                } else if (data.online && data.qr) {
                    badge.className = 'badge bg-warning text-dark';
                    badge.textContent = 'MENUNGGU SCAN QR';
                    content.innerHTML = `
                        <h6 class="fw-semibold text-dark mb-2">Scan QR Code dengan WhatsApp</h6>
                        <div class="d-inline-block p-2 bg-white border rounded shadow-sm mb-2">
                            <img src="${data.qr}" alt="QR Code" style="width: 180px; height: 180px;">
                        </div>
                        <p class="text-muted small mb-0">Buka WA di HP &gt; Perangkat Tertaut &gt; Tautkan Perangkat</p>
                    `;
                } else {
                    badge.className = 'badge bg-danger';
                    badge.textContent = 'OFFLINE';
                    content.innerHTML = `
                        <div class="text-secondary mb-2" style="font-size: 2.5rem;"><i class="bx bx-server"></i></div>
                        <h6 class="fw-bold text-dark mb-1">Layanan Baileys Belum Terhubung</h6>
                        <p class="text-muted small mb-2">${data.message || 'Pastikan gateway WhatsApp sedang aktif.'}</p>
                    `;
                }
            } catch (e) {}
        }

        // Jalankan pengecekan status saat halaman dimuat
        document.addEventListener('DOMContentLoaded', periksaStatusGateway);
    </script>
@endsection

