import sqlite3
db_path = 'db/inventaris.sqlite'
conn = sqlite3.connect(db_path)
for row in conn.execute("SELECT created_at, jenis, jumlah FROM transaksi ORDER BY id DESC LIMIT 5"):
    print(row)
