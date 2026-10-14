import os
import sys
import hmac
import hashlib
import json
import base64
import time
from functools import wraps
from flask import Flask, request, jsonify, render_template, Response
from flask_cors import CORS
import konfigurasi
from layanan_wajah import LayananWajah
from stream_scanner import ScannerOpenCV

# Inisialisasi Flask
template_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'templates')
app = Flask(__name__, template_folder=template_dir)
CORS(app)

# Inisialisasi Service AI dan Pemindai OpenCV
layanan = LayananWajah()
scanner = ScannerOpenCV(layanan_wajah=layanan, camera_source=0)

def get_app_key_bytes():
    """Mengambil byte secret APP_KEY Laravel dari environment."""
    key = getattr(konfigurasi, 'APP_KEY', '') or os.getenv('APP_KEY', '')
    if not key:
        return None
    if key.startswith('base64:'):
        try:
            return base64.b64decode(key[7:])
        except Exception:
            return key[7:].encode('utf-8')
    return key.encode('utf-8')

def verifikasi_token_auth(token):
    """
    Memverifikasi token otentikasi HMAC dari Laravel.
    Memastikan request berasal dari user yang telah login (admin / teacher).
    """
    if not token or '.' not in token:
        return False, "Token autentikasi tidak ditemukan"

    key_bytes = get_app_key_bytes()
    if not key_bytes:
        # Fallback aman: jika APP_KEY belum disetel, hanya izinkan akses dari localhost loopback
        remote_ip = request.remote_addr
        if remote_ip in ['127.0.0.1', 'localhost', '::1']:
            return True, {"role": "admin"}
        return False, "Kunci enkripsi APP_KEY belum dikonfigurasi"

    try:
        payload_b64, signature = token.split('.', 1)
        expected_sig = hmac.new(key_bytes, payload_b64.encode('utf-8'), hashlib.sha256).hexdigest()
        if not hmac.compare_digest(signature, expected_sig):
            return False, "Tanda tangan token tidak sah"

        payload_str = base64.b64decode(payload_b64).decode('utf-8')
        data = json.loads(payload_str)

        # Validasi masa berlaku
        if data.get('exp', 0) < time.time():
            return False, "Sesi autentikasi telah kedaluwarsa"

        # Validasi role yang diizinkan (admin atau teacher)
        if data.get('role') not in ['admin', 'teacher']:
            return False, "Hak akses tidak mencukupi"

        return True, data
    except Exception as e:
        return False, f"Token tidak valid: {str(e)}"

def login_diperlukan(f):
    """Decorator untuk memproteksi endpoint pemindai dari akses tanpa izin."""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        # 1. Fallback lokal aman: request dari localhost internal (127.0.0.1 / ::1) selalu diizinkan
        remote_ip = request.remote_addr
        if remote_ip in ['127.0.0.1', 'localhost', '::1']:
            return f(*args, **kwargs)

        # 2. Akses dari jaringan luar wajib memverifikasi token bertandatangan HMAC
        token = request.cookies.get('scanner_auth_token') or request.args.get('token') or request.headers.get('X-Scanner-Auth')
        if token:
            valid, result = verifikasi_token_auth(token)
            if valid:
                return f(*args, **kwargs)
        else:
            valid, result = False, "Token autentikasi tidak ditemukan"

        if not valid:
            if request.accept_mimetypes.accept_html:
                return f"""<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>403 - Akses Ditolak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card bg-secondary bg-opacity-25 border border-secondary text-center p-4 shadow" style="max-width: 460px;">
        <h4 class="text-danger fw-bold mb-2">Akses Ditolak (403)</h4>
        <p class="text-light small mb-3">Halaman pemindaian wajah dilindungi dan hanya dapat diakses setelah masuk melalui Laravel Admin Panel.</p>
        <p class="small text-white-50 mb-3">{result}</p>
        <a href="/masuk" class="btn btn-primary btn-sm">Masuk ke Admin Panel</a>
    </div>
</body>
</html>""", 403
            return jsonify({"status": "error", "pesan": f"Akses ditolak: {result}"}), 403
        return f(*args, **kwargs)
    return decorated_function

@app.route('/', methods=['GET'])
@app.route('/scanner', methods=['GET'])
@app.route('/scanner/', methods=['GET'])
@login_diperlukan
def index():
    """Halaman scanner OpenCV - stream video OpenCV native tanpa JavaScript scanner."""
    return render_template(
        'scanner.html',
        model_name=konfigurasi.MODEL_NAME,
        total_wajah=len(layanan.embeddings_db),
        kamera_aktif=scanner.camera_source
    )

@app.route('/video_feed')
@app.route('/scanner/video_feed')
@login_diperlukan
def video_feed():
    """
    Stream MJPEG langsung dari kamera OpenCV.
    Setiap frame diproses dan digambar kotak deteksi wajah oleh OpenCV & FaceNet.
    Ditampilkan di tag <img> secara native di browser mana pun tanpa JS.
    """
    if not scanner.is_running:
        scanner.mulai()
    return Response(
        scanner.generate_mjpeg_stream(),
        mimetype='multipart/x-mixed-replace; boundary=frame'
    )

@app.route('/ganti-kamera', methods=['POST'])
@app.route('/scanner/ganti-kamera', methods=['POST'])
@login_diperlukan
def ganti_kamera():
    """Mengubah sumber kamera OpenCV (Webcam 0, USB 1/2, atau URL IP Camera)."""
    data = request.get_json(silent=True) or {}
    sumber = data.get('sumber', 0)
    sukses = scanner.ganti_sumber_kamera(sumber)
    return jsonify({
        "status": "sukses" if sukses else "error",
        "sumber_sekarang": scanner.camera_source,
        "pesan": f"Kamera berhasil diubah ke {sumber}" if sukses else f"Perangkat Kamera {sumber} tidak ditemukan di sistem Windows."
    })

@app.route('/status-scanner', methods=['GET'])
@app.route('/scanner/status-scanner', methods=['GET'])
@login_diperlukan
def status_scanner():
    """Mendapatkan hasil scan terakhir dan status kamera secara komprehensif."""
    return jsonify({
        "aktif": scanner.is_running,
        "fps": scanner.fps,
        "sumber": scanner.camera_source,
        "wajah_terdeteksi": bool(scanner.smooth_box is not None),
        "sedang_memproses": scanner.is_recognizing,
        "status_scanner": getattr(scanner, 'status_scanner', 'standby'),
        "pesan_scanner": getattr(scanner, 'pesan_scanner', 'Arahkan Wajah ke Kamera'),
        "detail_scanner": getattr(scanner, 'detail_scanner', 'Posisikan wajah Anda di dalam frame untuk presensi otomatis'),
        "hasil_terakhir": scanner.latest_result
    })

@app.route('/status', methods=['GET'])
def status_ai():
    """Cek kesehatan server AI (dipanggil secara internal oleh Laravel)."""
    return jsonify({
        "status": "aktif",
        "layanan": "Layanan AI DeepFace & OpenCV Presensi SMAN 2 Tondano",
        "model": konfigurasi.MODEL_NAME,
        "total_wajah_terindeks": len(layanan.embeddings_db),
        "siap_digunakan": layanan.is_ready
    })

# Endpoint internal untuk inferensi frame langsung dari Laravel
@app.route('/proses-frame', methods=['POST'])
@app.route('/kenali-wajah', methods=['POST'])
def proses_frame_api():
    data = request.get_json(silent=True) or {}
    gambar_base64 = data.get('gambar')
    if not gambar_base64:
        return jsonify({"status": "error", "pesan": "Payload 'gambar' diperlukan."}), 400
    hasil = layanan.proses_frame_kamera(gambar_base64)
    return jsonify(hasil)

# Endpoint Sinkronisasi & Ekstraksi Embedding Wajah Siswa (DeepFace FaceNet)
@app.route('/sinkronisasi-siswa/<nisn>', methods=['POST'])
@app.route('/scanner/sinkronisasi-siswa/<nisn>', methods=['POST'])
def sinkronisasi_siswa_api(nisn):
    """Mengekstrak embedding DeepFace FaceNet saat siswa baru / update foto diunggah dari Laravel."""
    data = request.get_json(silent=True) or {}
    nama = data.get('nama', '')
    hasil = layanan.ekstrak_dan_sinkronisasi_siswa(nisn, nama=nama)
    status_code = 200 if hasil.get("status") == "sukses" else 400
    return jsonify(hasil), status_code

# Endpoint Penghapusan Embedding Wajah Siswa
@app.route('/hapus-siswa/<nisn>', methods=['DELETE', 'POST'])
@app.route('/scanner/hapus-siswa/<nisn>', methods=['DELETE', 'POST'])
def hapus_siswa_api(nisn):
    """Menghapus embedding siswa dari memori & cache saat siswa dihapus di Laravel."""
    hasil = layanan.hapus_embedding_siswa(nisn)
    return jsonify(hasil)

# Endpoint Pembaruan Konfigurasi Akurasi Pemindaian (dari Laravel)
@app.route('/update-konfigurasi', methods=['POST'])
@app.route('/scanner/update-konfigurasi', methods=['POST'])
def update_konfigurasi_api():
    """Menerima pembaruan batas minimum akurasi dari Laravel secara real-time."""
    data = request.get_json(silent=True) or {}
    if 'min_face_accuracy' in data:
        try:
            val = float(data['min_face_accuracy'])
            layanan.min_face_accuracy = val
            scanner.min_face_accuracy = val
            print(f"[+] Batas minimum akurasi pemindaian diperbarui ke: {val:.2f}%")
            return jsonify({"status": "sukses", "min_face_accuracy": val})
        except Exception as e:
            return jsonify({"status": "error", "pesan": str(e)}), 400
    return jsonify({"status": "error", "pesan": "Payload 'min_face_accuracy' diperlukan."}), 400

if __name__ == '__main__':
    print("====================================================")
    print("  Server Scanner OpenCV Presensi Wajah SMAN 2 Tondano")
    print(f"  Berjalan di http://{konfigurasi.HOST}:{konfigurasi.PORT}")
    print("====================================================")
    layanan.inisialisasi()
    scanner.mulai()
    app.run(host=konfigurasi.HOST, port=konfigurasi.PORT, debug=False, threaded=True)
