
try:
    with open(r'c:\Dev\debug_view.log', 'r', encoding='utf-8') as f:
        print(f.read())
except Exception as e:
    print(e)
