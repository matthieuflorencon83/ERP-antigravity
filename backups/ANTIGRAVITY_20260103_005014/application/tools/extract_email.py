import sys
import os
import json
import email
from email import policy
import glob

# Try importing extract_msg, handle failure gracefully
try:
    import extract_msg
    HAS_MSG_LIB = True
except ImportError:
    HAS_MSG_LIB = False

def extract_from_eml(file_path, output_dir):
    with open(file_path, 'rb') as f:
        msg = email.message_from_binary_file(f, policy=policy.default)
    
    body = ""
    if msg.is_multipart():
        for part in msg.walk():
            ctype = part.get_content_type()
            cdispo = str(part.get('Content-Disposition'))
            if ctype == 'text/plain' and 'attachment' not in cdispo:
                body += part.get_payload(decode=True).decode(part.get_content_charset() or 'utf-8', errors='replace')
    else:
        body = msg.get_payload(decode=True).decode(msg.get_content_charset() or 'utf-8', errors='replace')

    attachments = []
    for part in msg.iter_attachments():
        filename = part.get_filename()
        if filename:
            # Clean filename
            valid_chars = "-_.() abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
            filename = "".join(c for c in filename if c in valid_chars)
            save_path = os.path.join(output_dir, filename)
            with open(save_path, 'wb') as f:
                f.write(part.get_content())
            attachments.append(save_path)
            
    return {"success": True, "body": body.strip(), "attachments": attachments}

def extract_from_msg(file_path, output_dir):
    if not HAS_MSG_LIB:
        return {"success": False, "message": "Library extract-msg not installed"}
        
    msg = extract_msg.Message(file_path)
    body = msg.body
    
    attachments = []
    for att in msg.attachments:
        if att.longFilename:
            filename = att.longFilename
        elif att.shortFilename:
            filename = att.shortFilename
        else:
            filename = "unknown_attachment"
            
        valid_chars = "-_.() abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
        filename = "".join(c for c in filename if c in valid_chars)
        
        save_path = os.path.join(output_dir, filename)
        with open(save_path, 'wb') as f:
            f.write(att.data)
        attachments.append(save_path)
        
    return {"success": True, "body": body.strip(), "attachments": attachments}

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"success": False, "message": "Usage: python extract_email.py <input_file> <output_dir>"}))
        sys.exit(1)

    input_file = sys.argv[1]
    output_dir = sys.argv[2]
    
    if not os.path.exists(output_dir):
        os.makedirs(output_dir)

    ext = os.path.splitext(input_file)[1].lower()
    
    try:
        if ext == '.eml':
            result = extract_from_eml(input_file, output_dir)
        elif ext == '.msg':
            result = extract_from_msg(input_file, output_dir)
        else:
            result = {"success": False, "message": "Unsupported format"}
    except Exception as e:
        result = {"success": False, "message": str(e)}

    print(json.dumps(result)) // Output JSON to stdout
