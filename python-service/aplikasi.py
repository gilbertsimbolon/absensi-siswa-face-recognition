import os
import sys

# Konfigurasi encoding UTF-8 untuk output terminal / log Windows
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
if hasattr(sys.stderr, 'reconfigure'):
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

import hmac
import hashlib
import json
import base64
import time
from functools import wraps
from flask import Flask, request, jsonify
from flask_cors import CORS
import konfigurasi
from layanan_wajah import LayananWajah

# Inisialisasi Flask
app = Flask(__name__)
CORS(app)

# Inisialisasi Layanan AI Wajah (DeepFace & FaceNet)
layanan = LayananWajah()

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
def index():
    """Endpoint root - informasi status layanan AI."""
    return jsonify({
        "status": "aktif",
        "layanan": "Layanan AI DeepFace FaceNet Presensi SMAN 2 Tondano",
        "model": konfigurasi.MODEL_NAME,
        "total_wajah_terindeks": len(layanan.embeddings_db),
        "siap_digunakan": layanan.is_ready
    })

@app.route('/status-scanner', methods=['GET'])
@app.route('/scanner/status-scanner', methods=['GET'])
def status_scanner():
    """Status ketersediaan AI scanner (kompatibilitas)."""
    return jsonify({
        "aktif": layanan.is_ready,
        "status_scanner": "standby",
        "pesan_scanner": "Arahkan Wajah ke Kamera",
        "detail_scanner": "Posisikan wajah Anda di dalam frame untuk presensi otomatis"
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
    catat = bool(data.get('catat', False))
    hasil = layanan.proses_frame_kamera(gambar_base64, catat_laravel=catat)
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

# Endpoint Export Seluruh Embedding untuk Sinkronisasi Database Laravel
@app.route('/semua-embedding', methods=['GET'])
@app.route('/scanner/semua-embedding', methods=['GET'])
def semua_embedding_api():
    """Mengembalikan seluruh mapping NISN -> list of embeddings untuk disimpan di tabel MySQL."""
    result = {}
    for item in layanan.embeddings_db:
        lbl = str(item.get("label", "")).strip()
        if not lbl:
            continue
        if lbl not in result:
            result[lbl] = []
        raw_emb = item.get("embedding")
        emb_list = raw_emb.tolist() if hasattr(raw_emb, 'tolist') else list(raw_emb)
        result[lbl].append({
            "filename": os.path.basename(item.get("path", "")),
            "embedding": emb_list
        })
    return jsonify({"status": "sukses", "data": result})

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
            print(f"[+] Batas minimum akurasi pemindaian diperbarui ke: {val:.2f}%")
            return jsonify({"status": "sukses", "min_face_accuracy": val})
        except Exception as e:
            return jsonify({"status": "error", "pesan": str(e)}), 400
    return jsonify({"status": "error", "pesan": "Payload 'min_face_accuracy' diperlukan."}), 400

if __name__ == '__main__':
    print("====================================================")
    print("  Server AI DeepFace Presensi Wajah SMAN 2 Tondano")
    print(f"  Berjalan di http://{konfigurasi.HOST}:{konfigurasi.PORT}")
    print("====================================================")
    layanan.inisialisasi()
    try:
        app.run(host=konfigurasi.HOST, port=konfigurasi.PORT, debug=False, threaded=True)
    except BaseException as e:
        import traceback
        traceback.print_exc()
        print(f"[!] Server berhenti: {type(e).__name__}: {e}")
