# B:\Tools_And_Script\Python_project\mail_auto_gmail_inbox\php\imap_worker.py
import json
import imaplib
import email
from email.header import decode_header
import sys
import os

# Fix encoding for Windows console
sys.stdout.reconfigure(encoding='utf-8')

DB_PATH = os.path.join(os.path.dirname(__file__), 'config', 'database.json')

def decode_mime_words(s):
    if not s:
        return "<Unknown>"
    try:
        decoded_words = decode_header(s)
        final_string = ""
        for word, encoding in decoded_words:
            if isinstance(word, bytes):
                final_string += word.decode(encoding or 'utf-8', errors='ignore')
            else:
                final_string += str(word)
        return final_string
    except Exception:
        return str(s)

def fetch_inboxes():
    if not os.path.exists(DB_PATH):
        print(json.dumps({"success": False, "error": "database.json not found"}))
        return

    try:
        with open(DB_PATH, 'r', encoding='utf-8') as f:
            accounts = json.load(f)
    except Exception as e:
        print(json.dumps({"success": False, "error": f"DB Read Error: {e}"}))
        return

    active_accounts = [acc for acc in accounts if acc.get("is_active") == 1]
    all_inboxes = []
    
    for acc in active_accounts:
        email_addr = acc.get("email")
        password = acc.get("app_password")
        
        inbox_data = {
            "email": email_addr,
            "status": "error",
            "messages": []
        }
        
        try:
            mail = imaplib.IMAP4_SSL("imap.gmail.com")
            mail.login(email_addr, password)
            mail.select("inbox")
            
            status, messages = mail.search(None, "ALL")
            if status == "OK":
                inbox_data["status"] = "success"
                email_ids = messages[0].split()
                latest_ids = email_ids[-10:] # Get last 10
                
                for e_id in reversed(latest_ids):
                    res, msg_data = mail.fetch(e_id, "(RFC822)")
                    for response_part in msg_data:
                        if isinstance(response_part, tuple):
                            msg = email.message_from_bytes(response_part[1])
                            
                            subject = decode_mime_words(msg.get("Subject", "<No Subject>"))
                            sender = decode_mime_words(msg.get("From", "<Unknown Sender>"))
                            date_str = msg.get("Date", "")
                            
                            if len(sender) > 40:
                                sender = sender[:37] + "..."
                            
                            # Extract Body
                            body = ""
                            if msg.is_multipart():
                                for part in msg.walk():
                                    ctype = part.get_content_type()
                                    cdispo = str(part.get('Content-Disposition'))
                                    
                                    if ctype in ['text/html', 'text/plain'] and 'attachment' not in cdispo:
                                        try:
                                            p = part.get_payload(decode=True)
                                            if p:
                                                charset = part.get_content_charset() or 'utf-8'
                                                body = p.decode(charset, errors='ignore')
                                                if ctype == 'text/html' and len(body) > 0:
                                                    break # Prefer HTML
                                        except Exception:
                                            pass
                            else:
                                try:
                                    p = msg.get_payload(decode=True)
                                    if p:
                                        charset = msg.get_content_charset() or 'utf-8'
                                        body = p.decode(charset, errors='ignore')
                                except Exception:
                                    pass
                                
                            inbox_data["messages"].append({
                                "id": e_id.decode('utf-8'),
                                "subject": subject,
                                "sender": sender,
                                "date": date_str,
                                "body": body
                            })
                            
            mail.close()
            mail.logout()
            
        except Exception as e:
            inbox_data["error_msg"] = str(e)
            
        all_inboxes.append(inbox_data)
        
    print(json.dumps({
        "success": True,
        "data": all_inboxes
    }, ensure_ascii=False))

if __name__ == "__main__":
    fetch_inboxes()
