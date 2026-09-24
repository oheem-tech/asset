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

# Create tables
cursor.execute("CREATE TABLE IF NOT EXISTS ref_kategori (id INTEGER PRIMARY KEY AUTOINCREMENT, nama TEXT UNIQUE)")
cursor.execute("CREATE TABLE IF NOT EXISTS ref_satuan (id INTEGER PRIMARY KEY AUTOINCREMENT, nama TEXT UNIQUE)")
cursor.execute("CREATE TABLE IF NOT EXISTS ref_penerima (id INTEGER PRIMARY KEY AUTOINCREMENT, nama TEXT UNIQUE)")
conn.commit()

df = pd.read_excel(excel_path, sheet_name='Data Referensi', skiprows=2)

def insert_ref(table, item):
    item = str(item).strip()
    if pd.isna(item) or item == 'nan' or not item:
        return
    try:
        cursor.execute(f"INSERT OR IGNORE INTO {table} (nama) VALUES (?)", (item,))
    except Exception as e:
        pass

for index, row in df.iterrows():
    insert_ref('ref_kategori', row.iloc[0])
    insert_ref('ref_satuan', row.iloc[1])
    insert_ref('ref_penerima', row.iloc[3]) # Unit / Sub Unit
    insert_ref('ref_penerima', row.iloc[5]) # Nama Penerima

conn.commit()
conn.close()
print("Data Referensi berhasil diinjeksi.")
