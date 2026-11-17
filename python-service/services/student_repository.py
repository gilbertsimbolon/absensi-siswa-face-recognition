import os
import cv2
import requests
import numpy as np

class StudentRepository:
    """
    Modul repositori data siswa dan vektor embedding wajah.
    Berinteraksi dengan Laravel API untuk memuat data siswa terdaftar,
    menyimpan/menghapus embedding di memori, dan sinkronisasi on-demand.
    """

    def __init__(self, storage_path, laravel_api_url, embedder=None, detector=None):
        self.storage_path = storage_path
        self.laravel_api_url = laravel_api_url.rstrip('/')
        self.embedder = embedder
        self.detector = detector

        self.embeddings_db = []
        self.data_siswa = {}

    def muat_data_siswa(self):
        """Memuat daftar siswa dan vektor embedding wajah langsung dari database Laravel."""
        candidate_urls = [
            f"{self.laravel_api_url}/api/presensi/daftar-siswa",
            "http://127.0.0.1/api/presensi/daftar-siswa",
            "http://localhost/api/presensi/daftar-siswa",
        ]
        # Hilangkan duplikat
        seen = set()
        unique_urls = []
        for u in candidate_urls:
            if u not in seen:
                seen.add(u)
                unique_urls.append(u)

        for url in unique_urls:
            try:
                res = requests.get(url, timeout=3)
                if res.status_code == 200:
                    self.data_siswa = res.json()
                    print(f"[+] StudentRepository: Berhasil memuat data {len(self.data_siswa)} siswa dari {url}.")

                    db_embeddings = []
                    for nisn, info in self.data_siswa.items():
                        raw_embs = info.get("embeddings", [])
                        for idx, emb_raw in enumerate(raw_embs):
                            if emb_raw and len(emb_raw) == 128:
                                emb_vec = np.array(emb_raw, dtype=np.float32)
                                norm = np.linalg.norm(emb_vec)
                                if norm > 1e-6:
                                    emb_vec = emb_vec / norm
                                    db_embeddings.append({
                                        "label": str(nisn),
                                        "embedding": emb_vec,
                                        "path": f"db://faces/{nisn}/{idx}"
                                    })

                    if db_embeddings:
                        self.embeddings_db = db_embeddings
                        print(f"[+] StudentRepository: Berhasil memuat {len(self.embeddings_db)} vektor embedding wajah langsung dari MySQL.")
                    return True
            except Exception:
                pass

        print("[*] StudentRepository: Belum dapat terhubung ke Laravel untuk memuat daftar siswa.")
        return False

    def sinkronisasi_dataset_baru(self):
        """Memeriksa folder dataset di disk dan mengekstrak wajah baru yang belum ada di database memori."""
        if not os.path.exists(self.storage_path):
            return 0

        indexed_labels = set(str(item.get("label")) for item in self.embeddings_db)
        missing_labels = []

        for item in os.listdir(self.storage_path):
            item_path = os.path.join(self.storage_path, item)
            if os.path.isdir(item_path):
                if self.data_siswa and item not in self.data_siswa:
                    continue
                if item not in indexed_labels and item not in missing_labels:
                    missing_labels.append(item)

        total_added = 0
        if missing_labels:
            print(f"[*] StudentRepository: Ditemukan {len(missing_labels)} dataset siswa baru di disk: {missing_labels}")
            for label in missing_labels:
                res = self.ekstrak_dan_sinkronisasi_siswa(label, simpan_cache=False)
                if res.get("status") == "sukses":
                    total_added += res.get("total_embedding", 0)

        return total_added

    def potong_dan_zoom_wajah(self, img_path):
        """
        Mendeteksi wajah pada gambar lalu memotong dan melakukan zoom proporsional (pasfoto portrait)
        agar foto yang disimpan ke disk & database adalah pasfoto wajah optimal.
        """
        try:
            if not os.path.exists(img_path):
                return False

            img = cv2.imread(img_path)
            if img is None or img.size == 0:
                return False

            h_img, w_img = img.shape[:2]
            if self.detector is None:
                return False

            faces = self.detector.deteksi_wajah(img, min_score=0.45)
            if not faces:
                return False

            faces = sorted(faces, key=lambda f: f[2] * f[3], reverse=True)
            x, y, w, h = faces[0][:4]

            if (w * h) / (w_img * h_img) > 0.70:
                return True

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
        Mengekstrak fitur citra wajah (face embedding 128-D) dari foto yang diunggah ke storage siswa.
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
            img = cv2.imread(img_path)
            if img is None or img.size == 0:
                continue

            h_img, w_img = img.shape[:2]
            target_face = img

            if self.detector is not None:
                faces = self.detector.deteksi_wajah(img, min_score=0.40)
                if faces:
                    faces = sorted(faces, key=lambda f: f[2] * f[3], reverse=True)
                    fx, fy, fw, fh = faces[0][:4]
                    pad_top = int(fh * 0.35)
                    pad_bottom = int(fh * 0.40)
                    pad_side = int(fw * 0.30)
                    y1 = max(0, fy - pad_top)
                    y2 = min(h_img, fy + fh + pad_bottom)
                    x1 = max(0, fx - pad_side)
                    x2 = min(w_img, fx + fw + pad_side)
                    crop = img[y1:y2, x1:x2]
                    if crop is not None and crop.size > 0:
                        target_face = crop

            if self.embedder is not None:
                emb = self.embedder.ekstrak_embedding(target_face, frame_lengkap=img, detector_backend="skip")
                if emb is not None:
                    new_embeddings.append({
                        "label": nisn,
                        "embedding": emb,
                        "path": img_path
                    })

        if not new_embeddings:
            return {"status": "error", "pesan": f"Wajah tidak terdeteksi pada berkas foto NISN {nisn}."}

        # Perbarui basis data embedding di memori
        self.embeddings_db = [item for item in self.embeddings_db if str(item.get("label")) != nisn]
        self.embeddings_db.extend(new_embeddings)

        if nama:
            self.data_siswa[nisn] = {"nama": nama, "kelas": self.data_siswa.get(nisn, {}).get("kelas", "-")}

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
        """Menghapus embedding siswa dari memori saat siswa dihapus di Laravel."""
        nisn = str(nisn).strip()
        sebelum = len(self.embeddings_db)
        self.embeddings_db = [item for item in self.embeddings_db if str(item.get("label")) != nisn]
        sesudah = len(self.embeddings_db)

        if self.data_siswa and nisn in self.data_siswa:
            del self.data_siswa[nisn]

        print(f"[+] StudentRepository: Embedding untuk NISN {nisn} dihapus dari memori ({sebelum - sesudah} data).")
        return {
            "status": "sukses",
            "pesan": f"Embedding siswa {nisn} berhasil dihapus dari memori.",
            "dihapus": sebelum - sesudah
        }

    def semua_embedding_dict(self):
        """Mengembalikan mapping NISN -> list of embeddings untuk disimpan ke MySQL."""
        result = {}
        for item in self.embeddings_db:
            lbl = str(item.get("label", "")).strip()
            if not lbl:
                continue
            if lbl not in result:
                result[lbl] = []
            raw_emb = item.get("embedding")
            emb_list = raw_emb.tolist() if hasattr(raw_emb, 'tolist') else list(raw_emb)
            result[lbl].append({
                "filename": os.path.basename(item.get("path", "")),
                "embedding": emb_list
            })
        return result
