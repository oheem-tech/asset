import pandas as pd

excel_path = 'BHP.xlsx'

print("Sheets in Excel:")
xls = pd.ExcelFile(excel_path)
print(xls.sheet_names)

df_master = pd.read_excel(excel_path, sheet_name='Master Barang Persediaan', skiprows=2)
print("\nColumns in Master Barang Persediaan:")
for i, col in enumerate(df_master.columns):
    print(f"{i}: {col}")

print("\nSample Data (first 3 rows):")
for i, row in df_master.head(3).iterrows():
    print(f"Row {i}: {row.values}")
