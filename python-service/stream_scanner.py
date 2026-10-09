import os
import sys
import time
import threading
import cv2
import numpy as np
import requests
from deepface import DeepFace
import konfigurasi
from layanan_wajah import LayananWajah

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
                self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
                self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
                self.cap.set(cv2.CAP_PROP_FPS, 30)
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
        self.recognition_interval = 0.35 # detik antar inferensi
        
        # Cooldown per NISN agar tidak dobel presensi
        self.last_attendance = {}
        self.cooldown_sec = 6

        # Haar Cascade bawaan OpenCV
        cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
        self.face_cascade = cv2.CascadeClassifier(cascade_path)
        
        # Tracking wajah untuk temporal smoothing
        self.last_faces = []
        self.faces_missing_count = 0

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

            # Optimasi Deteksi: Jalankan Haar Cascade setiap 2 frame (interleaved)
            # Ini menghemat CPU sebesar 50% dan menjaga FPS tetap tinggi
            jalankan_deteksi = (frame_count % 2 == 0)

            if jalankan_deteksi:
                small = cv2.resize(frame, (0, 0), fx=0.5, fy=0.5)
                gray_small = cv2.cvtColor(small, cv2.COLOR_BGR2GRAY)
                faces_small = self.face_cascade.detectMultiScale(
                    gray_small, scaleFactor=1.18, minNeighbors=4, minSize=(30, 30)
                )

                if len(faces_small) > 0:
                    faces = [(int(x * 2), int(y * 2), int(fw * 2), int(fh * 2)) for (x, y, fw, fh) in faces_small]
                    self.last_faces = faces
                    self.faces_missing_count = 0
                else:
                    self.faces_missing_count += 1
                    if self.faces_missing_count <= 2:
                        faces = self.last_faces
                    else:
                        faces = []
            else:
                faces = self.last_faces

            # Trigger Asynchronous DeepFace di background worker terpisah
            if len(faces) > 0 and not self.is_recognizing:
                if (now - self.last_recognition_time) >= self.recognition_interval:
                    self.last_recognition_time = now
                    fx, fy, fw, fh = max(faces, key=lambda f: f[2] * f[3])
                    pad_w = int(fw * 0.12)
                    pad_h = int(fh * 0.12)
                    y1 = max(0, fy - pad_h)
                    y2 = min(h, fy + fh + pad_h)
                    x1 = max(0, fx - pad_w)
                    x2 = min(w, fx + fw + pad_w)

                    face_crop = frame[y1:y2, x1:x2].copy()
                    if face_crop.size > 0:
                        self.is_recognizing = True
                        threading.Thread(
                            target=self._worker_deepface,
                            args=(face_crop,),
                            daemon=True
                        ).start()

            # Gambar Anotasi Visual OpenCV langsung di atas frame
            self._gambar_anotasi_frame(frame, faces)

            # Kompresi JPEG cepat (kualitas 70 sangat ringan dan jernih)
            ret_enc, buffer = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 70])
            if ret_enc:
                with self.lock:
                    self.current_frame = frame
                    self.current_jpeg = buffer.tobytes()
                    self.frame_id += 1

            # Istirahat mikro untuk memberi kesempatan thread kamera membaca frame baru
            time.sleep(0.005)

    def _worker_deepface(self, face_crop):
        """Worker thread independen untuk inferensi FaceNet dan pengiriman ke Laravel."""
        try:
            if not self.layanan.embeddings_db or face_crop is None or face_crop.size == 0:
                return

            representations = DeepFace.represent(
                img_path=face_crop,
                model_name=self.layanan.model_name,
                detector_backend="skip",
                enforce_detection=False
            )

            if not representations or len(representations) == 0:
                return

            query_emb = np.array(representations[0]["embedding"], dtype=np.float32)
            query_norm = np.linalg.norm(query_emb)
            if query_norm > 0:
                query_emb = query_emb / query_norm

            db_embeddings = np.array([item["embedding"] for item in self.layanan.embeddings_db])
            cosine_distances = 1.0 - np.dot(db_embeddings, query_emb)

            best_idx = int(np.argmin(cosine_distances))
            min_dist = float(cosine_distances[best_idx])
            best_match = self.layanan.embeddings_db[best_idx]
            nisn = best_match["label"]

            skor = max(0.0, min(1.0, 1.0 - (min_dist / self.layanan.threshold)))
            persentase = f"{int(skor * 100)}%"

            siswa_data = self.layanan.data_siswa.get(nisn, {})
            nama_siswa = siswa_data.get("nama", f"Siswa {nisn}")
            kelas_siswa = siswa_data.get("kelas", "-")

            sekarang = time.time()
            if min_dist <= self.layanan.threshold:
                last_time = self.last_attendance.get(nisn, 0)
                status_absen = "HADIR"

                if (sekarang - last_time) > self.cooldown_sec:
                    self.last_attendance[nisn] = sekarang
                    try:
                        res_laravel = self.layanan.catat_presensi_ke_laravel(nisn)
                        if res_laravel:
                            status_absen = res_laravel.get("siswa", {}).get("status", "TERCATAT")
                    except Exception as e_api:
                        print(f"[!] Gagal mencatat presensi: {e_api}")

                self.latest_result = {
                    "nisn": nisn,
                    "nama": nama_siswa,
                    "kelas": kelas_siswa,
                    "akurasi": persentase,
                    "status": "sukses",
                    "status_absen": status_absen,
                    "timestamp": time.strftime("%H:%M:%S"),
                    "expire_at": sekarang + 2.5
                }
            else:
                self.latest_result = {
                    "nisn": None,
                    "nama": "TIDAK DIKENAL",
                    "akurasi": persentase,
                    "status": "tidak_dikenali",
                    "status_absen": "TIDAK TERDAFTAR",
                    "timestamp": time.strftime("%H:%M:%S"),
                    "expire_at": sekarang + 1.5
                }
        except Exception:
            pass
        finally:
            self.is_recognizing = False

    def _gambar_anotasi_frame(self, frame, faces):
        """Menggambar kotak deteksi wajah asli OpenCV dan HUD status."""
        h, w = frame.shape[:2]
        now = time.time()

        res = self.latest_result
        is_active = res and now < res.get("expire_at", 0)

        for (x, y, fw, fh) in faces:
            if is_active and res.get("status") == "sukses":
                color = (0, 220, 0) # Hijau
                label = f"{res.get('nama', 'Siswa')} ({res.get('akurasi', '')})"
                sub_label = f"[{res.get('status_absen', 'TERCATAT')}]"
                text_col = (0, 0, 0)
            elif is_active and res.get("status") == "tidak_dikenali":
                color = (0, 0, 240) # Merah
                label = "TIDAK DIKENAL"
                sub_label = "[TIDAK TERDAFTAR]"
                text_col = (255, 255, 255)
            else:
                color = (0, 220, 255) # Kuning
                label = "MEMINDAI WAJAH..."
                sub_label = "[DEEPFACE FACENET]"
                text_col = (0, 0, 0)

            # Kotak Wajah Utama
            cv2.rectangle(frame, (x, y), (x + fw, y + fh), color, 2)

            # Aksen Sudut (Corner Accents)
            c_len = max(14, int(min(fw, fh) * 0.20))
            c_thick = 3
            cv2.line(frame, (x, y), (x + c_len, y), color, c_thick)
            cv2.line(frame, (x, y), (x, y + c_len), color, c_thick)
            cv2.line(frame, (x + fw, y), (x + fw - c_len, y), color, c_thick)
            cv2.line(frame, (x + fw, y), (x + fw, y + c_len), color, c_thick)
            cv2.line(frame, (x, y + fh), (x + c_len, y + fh), color, c_thick)
            cv2.line(frame, (x, y + fh), (x, y + fh - c_len), color, c_thick)
            cv2.line(frame, (x + fw, y + fh), (x + fw - c_len, y + fh), color, c_thick)
            cv2.line(frame, (x + fw, y + fh), (x + fw, y + fh - c_len), color, c_thick)

            # Label Badge Atas Kepala
            font = cv2.FONT_HERSHEY_DUPLEX
            (tw, th), _ = cv2.getTextSize(label, font, 0.46, 1)
            badge_y1 = max(0, y - th - 10)
            badge_x2 = min(w, x + tw + 14)
            cv2.rectangle(frame, (x, badge_y1), (badge_x2, y), color, cv2.FILLED)
            cv2.putText(frame, label, (x + 6, y - 6), font, 0.46, text_col, 1, cv2.LINE_AA)

            # Badge Bawah Kotak
            (stw, sth), _ = cv2.getTextSize(sub_label, font, 0.36, 1)
            cv2.rectangle(frame, (x, y + fh), (min(w, x + stw + 12), min(h, y + fh + sth + 8)), color, cv2.FILLED)
            cv2.putText(frame, sub_label, (x + 6, y + fh + sth + 2), font, 0.36, text_col, 1, cv2.LINE_AA)

        # Header HUD Ringan
        header_h = 28
        header_roi = frame[0:header_h, 0:w]
        dark_bar = np.full_like(header_roi, (18, 18, 18))
        cv2.addWeighted(dark_bar, 0.75, header_roi, 0.25, 0, header_roi)
        frame[0:header_h, 0:w] = header_roi

        cv2.putText(frame, "SMAN 2 TONDANO | SCANNER OPENCV & FACENET", 
                    (10, 19), cv2.FONT_HERSHEY_SIMPLEX, 0.42, (0, 240, 180), 1, cv2.LINE_AA)

        fps_col = (0, 230, 0) if self.fps >= 20 else (0, 200, 255)
        info_hud = f"FPS: {self.fps} | DB: {len(self.layanan.embeddings_db)} WAJAH"
        (itw, _), _ = cv2.getTextSize(info_hud, cv2.FONT_HERSHEY_SIMPLEX, 0.38, 1)
        cv2.putText(frame, info_hud, (w - itw - 10, 19), 
                    cv2.FONT_HERSHEY_SIMPLEX, 0.38, fps_col, 1, cv2.LINE_AA)

    def generate_mjpeg_stream(self):
        """Generator MJPEG stream super responsif tanpa duplikasi frame."""
        last_sent_id = -1
        while True:
            with self.lock:
                if self.frame_id == last_sent_id or self.current_jpeg is None:
                    jpeg_bytes = None
                else:
                    jpeg_bytes = self.current_jpeg
                    last_sent_id = self.frame_id

            if jpeg_bytes is None:
                time.sleep(0.005)
                continue

            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + jpeg_bytes + b'\r\n')

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
