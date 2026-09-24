import pandas as pd
import sqlite3
import os

excel_path = 'BHP.xlsx'
db_path = 'db/inventaris.sqlite'

if not os.path.exists(db_path):
    print("Database not found!")
    exit(1)

conn = sqlite3.connect(db_path)
cursor = conn.cursor()

cursor.execute("CREATE TABLE IF NOT EXISTS ref_kode_barang (kode TEXT PRIMARY KEY, uraian TEXT)")
cursor.execute("DELETE FROM ref_kode_barang")
conn.commit()

df = pd.read_excel(excel_path, sheet_name='SUB-SUB RINCIAN OBJEK', skiprows=2)

count = 0
for index, row in df.iterrows():
    # Column 7 is the combined code (index 7 if 0-based, or index 8?)
    # Let's check the printed output:
    # 21 | 1 | 1 | 7 | 01 | 01 | 01 | 009 | 1.1.7.01.01.01.009 | Electro Dalas
    # So index 7 is the code, index 8 is the description.
    
    try:
        kode = str(row.iloc[7]).strip()
        uraian = str(row.iloc[8]).strip()
        
        if pd.isna(kode) or kode == 'nan' or not kode:
            continue
            
        if "Dst" in uraian or "dst" in uraian:
            continue # Skip generic "Dst..." entries
            
        cursor.execute("INSERT OR IGNORE INTO ref_kode_barang (kode, uraian) VALUES (?, ?)", (kode, uraian))
        count += 1
    except Exception as e:
        pass

conn.commit()
conn.close()
print(f"Berhasil menginjeksi {count} referensi kode barang.")
