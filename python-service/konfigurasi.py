import os
from dotenv import load_dotenv

# Muat variabel environment dari .env utama di root proyek
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT_DIR = os.path.abspath(os.path.join(BASE_DIR, '..'))

root_env = os.path.join(ROOT_DIR, '.env')
if os.path.exists(root_env):
    load_dotenv(dotenv_path=root_env, override=False)
else:
    load_dotenv(dotenv_path=os.path.join(BASE_DIR, '.env'), override=False)

# Konfigurasi Server (Menerima koneksi loopback lokal maupun via domain/IP VPS)
PORT = int(os.getenv('SERVICE_PORT', 5000))
HOST = os.getenv('SERVICE_HOST', '0.0.0.0')

# Ambil base URL Laravel dinamis dari APP_URL di .env utama (prioritas tertinggi)
LARAVEL_BASE_URL = (os.getenv('APP_URL') or os.getenv('LARAVEL_BASE_URL') or 'https://bolonsite.my.id').rstrip('/')
LARAVEL_API_URL = LARAVEL_BASE_URL
APP_KEY = os.getenv('APP_KEY', '')

# Model DeepFace & CNN Face Detector
MODEL_NAME = os.getenv('DEEPFACE_MODEL', 'Facenet')
DETECTOR_BACKEND = os.getenv('DETECTOR_BACKEND', 'opencv')
DISTANCE_METRIC = os.getenv('DISTANCE_METRIC', 'cosine')

# Model Deteksi Wajah Deep Learning CNN (YuNet ONNX)
YUNET_MODEL_PATH = os.path.join(BASE_DIR, 'weights', 'face_detection_yunet_2023mar.onnx')
if not os.path.exists(YUNET_MODEL_PATH):
    fallback_deepface = os.path.join(os.path.expanduser('~'), '.deepface', 'weights', 'face_detection_yunet_2023mar.onnx')
    if os.path.exists(fallback_deepface):
        YUNET_MODEL_PATH = fallback_deepface

# Ambang Batas Jarak Pencocokan (Threshold)
# Standar ketat FaceNet anti false-positive (wajah orang lain / belum terdaftar 100% ditolak): 0.28
THRESHOLD = float(os.getenv('FACE_THRESHOLD', 0.28))

# Path Penyimpanan Foto Wajah Laravel Storage (Single Source of Truth via php artisan storage:link)
# Seluruh foto disimpan murni di Laravel storage/app/public/faces, tanpa duplikasi di python
STORAGE_FACES_PATH = os.path.abspath(os.path.join(ROOT_DIR, 'storage', 'app', 'public', 'faces'))
if not os.path.exists(STORAGE_FACES_PATH):
    # Fallback symlink public/storage/faces
    STORAGE_FACES_PATH = os.path.abspath(os.path.join(ROOT_DIR, 'public', 'storage', 'faces'))

# Pastikan folder target selalu terdefinisi
if not os.path.exists(STORAGE_FACES_PATH):
    try:
        os.makedirs(STORAGE_FACES_PATH, exist_ok=True)
    except Exception:
        pass

# DATASET_PATH diarahkan langsung ke STORAGE_FACES_PATH agar tidak ada duplikasi berkas
DATASET_PATH = STORAGE_FACES_PATH

# Penyimpanan Embeddings: Database MySQL (tabel student_faces) sebagai Single Source of Truth
CACHE_EMBEDDINGS_FILE = None

# Sumber Kamera Pemindai OpenCV (0 untuk Webcam USB/Internal, atau URL string RTSP/HTTP untuk IP Kamera/CCTV)
CAMERA_SOURCE = os.getenv('CAMERA_SOURCE', '0')

