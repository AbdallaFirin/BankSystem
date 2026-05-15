import pypdf
import os

pdf_path = r'c:\Users\DELL\Documents\BankManagement\BankSystem\Docs\System Documentation.pdf'
txt_path = r'c:\Users\DELL\Documents\BankManagement\BankSystem\Docs\System_Documentation.txt'

try:
    with open(pdf_path, 'rb') as f:
        reader = pypdf.PdfReader(f)
        text_content = []
        for i, page in enumerate(reader.pages):
            text_content.append(f"--- PAGE {i+1} ---")
            text = page.extract_text()
            if text:
                text_content.append(text)
                
    with open(txt_path, 'w', encoding='utf-8') as f:
        f.write("\n".join(text_content))
    print(f"Successfully extracted {len(reader.pages)} pages to {txt_path}")
except Exception as e:
    print(f"Error extracting PDF: {e}")
