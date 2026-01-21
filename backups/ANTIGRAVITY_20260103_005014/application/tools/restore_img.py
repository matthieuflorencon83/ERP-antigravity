
import sys
from PIL import Image

# This is the FIRST image uploaded, which was the clean logo/header
source = r"C:\Users\utopi\.gemini\antigravity\brain\a1ae6081-ea7e-4cff-ac36-277f3de56130\uploaded_image_1766690844100.png"
dest = r"c:\laragon\www\antigravity\images\header_doc.jpg"

try:
    img = Image.open(source)
    rgb_im = img.convert('RGB')
    rgb_im.save(dest)
    print("Restore & Conversion Successful")
except Exception as e:
    print(f"Error: {e}")
