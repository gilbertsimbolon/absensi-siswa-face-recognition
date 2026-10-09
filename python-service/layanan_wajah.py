import os
import io
import time
import base64
import pickle
import numpy as np
import cv2
import requests
from PIL import Image
from deepface import DeepFace
import konfigurasi

class LayananWajah:
    def __init__(self):
        self.model_name = konfigurasi.MODEL_NAME
        self.detector_backend = konfigurasi.DETECTOR_BACKEND
        self.threshold = konfigurasi.THRESHOLD
        self.dataset_path = konfigurasi.DATASET_PATH
        self.storage_path = konfigurasi.STORAGE_FACES_PATH
        self.cache_file = konfigurasi.CACHE_EMBEDDINGS_FILE
        self.laravel_api_url = "http://127.0.0.1:8000"
        
        self.embeddings_db = []
        self.data_siswa = {}
        self.is_ready = False
        
        # Inisialisasi Haar Cascade Classifier untuk deteksi wajah OpenCV
        cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
        self.face_cascade = cv2.CascadeClassifier(cascade_path)
        
        # Cooldown agar tidak mengirim absensi bertubi-tubi untuk siswa yang sama
        self.last_attendance_time = {}
        self.cooldown_seconds = 5

    def inisialisasi(self):
        """Memuat cache embeddings dan data siswa dari Laravel."""
        print(f"[*] Menginisialisasi Layanan Wajah (Model: {self.model_name}, Threshold: {self.threshold})...")
        
        # 1. Muat database embeddings wajah
        if os.path.exists(self.cache_file):
            try:
                with open(self.cache_file, "rb") as f:
                    self.embeddings_db = pickle.load(f)
                print(f"[+] Berhasil memuat {len(self.embeddings_db)} data wajah dari cache ({self.cache_file}).")
                self.is_ready = True
            except Exception as e:
                print(f"[!] Gagal membaca cache: {e}. Melakukan indeks ulang dataset...")
                self.bangun_index_wajah()
        else:
            self.bangun_index_wajah()
            
        # 2. Muat data nama siswa dari Laravel
        self.muat_data_siswa()

    def muat_data_siswa(self):
        """Memuat daftar siswa (NISN => Nama, Kelas) dari backend Laravel."""
        try:
            url = f"{self.laravel_api_url}/api/presensi/daftar-siswa"
            res = requests.get(url, timeout=4)
            if res.status_code == 200:
                self.data_siswa = res.json()
                print(f"[+] Berhasil memuat data {len(self.data_siswa)} siswa dari Laravel.")
            else:
                print(f"[*] Gagal memuat daftar siswa dari Laravel (Status: {res.status_code}).")
        except Exception as e:
            print(f"[*] Belum dapat terhubung ke Laravel ({e}). Nama siswa akan menggunakan NISN.")

    def bangun_index_wajah(self):
        """Membuat embedding untuk semua foto siswa yang tersimpan di dataset."""
        print("[*] Memulai pemindaian direktori dataset untuk membangun index wajah...")
        self.embeddings_db = []
        
        folders_to_scan = []
        if os.path.exists(self.dataset_path):
            folders_to_scan.append(self.dataset_path)
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
                    if label in seen_labels:
                        continue
                    
                    for file_name in os.listdir(item_path):
                        if file_name.lower().endswith(('.jpg', '.jpeg', '.png')):
                            img_path = os.path.join(item_path, file_name)
                            try:
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

        print(f"[+] Selesai! Sebanyak {len(self.embeddings_db)} wajah siswa berhasil diindeks.")

        try:
            with open(self.cache_file, "wb") as f:
                pickle.dump(self.embeddings_db, f)
            print(f"[+] Cache embedding disimpan ke {self.cache_file}")
        except Exception as e:
            print(f"[!] Gagal menyimpan cache: {e}")

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

    def proses_frame_kamera(self, gambar_base64):
        """
        Menerima frame kamera dari browser HP / Desktop,
        Mendeteksi wajah, mencocokkan ke database DeepFace,
        Menggambar kotak-kotak wajah OpenCV,
        Mencatat absensi ke Laravel,
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

            # 1. Deteksi Wajah Cepat dengan Haar Cascade OpenCV
            gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
            faces = self.face_cascade.detectMultiScale(
                gray, scaleFactor=1.15, minNeighbors=5, minSize=(60, 60)
            )

            # Jika Haar Cascade belum mendeteksi, gunakan fallback represent DeepFace
            detected_faces = []
            if len(faces) > 0:
                for (x, y, w, h) in faces:
                    detected_faces.append({"x": int(x), "y": int(y), "w": int(w), "h": int(h)})
            else:
                try:
                    rep = DeepFace.represent(
                        img_path=frame,
                        model_name=self.model_name,
                        detector_backend=self.detector_backend,
                        enforce_detection=False
                    )
                    if rep and len(rep) > 0 and "facial_area" in rep[0]:
                        fa = rep[0]["facial_area"]
                        detected_faces.append({
                            "x": int(fa.get("x", 0)),
                            "y": int(fa.get("y", 0)),
                            "w": int(fa.get("w", 0)),
                            "h": int(fa.get("h", 0))
                        })
                except Exception:
                    pass

            hasil_pencocokan = None
            siswa_info = None

            # 2. Proses Pengenalan Wajah Jika Ada Wajah yang Terdeteksi
            if len(detected_faces) > 0:
                # Ambil embedding wajah utama
                try:
                    representations = DeepFace.represent(
                        img_path=frame,
                        model_name=self.model_name,
                        detector_backend="skip",
                        enforce_detection=False
                    )
                except Exception:
                    representations = []

                if representations and len(representations) > 0:
                    query_emb = np.array(representations[0]["embedding"], dtype=np.float32)
                    query_norm = np.linalg.norm(query_emb)
                    if query_norm > 0:
                        query_emb = query_emb / query_norm

                    db_embeddings = np.array([item["embedding"] for item in self.embeddings_db])
                    dot_products = np.dot(db_embeddings, query_emb)
                    cosine_distances = 1.0 - dot_products

                    best_idx = int(np.argmin(cosine_distances))
                    min_dist = float(cosine_distances[best_idx])
                    best_match = self.embeddings_db[best_idx]
                    nisn = best_match["label"]

                    skor_akurasi = max(0.0, min(1.0, 1.0 - (min_dist / self.threshold)))
                    persentase_skor = f"{int(skor_akurasi * 100)}%"

                    # Ambil nama siswa dari cache data siswa
                    siswa_data = self.data_siswa.get(nisn, {})
                    nama_siswa = siswa_data.get("nama", f"Siswa {nisn}")
                    kelas_siswa = siswa_data.get("kelas", "-")

                    fa = detected_faces[0]
                    x, y, w, h = fa["x"], fa["y"], fa["w"], fa["h"]

                    if min_dist <= self.threshold:
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

                        # Kirim presensi ke Laravel jika sudah melewati jeda cooldown
                        sekarang = time.time()
                        last_time = self.last_attendance_time.get(nisn, 0)
                        
                        if (sekarang - last_time) > self.cooldown_seconds:
                            res_laravel = self.catat_presensi_ke_laravel(nisn)
                            self.last_attendance_time[nisn] = sekarang
                            if res_laravel:
                                hasil_pencocokan["presensi"] = res_laravel
                                if res_laravel.get("status") == "sudah_absen":
                                    status_scan = "sudah_absen"

                        # Gambar Kotak Hijau OpenCV
                        self.gambar_bounding_box_opencv(
                            frame, x, y, w, h, 
                            label_text=nama_siswa, 
                            status=status_scan, 
                            skor=persentase_skor
                        )
                    else:
                        # Wajah Tidak Dikenal
                        hasil_pencocokan = {
                            "status": "tidak_dikenali",
                            "pesan": "Wajah tidak cocok dengan data siswa terdaftar."
                        }
                        # Gambar Kotak Merah OpenCV
                        self.gambar_bounding_box_opencv(
                            frame, x, y, w, h, 
                            label_text="TIDAK DIKENAL", 
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

            # 4. Encode kembali frame OpenCV ke JPEG Base64
            _, buffer = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 85])
            frame_base64 = "data:image/jpeg;base64," + base64.b64encode(buffer).decode('utf-8')

            return {
                "status": hasil_pencocokan.get("status", "ok") if hasil_pencocokan else "ok",
                "terdeteksi": len(detected_faces) > 0,
                "frame_annotated": frame_base64,
                "hasil": hasil_pencocokan,
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
            if response.status_code == 200:
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
