
try:
    with open(r'c:\Dev\debug_view.log', 'r', encoding='utf-8', errors='replace') as f:
        print(f.read())
except Exception as e:
    print(f"UTF8 failed: {e}")
    try:
         with open(r'c:\Dev\debug_view.log', 'r', encoding='latin-1') as f:
            print(f.read())
    except Exception as e2:
        print(f"Latin1 failed: {e2}")
