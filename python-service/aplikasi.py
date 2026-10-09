import os
import sys
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

@app.route('/', methods=['GET'])
def index():
    """Halaman scanner OpenCV sederhana - m3urni stream video OpenCV tanpa JS scanner."""
    return render_template(
        'scanner.html',
        model_name=konfigurasi.MODEL_NAME,
        total_wajah=len(layanan.embeddings_db),
        kamera_aktif=scanner.camera_source
    )

@app.route('/video_feed')
def video_feed():
    """
    Stream MJPEG langsung dari kamera OpenCV.
    Setiap frame diproses dan digambar kotak deteksi wajah oleh OpenCV.
    Bisa langsung ditampilkan di tag <img> di web browser mana pun (HP atau PC).
    """
    if not scanner.is_running:
        scanner.mulai()
    return Response(
        scanner.generate_mjpeg_stream(),
        mimetype='multipart/x-mixed-replace; boundary=frame'
    )

@app.route('/ganti-kamera', methods=['POST'])
def ganti_kamera():
    """Mengubah sumber kamera OpenCV (Webcam 0 atau URL IP Camera HP)."""
    data = request.get_json(silent=True) or {}
    sumber = data.get('sumber', 0)
    sukses = scanner.ganti_sumber_kamera(sumber)
    return jsonify({
        "status": "sukses" if sukses else "error",
        "sumber_sekarang": scanner.camera_source,
        "pesan": f"Kamera berhasil diubah ke {sumber}" if sukses else f"Perangkat Kamera {sumber} tidak ditemukan di sistem Windows."
    })

@app.route('/status-scanner', methods=['GET'])
def status_scanner():
    """Mendapatkan hasil scan terakhir dan status kamera."""
    return jsonify({
        "aktif": scanner.is_running,
        "fps": scanner.fps,
        "sumber": scanner.camera_source,
        "hasil_terakhir": scanner.latest_result
    })

@app.route('/status', methods=['GET'])
def status_ai():
    """Cek kesehatan server AI."""
    return jsonify({
        "status": "aktif",
        "layanan": "Layanan AI DeepFace & OpenCV Presensi SMAN 2 Tondano",
        "model": konfigurasi.MODEL_NAME,
        "total_wajah_terindeks": len(layanan.embeddings_db),
        "siap_digunakan": layanan.is_ready
    })

# Endpoint fallback untuk upload frame (jika ada sistem eksternal yang ingin mengirim gambar)
@app.route('/proses-frame', methods=['POST'])
@app.route('/kenali-wajah', methods=['POST'])
def proses_frame_api():
    data = request.get_json(silent=True) or {}
    gambar_base64 = data.get('gambar')
    if not gambar_base64:
        return jsonify({"status": "error", "pesan": "Payload 'gambar' diperlukan."}), 400
    hasil = layanan.proses_frame_kamera(gambar_base64)
    return jsonify(hasil)

if __name__ == '__main__':
    print("====================================================")
    print("  Server Scanner OpenCV Presensi Wajah SMAN 2 Tondano")
    print(f"  Berjalan di http://{konfigurasi.HOST}:{konfigurasi.PORT}")
    print("====================================================")
    layanan.inisialisasi()
    scanner.mulai()
    app.run(host=konfigurasi.HOST, port=konfigurasi.PORT, debug=False, threaded=True)
