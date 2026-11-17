import threading
import time
import numpy as np

class FaceTracker:
    """
    Modul pelacakan kestabilan identitas antar-frame (Temporal Smoothing & Majority Voting).
    Mencegah hasil deteksi melompat-lompat atau berganti-ganti nama antar frame,
    dan mencegah state wajah sebelumnya terbawa ke wajah orang berikutnya.
    """

    def __init__(self, max_window_size=5, required_votes=3, timeout_seconds=1.2):
        self.max_window_size = max_window_size
        self.required_votes = required_votes
        self.face_timeout_seconds = timeout_seconds

        self.history_lock = threading.RLock()
        self.voting_window = []
        self.last_seen_timestamp = 0.0
        self.last_bounding_box = None
        self.confirmed_identity = None

    def reset(self):
        """Mereset riwayat kestabilan ketika wajah menghilang atau berganti orang."""
        with self.history_lock:
            self.voting_window.clear()
            self.last_seen_timestamp = 0.0
            self.last_bounding_box = None
            self.confirmed_identity = None

    def evaluasi_frame(self, x, y, w, h, w_frame, h_frame, eval_res, data_siswa=None):
        """
        Mengevaluasi hasil pencocokan satu frame ke dalam buffer jendela voting geser.
        Mengembalikan tuple: (identitas_tervalidasi, terpilih_entry, status_tampilan, metadata)
        """
        if data_siswa is None:
            data_siswa = {}

        now = time.time()
        eval_status = eval_res.get("status")

        with self.history_lock:
            # 1. Reset jika jeda antar-frame melebihi batas waktu (timeout)
            if (now - self.last_seen_timestamp) > self.face_timeout_seconds:
                self.voting_window.clear()
                self.confirmed_identity = None

            # 2. Reset jika posisi wajah melompat drastis (indikasi pergantian subjek)
            if self.last_bounding_box is not None:
                lx, ly, lw, lh = self.last_bounding_box
                center_dist = np.hypot((x + w / 2.0) - (lx + lw / 2.0), (y + h / 2.0) - (ly + lh / 2.0))
                if center_dist > max(w_frame, h_frame) * 0.50:
                    self.voting_window.clear()
                    self.confirmed_identity = None

            self.last_seen_timestamp = now
            self.last_bounding_box = (x, y, w, h)

            # 3. Masukkan observasi frame ini ke dalam sliding window
            frame_entry = {
                "status": eval_status,
                "nisn": eval_res.get("nisn") if eval_status == "valid" else None,
                "nama": eval_res.get("nama") if eval_status == "valid" else None,
                "kelas": eval_res.get("kelas") if eval_status == "valid" else None,
                "akurasi": eval_res.get("akurasi") if eval_status == "valid" else None,
                "jarak": eval_res.get("jarak") if eval_status == "valid" else None,
                "margin": eval_res.get("margin", 0.0),
                "ambigu": eval_res.get("ambigu", False),
                "timestamp": now
            }

            # Buang frame kedaluwarsa
            self.voting_window = [
                e for e in self.voting_window 
                if (now - e["timestamp"]) <= self.face_timeout_seconds
            ]
            self.voting_window.append(frame_entry)
            if len(self.voting_window) > self.max_window_size:
                self.voting_window.pop(0)

            window_len = len(self.voting_window)

            # 4. Agregasi suara (voting) untuk tiap kandidat NISN
            vote_counts = {}
            candidate_latest_entry = {}
            for entry in self.voting_window:
                if entry["status"] == "valid" and entry["nisn"]:
                    n = entry["nisn"]
                    vote_counts[n] = vote_counts.get(n, 0) + 1
                    candidate_latest_entry[n] = entry

            best_nisn = None
            best_votes = 0
            if vote_counts:
                best_nisn, best_votes = max(vote_counts.items(), key=lambda it: it[1])

            # 5. Evaluasi Kestabilan Identitas
            identitas_tervalidasi = False
            terpilih_entry = None

            if self.confirmed_identity is not None:
                # Skenario A: Wajah sudah terkonfirmasi sebelumnya pada sesi tracking ini
                # Pertahankan identitas jika siswa tersebut masih didukung di jendela atau kandidat baru belum mayoritas mutlak
                if best_nisn == self.confirmed_identity or best_votes < self.required_votes:
                    terpilih_entry = candidate_latest_entry.get(self.confirmed_identity)
                    if not terpilih_entry:
                        s_data = data_siswa.get(self.confirmed_identity, {})
                        terpilih_entry = {
                            "nisn": self.confirmed_identity,
                            "nama": s_data.get("nama", f"Siswa {self.confirmed_identity}"),
                            "kelas": s_data.get("kelas", "-"),
                            "akurasi": eval_res.get("akurasi", "85%"),
                            "jarak": eval_res.get("jarak", 0.28)
                        }
                    identitas_tervalidasi = True
                else:
                    # Kandidat baru berhasil mengumpulkan suara mayoritas penuh
                    self.confirmed_identity = best_nisn
                    terpilih_entry = candidate_latest_entry[best_nisn]
                    identitas_tervalidasi = True
            else:
                # Skenario B: Belum ada identitas terkonfirmasi (wajah baru sedang diverifikasi)
                if best_nisn is not None and best_votes >= self.required_votes:
                    self.confirmed_identity = best_nisn
                    terpilih_entry = candidate_latest_entry[best_nisn]
                    identitas_tervalidasi = True
                else:
                    identitas_tervalidasi = False

            metadata = {
                "suara": best_votes,
                "target_suara": self.required_votes,
                "ukuran_jendela": window_len,
                "total_gagal_akurasi": sum(1 for e in self.voting_window if e["status"] == "gagal_akurasi")
            }

            return identitas_tervalidasi, terpilih_entry, metadata
