import os
import sys
import io
import time
import base64
import numpy as np
import cv2

# Konfigurasi encoding UTF-8 untuk output terminal / log Windows
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
if hasattr(sys.stderr, 'reconfigure'):
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

import konfigurasi
from services.face_detector import FaceDetector
from services.face_embedder import FaceEmbedder, hitung_persentase_akurasi
from services.face_matcher import FaceMatcher
from services.face_tracker import FaceTracker
from services.student_repository import StudentRepository
from services.attendance_client import AttendanceClient
from services.visualizer import Visualizer

class LayananWajah:
    """
    Fasade / Koordinator Utama Layanan Pengenalan Wajah Presensi Siswa.
    Mengorkestrasi modul-modul independen:
    - FaceDetector (Deteksi YuNet CNN & Pencahayaan Adaptif)
    - FaceEmbedder (Ekstraksi 128-D FaceNet & Perataan Aligned OpenCV)
    - FaceMatcher (Cosine Distance & Validasi Dua Kandidat)
    - FaceTracker (Temporal Voting & Sliding Window Multi-Frame)
    - StudentRepository (Pemuatan Data Siswa & Embeddings dari MySQL/Laravel)
    - AttendanceClient (Pencatatan Presensi Otomatis & Cooldown)
    - Visualizer (Rendering Bounding Box & HUD OpenCV)
    """

    def __init__(self):
        self.model_name = konfigurasi.MODEL_NAME
        self.detector_backend = konfigurasi.DETECTOR_BACKEND
        self.threshold = konfigurasi.THRESHOLD
        self.storage_path = konfigurasi.STORAGE_FACES_PATH
        self.dataset_path = self.storage_path
        self.laravel_api_url = konfigurasi.LARAVEL_BASE_URL
        self.cooldown_seconds = 5
        self.is_ready = False

        # Inisialisasi Modul-Modul Layanan
        self.detector = FaceDetector(yunet_model_path=konfigurasi.YUNET_MODEL_PATH)
        self.embedder = FaceEmbedder(model_name=self.model_name)
        self.matcher = FaceMatcher(threshold=self.threshold, min_persen_cocok=70.0)
        self.tracker = FaceTracker(max_window_size=5, required_votes=3, timeout_seconds=1.2)
        self.student_repo = StudentRepository(
            storage_path=self.storage_path,
            laravel_api_url=self.laravel_api_url,
            embedder=self.embedder,
            detector=self.detector
        )
        self.attendance_client = AttendanceClient(
            laravel_api_url=self.laravel_api_url,
            cooldown_seconds=self.cooldown_seconds
        )
        self.visualizer = Visualizer(model_name=self.model_name)

        # Alias Mutex untuk Kompatibilitas
        self.model_lock = self.embedder.model_lock
        self.detector_lock = self.detector.detector_lock
        self.history_lock = self.tracker.history_lock

    # =========================================================================
    # Properti Kompatibilitas dengan aplikasi.py dan modul lain
    # =========================================================================
    @property
    def embeddings_db(self):
        return self.student_repo.embeddings_db

    @embeddings_db.setter
    def embeddings_db(self, value):
        self.student_repo.embeddings_db = value

    @property
    def data_siswa(self):
        return self.student_repo.data_siswa

    @data_siswa.setter
    def data_siswa(self, value):
        self.student_repo.data_siswa = value

    @property
    def embeddings_matrix(self):
        return self.matcher.embeddings_matrix

    @embeddings_matrix.setter
    def embeddings_matrix(self, value):
        self.matcher.embeddings_matrix = value

    @property
    def labels_list(self):
        return self.matcher.labels_list

    @labels_list.setter
    def labels_list(self, value):
        self.matcher.labels_list = value

    @property
    def min_face_accuracy(self):
        return self.matcher.min_persen_cocok

    @min_face_accuracy.setter
    def min_face_accuracy(self, value):
        self.matcher.min_persen_cocok = float(value)

    # =========================================================================
    # Metode Siklus Hidup dan Sinkronisasi Data
    # =========================================================================
    def inisialisasi(self):
        """Memuat data siswa dan vektor embedding wajah langsung dari database Laravel."""
        print(f"[*] Menginisialisasi Layanan Wajah (Model: {self.model_name}, Threshold: {self.threshold})...")
        self.muat_data_siswa()
        self.muat_konfigurasi()
        self.perbarui_matrix_embeddings()
        self.embedder.warmup()
        if self.student_repo.embeddings_db:
            self.is_ready = True
            print(f"[+] Layanan Wajah Siap! ({len(self.student_repo.embeddings_db)} vektor embedding terindeks).")
        else:
            print("[*] Layanan Wajah diinisialisasi (database embedding belum terisi).")

    def muat_konfigurasi(self):
        """Memuat batas minimum akurasi dari konfigurasi Laravel."""
        acc = self.attendance_client.muat_konfigurasi()
        self.matcher.min_persen_cocok = acc
        return acc

    def muat_data_siswa(self):
        """Memuat daftar siswa dan embedding dari Laravel."""
        berhasil = self.student_repo.muat_data_siswa()
        if berhasil:
            self.perbarui_matrix_embeddings()
            if self.student_repo.embeddings_db:
                self.is_ready = True
        return berhasil

    def perbarui_matrix_embeddings(self):
        """Menyusun matriks numpy 2D dari embeddings_db untuk pencocokan cepat."""
        return self.matcher.perbarui_matrix(
            self.student_repo.embeddings_db,
            self.student_repo.data_siswa
        )

    def cocokkan_dua_kandidat(self, query_emb):
        """Mencocokkan embedding query ke database dengan validasi dua kandidat."""
        return self.matcher.cocokkan(
            query_emb,
            data_siswa=self.student_repo.data_siswa,
            fallback_db=self.student_repo.embeddings_db
        )

    def reset_riwayat_wajah(self):
        """Mereset riwayat kestabilan antar-frame."""
        self.tracker.reset()

    def catat_presensi_ke_laravel(self, nisn):
        """Memanggil API Laravel untuk mencatat presensi siswa otomatis."""
        return self.attendance_client.catat_presensi(nisn)

    def sinkronisasi_dataset_baru(self):
        """Memeriksa folder dataset di disk dan mengekstrak wajah baru."""
        total = self.student_repo.sinkronisasi_dataset_baru()
        if total > 0:
            self.perbarui_matrix_embeddings()
            self.is_ready = True
        return total

    def potong_dan_zoom_wajah(self, img_path):
        """Auto-crop dan zoom pasfoto wajah."""
        return self.student_repo.potong_dan_zoom_wajah(img_path)

    def ekstrak_dan_sinkronisasi_siswa(self, nisn, nama="", simpan_cache=True):
        """Mengekstrak embedding siswa baru yang diunggah dari Laravel."""
        res = self.student_repo.ekstrak_dan_sinkronisasi_siswa(nisn, nama=nama, simpan_cache=simpan_cache)
        if res.get("status") == "sukses":
            self.perbarui_matrix_embeddings()
            self.is_ready = True
        return res

    def hapus_embedding_siswa(self, nisn):
        """Menghapus embedding siswa dari memori."""
        res = self.student_repo.hapus_embedding_siswa(nisn)
        self.perbarui_matrix_embeddings()
        return res

    def deteksi_wajah_cnn(self, img, min_score=0.40):
        """Mendeteksi wajah menggunakan model Deep Learning CNN."""
        return self.detector.deteksi_wajah(img, min_score=min_score)

    @staticmethod
    def normalisasi_pencahayaan_adaptif(img):
        return FaceDetector.normalisasi_pencahayaan_adaptif(img)

    def gambar_bounding_box_opencv(self, frame, x, y, w, h, label_text="TERDETEKSI", status="sukses", skor=""):
        return self.visualizer.gambar_bounding_box(frame, x, y, w, h, label_text=label_text, status=status, skor=skor)

    def gambar_hud_opencv(self, frame):
        return self.visualizer.gambar_hud(frame, total_wajah_db=len(self.student_repo.embeddings_db))

    def kenali_wajah_dari_base64(self, gambar_base64):
        res = self.proses_frame_kamera(gambar_base64)
        if res.get("hasil"):
            return res["hasil"]
        return res

    # =========================================================================
    # Pipeline Utama Inferensi Frame Video Kamera
    # =========================================================================
    def proses_frame_kamera(self, gambar_base64, catat_laravel=False):
        """
        Menerima frame kamera, mendeteksi wajah dengan YuNet CNN,
        mengekstrak fitur FaceNet teraligni, menstabilkan identitas via sliding-window voting,
        merender visual OpenCV, dan mencatat absensi ke Laravel jika terkonfirmasi.
        """
        if not self.is_ready or not self.student_repo.embeddings_db:
            return {
                "status": "memindai",
                "pesan": "Server AI sedang menyiapkan model...",
                "hasil": {
                    "status": "memindai",
                    "pesan": "Server AI sedang menyiapkan model..."
                }
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
            cnn_faces = self.detector.deteksi_wajah(frame, min_score=0.50)
            if not cnn_faces:
                frame_enhanced = self.detector.normalisasi_pencahayaan_adaptif(frame)
                cnn_faces = self.detector.deteksi_wajah(frame_enhanced, min_score=0.40)

            detected_faces = []
            for item in cnn_faces:
                detected_faces.append({
                    "x": int(item[0]),
                    "y": int(item[1]),
                    "w": int(item[2]),
                    "h": int(item[3])
                })

            hasil_pencocokan = None

            # 2. Proses Pengenalan Wajah
            if len(detected_faces) > 0:
                fa = detected_faces[0]
                fx, fy, fw, fh = fa["x"], fa["y"], fa["w"], fa["h"]
                x, y, w, h = fx, fy, fw, fh

                # Crop wajah dengan padding proporsional untuk area ekstraksi & perataan wajah
                pad_top = int(fh * 0.35)
                pad_bottom = int(fh * 0.40)
                pad_side = int(fw * 0.30)
                y1 = max(0, fy - pad_top)
                y2 = min(h_orig, fy + fh + pad_bottom)
                x1 = max(0, fx - pad_side)
                x2 = min(w_orig, fx + fw + pad_side)
                face_crop = frame[y1:y2, x1:x2]

                target_img = face_crop if (face_crop is not None and face_crop.size > 0 and 
                                           face_crop.shape[0] > 20 and face_crop.shape[1] > 20) else frame

                # Ekstraksi fitur embedding FaceNet dengan perataan wajah (aligned opencv) konsisten
                query_emb = self.embedder.ekstrak_embedding(
                    target_img, 
                    frame_lengkap=frame, 
                    detector_backend="opencv"
                )

                if query_emb is not None:
                    # Cocokkan embedding query ke database
                    eval_res = self.matcher.cocokkan(
                        query_emb,
                        data_siswa=self.student_repo.data_siswa,
                        fallback_db=self.student_repo.embeddings_db
                    )

                    # Evaluasi kestabilan multi-frame (sliding window voting)
                    identitas_tervalidasi, terpilih_entry, meta = self.tracker.evaluasi_frame(
                        x, y, w, h, w_orig, h_orig,
                        eval_res=eval_res,
                        data_siswa=self.student_repo.data_siswa
                    )

                    if identitas_tervalidasi and terpilih_entry:
                        kandidat_nisn = terpilih_entry["nisn"]
                        nama_siswa = terpilih_entry["nama"]
                        kelas_siswa = terpilih_entry["kelas"]
                        persentase_skor = terpilih_entry["akurasi"]
                        min_dist = terpilih_entry["jarak"]

                        status_scan = "sukses"
                        hasil_pencocokan = {
                            "status": "sukses",
                            "nisn": kandidat_nisn,
                            "nama": nama_siswa,
                            "kelas": kelas_siswa,
                            "akurasi": persentase_skor,
                            "jarak": min_dist,
                            "suara": meta["suara"],
                            "target_suara": meta["target_suara"],
                            "ukuran_jendela": meta["ukuran_jendela"]
                        }

                        # Kirim presensi ke Laravel jika diminta
                        if catat_laravel:
                            res_laravel = self.attendance_client.catat_presensi(kandidat_nisn)
                            if res_laravel:
                                hasil_pencocokan["presensi"] = res_laravel
                                if res_laravel.get("status") == "sudah_absen":
                                    status_scan = "sudah_absen"
                                    hasil_pencocokan["status"] = "sudah_absen"

                        # Gambar Kotak Hijau OpenCV
                        self.visualizer.gambar_bounding_box(
                            frame, x, y, w, h, 
                            label_text=nama_siswa, 
                            status=status_scan, 
                            skor=persentase_skor
                        )

                    else:
                        # Identitas belum tervalidasi pada jendela geser saat ini
                        if meta["ukuran_jendela"] >= 3 and meta["suara"] == 0:
                            if meta["total_gagal_akurasi"] >= 2:
                                hasil_pencocokan = {
                                    "status": "gagal_akurasi",
                                    "pesan": "Posisikan wajah lebih jelas tegak lurus ke kamera.",
                                    "nama": eval_res.get("nama", "Siswa"),
                                    "akurasi": eval_res.get("akurasi", "")
                                }
                                self.visualizer.gambar_bounding_box(
                                    frame, x, y, w, h,
                                    label_text=f"{hasil_pencocokan['nama']} ({hasil_pencocokan['akurasi']})",
                                    status="gagal_akurasi",
                                    skor=hasil_pencocokan['akurasi']
                                )
                            else:
                                hasil_pencocokan = {
                                    "status": "tidak_dikenali",
                                    "pesan": "Data belum terdaftar. Silahkan hubungi administrator."
                                }
                                self.visualizer.gambar_bounding_box(
                                    frame, x, y, w, h, 
                                    label_text="BELUM TERDAFTAR", 
                                    status="tidak_dikenali", 
                                    skor=None
                                )
                        else:
                            # Masih dalam tahap stabilisasi: Status memindai
                            suara = meta["suara"]
                            target_suara = meta["target_suara"]
                            target_info = f"({suara}/{target_suara})" if suara > 0 else "..."
                            hasil_pencocokan = {
                                "status": "memindai",
                                "pesan": f"Memvalidasi kestabilan wajah {target_info}...",
                                "nisn": None,
                                "nama": None,
                                "suara": suara,
                                "target_suara": target_suara,
                                "ukuran_jendela": meta["ukuran_jendela"]
                            }
                            self.visualizer.gambar_bounding_box(
                                frame, x, y, w, h, 
                                label_text="MEMINDAI...", 
                                status="memindai", 
                                skor=None
                            )
                else:
                    # Gagal ekstraksi embedding
                    self.tracker.reset()
                    hasil_pencocokan = {
                        "status": "tidak_ada_wajah",
                        "pesan": "Arahkan wajah ke kamera."
                    }
                    fa = detected_faces[0]
                    self.visualizer.gambar_bounding_box(
                        frame, fa["x"], fa["y"], fa["w"], fa["h"], 
                        label_text="MEMINDAI...", 
                        status="memindai"
                    )
            else:
                # Wajah menghilang dari kamera: reset riwayat pelacakan
                self.tracker.reset()
                hasil_pencocokan = {
                    "status": "tidak_ada_wajah",
                    "pesan": "Arahkan wajah ke kamera."
                }

            # 3. Gambar HUD Atas
            self.visualizer.gambar_hud(frame, total_wajah_db=len(self.student_repo.embeddings_db))

            # 4. Susun data bounding box untuk frontend client (HTML5 Video & Canvas)
            wajah_data = None
            if len(detected_faces) > 0:
                fa = detected_faces[0]
                status_box = "memindai"
                nama_box = "MEMINDAI..."
                skor_box = ""
                if hasil_pencocokan:
                    st_pencocokan = hasil_pencocokan.get("status")
                    if st_pencocokan in ["sukses", "sudah_absen"]:
                        status_box = st_pencocokan
                        nama_box = hasil_pencocokan.get("nama", "SISWA")
                        skor_box = hasil_pencocokan.get("akurasi", "")
                    elif st_pencocokan == "memindai":
                        status_box = "memindai"
                        nama_box = "MEMINDAI..."
                        skor_box = ""
                    elif st_pencocokan == "gagal_akurasi":
                        status_box = "gagal_akurasi"
                        nama_box = hasil_pencocokan.get("nama", "SISWA")
                        skor_box = hasil_pencocokan.get("akurasi", "")
                    else:
                        status_box = "tidak_dikenali"
                        nama_box = "BELUM TERDAFTAR"
                        skor_box = ""

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

            return {
                "status": hasil_pencocokan.get("status", "ok") if hasil_pencocokan else "ok",
                "terdeteksi": len(detected_faces) > 0,
                "frame_annotated": None,
                "hasil": hasil_pencocokan,
                "wajah": wajah_data,
                "total_wajah_frame": len(detected_faces)
            }

        except Exception as e:
            return {
                "status": "error",
                "pesan": f"Kesalahan memproses frame: {str(e)}"
            }
