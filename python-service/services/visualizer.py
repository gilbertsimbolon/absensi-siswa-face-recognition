import cv2

class Visualizer:
    """
    Modul visualisasi grafis OpenCV untuk rendering bounding box,
    sudut aksen (corner brackets), label siswa, dan banner HUD.
    """

    def __init__(self, model_name="Facenet"):
        self.model_name = model_name

    def gambar_bounding_box(self, frame, x, y, w, h, label_text="TERDETEKSI", status="sukses", skor=""):
        """
        Menggambar kotak deteksi wajah OpenCV:
        - Bounding rectangle presisi
        - Corner brackets beraksen cyberpunk/CV
        - Badge label di atas kepala (Nama Siswa, NISN, Persentase)
        - Status badge terverifikasi di bawah kotak
        """
        if frame is None or frame.size == 0:
            return frame

        # Konfigurasi warna (BGR OpenCV)
        if status == "sukses":
            color = (0, 220, 0)         # Hijau Cerah
            status_text = "TERVERIFIKASI"
            text_color = (0, 0, 0)
        elif status == "sudah_absen":
            color = (255, 170, 0)       # Cyan / Biru Muda
            status_text = "SUDAH ABSEN"
            text_color = (0, 0, 0)
        elif status == "gagal_akurasi":
            color = (0, 140, 255)       # Oranye
            status_text = "AKURASI RENDAH"
            text_color = (255, 255, 255)
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

    def gambar_hud(self, frame, total_wajah_db=0):
        """Menggambar banner HUD OpenCV di bagian atas frame video."""
        if frame is None or frame.size == 0:
            return frame

        h, w = frame.shape[:2]
        overlay = frame.copy()
        cv2.rectangle(overlay, (0, 0), (w, 32), (18, 18, 18), cv2.FILLED)
        cv2.addWeighted(overlay, 0.75, frame, 0.25, 0, frame)

        # Teks Judul Kiri
        cv2.putText(frame, "SMAN 2 TONDANO | COMPUTER VISION (OPENCV & DEEPFACE)", 
                    (12, 21), cv2.FONT_HERSHEY_SIMPLEX, 0.44, (0, 245, 180), 1, cv2.LINE_AA)

        # Status Kanan
        status_info = f"MODEL: {self.model_name.upper()} | DB: {total_wajah_db} WAJAH"
        (tw, _), _ = cv2.getTextSize(status_info, cv2.FONT_HERSHEY_SIMPLEX, 0.38, 1)
        cv2.putText(frame, status_info, (w - tw - 12, 21), 
                    cv2.FONT_HERSHEY_SIMPLEX, 0.38, (0, 220, 0), 1, cv2.LINE_AA)
        return frame
