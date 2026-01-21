
import os
import dotenv
from django.conf import settings
import django

dotenv.load_dotenv()
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

print(f"GEMINI_API_KEY (from env): {os.environ.get('GEMINI_API_KEY')}")
print(f"GEMINI_API_KEYS (from env): {os.environ.get('GEMINI_API_KEYS')}")
print(f"settings.GEMINI_API_KEY: {getattr(settings, 'GEMINI_API_KEY', 'NOT SET')}")
print(f"settings.GEMINI_API_KEYS: {getattr(settings, 'GEMINI_API_KEYS', 'NOT SET')}")
