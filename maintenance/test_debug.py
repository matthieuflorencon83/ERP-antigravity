
import os
import sys
import django
from django.template.loader import render_to_string

# Add apps to path
sys.path.append(os.path.join(os.getcwd(), 'apps'))

# Setup Django
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

def test_templates():
    print("--- Testing Templates ---")
    
    # 1. Article List
    try:
        print("Rendering article_list.html...")
        # Mocking context
        render_to_string('core/article_list.html', {'articles': []}) 
        print("PASS: article_list.html")
    except Exception as e:
        print("FAIL: article_list.html")
        print(e)

    # 2. Article Form Partial
    try:
        print("Rendering core/partials/article_form_partial.html...")
        # Try importing from apps.core first, then core
        try:
            from apps.core.forms import ArticleForm
        except ImportError:
            from core.forms import ArticleForm
            
        form = ArticleForm()
        render_to_string('core/partials/article_form_partial.html', {'form': form})
        print("PASS: article_form_partial.html")
    except Exception as e:
        print("FAIL: article_form_partial.html")
        print(e)

if __name__ == "__main__":
    test_templates()
