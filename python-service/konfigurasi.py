import os
from dotenv import load_dotenv

# Muat variabel environment dari .env utama di root proyek
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
ROOT_DIR = os.path.abspath(os.path.join(BASE_DIR, '..'))

root_env = os.path.join(ROOT_DIR, '.env')
if os.path.exists(root_env):
    load_dotenv(root_env)
else:
    load_dotenv(os.path.join(BASE_DIR, '.env'))

# Konfigurasi Server (Terkunci ke localhost loopback internal demi keamanan)
PORT = int(os.getenv('SERVICE_PORT', 5000))
HOST = os.getenv('SERVICE_HOST', '127.0.0.1')
LARAVEL_BASE_URL = os.getenv('LARAVEL_BASE_URL') or os.getenv('APP_URL', 'http://127.0.0.1:8000')
LARAVEL_API_URL = os.getenv('LARAVEL_API_URL', f"{LARAVEL_BASE_URL.rstrip('/')}/admin/absensi/proses-pindai")
APP_KEY = os.getenv('APP_KEY', '')

# Model DeepFace & CNN Face Detector
MODEL_NAME = os.getenv('DEEPFACE_MODEL', 'Facenet')
DETECTOR_BACKEND = os.getenv('DETECTOR_BACKEND', 'yunet')
DISTANCE_METRIC = os.getenv('DISTANCE_METRIC', 'cosine')

# Model Deteksi Wajah Deep Learning CNN (YuNet ONNX)
YUNET_MODEL_PATH = os.path.join(BASE_DIR, 'weights', 'face_detection_yunet_2023mar.onnx')
if not os.path.exists(YUNET_MODEL_PATH):
    fallback_deepface = os.path.join(os.path.expanduser('~'), '.deepface', 'weights', 'face_detection_yunet_2023mar.onnx')
    if os.path.exists(fallback_deepface):
        YUNET_MODEL_PATH = fallback_deepface

# Ambang Batas Jarak Pencocokan (Threshold)
# Untuk model Facenet dengan metrik cosine pada webcam beragam pencahayaan: 0.78
THRESHOLD = float(os.getenv('FACE_THRESHOLD', 0.78))

# Path Dataset Wajah
# Prioritaskan folder dataset internal python-service, jika tidak ada gunakan storage/app/public/faces
DATASET_PATH = os.path.abspath(os.path.join(BASE_DIR, 'dataset'))
STORAGE_FACES_PATH = os.path.abspath(os.path.join(BASE_DIR, '..', 'storage', 'app', 'public', 'faces'))

# Penyimpanan Embeddings: Database MySQL (tabel student_faces) sebagai Single Source of Truth
CACHE_EMBEDDINGS_FILE = None

# Sumber Kamera Pemindai OpenCV (0 untuk Webcam USB/Internal, atau URL string RTSP/HTTP untuk IP Kamera/CCTV)
CAMERA_SOURCE = os.getenv('CAMERA_SOURCE', '0')

