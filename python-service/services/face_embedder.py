import threading
import cv2
import numpy as np
from deepface import DeepFace

def hitung_persentase_akurasi(cosine_dist, threshold=0.32):
    """
    Mengonversi jarak kosinus FaceNet (0.0 - 1.0+) menjadi persentase akurasi yang proporsional.
    - Wajah cocok (jarak <= threshold 0.32): menghasilkan skor 85.0% - 99.9%
      sehingga siswa terdaftar langsung lolos verifikasi standar sekolah dalam < 1-2 detik.
    - Wajah bukan siswa / berbeda (jarak > threshold 0.32): skor langsung drop di bawah 60.0%
      sehingga tidak akan pernah lolos verifikasi presensi.
    """
    if cosine_dist <= 0:
        return 100.0

    th = float(threshold) if threshold and float(threshold) > 0 else 0.32
    d = float(cosine_dist)

    if d <= th:
        skor = 100.0 - (d / th) * 15.0
        return round(max(85.0, min(99.99, skor)), 2)
    else:
        over = d - th
        skor = 60.0 - (over / 0.10) * 40.0
        return round(max(0.0, min(60.0, skor)), 2)


class FaceEmbedder:
    """
    Modul ekstraksi vektor embedding wajah 128-dimensi menggunakan model FaceNet DeepFace.
    Memastikan perataan wajah (face alignment) dan normalisasi L2 konsisten.
    """

    def __init__(self, model_name="Facenet"):
        self.model_name = model_name
        self.model_lock = threading.RLock()

    def warmup(self):
        """Pemanasan model FaceNet agar inferensi pertama berlangsung instan (< 1-2 detik)."""
        print("[*] FaceEmbedder: Melakukan pemanasan (warmup) model FaceNet...")
        try:
            dummy = np.zeros((160, 160, 3), dtype=np.uint8)
            with self.model_lock:
                DeepFace.represent(dummy, model_name=self.model_name, detector_backend="skip", enforce_detection=False)
            print("[+] FaceEmbedder: Pemanasan model FaceNet selesai. Siap memindai!")
            return True
        except Exception as e:
            print(f"[!] FaceEmbedder: Peringatan warmup model: {e}")
            return False

    def ekstrak_embedding(self, target_img, frame_lengkap=None, detector_backend="skip"):
        """
        Mengekstrak vektor embedding 128-dimensi FaceNet dengan normalisasi L2.
        Secara default menggunakan detector_backend='skip' karena target_img
        sudah dipotong dan diselaraskan secara presisi oleh YuNet CNN detector (~20ms).
        Fallback berjenjang:
        1. target_img dengan backend skip (instan, ~20ms)
        2. frame_lengkap dengan backend opencv (jika target_img gagal)
        3. target_img dengan backend opencv (jika masih gagal)
        """
        if target_img is None or target_img.size == 0:
            return None

        representations = []

        # 1. Ekstraksi langsung dari target_img (YuNet crop) dengan backend skip
        try:
            with self.model_lock:
                representations = DeepFace.represent(
                    img_path=target_img,
                    model_name=self.model_name,
                    detector_backend="skip",
                    enforce_detection=False
                )
        except Exception:
            representations = []

        # 2. Jika gagal dan frame_lengkap tersedia, coba pada frame_lengkap dengan opencv
        if not representations and frame_lengkap is not None and frame_lengkap.size > 0:
            try:
                with self.model_lock:
                    representations = DeepFace.represent(
                        img_path=frame_lengkap,
                        model_name=self.model_name,
                        detector_backend="opencv",
                        enforce_detection=False
                    )
            except Exception:
                representations = []

        # 3. Fallback terakhir: coba target_img dengan opencv
        if not representations:
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
            raw_emb = representations[0].get("embedding")
            if raw_emb is not None:
                emb = np.array(raw_emb, dtype=np.float32).flatten()
                if emb.shape == (128,):
                    norm = np.linalg.norm(emb)
                    if norm > 1e-6:
                        return emb / norm

        return None
