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

print("Membaca Master Barang dari Excel...")
df_master = pd.read_excel(excel_path, sheet_name='Master Barang Persediaan', skiprows=2)

updated = 0
inserted = 0

for index, row in df_master.iterrows():
    nama = str(row.iloc[0]).strip()
    kode = str(row.iloc[1]).strip()
    
    if pd.isna(row.iloc[0]) or nama == 'nan' or not nama or "Hasil Opname" in nama or "Nama Barang" in nama:
        continue
        
    kategori = str(row.iloc[2]).strip()
    satuan = str(row.iloc[3]).strip()
    
    try:
        harga = float(row.iloc[4])
        if pd.isna(harga): harga = 0.0
    except:
        harga = 0.0
        
    try:
        stok_awal = int(row.iloc[5])
        if pd.isna(stok_awal): stok_awal = 0
    except:
        stok_awal = 0
        
    if pd.isna(kode) or kode == 'nan':
        continue

    # Clean quotes just in case
    nama = nama.replace("'", "")
    
    # Check if exists
    cursor.execute("SELECT id FROM barang WHERE nama_barang = ? AND kode_barang = ?", (nama, kode))
    existing = cursor.fetchone()
    
    if existing:
        cursor.execute("""
            UPDATE barang 
            SET kategori = ?, satuan = ?, harga_satuan = ?, stok_awal = ?
            WHERE id = ?
        """, (kategori, satuan, harga, stok_awal, existing[0]))
        updated += 1
    else:
        cursor.execute("""
            INSERT INTO barang (kode_barang, nama_barang, kategori, satuan, harga_satuan, stok_awal)
            VALUES (?, ?, ?, ?, ?, ?)
        """, (kode, nama, kategori, satuan, harga, stok_awal))
        inserted += 1

conn.commit()
conn.close()

print(f"Selesai! {inserted} barang baru ditambahkan. {updated} barang diperbarui harganya/stok awalnya.")
