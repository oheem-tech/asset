import sqlite3
db_path = 'db/inventaris.sqlite'
conn = sqlite3.connect(db_path)
cur = conn.cursor()
cur.execute("SELECT name, sql FROM sqlite_master WHERE type='table'")
tables = cur.fetchall()
for t in tables:
    print('--- ' + t[0] + ' ---')
    print(t[1])
