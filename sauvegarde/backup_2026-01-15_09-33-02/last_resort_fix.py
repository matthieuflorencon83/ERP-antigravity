
import os

path = r'c:\Dev\templates\documents\upload_v3.html'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

count = content.count("doc_type_selected==")
print(f"Occurrences found: {count}")

new_content = content.replace("doc_type_selected==", "doc_type_selected == ")

with open(path, 'w', encoding='utf-8') as f:
    f.write(new_content)

with open(path, 'r', encoding='utf-8') as f:
    content_after = f.read()

count_after = content_after.count("doc_type_selected==")
print(f"Occurrences after: {count_after}")
