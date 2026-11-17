import time
import requests

class AttendanceClient:
    """
    Klien HTTP untuk komunikasi dengan endpoint Presensi Laravel:
    - Mencatat kehadiran siswa secara otomatis saat wajah terverifikasi.
    - Mengelola jeda cooldown agar tidak terjadi duplikasi presensi.
    - Mengambil parameter konfigurasi akurasi minimum dari Laravel.
    """

    def __init__(self, laravel_api_url, cooldown_seconds=5):
        self.laravel_api_url = laravel_api_url.rstrip('/')
        self.cooldown_seconds = cooldown_seconds
        self.last_attendance_time = {}

    def muat_konfigurasi(self):
        """Memuat batas minimum akurasi dari konfigurasi Laravel."""
        try:
            url = f"{self.laravel_api_url}/api/presensi/konfigurasi"
            res = requests.get(url, timeout=3)
            if res.status_code == 200:
                cfg = res.json()
                min_acc = float(cfg.get("min_face_accuracy", 70.0))
                print(f"[+] AttendanceClient: Batas minimum akurasi Laravel: {min_acc:.2f}%")
                return min_acc
        except Exception as e:
            print(f"[!] AttendanceClient: Info konfigurasi akurasi Laravel: {e}")
        return 70.0

    def catat_presensi(self, nisn):
        """
        Memanggil API Laravel untuk mencatat absensi siswa secara otomatis.
        Menerapkan cooldown per-NISN agar tidak mencatat berulang dalam interval singkat.
        """
        sekarang = time.time()
        last_time = self.last_attendance_time.get(nisn, 0)

        if (sekarang - last_time) <= self.cooldown_seconds:
            return None

        try:
            url = f"{self.laravel_api_url}/api/presensi/pindai-wajah"
            payload = {"nisn": str(nisn)}
            response = requests.post(url, json=payload, timeout=3)
            self.last_attendance_time[nisn] = sekarang

            if response.status_code in [200, 400, 404]:
                return response.json()
            return None
        except Exception as e:
            print(f"[!] AttendanceClient: Gagal mencatat presensi ke Laravel untuk NISN {nisn}: {e}")
            return None
