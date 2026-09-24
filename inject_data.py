import pandas as pd
import sqlite3
import os

# Paths
excel_path = 'BHP.xlsx'
db_path = 'db/inventaris.sqlite'

if not os.path.exists(db_path):
    print("Database not found!")
    exit(1)

conn = sqlite3.connect(db_path)
cursor = conn.cursor()

# Clear existing data to prevent duplicates on rerun
cursor.execute("DELETE FROM transaksi")
cursor.execute("DELETE FROM barang")
# Reset Auto Increment
cursor.execute("DELETE FROM sqlite_sequence WHERE name='transaksi'")
cursor.execute("DELETE FROM sqlite_sequence WHERE name='barang'")
conn.commit()

print("Membaca Master Barang...")
df_master = pd.read_excel(excel_path, sheet_name='Master Barang Persediaan', skiprows=2)

barang_map = {} # Maps (kode_barang, nama_barang) to barang_id
inserted_barang = {} # Just by nama_barang to be safe

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
        
    # Check if duplicate by name just in case, though Excel has duplicates.
    # We will just insert them all, but keep track of ID by name for transactions
    cursor.execute("""
        INSERT INTO barang (kode_barang, nama_barang, kategori, satuan, harga_satuan, stok_awal)
        VALUES (?, ?, ?, ?, ?, ?)
    """, (kode, nama, kategori, satuan, harga, stok_awal))
    
    barang_id = cursor.lastrowid
    inserted_barang[nama.lower()] = barang_id

conn.commit()
print(f"Berhasil menginjeksi {len(inserted_barang)} Master Barang.")

# Parse Penerimaan
print("Membaca Transaksi Penerimaan...")
try:
    df_in = pd.read_excel(excel_path, sheet_name='2. Buku Penerimaan Barang', skiprows=6)
    in_count = 0
    for index, row in df_in.iterrows():
        tanggal = str(row.iloc[1]).split(' ')[0] # yyyy-mm-dd
        if pd.isna(row.iloc[1]) or tanggal == 'nan' or tanggal == 'Tanggal' or tanggal == '5.1.02.01.01.0024':
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
            
        # Find barang_id
        bid = inserted_barang.get(nama_barang.lower())
        if bid:
            cursor.execute("""
                INSERT INTO transaksi (tanggal, jenis, barang_id, jumlah, keterangan, bukti)
                VALUES (?, 'Masuk', ?, ?, ?, ?)
            """, (tanggal, bid, jumlah, keterangan, bukti))
            in_count += 1
            
    print(f"Berhasil menginjeksi {in_count} transaksi Masuk.")
except Exception as e:
    print("Gagal membaca Penerimaan:", e)

# Parse Pengeluaran
print("Membaca Transaksi Pengeluaran...")
try:
    df_out = pd.read_excel(excel_path, sheet_name='5. Buku Pengeluaran Persediaan', skiprows=6)
    out_count = 0
    for index, row in df_out.iterrows():
        tanggal = str(row.iloc[1]).split(' ')[0] # yyyy-mm-dd
        if pd.isna(row.iloc[1]) or tanggal == 'nan' or tanggal == 'Tanggal' or tanggal == '5.1.02.01.01.0024':
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
            
        # Find barang_id
        bid = inserted_barang.get(nama_barang.lower())
        if bid:
            cursor.execute("""
                INSERT INTO transaksi (tanggal, jenis, barang_id, jumlah, keterangan, bukti)
                VALUES (?, 'Keluar', ?, ?, ?, ?)
            """, (tanggal, bid, jumlah, keterangan, bukti))
            out_count += 1
            
    print(f"Berhasil menginjeksi {out_count} transaksi Keluar.")
except Exception as e:
    print("Gagal membaca Pengeluaran:", e)

conn.commit()
conn.close()
print("Proses injeksi data selesai!")
