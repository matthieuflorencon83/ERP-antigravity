import requests
import os
from dotenv import load_dotenv

load_dotenv()

def test_rest_call():
    api_key = os.getenv("GEMINI_API_KEY")
    url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={api_key}"
    
    # Tiny dummy PDF (base64)
    dummy_pdf = "JVBERi0xLjEKMSAwIG9iagogIDw8IC9UeXBlIC9DYXRhbG9nCiAgICAgL1BhZ2VzIDIgMCBSCiAgPj4KZW5kb2JqCjIgMCBvYmoKICA8PCAvVHlwZSAvUGFnZXMKICAgICAvS2lkcyBbIDMgMCBSIF0KICAgICAvQ291bnQgMQogID4+CmVuZG9iagozIDAgb2JqCiAgPDwgL1R5cGUgL1BhZ2UKICAgICAvUGFyZW50IDIgMCBSCiAgICAgL1Jlc291cmNlcyA8PAogICAgICAgIC9Gb250IDw8CiAgICAgICAgICAgL0YxIDQgMCBSCiAgICAgICAgPj4KICAgICA+PgogICAgIC9Db250ZW50cyA1IDAgUgogID4+CmVuZG9iago0IDAgb2JqCiAgPDwgL1R5cGUgL0ZvbnQKICAgICAvU3VidHlwZSAvVHlwZTEKICAgICAvQmFzZUZvbnQgL0hlbHZldGljYQogID4+CmVuZG9iago1IDAgb2JqCiAgPDwgL0xlbmd0aCA0NCA+PgpzdHJlYW0KQlQKICAvRjEgMjQgVGYKICA3MiA3MjAgVGQKICAoTWFyY2hlKSBUagpFVAplbmRzdHJlYW0KZW5kb2JqCnhyZWYKMCA2CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAxNyAwMDAwMCBuIAowMDAwMDAwMDc5IDAwMDAwIG4gCjAwMDAwMDAxNTMgMDAwMDAgbiAKMDAwMDAwMDI5NiAwMDAwMCBuIAowMDAwMDAwMzg5IDAwMDAwIG4gCnRyYWlsZXIKICA8PCAvU2l6ZSA2CiAgICAgL1Jvb3QgMSAwIFIKICA+PgpzdGFydHhyZWYKNDg0CiUlRU9GCg=="
    
    payload = {
        "contents": [{
            "parts": [
                {"text": "Is this a PDF? Respond in JSON: {'is_pdf': true}"},
                {
                    "inlineData": {
                        "mimeType": "application/pdf",
                        "data": dummy_pdf
                    }
                }
            ]
        }],
        "generationConfig": {
            "responseMimeType": "application/json"
        }
    }

    print(f"Testing URL: {url.split('?')[0]}")
    response = requests.post(url, json=payload)
    print(f"Status: {response.status_code}")
    print(f"Response: {response.text}")

if __name__ == "__main__":
    test_rest_call()
