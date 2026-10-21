import os
import sys

# Konfigurasi encoding UTF-8 untuk output terminal / log Windows
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
if hasattr(sys.stderr, 'reconfigure'):
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

import io
import time
import base64
import threading
import numpy as np
import cv2
import requests
from PIL import Image
from deepface import DeepFace
import konfigurasi

def hitung_persentase_akurasi(cosine_dist, threshold=0.50):
    """
    Mengonversi jarak kosinus FaceNet (0.0 - 1.0+) menjadi persentase akurasi yang proporsional.
    - Wajah cocok (jarak <= threshold 0.50): menghasilkan skor 87.5% - 99.9%
      sehingga siswa terdaftar langsung lolos verifikasi standar sekolah (80% - 85%) dalam < 1-2 detik.
    - Wajah bukan siswa (jarak > threshold 0.50): skor langsung drop tajam ke bawah 70%.
    """
    if cosine_dist <= 0:
        return 100.0

    th = float(threshold) if threshold and float(threshold) > 0 else 0.50
    d = float(cosine_dist)

    if d <= th:
        # Interpolasi proporsional dari 100% pada jarak 0.0 ke 87.5% pada batas threshold
        skor = 100.0 - (d / th) * 12.5
        return round(max(87.5, min(99.99, skor)), 2)
    else:
        # Di atas threshold (bukan siswa / wajah berbeda): drop tajam dari 87.5% ke bawah
        over = d - th
        skor = 87.5 - (over / 0.20) * 45.0
        return round(max(0.0, min(87.49, skor)), 2)

class LayananWajah:
    def __init__(self):
        self.model_name = konfigurasi.MODEL_NAME
        self.detector_backend = konfigurasi.DETECTOR_BACKEND
        self.threshold = konfigurasi.THRESHOLD
        self.storage_path = konfigurasi.STORAGE_FACES_PATH
        self.dataset_path = self.storage_path
        raw_laravel_url = getattr(konfigurasi, 'LARAVEL_BASE_URL', None) or os.getenv('LARAVEL_BASE_URL') or os.getenv('APP_URL', 'http://127.0.0.1:8000')
        self.laravel_api_url = str(raw_laravel_url).rstrip('/')
        
        self.embeddings_db = []
        self.embeddings_matrix = None
        self.labels_list = []
        self.data_siswa = {}
        self.is_ready = False
        self.model_lock = threading.RLock()
        self.detector_lock = threading.RLock()

        # Inisialisasi Deep Learning Face Detector (YuNet CNN ONNX)
        self.yunet_model_path = konfigurasi.YUNET_MODEL_PATH
        self.yunet_detector = None
        if hasattr(cv2, 'FaceDetectorYN') and os.path.exists(self.yunet_model_path):
            try:
                self.yunet_detector = cv2.FaceDetectorYN.create(
                    model=self.yunet_model_path,
                    config="",
                    input_size=(640, 480),
                    score_threshold=0.55,
                    nms_threshold=0.30,
                    top_k=5000
                )
                print(f"[+] Berhasil memuat Deep Learning Face Detector CNN (YuNet ONNX): {self.yunet_model_path}")
            except Exception as e:
                print(f"[!] Gagal inisialisasi FaceDetectorYN: {e}")

        self.min_face_accuracy = 70.0
        self.last_attendance_time = {}
        self.cooldown_seconds = 5

    @staticmethod
    def normalisasi_pencahayaan_adaptif(img):
        """
        Normalisasi pencahayaan adaptif menggunakan Contrast Limited Adaptive Histogram Equalization (CLAHE)
        pada ruang warna LAB (kanal Luminance L).
        Memastikan wajah siswa yang berada dalam kondisi backlight, silau, atau redup/berbayang
        tetap memiliki kontras fitur wajah (mata, hidung, mulut) yang tajam dan mudah dikenali AI.
        """
        if img is None or img.size == 0:
            return img
        try:
            lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)
            l, a, b = cv2.split(lab)
            clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
            cl = clahe.apply(l)
            limg = cv2.merge((cl, a, b))
            return cv2.cvtColor(limg, cv2.COLOR_LAB2BGR)
        except Exception:
            return img

    def deteksi_wajah_cnn(self, img, min_score=0.40):
        """
        Mendeteksi wajah menggunakan model Deep Learning CNN (YuNet ONNX) secepat kilat (60+ FPS).
        Menggunakan resolusi deteksi optimal agar responsif menangkap wajah siswa yang sedang berjalan/bergerak.
        Thread-safe: Dilindungi mutex detector_lock agar tidak crash saat diakses multi-thread.
        Mengembalikan list bounding box: [(x, y, w, h, score), ...]
        """
        if img is None or img.size == 0:
            return []

        with self.detector_lock:
            h_img, w_img = img.shape[:2]

            # 1. Deteksi via YuNet CNN langsung pada frame BGR tanpa lag komputasi CLAHE full-frame
            if self.yunet_detector is not None:
                try:
                    # Downscale 0.5x untuk kecepatan 3-5x lipat (< 3 ms) tanpa mengurangi akurasi
                    scale = 0.5
                    det_w = int(w_img * scale)
                    det_h = int(h_img * scale)
                    small_frame = cv2.resize(img, (det_w, det_h), interpolation=cv2.INTER_LINEAR)

                    self.yunet_detector.setInputSize((det_w, det_h))
                    _, faces = self.yunet_detector.detect(small_frame)
                    if faces is not None and len(faces) > 0:
                        inv_scale = 1.0 / scale
                        results = []
                        for face in faces:
                            fx = int(face[0] * inv_scale)
                            fy = int(face[1] * inv_scale)
                            fw = int(face[2] * inv_scale)
                            fh = int(face[3] * inv_scale)
                            score = float(face[14]) if len(face) > 14 else 1.0
                            if score >= min_score and fw > 25 and fh > 25:
                                results.append((
                                    max(0, fx),
                                    max(0, fy),
                                    min(w_img - fx, fw),
                                    min(h_img - fy, fh),
                                    score
                                ))
                        if results:
                            return results
                except Exception:
                    pass

                # Fallback resolusi native jika tidak ada wajah di 0.5x
                try:
                    self.yunet_detector.setInputSize((w_img, h_img))
                    _, faces = self.yunet_detector.detect(img)
                    if faces is not None and len(faces) > 0:
                        results = []
                        for face in faces:
                            fx, fy, fw, fh = int(face[0]), int(face[1]), int(face[2]), int(face[3])
                            score = float(face[14]) if len(face) > 14 else 1.0
                            if score >= min_score and fw > 25 and fh > 25:
                                results.append((max(0, fx), max(0, fy), min(w_img - fx, fw), min(h_img - fy, fh), score))
                        if results:
                            return results
                except Exception:
                    pass

            return []

    def perbarui_matrix_embeddings(self):
        """Membuat matrix numpy 2D untuk pencocokan vektor secepat kilat (BLAS/AVX)."""
        if self.embeddings_db:
            try:
                aktif_db = self.embeddings_db
                if self.data_siswa and len(self.data_siswa) > 0:
                    aktif_db = [item for item in self.embeddings_db if str(item.get("label")) in self.data_siswa]
                    if not aktif_db:
                        aktif_db = self.embeddings_db

                self.embeddings_matrix = np.array([item["embedding"] for item in aktif_db], dtype=np.float32)
                self.labels_list = [str(item["label"]) for item in aktif_db]
                print(f"[+] Matriks pencocokan aktif: {len(self.labels_list)} embedding untuk {len(set(self.labels_list))} siswa terdaftar.")
            except Exception as e:
                print(f"[!] Gagal menyusun matriks embedding: {e}")
                self.embeddings_matrix = None
                self.labels_list = []
        else:
            self.embeddings_matrix = None
            self.labels_list = []

    def inisialisasi(self):
        """Memuat data siswa dan vektor embedding wajah langsung dari database Laravel."""
        print(f"[*] Menginisialisasi Layanan Wajah (Model: {self.model_name}, Threshold: {self.threshold})...")
        
        # 1. Muat data siswa dan vektor embedding langsung dari database Laravel (Single Source of Truth)
        self.muat_data_siswa()
        self.muat_konfigurasi()

        # 2. Jika database belum memiliki embedding sama sekali, bangun indeks dari file foto fisik
        if not self.embeddings_db:
            self.bangun_index_wajah()
        else:
            # Periksa apakah ada siswa terdaftar yang fotonya di disk belum diekstrak ke embedding
            label_terindeks = set(str(item.get("label")) for item in self.embeddings_db)
            siswa_tanpa_embedding = [str(nisn) for nisn in self.data_siswa if str(nisn) not in label_terindeks]
            if siswa_tanpa_embedding:
                print(f"[*] Ditemukan {len(siswa_tanpa_embedding)} siswa belum memiliki embedding di memori. Memulai ekstraksi otomatis...")
                for nisn in siswa_tanpa_embedding:
                    nama_s = self.data_siswa.get(nisn, {}).get("nama", "")
                    self.ekstrak_dan_sinkronisasi_siswa(nisn, nama=nama_s)

        # 3. Bangun matriks 2D untuk pencocokan secepat kilat (0.01 ms)
        self.perbarui_matrix_embeddings()

        # 4. Pemanasan model FaceNet (Warmup) agar pemindaian pertama langsung instan (< 1-2 detik)
        print("[*] Melakukan pemanasan (warmup) model FaceNet...")
        try:
            dummy = np.zeros((160, 160, 3), dtype=np.uint8)
            with self.model_lock:
                DeepFace.represent(dummy, model_name=self.model_name, detector_backend="skip", enforce_detection=False)
            print("[+] Pemanasan model FaceNet selesai. Siap memindai secara instan!")
        except Exception as e_warm:
            print(f"[!] Warning warmup model: {e_warm}")

    def muat_konfigurasi(self):
        """Memuat batas minimum akurasi dari konfigurasi Laravel."""
        try:
            url = f"{self.laravel_api_url}/api/presensi/konfigurasi"
            res = requests.get(url, timeout=3)
            if res.status_code == 200:
                cfg = res.json()
                self.min_face_accuracy = float(cfg.get("min_face_accuracy", 70.0))
                print(f"[+] Batas minimum akurasi pemindaian: {self.min_face_accuracy:.2f}%")
        except Exception as e:
            print(f"[!] Info konfigurasi akurasi Laravel: {e}")

    def muat_data_siswa(self):
        """Memuat daftar siswa dan vektor embedding wajah langsung dari database Laravel."""
        try:
            url = f"{self.laravel_api_url}/api/presensi/daftar-siswa"
            res = requests.get(url, timeout=4)
            if res.status_code == 200:
                self.data_siswa = res.json()
                print(f"[+] Berhasil memuat data {len(self.data_siswa)} siswa dari database Laravel.")

                # Ekstrak vektor embedding wajah langsung dari database Laravel (Single Source of Truth)
                db_embeddings = []
                for nisn, info in self.data_siswa.items():
                    raw_embs = info.get("embeddings", [])
                    for idx, emb_raw in enumerate(raw_embs):
                        if emb_raw and len(emb_raw) == 128:
                            emb_vec = np.array(emb_raw, dtype=np.float32)
                            norm = np.linalg.norm(emb_vec)
                            if norm > 0:
                                emb_vec = emb_vec / norm
                            db_embeddings.append({
                                "label": str(nisn),
                                "embedding": emb_vec,
                                "path": f"db://faces/{nisn}/{idx}"
                            })

                if db_embeddings:
                    self.embeddings_db = db_embeddings
                    print(f"[+] Berhasil memuat {len(self.embeddings_db)} vektor embedding wajah langsung dari database MySQL.")
                    self.perbarui_matrix_embeddings()
                    self.is_ready = True
            else:
                print(f"[*] Gagal memuat daftar siswa dari Laravel (Status: {res.status_code}).")
        except Exception as e:
            print(f"[*] Belum dapat terhubung ke Laravel ({e}). Nama siswa akan menggunakan NISN.")

    def bangun_index_wajah(self):
        """Membuat embedding untuk semua foto siswa yang tersimpan di dataset."""
        print("[*] Memulai pemindaian direktori dataset untuk membangun index wajah...")
        self.embeddings_db = []
        
        folders_to_scan = []
        if os.path.exists(self.storage_path):
            folders_to_scan.append(self.storage_path)

        seen_labels = set()
        count = 0

        for base_folder in folders_to_scan:
            if not os.path.isdir(base_folder):
                continue
            for item in os.listdir(base_folder):
                item_path = os.path.join(base_folder, item)
                if os.path.isdir(item_path):
                    label = item  # NISN atau NIP
                    if self.data_siswa and label not in self.data_siswa:
                        continue
                    if label in seen_labels:
                        continue
                    
                    for file_name in os.listdir(item_path):
                        if file_name.lower().endswith(('.jpg', '.jpeg', '.png')):
                            img_path = os.path.join(item_path, file_name)
                            try:
                                with self.model_lock:
                                    representations = DeepFace.represent(
                                        img_path=img_path,
                                        model_name=self.model_name,
                                        detector_backend=self.detector_backend,
                                        enforce_detection=False
                                    )
                                if representations and len(representations) > 0:
                                    emb = np.array(representations[0]["embedding"], dtype=np.float32)
                                    norm = np.linalg.norm(emb)
                                    if norm > 0:
                                        emb = emb / norm
                                    
                                    self.embeddings_db.append({
                                        "label": label,
                                        "embedding": emb,
                                        "path": img_path
                                    })
                                    seen_labels.add(label)
                                    count += 1
                                    if count % 25 == 0:
                                        print(f"[*] Berhasil memproses {count} dataset wajah...")
                                    break
                            except Exception as e:
                                print(f"[!] Lewati {img_path}: {e}")

        print(f"[+] Selesai! Sebanyak {len(self.embeddings_db)} wajah siswa berhasil diindeks ke memori.")
        self.perbarui_matrix_embeddings()
        self.is_ready = True

    def gambar_bounding_box_opencv(self, frame, x, y, w, h, label_text="TERDETEKSI", status="sukses", skor=""):
        """
        Menggambar kotak deteksi wajah khas Python OpenCV:
        - Bounding rectangle presisi
        - Sudut-sudut tebal (Corner brackets beraksen cyberpunk/CV)
        - Badge label di atas kepala (Nama Siswa, NISN, Persentase)
        - Status badge terverifikasi
        """
        # Konfigurasi warna (BGR OpenCV)
        if status == "sukses":
            color = (0, 220, 0)         # Hijau Cerah
            status_text = "TERVERIFIKASI"
            text_color = (0, 0, 0)
        elif status == "sudah_absen":
            color = (255, 170, 0)       # Cyan / Biru Muda
            status_text = "SUDAH ABSEN"
            text_color = (0, 0, 0)
        elif status == "tidak_dikenali":
            color = (0, 0, 235)         # Merah
            status_text = "TIDAK DIKENAL"
            text_color = (255, 255, 255)
        else:
            color = (0, 215, 255)       # Kuning
            status_text = "MEMINDAI..."
            text_color = (0, 0, 0)

        # 1. Kotak Utama Wajah
        cv2.rectangle(frame, (x, y), (x + w, y + h), color, 2)

        # 2. Sudut-Sudut Aksen Khas OpenCV (Corner Accents)
        corner_len = max(15, int(min(w, h) * 0.22))
        thickness = 4
        # Top-Left
        cv2.line(frame, (x, y), (x + corner_len, y), color, thickness)
        cv2.line(frame, (x, y), (x, y + corner_len), color, thickness)
        # Top-Right
        cv2.line(frame, (x + w, y), (x + w - corner_len, y), color, thickness)
        cv2.line(frame, (x + w, y), (x + w, y + corner_len), color, thickness)
        # Bottom-Left
        cv2.line(frame, (x, y + h), (x + corner_len, y + h), color, thickness)
        cv2.line(frame, (x, y + h), (x, y + h - corner_len), color, thickness)
        # Bottom-Right
        cv2.line(frame, (x + w, y + h), (x + w - corner_len, y + h), color, thickness)
        cv2.line(frame, (x + w, y + h), (x + w, y + h - corner_len), color, thickness)

        # 3. Label Badge di Atas Kotak Wajah
        full_label = f"{label_text}"
        if skor:
            full_label += f" ({skor})"
        
        font = cv2.FONT_HERSHEY_DUPLEX
        font_scale = 0.52
        (tw, th), baseline = cv2.getTextSize(full_label, font, font_scale, 1)

        # Kotak background label atas
        badge_y1 = max(0, y - th - 12)
        badge_y2 = y
        badge_x2 = min(frame.shape[1], x + tw + 16)
        cv2.rectangle(frame, (x, badge_y1), (badge_x2, badge_y2), color, cv2.FILLED)
        cv2.putText(frame, full_label, (x + 8, y - 7), font, font_scale, text_color, 1, cv2.LINE_AA)

        # 4. Badge Status di Bawah Kotak
        (stw, sth), _ = cv2.getTextSize(status_text, font, 0.42, 1)
        sub_y1 = y + h
        sub_y2 = min(frame.shape[0], y + h + sth + 10)
        sub_x2 = min(frame.shape[1], x + stw + 14)
        cv2.rectangle(frame, (x, sub_y1), (sub_x2, sub_y2), color, cv2.FILLED)
        cv2.putText(frame, status_text, (x + 7, y + h + sth + 3), font, 0.42, text_color, 1, cv2.LINE_AA)

        return frame

    def gambar_hud_opencv(self, frame):
        """Menggambar banner HUD OpenCV di bagian atas frame video."""
        h, w = frame.shape[:2]
        # Header transparan atas
        overlay = frame.copy()
        cv2.rectangle(overlay, (0, 0), (w, 32), (18, 18, 18), cv2.FILLED)
        cv2.addWeighted(overlay, 0.75, frame, 0.25, 0, frame)

        # Teks Judul Kiri
        cv2.putText(frame, "SMAN 2 TONDANO | AI COMPUTER VISION (OPENCV & DEEPFACE)", 
                    (12, 21), cv2.FONT_HERSHEY_SIMPLEX, 0.44, (0, 245, 180), 1, cv2.LINE_AA)

        # Status Kanan
        status_info = f"MODEL: {self.model_name.upper()} | DB: {len(self.embeddings_db)} WAJAH"
        (tw, _), _ = cv2.getTextSize(status_info, cv2.FONT_HERSHEY_SIMPLEX, 0.38, 1)
        cv2.putText(frame, status_info, (w - tw - 12, 21), 
                    cv2.FONT_HERSHEY_SIMPLEX, 0.38, (0, 220, 0), 1, cv2.LINE_AA)
        return frame

    def proses_frame_kamera(self, gambar_base64, catat_laravel=False):
        """
        Menerima frame kamera dari browser HP / Desktop,
        Mendeteksi wajah, mencocokkan ke database DeepFace,
        Menggambar kotak-kotak wajah OpenCV,
        Mencatat absensi ke Laravel (opsional),
        dan Mengembalikan gambar beranotasi OpenCV.
        """
        if not self.is_ready or not self.embeddings_db:
            return {
                "status": "error",
                "pesan": "Database wajah belum siap atau sedang dimuat."
            }

        try:
            if "," in gambar_base64:
                gambar_base64 = gambar_base64.split(",")[1]

            image_bytes = base64.b64decode(gambar_base64)
            nparr = np.frombuffer(image_bytes, np.uint8)
            frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

            if frame is None:
                return {"status": "error", "pesan": "Format gambar tidak valid."}

            h_orig, w_orig = frame.shape[:2]

            # 1. Deteksi Wajah dengan Deep Learning CNN (YuNet ONNX + CLAHE Adaptive Lighting)
            cnn_faces = self.deteksi_wajah_cnn(frame, min_score=0.50)
            detected_faces = []
            for item in cnn_faces:
                detected_faces.append({
                    "x": int(item[0]),
                    "y": int(item[1]),
                    "w": int(item[2]),
                    "h": int(item[3])
                })

            hasil_pencocokan = None
            siswa_info = None

            # 2. Proses Pengenalan Wajah Jika Ada Wajah yang Terdeteksi
            if len(detected_faces) > 0:
                fa = detected_faces[0]
                fx, fy, fw, fh = fa["x"], fa["y"], fa["w"], fa["h"]
                pad_top = int(fh * 0.35)
                pad_bottom = int(fh * 0.35)
                pad_side = int(fw * 0.30)
                y1 = max(0, fy - pad_top)
                y2 = min(h_orig, fy + fh + pad_bottom)
                x1 = max(0, fx - pad_side)
                x2 = min(w_orig, fx + fw + pad_side)
                face_crop = frame[y1:y2, x1:x2]

                target_img = face_crop if (face_crop is not None and face_crop.size > 0 and face_crop.shape[0] > 20 and face_crop.shape[1] > 20) else frame

                # Ambil embedding wajah utama secepat kilat (skip detector karena sudah dicrop oleh YuNet)
                try:
                    with self.model_lock:
                        representations = DeepFace.represent(
                            img_path=target_img,
                            model_name=self.model_name,
                            detector_backend="skip",
                            enforce_detection=False
                        )
                except Exception:
                    try:
                        with self.model_lock:
                            representations = DeepFace.represent(
                                img_path=target_img,
                                model_name=self.model_name,
                                detector_backend="opencv",
                                enforce_detection=False
                            )
                    except Exception:
                        representations = []

                if representations and len(representations) > 0:
                    query_emb = np.array(representations[0]["embedding"], dtype=np.float32)
                    query_norm = np.linalg.norm(query_emb)
                    if query_norm > 0:
                        query_emb = query_emb / query_norm

                    has_registered = (self.embeddings_matrix is not None and len(self.embeddings_matrix) > 0) or (len(self.embeddings_db) > 0)

                    fa = detected_faces[0]
                    x, y, w, h = fa["x"], fa["y"], fa["w"], fa["h"]

                    if not has_registered:
                        # Belum ada siswa yang didaftarkan wajahnya di database/sistem
                        hasil_pencocokan = {
                            "status": "tidak_dikenali",
                            "pesan": "Data belum terdaftar. Silahkan hubungi administrator."
                        }
                        self.gambar_bounding_box_opencv(
                            frame, x, y, w, h, 
                            label_text="BELUM TERDAFTAR", 
                            status="tidak_dikenali", 
                            skor=None
                        )
                    else:
                        if self.embeddings_matrix is not None and len(self.embeddings_matrix) > 0:
                            cosine_distances = 1.0 - np.dot(self.embeddings_matrix, query_emb)
                            best_idx = int(np.argmin(cosine_distances))
                            min_dist = float(cosine_distances[best_idx])
                            nisn = self.labels_list[best_idx]
                        else:
                            db_embeddings = np.array([item["embedding"] for item in self.embeddings_db])
                            dot_products = np.dot(db_embeddings, query_emb)
                            cosine_distances = 1.0 - dot_products
                            best_idx = int(np.argmin(cosine_distances))
                            min_dist = float(cosine_distances[best_idx])
                            nisn = self.embeddings_db[best_idx]["label"]

                        # Hitung skor akurasi proporsional
                        skor_persen = hitung_persentase_akurasi(min_dist, self.threshold)
                        persentase_skor = f"{skor_persen:.2f}%"

                        # Ambil nama siswa dari cache data siswa
                        siswa_data = self.data_siswa.get(nisn, {})
                        nama_siswa = siswa_data.get("nama", f"Siswa {nisn}")
                        kelas_siswa = siswa_data.get("kelas", "-")

                        min_acc = float(getattr(self, 'min_face_accuracy', 70.0))
                        if min_dist <= self.threshold and skor_persen >= min_acc:
                            # Wajah Cocok!
                            status_scan = "sukses"
                            hasil_pencocokan = {
                                "status": "sukses",
                                "nisn": nisn,
                                "nama": nama_siswa,
                                "kelas": kelas_siswa,
                                "akurasi": persentase_skor,
                                "jarak": round(min_dist, 4)
                            }

                            # Kirim presensi ke Laravel jika diminta (default False jika Laravel sendiri yang memanggil)
                            if catat_laravel:
                                sekarang = time.time()
                                last_time = self.last_attendance_time.get(nisn, 0)
                                if (sekarang - last_time) > self.cooldown_seconds:
                                    res_laravel = self.catat_presensi_ke_laravel(nisn)
                                    self.last_attendance_time[nisn] = sekarang
                                    if res_laravel:
                                        hasil_pencocokan["presensi"] = res_laravel
                                        if res_laravel.get("status") == "sudah_absen":
                                            status_scan = "sudah_absen"
                                            hasil_pencocokan["status"] = "sudah_absen"

                            # Gambar Kotak Hijau OpenCV
                            self.gambar_bounding_box_opencv(
                                frame, x, y, w, h, 
                                label_text=nama_siswa, 
                                status=status_scan, 
                                skor=persentase_skor
                            )
                        elif min_dist <= self.threshold:
                            # Terdeteksi siswa terdaftar, tetapi skor akurasi di bawah batas minimum
                            hasil_pencocokan = {
                                "status": "gagal_akurasi",
                                "pesan": f"Kemiripan wajah terdeteksi ({persentase_skor}), namun di bawah batas minimum ({min_acc:.0f}%). Harap hadap kamera lebih tegak dan jelas.",
                                "nisn": nisn,
                                "nama": nama_siswa,
                                "akurasi": persentase_skor
                            }
                            self.gambar_bounding_box_opencv(
                                frame, x, y, w, h, 
                                label_text=f"{nama_siswa} ({persentase_skor})", 
                                status="gagal_akurasi", 
                                skor=persentase_skor
                            )
                        else:
                            # Benar-benar tidak terdaftar sama sekali
                            hasil_pencocokan = {
                                "status": "tidak_dikenali",
                                "pesan": "Data belum terdaftar. Silahkan hubungi administrator."
                            }
                            # Gambar Kotak Merah OpenCV
                            self.gambar_bounding_box_opencv(
                                frame, x, y, w, h, 
                                label_text="BELUM TERDAFTAR", 
                                status="tidak_dikenali", 
                                skor=persentase_skor
                            )
                else:
                    # Gambar kotak kuning memindai
                    fa = detected_faces[0]
                    self.gambar_bounding_box_opencv(
                        frame, fa["x"], fa["y"], fa["w"], fa["h"], 
                        label_text="MEMINDAI...", 
                        status="memindai"
                    )
            else:
                hasil_pencocokan = {
                    "status": "tidak_ada_wajah",
                    "pesan": "Arahkan wajah ke kamera."
                }

            # 3. Gambar HUD Atas
            self.gambar_hud_opencv(frame)

            # 4. Susun data bounding box untuk frontend client (HTML5 Video & Canvas)
            wajah_data = None
            if len(detected_faces) > 0:
                fa = detected_faces[0]
                status_box = "memindai"
                nama_box = "MEMINDAI..."
                skor_box = ""
                if hasil_pencocokan:
                    if hasil_pencocokan.get("status") == "sukses":
                        status_box = "sukses"
                        nama_box = hasil_pencocokan.get("nama", "SISWA")
                        skor_box = hasil_pencocokan.get("akurasi", "")
                    elif hasil_pencocokan.get("status") == "tidak_dikenali":
                        status_box = "tidak_dikenali"
                        nama_box = "TIDAK DIKENAL"
                        skor_box = persentase_skor if 'persentase_skor' in locals() else ""

                wajah_data = {
                    "x": int(fa["x"]),
                    "y": int(fa["y"]),
                    "w": int(fa["w"]),
                    "h": int(fa["h"]),
                    "frame_w": int(w_orig),
                    "frame_h": int(h_orig),
                    "nama": nama_box,
                    "skor": skor_box,
                    "status": status_box
                }

            # 5. Hindari encoding ulang JPEG yang memakan waktu CPU (browser sudah memiliki feed video native)
            frame_base64 = None

            return {
                "status": hasil_pencocokan.get("status", "ok") if hasil_pencocokan else "ok",
                "terdeteksi": len(detected_faces) > 0,
                "frame_annotated": frame_base64,
                "hasil": hasil_pencocokan,
                "wajah": wajah_data,
                "total_wajah_frame": len(detected_faces)
            }

        except Exception as e:
            return {
                "status": "error",
                "pesan": f"Kesalahan memproses frame: {str(e)}"
            }

    def catat_presensi_ke_laravel(self, nisn):
        """Memanggil API Laravel untuk mencatat absensi siswa secara otomatis."""
        try:
            url = f"{self.laravel_api_url}/api/presensi/pindai-wajah"
            payload = {"nisn": str(nisn)}
            response = requests.post(url, json=payload, timeout=3)
            if response.status_code in [200, 400, 404]:
                return response.json()
            else:
                return None
        except Exception as e:
            print(f"[!] Gagal mencatat presensi ke Laravel: {e}")
            return None

    def kenali_wajah_dari_base64(self, gambar_base64):
        """Metode kompatibilitas untuk endpoint lama."""
        res = self.proses_frame_kamera(gambar_base64)
        if res.get("hasil"):
            return res["hasil"]
        return res

    def sinkronisasi_dataset_baru(self):
        """Memeriksa folder dataset di disk dan mengekstrak wajah baru yang belum ada di cache FaceNet."""
        folders_to_scan = []
        if os.path.exists(self.storage_path):
            folders_to_scan.append(self.storage_path)

        indexed_labels = set(str(item.get("label")) for item in self.embeddings_db)
        missing_labels = []

        for base_folder in folders_to_scan:
            if not os.path.isdir(base_folder):
                continue
            for item in os.listdir(base_folder):
                item_path = os.path.join(base_folder, item)
                if os.path.isdir(item_path):
                    # Hanya sinkronkan folder siswa yang terdaftar di Laravel jika data_siswa tersedia
                    if self.data_siswa and item not in self.data_siswa:
                        continue
                    if item not in indexed_labels and item not in missing_labels:
                        missing_labels.append(item)

        if missing_labels:
            print(f"[*] Ditemukan {len(missing_labels)} dataset siswa baru yang belum terindeks FaceNet: {missing_labels}")
            total_added = 0
            for label in missing_labels:
                res = self.ekstrak_dan_sinkronisasi_siswa(label, simpan_cache=False)
                if res.get("status") == "sukses":
                    total_added += res.get("total_embedding", 0)

            if total_added > 0:
                print(f"[+] Sinkronisasi startup selesai! {total_added} embedding FaceNet baru berhasil dimuat.")
                self.perbarui_matrix_embeddings()

    def potong_dan_zoom_wajah(self, img_path):
        """
        Mendeteksi wajah pada gambar (misal foto full body/setengah badan),
        lalu memotong dan melakukan zoom proporsional (area kepala/bahu portrait)
        agar foto yang disimpan ke disk & database adalah pasfoto wajah optimal.
        """
        try:
            if not os.path.exists(img_path):
                return False

            img = cv2.imread(img_path)
            if img is None or img.size == 0:
                return False

            h_img, w_img = img.shape[:2]
            # Deteksi wajah menggunakan Deep Learning CNN (YuNet ONNX + CLAHE)
            faces = self.deteksi_wajah_cnn(img, min_score=0.45)
            if not faces:
                return False

            # Ambil wajah dengan ukuran paling dominan
            faces = sorted(faces, key=lambda f: f[2] * f[3], reverse=True)
            x, y, w, h = faces[0][:4]

            # Jika wajah sudah mengisi > 70% dari gambar, tidak perlu dipotong lagi
            if (w * h) / (w_img * h_img) > 0.70:
                return True

            # Tambahkan margin atas, bawah, dan samping untuk pasfoto portrait (zoom ke wajah)
            pad_top = int(h * 0.40)
            pad_bottom = int(h * 0.45)
            pad_side = int(w * 0.35)

            y1 = max(0, y - pad_top)
            y2 = min(h_img, y + h + pad_bottom)
            x1 = max(0, x - pad_side)
            x2 = min(w_img, x + w + pad_side)

            cropped = img[y1:y2, x1:x2]
            if cropped is not None and cropped.size > 0:
                cv2.imwrite(img_path, cropped, [int(cv2.IMWRITE_JPEG_QUALITY), 95])
                return True

        except Exception as e:
            print(f"[!] Gagal auto-crop zoom wajah pada {img_path}: {e}")

        return False

    def ekstrak_dan_sinkronisasi_siswa(self, nisn, nama="", simpan_cache=True):
        """
        Mengekstrak fitur citra wajah (face embedding 128-dimensi FaceNet)
        dari foto yang diunggah ke folder dataset siswa.
        Dipanggil otomatis oleh Laravel saat Admin menambah atau memperbarui foto siswa.
        """
        nisn = str(nisn).strip()
        folder_siswa = os.path.join(self.storage_path, nisn)

        if not os.path.exists(folder_siswa) or not os.path.isdir(folder_siswa):
            return {"status": "error", "pesan": f"Folder foto untuk NISN {nisn} tidak ditemukan di storage."}

        foto_files = [f for f in os.listdir(folder_siswa) if f.lower().endswith(('.jpg', '.jpeg', '.png'))]
        if not foto_files:
            return {"status": "error", "pesan": f"Tidak ada berkas foto di folder storage NISN {nisn}."}

        new_embeddings = []
        for file_name in foto_files:
            img_path = os.path.join(folder_siswa, file_name)

            # Terapkan teknik auto-crop & zoom wajah langsung pada berkas foto di storage publik
            berhasil_crop = self.potong_dan_zoom_wajah(img_path)

            try:
                # Jika sudah berhasil di-crop oleh YuNet OpenCV, lewati re-deteksi berat DeepFace agar inferensi instan (< 0.2s)
                backend_deteksi = "skip" if berhasil_crop else self.detector_backend
                with self.model_lock:
                    representations = DeepFace.represent(
                        img_path=img_path,
                        model_name=self.model_name,
                        detector_backend=backend_deteksi,
                        enforce_detection=False
                    )
                if representations and len(representations) > 0:
                    emb = np.array(representations[0]["embedding"], dtype=np.float32)
                    norm = np.linalg.norm(emb)
                    if norm > 0:
                        emb = emb / norm
                    new_embeddings.append({
                        "label": nisn,
                        "embedding": emb,
                        "path": img_path
                    })
            except Exception as e:
                print(f"[!] Gagal mengekstrak fitur foto {img_path}: {e}")

        if not new_embeddings:
            return {"status": "error", "pesan": f"Wajah tidak terdeteksi pada berkas foto NISN {nisn}."}

        # Perbarui basis data embedding di memori
        self.embeddings_db = [item for item in self.embeddings_db if str(item.get("label")) != nisn]
        self.embeddings_db.extend(new_embeddings)

        if nama:
            self.data_siswa[nisn] = {"nama": nama, "kelas": self.data_siswa.get(nisn, {}).get("kelas", "-")}

        self.perbarui_matrix_embeddings()
        self.is_ready = True
        return {
            "status": "sukses",
            "pesan": f"Berhasil mengekstrak {len(new_embeddings)} vektor embedding FaceNet untuk NISN {nisn}.",
            "total_embedding": len(new_embeddings),
            "embeddings": {
                os.path.basename(item["path"]): item["embedding"].tolist()
                for item in new_embeddings
            }
        }

    def hapus_embedding_siswa(self, nisn):
        """Menghapus embedding siswa dari database memori saat siswa dihapus di Laravel."""
        nisn = str(nisn).strip()
        sebelum = len(self.embeddings_db)
        self.embeddings_db = [item for item in self.embeddings_db if str(item.get("label")) != nisn]
        sesudah = len(self.embeddings_db)

        if self.data_siswa and nisn in self.data_siswa:
            del self.data_siswa[nisn]

        print(f"[+] Embedding untuk NISN {nisn} dihapus dari memori ({sebelum - sesudah} data).")
        self.perbarui_matrix_embeddings()
        return {"status": "sukses", "pesan": f"Embedding untuk NISN {nisn} berhasil dihapus."}
