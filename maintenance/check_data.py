import os
import django
import sys

# Setup Django environment
sys.path.append('c:\\Dev')
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()

from apps.core.models import Article

total = Article.objects.count()
with_cond = Article.objects.exclude(conditionnement__exact='').exclude(conditionnement__isnull=True).count()

print(f"Total Articles: {total}")
print(f"Articles with Conditionnement: {with_cond}")
