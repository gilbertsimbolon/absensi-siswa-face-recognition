import os
import sys

# Pastikan encoding UTF-8 untuk terminal Windows agar Deepface tidak crash saat log emoji
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
if hasattr(sys.stderr, 'reconfigure'):
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')
os.environ['PYTHONIOENCODING'] = 'utf-8'
os.environ['PYTHONUTF8'] = '1'

# Jalankan server aplikasi Flask dengan Pemindai OpenCV
if __name__ == '__main__':
    from aplikasi import app, layanan, scanner
    import konfigurasi

    print("====================================================")
    print("  Server Scanner OpenCV Presensi SMAN 2 Tondano     ")
    print(f"  Berjalan di http://{konfigurasi.HOST}:{konfigurasi.PORT}")
    print("====================================================")
    layanan.inisialisasi()
    scanner.mulai()
    app.run(host=konfigurasi.HOST, port=konfigurasi.PORT, debug=False, threaded=True)
