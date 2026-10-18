import os
import sys
import time
import threading
import cv2
import numpy as np
import requests
from deepface import DeepFace
from datetime import datetime
import zoneinfo
import konfigurasi
from layanan_wajah import LayananWajah, hitung_persentase_akurasi

TZ_WITA = zoneinfo.ZoneInfo("Asia/Makassar")

def dapatkan_jam_wita():
    try:
        return datetime.now(TZ_WITA).strftime("%H:%M:%S")
    except Exception:
        return time.strftime("%H:%M:%S")

class ThreadedCamera:
    """
    Kamera Threading Khusus (Dedicated Capture Thread).
    Membaca frame dari hardware USB/Webcam secara terus menerus di thread terpisah.
    Manfaat:
    - Menghilangkan buffer antrean hardware yang menyebabkan lag/delay
    - Fungsi read() mengembalikan frame terbaru secara instan (0.001 ms) tanpa pernah stuck
    - Mencapai 30-60+ FPS stabil pada Windows DirectShow, Iriun USB, maupun Webcam internal
    """
    def __init__(self, source=0):
        self.source = source
        self.cap = None
        self.ret = False
        self.frame = None
        self.is_running = False
        self.thread = None
        self.lock = threading.Lock()
        self._buka_kamera()

    def _buka_kamera(self):
        src = int(self.source) if str(self.source).isdigit() else self.source
        try:
            if isinstance(src, int):
                self.cap = cv2.VideoCapture(src, cv2.CAP_DSHOW)
                if not self.cap.isOpened():
                    self.cap = cv2.VideoCapture(src)
            else:
                self.cap = cv2.VideoCapture(src)
        except Exception as e:
            print(f"[!] Gagal membuka VideoCapture untuk sumber {src}: {e}")
            self.cap = None

        if self.cap and self.cap.isOpened():
            try:
                self.cap.set(cv2.CAP_PROP_FOURCC, cv2.VideoWriter_fourcc(*'MJPG'))
                self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
                self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
                self.cap.set(cv2.CAP_PROP_FPS, 60)
                self.cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
            except Exception:
                pass

            self.ret, self.frame = self.cap.read()
            self.is_running = True
            self.thread = threading.Thread(target=self._loop_capture, daemon=True)
            self.thread.start()

    def _loop_capture(self):
        """Thread khusus yang hanya membaca frame secepat mungkin dari sensor kamera."""
        while self.is_running and self.cap and self.cap.isOpened():
            ret, frame = self.cap.read()
            if ret and frame is not None:
                with self.lock:
                    self.ret = ret
                    self.frame = frame
            else:
                time.sleep(0.01)
            time.sleep(0.001)

    def read(self):
        """Mengambil frame kamera terbaru secara instan tanpa menunggu hardware."""
        with self.lock:
            if self.frame is not None:
                return self.ret, self.frame.copy()
            return False, None

    def stop(self):
        """Hentikan thread penangkap dan bebaskan hardware kamera."""
        self.is_running = False
        if self.thread and self.thread.is_alive():
            self.thread.join(timeout=1.0)
        if self.cap:
            try:
                self.cap.release()
            except Exception:
                pass
            self.cap = None

class ScannerOpenCV:
    """
    Mesin Pemindai Wajah Multi-Threaded OpenCV & DeepFace (Ultra Smooth & Zero Lag).
    - Thread 1: ThreadedCamera membaca sensor USB secara independen tanpa buffer bloat
    - Thread 2: Loop pemrosesan frame OpenCV & rendering visual HUD
    - Thread 3: Worker asynchronous DeepFace (Facenet) untuk identifikasi wajah non-blocking
    """
    def __init__(self, layanan_wajah=None, camera_source=0):
        self.layanan = layanan_wajah or LayananWajah()
        self.camera_source = camera_source
        self.camera = None
        self.is_running = False
        self.process_thread = None
        self.lock = threading.Lock()
        
        self.current_frame = None
        self.current_jpeg = None
        self.frame_id = 0
        self.latest_result = None
        self.fps = 0
        
        # Worker pengenalan wajah async DeepFace
        self.is_recognizing = False
        self.last_recognition_time = 0
        self.recognition_interval = 0.05 # detik antar inferensi (responsif instan)
        
        # Cooldown per NISN agar tidak dobel presensi
        self.last_attendance = {}
        self._last_status = {}
        self.cooldown_sec = 6
        self.scan_counter = 0

        # Status dinamis scanner untuk informasi real-time ke siswa
        self.status_scanner = "standby"
        self.pesan_scanner = "Arahkan Wajah ke Kamera"
        self.detail_scanner = "Posisikan wajah Anda di dalam frame untuk presensi otomatis"
        self.face_detected_time = 0.0

        # Deep Learning CNN Face Detector diwarisi dari instance LayananWajah (YuNet ONNX + CLAHE)

        # Tracking & smoothing wajah (menghilangkan kedip / jitter)
        self.smooth_box = None
        self.faces_missing_count = 0
        self.max_missing_frames = 12

        # Tampilan nama siswa yang stabil tanpa kedip
        self.active_name = ""
        self.name_display_until = 0.0

    def mulai(self):
        """Memulai kamera berulir dan loop pemrosesan OpenCV."""
        if self.is_running:
            return True

        if not self.layanan.is_ready:
            self.layanan.inisialisasi()

        print(f"[*] Menginisialisasi Threaded Camera (Sumber: {self.camera_source})...")
        self.camera = ThreadedCamera(self.camera_source)
        if not self.camera.cap or not self.camera.cap.isOpened():
            print(f"[!] Gagal membuka perangkat kamera: {self.camera_source}")
            return False

        self.is_running = True
        self.process_thread = threading.Thread(target=self._loop_pemrosesan, daemon=True)
        self.process_thread.start()
        print(f"[+] Kamera Berulir Aktif! Stream super mulus 30-60 FPS berjalan...")
        return True

    def berhenti(self):
        """Hentikan seluruh thread pemrosesan dan kamera."""
        self.is_running = False
        if self.process_thread and self.process_thread.is_alive():
            self.process_thread.join(timeout=1.5)
        if self.camera:
            self.camera.stop()
            self.camera = None
        print("[*] Scanner OpenCV dihentikan.")

    def ganti_sumber_kamera(self, sumber_baru):
        """Mengganti sumber kamera secara instan."""
        print(f"[*] Mengganti sumber kamera ke: {sumber_baru}")
        sumber_lama = self.camera_source
        self.berhenti()
        self.camera_source = sumber_baru
        sukses = self.mulai()
        if not sukses:
            print(f"[!] Gagal ke {sumber_baru}, mengembalikan ke {sumber_lama}...")
            self.camera_source = sumber_lama
            self.mulai()
            return False
        return True

    def _loop_pemrosesan(self):
        """Thread pemrosesan frame OpenCV, anotasi kotak, dan kompresi MJPEG."""
        frame_count = 0
        fps_start = time.time()

        while self.is_running and self.camera:
            ret, frame = self.camera.read()
            if not ret or frame is None:
                time.sleep(0.01)
                continue

            frame_count += 1
            now = time.time()
            if (now - fps_start) >= 1.0:
                self.fps = frame_count
                frame_count = 0
                fps_start = now

            h, w = frame.shape[:2]

            # Deteksi wajah menggunakan Deep Learning CNN (YuNet ONNX) secepat kilat (60+ FPS)
            faces_raw = self.layanan.deteksi_wajah_cnn(frame, min_score=0.40)

            current_face = None
            if len(faces_raw) > 0:
                # Ambil wajah utama / paling dominan di kamera
                faces_raw = sorted(faces_raw, key=lambda f: f[2] * f[3], reverse=True)
                tx, ty, tw, th = faces_raw[0][:4]

                if self.smooth_box is None:
                    self.smooth_box = [tx, ty, tw, th]
                else:
                    # Adaptive smoothing: jika wajah sedang bergerak/berjalan, respons seketika (zero lag)
                    # Jika wajah relatif diam, gunakan smoothing halus untuk anti-jitter
                    dx = abs(tx - self.smooth_box[0])
                    dy = abs(ty - self.smooth_box[1])
                    alpha = 0.85 if max(dx, dy) > 12 else 0.45

                    self.smooth_box[0] = int(alpha * tx + (1 - alpha) * self.smooth_box[0])
                    self.smooth_box[1] = int(alpha * ty + (1 - alpha) * self.smooth_box[1])
                    self.smooth_box[2] = int(alpha * tw + (1 - alpha) * self.smooth_box[2])
                    self.smooth_box[3] = int(alpha * th + (1 - alpha) * self.smooth_box[3])

                self.faces_missing_count = 0
                current_face = tuple(self.smooth_box)
            else:
                self.faces_missing_count += 1
                if self.faces_missing_count <= self.max_missing_frames and self.smooth_box is not None:
                    current_face = tuple(self.smooth_box)
                else:
                    self.smooth_box = None
                    current_face = None
                    if (now - self.last_recognition_time) > 2.0:
                        self.active_name = ""

            # Lacak durasi kehadiran wajah dan perbarui pesan/status scanner real-time
            if current_face is not None:
                if self.face_detected_time == 0.0:
                    self.face_detected_time = now

                durasi_hadir = now - self.face_detected_time
                cw, ch = current_face[2], current_face[3]

                if self.active_name and now < self.name_display_until:
                    self.status_scanner = "sukses"
                    self.pesan_scanner = self.active_name
                    self.detail_scanner = "Presensi Anda berhasil diverifikasi."
                elif cw < 40 or ch < 40:
                    self.status_scanner = "peringatan"
                    self.pesan_scanner = "Wajah Terlalu Jauh dari Kamera"
                    self.detail_scanner = "Silakan melangkah lebih dekat ke kamera agar wajah terdeteksi jelas."
                elif durasi_hadir > 2.5 and not self.active_name:
                    self.status_scanner = "memproses_lama"
                    self.pesan_scanner = "Menganalisis Biometrik Wajah..."
                    self.detail_scanner = "Proses membutuhkan waktu ekstra: pastikan wajah menghadap lurus dan pencahayaan ruangan cukup terang."
                elif self.is_recognizing or durasi_hadir >= 0.08:
                    if self.status_scanner not in ["gagal_akurasi", "tidak_dikenali"] or (now - self.last_recognition_time) > 1.8:
                        self.status_scanner = "memproses"
                        self.pesan_scanner = "Wajah Terdeteksi! Sedang Memindai..."
                        self.detail_scanner = "Mohon tahan posisi wajah Anda sejenak menghadap kamera."
            else:
                self.face_detected_time = 0.0
                if self.active_name and now < self.name_display_until:
                    self.status_scanner = "sukses"
                else:
                    self.status_scanner = "standby"
                    self.pesan_scanner = "Arahkan Wajah ke Kamera"
                    self.detail_scanner = "Posisikan wajah Anda di dalam frame untuk presensi otomatis"

            # Trigger Asynchronous DeepFace di background worker terpisah
            if current_face is not None and not self.is_recognizing:
                if (now - self.last_recognition_time) >= self.recognition_interval:
                    self.last_recognition_time = now
                    cx, cy, cw, ch = current_face
                    pad_top = int(ch * 0.35)
                    pad_bottom = int(ch * 0.35)
                    pad_side = int(cw * 0.30)
                    y1 = max(0, cy - pad_top)
                    y2 = min(h, cy + ch + pad_bottom)
                    x1 = max(0, cx - pad_side)
                    x2 = min(w, cx + cw + pad_side)

                    face_crop = frame[y1:y2, x1:x2].copy()
                    if face_crop.size > 0:
                        self.is_recognizing = True
                        threading.Thread(
                            target=self._worker_deepface,
                            args=(face_crop,),
                            daemon=True
                        ).start()

            # Gambar Kotak Wajah dan Nama Siswa (Bersih & Tanpa Kedip)
            self._gambar_anotasi_frame(frame, current_face)

            # Kompresi JPEG cepat (kualitas 65 sangat ringan, tajam, dan cepat dieksekusi)
            ret_enc, buffer = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 65])
            if ret_enc:
                with self.lock:
                    self.current_frame = frame
                    self.current_jpeg = buffer.tobytes()
                    self.frame_id += 1

            # Istirahat mikro minimal agar thread capture kamera dapat menulis frame baru
            time.sleep(0.001)

    def _kirim_presensi_background(self, nisn, scan_id):
        """Mengirim data absensi ke Laravel di thread latar belakang tanpa menghambat video feed atau DeepFace worker."""
        try:
            res_api = self.layanan.catat_presensi_ke_laravel(nisn)
            if res_api and isinstance(res_api, dict):
                st = res_api.get("siswa", {}).get("status")
                status_absen = "HADIR"
                if res_api.get("status") == "sudah_absen":
                    status_absen = "SUDAH ABSEN"
                elif st:
                    status_absen = str(st).upper()
                elif res_api.get("status") == "diluar_waktu":
                    status_absen = "DILUAR WAKTU ABSEN"
                elif res_api.get("status") in ["tidak_ditemukan", "error"]:
                    status_absen = "GAGAL"

                self._last_status[nisn] = status_absen

                # Update status_absen pada latest_result jika masih scan_id yang sama
                with self.lock:
                    if self.latest_result and self.latest_result.get("scan_id") == scan_id:
                        self.latest_result["status_absen"] = status_absen
                        if status_absen == "SUDAH ABSEN":
                            self.latest_result["status"] = "sudah_absen"
                            pesan_api = res_api.get("pesan") or "Sudah melakukan presensi hari ini."
                            self.detail_scanner = pesan_api
                            siswa_nama = self.latest_result.get("nama", "")
                            acc_fmt = self.latest_result.get("akurasi_format", "")
                            self.active_name = f"{siswa_nama} {acc_fmt} [SUDAH ABSEN]".strip()
                            self.latest_result["nama_dengan_akurasi"] = self.active_name
        except Exception as e_api:
            print(f"[!] Gagal mencatat presensi background: {e_api}")

    def _worker_deepface(self, face_crop):
        """Worker thread independen untuk inferensi FaceNet dan pengiriman ke Laravel."""
        try:
            if not self.layanan.embeddings_db or face_crop is None or face_crop.size == 0:
                return

            representations = DeepFace.represent(
                img_path=face_crop,
                model_name=self.layanan.model_name,
                detector_backend="opencv",
                enforce_detection=False
            )

            if not representations or len(representations) == 0:
                return

            query_emb = np.array(representations[0]["embedding"], dtype=np.float32)
            query_norm = np.linalg.norm(query_emb)
            if query_norm > 0:
                query_emb = query_emb / query_norm

            # Gunakan matriks 2D pre-computed jika ada untuk kalkulasi secepat kilat (0.01 ms)
            if getattr(self.layanan, 'embeddings_matrix', None) is not None and len(self.layanan.embeddings_matrix) > 0:
                cosine_distances = 1.0 - np.dot(self.layanan.embeddings_matrix, query_emb)
                best_idx = int(np.argmin(cosine_distances))
                min_dist = float(cosine_distances[best_idx])
                nisn = self.layanan.labels_list[best_idx]
            else:
                db_embeddings = np.array([item["embedding"] for item in self.layanan.embeddings_db])
                cosine_distances = 1.0 - np.dot(db_embeddings, query_emb)
                best_idx = int(np.argmin(cosine_distances))
                min_dist = float(cosine_distances[best_idx])
                nisn = self.layanan.embeddings_db[best_idx]["label"]

            # Hitung skor akurasi proporsional
            persentase_skor = hitung_persentase_akurasi(min_dist, self.layanan.threshold)
            persentase_format = f"({persentase_skor:.2f}%)"

            siswa_data = self.layanan.data_siswa.get(nisn, {})
            nama_siswa = siswa_data.get("nama")
            kelas_siswa = siswa_data.get("kelas", "-")

            sekarang = time.time()
            min_acc = getattr(self, 'min_face_accuracy', None) or getattr(self.layanan, 'min_face_accuracy', 70.0)
            max_thresh = max(float(self.layanan.threshold), 0.52)

            terdaftar_di_laravel = (not self.layanan.data_siswa) or (nisn in self.layanan.data_siswa)
            lolos = (min_dist <= max_thresh) and (persentase_skor >= min_acc) and terdaftar_di_laravel

            if not nama_siswa:
                nama_siswa = f"Siswa {nisn}"

            print(f"[*] Scan Wajah: NISN={nisn}, {nama_siswa}, Jarak={min_dist:.4f}, Akurasi={persentase_skor:.2f}%, Min={min_acc:.2f}%, Lolos={lolos}")

            # Lolos jika jarak cosine dalam threshold, akurasi >= batas minimum, dan NISN terdaftar di Laravel
            if lolos:
                last_time = self.last_attendance.get(nisn, 0)
                status_absen = self._last_status.get(nisn, "HADIR")
                self.scan_counter += 1
                current_scan_id = self.scan_counter

                self.status_scanner = "sukses"
                self.pesan_scanner = f"{nama_siswa} {persentase_format}"
                if status_absen == "SUDAH ABSEN":
                    self.detail_scanner = f"Siswa {kelas_siswa} • Sudah Melakukan Presensi Hari Ini"
                    self.active_name = f"{nama_siswa} {persentase_format} [SUDAH ABSEN]"
                else:
                    self.detail_scanner = f"Siswa {kelas_siswa} • Presensi Terverifikasi"
                    self.active_name = f"{nama_siswa} {persentase_format}"

                # Kirim data presensi ke Laravel secara background non-blocking
                if (sekarang - last_time) > self.cooldown_sec:
                    self.last_attendance[nisn] = sekarang
                    threading.Thread(
                        target=self._kirim_presensi_background,
                        args=(nisn, current_scan_id),
                        daemon=True
                    ).start()

                self.name_display_until = sekarang + 5.0

                with self.lock:
                    self.latest_result = {
                        "scan_id": current_scan_id,
                        "nisn": nisn,
                        "nama": nama_siswa,
                        "nama_dengan_akurasi": self.active_name,
                        "kelas": kelas_siswa,
                        "akurasi": f"{persentase_skor:.2f}%",
                        "akurasi_format": persentase_format,
                        "akurasi_num": persentase_skor,
                        "status": "sudah_absen" if status_absen == "SUDAH ABSEN" else "sukses",
                        "status_absen": status_absen,
                        "timestamp": dapatkan_jam_wita(),
                        "expire_at": sekarang + 5.0
                    }
            else:
                if not terdaftar_di_laravel:
                    self.status_scanner = "tidak_dikenali"
                    self.pesan_scanner = "Wajah Belum Terdaftar"
                    self.detail_scanner = f"Identitas NISN {nisn} tidak ditemukan dalam database siswa aktif."
                elif min_dist <= 0.52:
                    self.status_scanner = "gagal_akurasi"
                    self.pesan_scanner = f"Akurasi ({persentase_skor:.2f}%) Belum Memenuhi Syarat"
                    self.detail_scanner = f"Standar minimum sekolah adalah {min_acc:.2f}%. Mohon hadap lurus ke kamera dan pastikan cahaya cukup terang."
                else:
                    self.status_scanner = "tidak_dikenali"
                    self.pesan_scanner = "Wajah Tidak Dikenali / Belum Terdaftar"
                    self.detail_scanner = "Wajah Anda tidak cocok dengan data siswa terdaftar. Silakan hubungi operator sekolah."

                # Jangan mereset nama siswa secara mendadak jika baru saja terdeteksi
                if sekarang >= self.name_display_until:
                    self.active_name = ""
                    with self.lock:
                        self.latest_result = {
                            "scan_id": 0,
                            "nisn": None,
                            "nama": "Wajah Tidak Dikenali",
                            "akurasi": f"{persentase_skor:.2f}%",
                            "akurasi_format": persentase_format,
                            "akurasi_num": persentase_skor,
                            "status": "tidak_dikenali",
                            "status_absen": "TIDAK DIKENAL",
                            "pesan_gagal": self.pesan_scanner,
                            "detail_gagal": self.detail_scanner,
                            "timestamp": dapatkan_jam_wita(),
                            "expire_at": sekarang + 2.0
                        }
        except Exception as e:
            print(f"[!] Error pada _worker_deepface: {e}")
        finally:
            self.is_recognizing = False

    def _gambar_anotasi_frame(self, frame, face):
        """
        Menggambar kotak deteksi wajah dan nama siswa.
        Status di bawah kotak dihapus total sesuai permintaan pengguna.
        """
        if face is None:
            return

        x, y, fw, fh = face
        h, w = frame.shape[:2]
        now = time.time()

        is_recognized = bool(self.active_name and now < self.name_display_until)

        if is_recognized:
            if "SUDAH ABSEN" in self.active_name:
                color = (255, 140, 0) # BGR: Biru langit (B=255, G=140, R=0) untuk siswa yang sudah absen
            else:
                color = (0, 200, 0) # BGR: Hijau sukses untuk presensi baru
            nama_tampil = self.active_name
        else:
            # Kotak putih halus saat wajah terdeteksi (sebelum pengenalan)
            color = (255, 255, 255)
            nama_tampil = ""

        # 1. Gambar Kotak Wajah Utama
        cv2.rectangle(frame, (x, y), (x + fw, y + fh), color, 2)

        # 2. Gambar Nama Siswa di Atas Kotak Saja
        if nama_tampil:
            font = cv2.FONT_HERSHEY_DUPLEX
            font_scale = 0.52
            thickness = 1
            (tw, th), _ = cv2.getTextSize(nama_tampil, font, font_scale, thickness)

            badge_h = th + 12
            badge_w = tw + 16
            badge_y1 = max(0, y - badge_h)
            badge_x2 = min(w, x + badge_w)

            # Background badge nama
            cv2.rectangle(frame, (x, badge_y1), (badge_x2, y), color, cv2.FILLED)
            # Teks nama siswa berwarna putih
            cv2.putText(frame, nama_tampil, (x + 8, y - 7), font, font_scale, (255, 255, 255), thickness, cv2.LINE_AA)

        # CATATAN: Status di bawah kotak (sub_label) telah dihapus total sesuai permintaan pengguna!

    def generate_mjpeg_stream(self):
        """Generator MJPEG stream super responsif dan mulus 60 FPS tanpa lag buffer."""
        last_sent_id = -1
        while self.is_running:
            try:
                with self.lock:
                    if self.frame_id == last_sent_id or self.current_jpeg is None:
                        jpeg_bytes = None
                    else:
                        jpeg_bytes = self.current_jpeg
                        last_sent_id = self.frame_id

                if jpeg_bytes is None:
                    time.sleep(0.002)
                    continue

                yield (b'--frame\r\n'
                       b'Content-Type: image/jpeg\r\n\r\n' + jpeg_bytes + b'\r\n')
                time.sleep(0.003)
            except (GeneratorExit, ConnectionResetError, BrokenPipeError):
                break

    def jalankan_window_desktop(self):
        """Membuka window GUI OpenCV di desktop komputer."""
        if not self.mulai():
            return

        window_name = "Scanner Presensi Wajah OpenCV - SMAN 2 Tondano"
        cv2.namedWindow(window_name, cv2.WINDOW_AUTOSIZE)

        while self.is_running:
            with self.lock:
                frame = self.current_frame
            if frame is not None:
                cv2.imshow(window_name, frame)
            key = cv2.waitKey(10) & 0xFF
            if key == ord('q'):
                break

        cv2.destroyAllWindows()
        self.berhenti()

if __name__ == '__main__':
    sumber = sys.argv[1] if len(sys.argv) > 1 else 0
    scanner = ScannerOpenCV(camera_source=sumber)
    scanner.jalankan_window_desktop()
