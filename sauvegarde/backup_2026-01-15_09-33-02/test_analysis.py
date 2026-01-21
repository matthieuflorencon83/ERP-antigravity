
import os
import django
import json
import dotenv

dotenv.load_dotenv()
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

from apps.core.models import Document
from apps.core.services import analyze_document

doc = Document.objects.last()
if not doc:
    print("No documents found.")
else:
    print(f"Analyzing Document ID: {doc.id}")
    print(f"File path: {doc.fichier.path}")
    
    try:
        # We need to open the file in binary mode
        with open(doc.fichier.path, 'rb') as f:
            result = analyze_document(f)
            if "error" in result:
                print(f"Analysis returned error: {result['error']}")
            else:
                print("Analysis Result:")
                print(json.dumps(result, indent=2))
    except Exception as e:
        import traceback
        print(f"Error during manual analysis: {e}")
        traceback.print_exc()
