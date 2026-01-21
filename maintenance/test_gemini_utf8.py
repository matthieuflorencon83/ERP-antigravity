import google.generativeai as genai

key = "AIzaSyAYR9m0CwI6at_2zS_sGN3fdOBuH64GDo8"
genai.configure(api_key=key)

print("Listing models...")
try:
    for m in genai.list_models():
        if 'generateContent' in m.supported_generation_methods:
            print(f"- {m.name}")
except Exception as e:
    print(f"Error: {e}")
