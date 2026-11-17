import os
import threading
import cv2
import numpy as np

class FaceDetector:
    """
    Modul deteksi wajah berbasis Deep Learning CNN (YuNet ONNX).
    Menangani deteksi wajah real-time dengan multi-scale (0.5x dan native)
    serta normalisasi pencahayaan adaptif (CLAHE).
    """

    def __init__(self, yunet_model_path=None):
        self.yunet_model_path = yunet_model_path
        self.yunet_detector = None
        self.detector_lock = threading.RLock()

        if self.yunet_model_path and hasattr(cv2, 'FaceDetectorYN') and os.path.exists(self.yunet_model_path):
            try:
                self.yunet_detector = cv2.FaceDetectorYN.create(
                    model=self.yunet_model_path,
                    config="",
                    input_size=(640, 480),
                    score_threshold=0.55,
                    nms_threshold=0.30,
                    top_k=5000
                )
                print(f"[+] FaceDetector: Berhasil memuat model YuNet ONNX: {self.yunet_model_path}")
            except Exception as e:
                print(f"[!] FaceDetector: Gagal inisialisasi FaceDetectorYN: {e}")

    @staticmethod
    def normalisasi_pencahayaan_adaptif(img):
        """
        Meningkatkan kontras dan kecerahan wajah secara adaptif (CLAHE)
        pada channel Luminance (L*a*b*) tanpa merusak integritas warna citra.
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

    def deteksi_wajah(self, img, min_score=0.40):
        """
        Mendeteksi wajah menggunakan model Deep Learning CNN (YuNet ONNX).
        Thread-safe: Dilindungi mutex detector_lock.
        Mengembalikan list tuple: [(x, y, w, h, score), ...]
        """
        if img is None or img.size == 0:
            return []

        with self.detector_lock:
            h_img, w_img = img.shape[:2]

            if self.yunet_detector is not None:
                # 1. Coba deteksi pada skala 0.5x untuk kecepatan inferensi (< 3ms)
                try:
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

                # 2. Fallback deteksi pada resolusi native jika skala 0.5x tidak menemukan wajah
                try:
                    self.yunet_detector.setInputSize((w_img, h_img))
                    _, faces = self.yunet_detector.detect(img)
                    if faces is not None and len(faces) > 0:
                        results = []
                        for face in faces:
                            fx = int(face[0])
                            fy = int(face[1])
                            fw = int(face[2])
                            fh = int(face[3])
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

            # 3. Fallback jika YuNet belum tersedia: gunakan Haar Cascade bawaan OpenCV
            try:
                gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
                cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
                if os.path.exists(cascade_path):
                    cascade = cv2.CascadeClassifier(cascade_path)
                    cv_faces = cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(30, 30))
                    results = []
                    for (x, y, w, h) in cv_faces:
                        results.append((int(x), int(y), int(w), int(h), 0.75))
                    return results
            except Exception:
                pass

            return []
