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

# Konfigurasi Server
PORT = int(os.getenv('SERVICE_PORT', 5000))
HOST = os.getenv('SERVICE_HOST', '0.0.0.0')
LARAVEL_API_URL = os.getenv('LARAVEL_API_URL', 'http://127.0.0.1:8000/admin/absensi/proses-pindai')

# Model DeepFace
MODEL_NAME = os.getenv('DEEPFACE_MODEL', 'Facenet')
DETECTOR_BACKEND = os.getenv('DETECTOR_BACKEND', 'opencv')
DISTANCE_METRIC = os.getenv('DISTANCE_METRIC', 'cosine')

# Ambang Batas Jarak Pencocokan (Threshold)
# Untuk model Facenet dengan metrik cosine, nilai <= 0.40 umumnya adalah orang yang sama
THRESHOLD = float(os.getenv('FACE_THRESHOLD', 0.40))

# Path Dataset Wajah
# Prioritaskan folder dataset internal python-service, jika tidak ada gunakan storage/app/public/faces
DATASET_PATH = os.path.abspath(os.path.join(BASE_DIR, 'dataset'))
STORAGE_FACES_PATH = os.path.abspath(os.path.join(BASE_DIR, '..', 'storage', 'app', 'public', 'faces'))

# File Penyimpanan Cache Embeddings
CACHE_EMBEDDINGS_FILE = os.path.join(BASE_DIR, f'cache_embeddings_{MODEL_NAME.lower()}.pkl')

