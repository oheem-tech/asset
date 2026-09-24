import pandas as pd
import sqlite3

excel_path = 'BHP.xlsx'
db_path = 'db/inventaris.sqlite'

try:
    df_in = pd.read_excel(excel_path, sheet_name='2. Buku Penerimaan Barang', skiprows=6)
    in_excel_count = 0
    for _, row in df_in.iterrows():
        tgl = str(row.iloc[1]).strip()
        if tgl and tgl != 'nan' and tgl != 'Tanggal':
            in_excel_count += 1
except Exception as e:
    in_excel_count = 0
    print(e)

try:
    df_out = pd.read_excel(excel_path, sheet_name='5. Buku Pengeluaran Persediaan', skiprows=6)
    out_excel_count = 0
    for _, row in df_out.iterrows():
        tgl = str(row.iloc[1]).strip()
        if tgl and tgl != 'nan' and tgl != 'Tanggal':
            out_excel_count += 1
except Exception as e:
    out_excel_count = 0
    print(e)

conn = sqlite3.connect(db_path)
in_db_count = conn.execute("SELECT COUNT(*) FROM transaksi WHERE jenis='Masuk'").fetchone()[0]
out_db_count = conn.execute("SELECT COUNT(*) FROM transaksi WHERE jenis='Keluar'").fetchone()[0]

print(f"Transactions in Excel: {in_excel_count} Masuk, {out_excel_count} Keluar")
print(f"Transactions in DB: {in_db_count} Masuk, {out_db_count} Keluar")
