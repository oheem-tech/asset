import pandas as pd
import sqlite3
import re

excel_path = 'BHP.xlsx'
db_path = 'db/inventaris.sqlite'

conn = sqlite3.connect(db_path)
cursor = conn.cursor()

# Get all master items
cursor.execute("SELECT id, nama_barang FROM barang")
barang_db = cursor.fetchall()

def normalize_name(name):
    # Remove all non-alphanumeric, lowercase it, and remove extra spaces
    name = str(name).lower()
    name = re.sub(r'[^a-z0-9]', '', name)
    return name

barang_map = {normalize_name(name): id for id, name in barang_db}

# Clear old transactions
cursor.execute("DELETE FROM transaksi")
cursor.execute("DELETE FROM sqlite_sequence WHERE name='transaksi'")
conn.commit()

in_count = 0
out_count = 0
not_found = []

try:
    df_in = pd.read_excel(excel_path, sheet_name='2. Buku Penerimaan Barang', skiprows=6)
    for index, row in df_in.iterrows():
        tanggal = str(row.iloc[1]).split(' ')[0]
        if pd.isna(row.iloc[1]) or tanggal == 'nan' or tanggal == 'Tanggal' or '5.1.' in tanggal:
            continue
            
        bukti = str(row.iloc[2]).strip()
        keterangan = str(row.iloc[3]).strip()
        nama_barang = str(row.iloc[4]).strip()
        
        try:
            jumlah = int(row.iloc[8])
        except:
            continue
            
        if jumlah <= 0:
            continue
            
        norm_name = normalize_name(nama_barang)
        bid = barang_map.get(norm_name)
        
        if bid:
            cursor.execute("""
                INSERT INTO transaksi (tanggal, jenis, barang_id, jumlah, keterangan, bukti)
                VALUES (?, 'Masuk', ?, ?, ?, ?)
            """, (tanggal, bid, jumlah, keterangan, bukti))
            in_count += 1
        else:
            not_found.append(f"IN: {nama_barang}")
except Exception as e:
    print("Error in:", e)

try:
    df_out = pd.read_excel(excel_path, sheet_name='5. Buku Pengeluaran Persediaan', skiprows=6)
    for index, row in df_out.iterrows():
        tanggal = str(row.iloc[1]).split(' ')[0]
        if pd.isna(row.iloc[1]) or tanggal == 'nan' or tanggal == 'Tanggal' or '5.1.' in tanggal:
            continue
            
        bukti = str(row.iloc[2]).strip()
        keterangan = str(row.iloc[3]).strip()
        nama_barang = str(row.iloc[5]).strip()
        
        try:
            jumlah = int(row.iloc[8])
        except:
            continue
            
        if jumlah <= 0:
            continue
            
        norm_name = normalize_name(nama_barang)
        bid = barang_map.get(norm_name)
        
        if bid:
            cursor.execute("""
                INSERT INTO transaksi (tanggal, jenis, barang_id, jumlah, keterangan, bukti)
                VALUES (?, 'Keluar', ?, ?, ?, ?)
            """, (tanggal, bid, jumlah, keterangan, bukti))
            out_count += 1
        else:
            not_found.append(f"OUT: {nama_barang}")
except Exception as e:
    print("Error out:", e)

conn.commit()
conn.close()

print(f"Re-injected {in_count} Masuk, {out_count} Keluar.")
print("Not found mapping for:")
for nf in not_found:
    print(" - " + nf)
