

path = r'c:\Dev\templates\documents\upload_v3.html'
with open(path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
for line in lines:
    # Ensure spaces around == in Django tags
    if "{% if" in line and "==" in line:
        line = line.replace("==", " == ")
    new_lines.append(line)

with open(path, 'w', encoding='utf-8') as f:
    f.writelines(new_lines)

print("Template syntax fixed robustly.")
