import threading
import numpy as np
from .face_embedder import hitung_persentase_akurasi

class FaceMatcher:
    """
    Modul pencocokan vektor wajah menggunakan Cosine Distance (1.0 - dot product).
    Menangani matriks vektor numpy terakselerasi dan fallback terstandarisasi.
    """

    def __init__(self, threshold=0.32, min_persen_cocok=70.0):
        self.threshold = float(threshold)
        self.min_persen_cocok = float(min_persen_cocok)
        self.embeddings_matrix = None
        self.labels_list = []
        self.matrix_lock = threading.RLock()

    def perbarui_matrix(self, embeddings_db, data_siswa=None):
        """
        Menyusun matriks numpy 2D (N, 128) dari embeddings_db untuk pencocokan vektor secepat kilat (0.01 ms).
        Hanya menyertakan vektor yang valid (128-D) dan ternormalisasi L2.
        """
        with self.matrix_lock:
            if not embeddings_db:
                self.embeddings_matrix = None
                self.labels_list = []
                return False

            try:
                # Saring hanya embedding dari siswa yang masih terdaftar aktif di Laravel
                if not data_siswa:
                    aktif_db = embeddings_db
                else:
                    aktif_db = [
                        item for item in embeddings_db 
                        if str(item.get("label", "")).strip() in data_siswa
                    ]

                if not aktif_db:
                    self.embeddings_matrix = None
                    self.labels_list = []
                    return False

                clean_embeddings = []
                clean_labels = []

                for item in aktif_db:
                    raw_emb = item.get("embedding")
                    lbl = str(item.get("label", "")).strip()
                    if raw_emb is None or not lbl:
                        continue
                    emb = np.array(raw_emb, dtype=np.float32).flatten()
                    if emb.shape != (128,):
                        continue
                    norm = np.linalg.norm(emb)
                    if norm > 1e-6:
                        emb = emb / norm
                    else:
                        continue
                    clean_embeddings.append(emb)
                    clean_labels.append(lbl)

                if clean_embeddings:
                    self.embeddings_matrix = np.array(clean_embeddings, dtype=np.float32)
                    self.labels_list = clean_labels
                    print(f"[+] FaceMatcher: Matriks pencocokan aktif dengan {len(self.labels_list)} embedding untuk {len(set(self.labels_list))} siswa.")
                    return True
                else:
                    self.embeddings_matrix = None
                    self.labels_list = []
                    return False

            except Exception as e:
                print(f"[!] FaceMatcher: Gagal menyusun matriks embedding: {e}")
                self.embeddings_matrix = None
                self.labels_list = []
                return False

    def cocokkan(self, query_emb, data_siswa=None, fallback_db=None):
        """
        Mencocokkan embedding query ke database dengan validasi dua kandidat:
        - Mendukung jalur embeddings_matrix maupun fallback embeddings_db secara identik.
        - Memastikan query_emb dan database ter-normalisasi L2 secara konsisten.
        - Mengurutkan dua kandidat NISN terdekat.
        - Memvalidasi jarak kandidat pertama terhadap threshold (dist1 <= threshold).
        - Menghitung margin selisih jarak (dist2 - dist1) sebagai bobot keyakinan.
        - Mengembalikan dictionary status: 'valid', 'tidak_dikenali', atau 'gagal_akurasi'.
        """
        if data_siswa is None:
            data_siswa = {}

        # 1. Tentukan sumber matriks dan label aktif
        with self.matrix_lock:
            if (self.embeddings_matrix is not None and len(self.embeddings_matrix) > 0 and 
                len(self.labels_list) == len(self.embeddings_matrix)):
                matrix = self.embeddings_matrix
                labels = self.labels_list
            elif fallback_db and len(fallback_db) > 0:
                # Jalur Fallback dengan normalisasi dan sinkronisasi identik
                clean_embs = []
                clean_lbls = []
                for item in fallback_db:
                    raw_emb = item.get("embedding")
                    lbl = str(item.get("label", "")).strip()
                    if raw_emb is None or not lbl:
                        continue
                    emb = np.array(raw_emb, dtype=np.float32).flatten()
                    if emb.shape != (128,):
                        continue
                    norm = np.linalg.norm(emb)
                    if norm > 1e-6:
                        emb = emb / norm
                    else:
                        continue
                    clean_embs.append(emb)
                    clean_lbls.append(lbl)

                if not clean_embs:
                    return {"status": "tidak_dikenali", "pesan": "Database wajah belum tersedia."}
                matrix = np.array(clean_embs, dtype=np.float32)
                labels = clean_lbls
            else:
                return {"status": "tidak_dikenali", "pesan": "Database wajah belum tersedia."}

        # 2. Normalisasi konsisten embedding query (L2 norm)
        q_emb = np.array(query_emb, dtype=np.float32).flatten()
        if q_emb.shape != (128,):
            return {"status": "tidak_dikenali", "pesan": "Dimensi embedding tidak valid."}
        q_norm = np.linalg.norm(q_emb)
        if q_norm < 1e-6:
            return {"status": "tidak_dikenali", "pesan": "Fitur wajah tidak valid."}
        q_emb = q_emb / q_norm

        # 3. Hitung cosine distance (1.0 - dot product)
        cosine_distances = 1.0 - np.dot(matrix, q_emb)

        sorted_indices = np.argsort(cosine_distances)
        if len(sorted_indices) == 0:
            return {"status": "tidak_dikenali", "pesan": "Tidak ada data pembanding."}

        # Ambil Kandidat 1 terdekat
        idx1 = int(sorted_indices[0])
        dist1 = float(cosine_distances[idx1])
        nisn1 = str(labels[idx1])

        # Ambil Kandidat 2 terdekat dengan NISN berbeda (identitas berbeda)
        idx2 = None
        dist2 = 1.0
        nisn2 = None
        for idx in sorted_indices[1:]:
            lbl = str(labels[idx])
            if lbl != nisn1:
                idx2 = int(idx)
                dist2 = float(cosine_distances[idx2])
                nisn2 = lbl
                break

        nama1 = data_siswa.get(nisn1, {}).get("nama", f"Siswa {nisn1}")
        nama2 = data_siswa.get(nisn2, {}).get("nama", f"Siswa {nisn2}") if nisn2 else "None"
        kelas1 = data_siswa.get(nisn1, {}).get("kelas", "-")

        # Selisih jarak antara kandidat pertama dan kedua
        selisih = (dist2 - dist1) if nisn2 is not None else 1.0

        # Log diagnostik pencocokan wajah
        diag_log = f"[DIAGNOSTIK WAJAH] kandidat_1({nisn1}:{nama1})_dist={dist1:.4f}, kandidat_2({nisn2}:{nama2})_dist={dist2:.4f}, margin={selisih:.4f}, th={self.threshold:.4f}"
        print(diag_log, flush=True)

        # 4. Validasi Jarak Terhadap Threshold
        if dist1 > self.threshold:
            return {
                "status": "tidak_dikenali",
                "pesan": "Data belum terdaftar. Silahkan hubungi administrator.",
                "nisn": nisn1,
                "jarak": round(dist1, 4),
                "margin": round(selisih, 4)
            }

        # 5. Informasi Selisih Dua Kandidat untuk Analisis Kestabilan (bukan penolakan otomatis mutlak)
        ambigu = bool(nisn2 is not None and selisih < 0.035)
        if ambigu:
            print(f"[*] Informasi margin tipis: {nama1} ({dist1:.4f}) vs {nama2} ({dist2:.4f}), selisih={selisih:.4f}", flush=True)

        # 6. Validasi Persentase Akurasi Minimum
        skor_persen = hitung_persentase_akurasi(dist1, self.threshold)
        persentase_skor = f"{skor_persen}%"
        if skor_persen < self.min_persen_cocok:
            return {
                "status": "gagal_akurasi",
                "pesan": f"Tingkat kemiripan {persentase_skor} belum mencapai batas minimal ({self.min_persen_cocok}%).",
                "nisn": nisn1,
                "nama": nama1,
                "kelas": kelas1,
                "akurasi": persentase_skor,
                "skor_persen": skor_persen,
                "jarak": round(dist1, 4),
                "margin": round(selisih, 4)
            }

        return {
            "status": "valid",
            "pesan": "Wajah teridentifikasi.",
            "nisn": nisn1,
            "nama": nama1,
            "kelas": kelas1,
            "akurasi": persentase_skor,
            "skor_persen": skor_persen,
            "jarak": round(dist1, 4),
            "margin": round(selisih, 4),
            "ambigu": ambigu,
            "kandidat_2_nisn": nisn2,
            "kandidat_2_jarak": round(dist2, 4) if nisn2 else None
        }
