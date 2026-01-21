
import os
import google.generativeai as genai
import dotenv

dotenv.load_dotenv()
keys = os.environ.get("GEMINI_API_KEYS", "").split(",")
if not keys:
    keys = [os.environ.get("GEMINI_API_KEY")]

for i, key in enumerate(keys):
    key = key.strip()
    print(f"--- Testing Key {i+1}: {key[:10]}... ---")
    try:
        genai.configure(api_key=key)
        model = genai.GenerativeModel("gemini-flash-latest")
        response = model.generate_content("Say 'OK'")
        print(f"Result: {response.text.strip()}")
    except Exception as e:
        print(f"Error: {e}")
