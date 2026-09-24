import pandas as pd
import sqlite3
import re

excel_path = 'BHP.xlsx'
db_path = 'db/inventaris.sqlite'

conn = sqlite3.connect(db_path)
cursor = conn.cursor()

cursor.execute("SELECT id, nama_barang FROM barang")
barang_db = cursor.fetchall()

def normalize_name(name):
    name = str(name).lower()
    name = re.sub(r'[^a-z0-9]', '', name)
    return name

barang_map = {normalize_name(name): id for id, name in barang_db}
# Manual mapping for typos in excel
barang_map[normalize_name('Master Riso KS')] = barang_map.get(normalize_name('Master Riso'))

cursor.execute("DELETE FROM transaksi")
cursor.execute("DELETE FROM sqlite_sequence WHERE name='transaksi'")
conn.commit()

in_count = 0; out_count = 0

df_in = pd.read_excel(excel_path, sheet_name='2. Buku Penerimaan Barang', skiprows=6)
for index, row in df_in.iterrows():
    tanggal = str(row.iloc[1]).split(' ')[0]
    if pd.isna(row.iloc[1]) or tanggal == 'nan' or tanggal == 'Tanggal' or '5.1.' in tanggal:
        continue
    bukti = str(row.iloc[2]).strip()
    keterangan = str(row.iloc[3]).strip()
    nama_barang = str(row.iloc[4]).strip()
    try: jumlah = int(row.iloc[8])
    except: continue
    if jumlah <= 0: continue
    
    bid = barang_map.get(normalize_name(nama_barang))
    if bid:
        cursor.execute("INSERT INTO transaksi (tanggal, jenis, barang_id, jumlah, keterangan, bukti) VALUES (?, 'Masuk', ?, ?, ?, ?)", (tanggal, bid, jumlah, keterangan, bukti))
        in_count += 1

df_out = pd.read_excel(excel_path, sheet_name='5. Buku Pengeluaran Persediaan', skiprows=6)
for index, row in df_out.iterrows():
    tanggal = str(row.iloc[1]).split(' ')[0]
    if pd.isna(row.iloc[1]) or tanggal == 'nan' or tanggal == 'Tanggal' or '5.1.' in tanggal:
        continue
    bukti = str(row.iloc[2]).strip()
    keterangan = str(row.iloc[3]).strip()
    nama_barang = str(row.iloc[5]).strip()
    try: jumlah = int(row.iloc[8])
    except: continue
    if jumlah <= 0: continue
    
    bid = barang_map.get(normalize_name(nama_barang))
    if bid:
        cursor.execute("INSERT INTO transaksi (tanggal, jenis, barang_id, jumlah, keterangan, bukti) VALUES (?, 'Keluar', ?, ?, ?, ?)", (tanggal, bid, jumlah, keterangan, bukti))
        out_count += 1

conn.commit()
conn.close()
print(f"Re-injected {in_count} Masuk, {out_count} Keluar.")
