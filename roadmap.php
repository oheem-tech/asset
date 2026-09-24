<?php
require_once 'config.php';
include 'layout_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom d-print-none">
    <h1 class="h2">SOP & Roadmap Aplikasi Aset</h1>
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak ke PDF / Printer</button>
</div>

<div class="card border-0 shadow-sm mb-5">
    <div class="card-body bg-white px-5 py-5" style="color: #000;">
        <div class="text-center mb-5">
            <h2 class="text-uppercase fw-bold text-decoration-underline">ROADMAP PENGGUNAAN APLIKASI ASET & PERSEDIAAN</h2>
            <p class="lead">Standard Operating Procedure (SOP) Alur Kerja Sistem Inventaris Sekolah</p>
        </div>

        <h4 class="fw-bold mb-3">Diagram Alur Kerja (Workflow)</h4>
        <div class="mermaid text-center mb-5 border rounded p-4 bg-light">
        flowchart TD
            subgraph PERSIAPAN [1. PERSIAPAN AWAL]
                A[Menu: Pengaturan Identitas & Pejabat] --> B[Menu: Data Master Barang]
            end

            subgraph PENERIMAAN [2. PENERIMAAN BARANG]
                B --> C[Menu: Transaksi Barang Masuk]
                C --> D(Cetak: BA Pemeriksaan Barang)
                C --> E(Cetak: Buku Penerimaan)
            end

            subgraph PENYIMPANAN [3. PUSAT STOK]
                C --> F[(Menu: Kartu Barang Persediaan)]
            end

            subgraph PENYALURAN [4. PENYALURAN BARANG]
                F --> G[Menu: Transaksi Barang Keluar]
                G --> H(Cetak: NPB, SPB, SPPB)
                G --> I(Cetak: BAST Pengeluaran)
                G --> J(Cetak: Buku Penyaluran)
            end

            subgraph PELAPORAN [5. PELAPORAN & EVALUASI]
                F --> K(Cetak: Daftar Mutasi BHP / Rekap Total)
                F --> L(Cetak: BA Stock Opname)
            end
            
            classDef menu fill:#dbeafe,stroke:#3b82f6,stroke-width:2px,color:#1e3a8a;
            classDef cetak fill:#fef3c7,stroke:#d97706,stroke-width:2px,color:#92400e;
            classDef db fill:#d1fae5,stroke:#10b981,stroke-width:2px,color:#065f46;
            
            class A,B,C,G menu;
            class D,E,H,I,J,K,L cetak;
            class F db;
        </div>

        <h4 class="fw-bold mb-3 border-bottom pb-2">Penjelasan Detail Per Fase</h4>
        
        <div class="mb-4">
            <h5 class="fw-bold text-primary">Fase 1: Persiapan Awal (Setup)</h5>
            <p>Fase ini dilakukan pertama kali saat menggunakan aplikasi atau setiap awal tahun ajaran/anggaran.</p>
            <ol>
                <li><strong>Atur Identitas & Pejabat:</strong> Masuk ke menu <code>Pengaturan</code>. Isi nama sekolah (Kop Surat) dan daftarkan nama-nama pejabat (Kepala Sekolah, Pengurus Barang, Tim Pemeriksa). Lakukan <em>Plotting</em> tanda tangan agar dokumen otomatis terisi.</li>
                <li><strong>Input Master Barang:</strong> Masuk ke menu <code>Data Master Barang</code>. Daftarkan seluruh jenis barang beserta spesifikasi, satuan, dan harga satuannya.</li>
            </ol>
        </div>

        <div class="mb-4">
            <h5 class="fw-bold text-primary">Fase 2: Penerimaan Barang (Inbound)</h5>
            <p>Fase ini dilakukan saat sekolah membeli barang atau menerima bantuan (Barang Datang).</p>
            <ol>
                <li><strong>Catat Transaksi:</strong> Buka menu <code>Transaksi</code>, lakukan Input Transaksi <strong>(Jenis: Masuk)</strong>. Masukkan jumlah yang diterima dan nama CV/Penyedia di kolom sumber.</li>
                <li><strong>Birokrasi Masuk:</strong> Buka menu <code>Laporan & Rekap</code>. 
                    <ul>
                        <li>Cetak <strong>Berita Acara Pemeriksaan Barang</strong> (Sistem akan meminta input No. SK, Invoice, dan Surat Pesanan (PO) untuk di-generate ke dalam format surat).</li>
                        <li>Cetak <strong>Buku Penerimaan Barang</strong> untuk rekapitulasi bulanan penerimaan.</li>
                    </ul>
                </li>
            </ol>
        </div>

        <div class="mb-4">
            <h5 class="fw-bold text-primary">Fase 3: Pemantauan Stok Gudang (Monitoring)</h5>
            <p>Fase pasif. Setelah transaksi "Masuk" dilakukan, barang otomatis mengendap di gudang.</p>
            <ul>
                <li>Buka menu <code>Laporan & Rekap</code> > <strong>Kartu Barang Persediaan</strong>.</li>
                <li>Anda bisa melacak riwayat saldo, penambahan, dan pengurangan per satu jenis spesifik barang di sini layaknya buku tabungan.</li>
            </ul>
        </div>

        <div class="mb-4">
            <h5 class="fw-bold text-primary">Fase 4: Penyaluran Barang (Outbound)</h5>
            <p>Fase ini dilakukan saat ada guru, staf, atau unit kerja yang meminta barang persediaan dari gudang.</p>
            <ol>
                <li><strong>Catat Transaksi:</strong> Buka menu <code>Transaksi</code>, lakukan Input Transaksi <strong>(Jenis: Keluar)</strong>. Masukkan jumlah barang yang diminta dan nama pemohon/unit di kolom keterangan.</li>
                <li><strong>Birokrasi Keluar:</strong> Buka menu <code>Laporan & Rekap</code>. Pilih laporan terkait, lalu klik transaksi tersebut:
                    <ul>
                        <li>Cetak <strong>NPB, SPB, dan SPPB</strong> sebagai dokumen administrasi pengajuan dan persetujuan barang.</li>
                        <li>Cetak <strong>BAST Pengeluaran Barang</strong> sebagai bukti serah terima fisik ke guru/unit pemohon.</li>
                        <li>Cetak <strong>Buku Pengeluaran / Penyaluran</strong> sebagai log rekapitulasi distribusi bulanan.</li>
                    </ul>
                </li>
            </ol>
        </div>

        <div class="mb-4">
            <h5 class="fw-bold text-primary">Fase 5: Pelaporan & Evaluasi (Closing)</h5>
            <p>Fase ini dilakukan pada akhir periode (akhir semester atau akhir tahun) untuk pelaporan ke dinas atau audit.</p>
            <ol>
                <li><strong>Cetak Daftar Mutasi BHP:</strong> Laporan ini mencetak rekap total (Garis Finish). Menampilkan secara keseluruhan saldo awal, total masuk, total keluar, dan saldo akhir beserta Nilai Aset (Rp) yang tersisa di sekolah.</li>
                <li><strong>Cetak Berita Acara Stock Opname:</strong> Laporan ini dicetak untuk dibawa ke gudang saat melakukan pengecekan fisik secara manual, guna memastikan stok di aplikasi sama dengan fisik di lapangan.</li>
            </ol>
        </div>

        <div class="alert alert-warning">
            <strong>Catatan untuk Barang "Salur Langsung":</strong><br>
            Jika barang datang dan hari itu juga langsung dibagikan (misal: konsumsi/jamuan atau alat praktik lab), jalankan <em>Fase 2</em> dan <em>Fase 4</em> pada hari yang sama secara berurutan. Barang tidak perlu mengendap lama di <em>Fase 3</em>.
        </div>
    </div>
</div>

<script type="module">
  import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
  mermaid.initialize({ startOnLoad: true });
</script>

<?php include 'layout_footer.php'; ?>
