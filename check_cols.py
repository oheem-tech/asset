import pandas as pd
excel_path = 'BHP.xlsx'
df_in = pd.read_excel(excel_path, sheet_name='2. Buku Penerimaan Barang', skiprows=6)
print("Penerimaan cols:")
for i, c in enumerate(df_in.columns): print(f"{i}: {c}")
for i, r in df_in.head(3).iterrows(): print(f"{i}: {r.values}")
