import sys
import json
from pdfminer.high_level import extract_text

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "message": "Missing file path"}))
        sys.exit(1)

    file_path = sys.argv[1]
    
    try:
        from pdfminer.layout import LAParams
        # boxes_flow=None forces physical layout analysis (Row by Row) instead of flow analysis
        laparams = LAParams(boxes_flow=None)
        text = extract_text(file_path, laparams=laparams)
        print(json.dumps({"success": True, "text": text.strip()}))
    except Exception as e:
        print(json.dumps({"success": False, "message": str(e)}))
