<?php
require_once 'config.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'save_kop') {
        $jenis_kop = isset($_POST['jenis_kop']) ? $_POST['jenis_kop'] : 'teks';
        $pdo->prepare("UPDATE pengaturan SET nilai = ? WHERE kunci = 'jenis_kop'")->execute([$jenis_kop]);

        foreach (['kop_1', 'kop_2', 'kop_3', 'kop_4', 'teks_spb_dasar', 'teks_sppb_dasar', 'teks_bast_pembuka', 'teks_bast_penutup'] as $kunci) {
            if (isset($_POST[$kunci])) {
                $stmt = $pdo->prepare("UPDATE pengaturan SET nilai = ? WHERE kunci = ?");
                $stmt->execute([$_POST[$kunci], $kunci]);
            }
        }
        
        // Handle Logo Upload (if any)
        $allowed = ['jpg', 'jpeg', 'png'];
        if (isset($_FILES['logo_kiri']) && $_FILES['logo_kiri']['error'] == 0) {
            $ext = pathinfo($_FILES['logo_kiri']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), $allowed)) {
                if (!is_dir('img')) mkdir('img', 0777, true);
                $path = 'img/logo_kiri.' . $ext;
                move_uploaded_file($_FILES['logo_kiri']['tmp_name'], $path);
                $stmt = $pdo->prepare("UPDATE pengaturan SET nilai = ? WHERE kunci = 'logo_kiri'");
                $stmt->execute([$path]);
            } else {
                $error_msg = "Format logo harus JPG atau PNG.";
            }
        }
        
        if (isset($_POST['hapus_logo']) && $_POST['hapus_logo'] == '1') {
            $stmt = $pdo->prepare("UPDATE pengaturan SET nilai = '' WHERE kunci = 'logo_kiri'");
            $stmt->execute();
        }

        // Handle Banner Upload (if any)
        if (isset($_FILES['kop_banner']) && $_FILES['kop_banner']['error'] == 0) {
            $ext = pathinfo($_FILES['kop_banner']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), $allowed)) {
                if (!is_dir('img')) mkdir('img', 0777, true);
                $path = 'img/kop_banner.' . $ext;
                move_uploaded_file($_FILES['kop_banner']['tmp_name'], $path);
                $stmt = $pdo->prepare("UPDATE pengaturan SET nilai = ? WHERE kunci = 'kop_banner'");
                $stmt->execute([$path]);
            } else {
                $error_msg = "Format gambar banner harus JPG atau PNG.";
            }
        }
        
        if (isset($_POST['hapus_banner']) && $_POST['hapus_banner'] == '1') {
            $stmt = $pdo->prepare("UPDATE pengaturan SET nilai = '' WHERE kunci = 'kop_banner'");
            $stmt->execute();
        }
        
        if (!$error_msg) $success_msg = "Pengaturan Kop Surat berhasil disimpan.";
    } 
    elseif ($action == 'add_pejabat') {
        $stmt = $pdo->prepare("INSERT INTO ref_pejabat (nama, nip) VALUES (?, ?)");
        $stmt->execute([$_POST['nama'], $_POST['nip']]);
        $success_msg = "Data Pejabat berhasil ditambahkan.";
    } 
    elseif ($action == 'del_pejabat') {
        // Hapus pejabat, tapi harus hati-hati kalau sudah diplot. Lebih aman jika dibiarkan/diperingatkan.
        // Tapi untuk simplisitas, hapus saja dan null-kan di pengaturan_ttd.
        $stmt = $pdo->prepare("DELETE FROM ref_pejabat WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $pdo->prepare("DELETE FROM pengaturan_ttd WHERE pejabat_id = ?")->execute([$_POST['id']]);
        $success_msg = "Pejabat berhasil dihapus.";
    }
    elseif ($action == 'save_plot') {
        $jenis_laporan = $_POST['jenis_laporan'];
        
        // Bersihkan plot lama untuk jenis_laporan ini
        $stmt = $pdo->prepare("DELETE FROM pengaturan_ttd WHERE jenis_laporan = ?");
        $stmt->execute([$jenis_laporan]);
        
        if (isset($_POST['ttd'])) {
            $urutan = 1;
            foreach ($_POST['ttd'] as $t) {
                $jabatan = $t['jabatan'];
                $pejabat_id = $t['pejabat_id'];
                $is_dinamis = isset($t['is_dinamis']) ? 1 : 0;
                
                if (!empty($jabatan) && (!empty($pejabat_id) || $is_dinamis)) {
                    $ins = $pdo->prepare("INSERT INTO pengaturan_ttd (jenis_laporan, urutan, jabatan, pejabat_id, is_dinamis) VALUES (?, ?, ?, ?, ?)");
                    $ins->execute([$jenis_laporan, $urutan, $jabatan, $is_dinamis ? null : $pejabat_id, $is_dinamis]);
                    $urutan++;
                }
            }
        }
        $success_msg = "Plotting tanda tangan berhasil disimpan.";
    }
    elseif ($action == 'reset_data') {
        $tipe_reset = $_POST['tipe_reset'];
        $konfirmasi = strtoupper(trim($_POST['konfirmasi']));
        
        if ($konfirmasi !== 'HAPUS') {
            $error_msg = "Kata konfirmasi salah. Data tidak dihapus.";
        } else {
            if ($tipe_reset == 'transaksi_master') {
                $pdo->exec("DELETE FROM transaksi");
                $pdo->exec("DELETE FROM barang");
                // Reset auto increment
                $pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('transaksi', 'barang')");
                $success_msg = "Semua Data Master Barang dan Transaksi berhasil dihapus.";
            } elseif ($tipe_reset == 'full_reset') {
                $pdo->exec("DELETE FROM transaksi");
                $pdo->exec("DELETE FROM barang");
                $pdo->exec("DELETE FROM referensi");
                $pdo->exec("DELETE FROM ref_pejabat");
                $pdo->exec("DELETE FROM pengaturan_ttd");
                $pdo->exec("DELETE FROM sqlite_sequence");
                // Untuk tabel pengaturan (identitas), kita kosongkan nilainya tapi biarkan key-nya tetap ada
                $pdo->exec("UPDATE pengaturan SET nilai = ''");
                $success_msg = "Reset Pabrik berhasil! Seluruh data (Transaksi, Master, Pejabat, Pengaturan) telah dikosongkan.";
            }
        }
    }
    
    // Redirect to clear post data but keep active tab
    $active_tab = isset($_POST['tab']) ? $_POST['tab'] : 'kop';
    header("Location: pengaturan.php?msg=" . urlencode($success_msg) . "&err=" . urlencode($error_msg) . "&tab=" . urlencode($active_tab));
    exit;
}

if (isset($_GET['msg']) && !empty($_GET['msg'])) $success_msg = $_GET['msg'];
if (isset($_GET['err']) && !empty($_GET['err'])) $error_msg = $_GET['err'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'kop';

// Load Kop
$kop = [];
$r = $pdo->query("SELECT kunci, nilai FROM pengaturan")->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) $kop[$row['kunci']] = $row['nilai'];

// Load Pejabat
$pejabat = $pdo->query("SELECT * FROM ref_pejabat ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);

// Load Plotting
$jenis_list = [
    'kartu' => 'Kartu Barang Persediaan',
    'penerimaan' => 'Buku Penerimaan Barang',
    'pengeluaran' => 'Buku Pengeluaran Persediaan',
    'penyaluran' => 'Buku Penyaluran Persediaan',
    'opname' => 'Berita Acara Stock Opname',
    'mutasi' => 'Daftar Mutasi BHP (Rekap Total)',
    'bast_masuk' => 'BA Pemeriksaan (Penerimaan)',
    'bast_keluar' => 'BAST Penyaluran (Pengeluaran)'
];

$plot_terpilih = isset($_GET['plot_jenis']) ? $_GET['plot_jenis'] : 'kartu';
$current_plot = $pdo->prepare("SELECT * FROM pengaturan_ttd WHERE jenis_laporan = ? ORDER BY urutan ASC");
$current_plot->execute([$plot_terpilih]);
$current_plot_data = $current_plot->fetchAll(PDO::FETCH_ASSOC);

include 'layout_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Pengaturan Sistem</h1>
</div>

<?php if($error_msg): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>
<?php if($success_msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<ul class="nav nav-tabs" id="myTab" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link <?= $active_tab == 'kop' ? 'active' : '' ?>" id="kop-tab" data-bs-toggle="tab" data-bs-target="#kop" type="button" role="tab">Kop Surat</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?= $active_tab == 'pejabat' ? 'active' : '' ?>" id="pejabat-tab" data-bs-toggle="tab" data-bs-target="#pejabat" type="button" role="tab">Master Pejabat</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?= $active_tab == 'plot' ? 'active' : '' ?>" id="plot-tab" data-bs-toggle="tab" data-bs-target="#plot" type="button" role="tab">Plotting Tanda Tangan</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link <?= $active_tab == 'teks' ? 'active' : '' ?>" id="teks-tab" data-bs-toggle="tab" data-bs-target="#teks" type="button" role="tab">Teks Dokumen</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link text-danger fw-bold <?= $active_tab == 'reset' ? 'active' : '' ?>" id="reset-tab" data-bs-toggle="tab" data-bs-target="#reset" type="button" role="tab"><i class="bi bi-exclamation-triangle-fill"></i> Reset Sistem</button>
  </li>
</ul>

<div class="tab-content pt-4" id="myTabContent">
  <!-- TAB KOP SURAT -->
  <div class="tab-pane fade <?= $active_tab == 'kop' ? 'show active' : '' ?>" id="kop" role="tabpanel">
    <div class="card w-100" style="max-width: 900px;">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_kop">
                <input type="hidden" name="tab" value="kop">
                
                <?php $jenis_kop = isset($kop['jenis_kop']) ? $kop['jenis_kop'] : 'teks'; ?>
                <div class="mb-4 p-3 bg-light border rounded">
                    <label class="form-label fw-bold">Jenis Kop Surat Laporan:</label>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="jenis_kop" id="jk_teks" value="teks" <?= $jenis_kop == 'teks' ? 'checked' : '' ?> onchange="toggleKop()">
                      <label class="form-check-label" for="jk_teks">
                        Teks & Logo Kiri (Isi manual baris per baris)
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="jenis_kop" id="jk_gambar" value="gambar" <?= $jenis_kop == 'gambar' ? 'checked' : '' ?> onchange="toggleKop()">
                      <label class="form-check-label" for="jk_gambar">
                        Gambar Penuh / Banner (Gunakan gambar desain utuh seperti MS Word)
                      </label>
                    </div>
                </div>

                <div id="panel_teks" style="<?= $jenis_kop == 'gambar' ? 'display: none;' : '' ?>">
                    <h5 class="mb-3">Pengaturan Teks & Logo</h5>
                    <div class="row mb-3">
                        <div class="col-md-3 text-center">
                            <label class="form-label d-block">Logo Saat Ini</label>
                            <?php if (!empty($kop['logo_kiri'])): ?>
                                <img src="<?= htmlspecialchars($kop['logo_kiri']) ?>" class="img-fluid mb-2" style="max-height: 100px;">
                                <div class="form-check">
                                  <input class="form-check-input" type="checkbox" name="hapus_logo" value="1" id="hapus_logo">
                                  <label class="form-check-label text-danger" for="hapus_logo">Hapus Logo</label>
                                </div>
                            <?php else: ?>
                                <div class="text-muted border p-4 mb-2">Tanpa Logo</div>
                            <?php endif; ?>
                            <input type="file" name="logo_kiri" class="form-control form-control-sm mt-2" accept=".jpg,.jpeg,.png">
                        </div>
                        <div class="col-md-9">
                            <div class="mb-2">
                                <label class="form-label">Baris 1 (Pemerintah/Yayasan)</label>
                                <input type="text" name="kop_1" class="form-control font-monospace" value="<?= htmlspecialchars(isset($kop['kop_1']) ? $kop['kop_1'] : '') ?>" placeholder="PEMERINTAH PROVINSI...">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Baris 2 (Dinas/Lembaga Utama)</label>
                                <input type="text" name="kop_2" class="form-control font-monospace fw-bold" value="<?= htmlspecialchars(isset($kop['kop_2']) ? $kop['kop_2'] : '') ?>" placeholder="DINAS PENDIDIKAN">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Baris 3 (Nama Unit / Sekolah)</label>
                                <input type="text" name="kop_3" class="form-control font-monospace fw-bold fs-5" value="<?= htmlspecialchars(isset($kop['kop_3']) ? $kop['kop_3'] : '') ?>" placeholder="SMA NEGERI 1 X">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Baris 4 (Alamat / Kontak)</label>
                                <input type="text" name="kop_4" class="form-control font-monospace" value="<?= htmlspecialchars(isset($kop['kop_4']) ? $kop['kop_4'] : '') ?>" placeholder="Jalan Raya No. 123, Telp. 081...">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="panel_gambar" style="<?= $jenis_kop == 'teks' ? 'display: none;' : '' ?>">
                    <h5 class="mb-3">Pengaturan Gambar Kop Penuh</h5>
                    <div class="mb-3">
                        <label class="form-label">Gambar Kop Banner Saat Ini</label>
                        <?php if (!empty($kop['kop_banner'])): ?>
                            <div class="mb-2 border p-2 text-center bg-white">
                                <img src="<?= htmlspecialchars($kop['kop_banner']) ?>" class="img-fluid" style="max-height: 120px; width: auto;">
                            </div>
                            <div class="form-check mb-2">
                              <input class="form-check-input" type="checkbox" name="hapus_banner" value="1" id="hapus_banner">
                              <label class="form-check-label text-danger" for="hapus_banner">Hapus Banner</label>
                            </div>
                        <?php else: ?>
                            <div class="text-muted border p-4 mb-2 text-center">Belum ada gambar banner.</div>
                        <?php endif; ?>
                        <input type="file" name="kop_banner" class="form-control" accept=".jpg,.jpeg,.png">
                        <small class="text-muted">Gunakan gambar persegi panjang mendatar, contoh rasio 8:1 (mirip kop surat Word).</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Simpan Pengaturan Kop</button>
            </form>
        </div>
    </div>
  </div>

<script>
function toggleKop() {
    let type = document.querySelector('input[name="jenis_kop"]:checked').value;
    if (type === 'teks') {
        document.getElementById('panel_teks').style.display = 'block';
        document.getElementById('panel_gambar').style.display = 'none';
    } else {
        document.getElementById('panel_teks').style.display = 'none';
        document.getElementById('panel_gambar').style.display = 'block';
    }
}
</script>

  <!-- TAB MASTER PEJABAT -->
  <div class="tab-pane fade <?= $active_tab == 'pejabat' ? 'show active' : '' ?>" id="pejabat" role="tabpanel">
    <div class="card w-75">
        <div class="card-body">
            <form method="post" class="d-flex gap-2 mb-4">
                <input type="hidden" name="action" value="add_pejabat">
                <input type="hidden" name="tab" value="pejabat">
                <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap Pejabat" required>
                <input type="text" name="nip" class="form-control" placeholder="NIP (Opsional)">
                <button type="submit" class="btn btn-primary text-nowrap">Tambah Pejabat</button>
            </form>
            
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>NIP</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pejabat as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nama']) ?></td>
                        <td><?= htmlspecialchars($p['nip']) ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Hapus pejabat ini?');">
                                <input type="hidden" name="action" value="del_pejabat">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="tab" value="pejabat">
                                <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pejabat)): ?>
                    <tr><td colspan="3" class="text-center">Belum ada data master pejabat.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
  </div>

  <!-- TAB PLOTTING -->
  <div class="tab-pane fade <?= $active_tab == 'plot' ? 'show active' : '' ?>" id="plot" role="tabpanel">
    <div class="card w-75 mb-3">
        <div class="card-body bg-light">
            <form method="get" class="d-flex align-items-center gap-2">
                <input type="hidden" name="tab" value="plot">
                <label class="text-nowrap fw-bold">Pilih Jenis Laporan :</label>
                <select name="plot_jenis" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($jenis_list as $key => $val): ?>
                    <option value="<?= $key ?>" <?= $plot_terpilih == $key ? 'selected' : '' ?>><?= $val ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <div class="card w-100">
        <div class="card-body">
            <form method="post" id="formPlot">
                <input type="hidden" name="action" value="save_plot">
                <input type="hidden" name="tab" value="plot">
                <input type="hidden" name="jenis_laporan" value="<?= $plot_terpilih ?>">
                
                <p class="text-muted">Plotting tanda tangan akan diurutkan dari Kiri ke Kanan pada saat dicetak. Jika kolom dikosongkan, TTD urutan tersebut tidak akan muncul.</p>

                <div class="row" id="plotContainer">
                    <?php 
                    // Tampilkan slot 1, 2, 3. Maksimal sediakan 4 slot untuk fleksibilitas.
                    for ($i = 0; $i < 4; $i++): 
                        $curr = isset($current_plot_data[$i]) ? $current_plot_data[$i] : null;
                    ?>
                    <div class="col-md-3 border-end">
                        <div class="mb-2 fw-bold text-primary">Tanda Tangan <?= $i+1 ?></div>
                        <div class="mb-2">
                            <label class="form-label small">Jabatan / Gelar Posisi</label>
                            <input type="text" name="ttd[<?= $i ?>][jabatan]" class="form-control form-control-sm" placeholder="Contoh: Mengetahui, Kepala Sekolah" value="<?= htmlspecialchars(isset($curr['jabatan']) ? $curr['jabatan'] : '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Nama Pejabat</label>
                            <select name="ttd[<?= $i ?>][pejabat_id]" class="form-select form-select-sm pejabat-select">
                                <option value="">-- Kosongkan / Tidak Dipakai --</option>
                                <?php foreach ($pejabat as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($curr && $curr['pejabat_id'] == $p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nama']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($plot_terpilih == 'bast_keluar' || $plot_terpilih == 'bast_masuk'): ?>
                        <div class="form-check mt-3">
                            <input class="form-check-input dinamis-check" type="checkbox" name="ttd[<?= $i ?>][is_dinamis]" value="1" <?= ($curr && $curr['is_dinamis']) ? 'checked' : '' ?>>
                            <label class="form-check-label small text-warning fw-bold">
                                Gunakan Sumber Data Transaksi (Penerima/Sumber)
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
                
                <hr>
                <button type="submit" class="btn btn-success">Simpan Plotting Laporan Ini</button>
            </form>
        </div>
    </div>
  </div>
  
  <!-- TAB TEKS DOKUMEN -->
  <div class="tab-pane fade <?= $active_tab == 'teks' ? 'show active' : '' ?>" id="teks" role="tabpanel">
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">Template Teks Dokumen Pengeluaran</div>
        <div class="card-body">
            <form method="post" action="pengaturan.php">
                <input type="hidden" name="action" value="save_kop"> <!-- reuse save_kop logic to save into pengaturan table -->
                <input type="hidden" name="tab" value="teks">
                
                <div class="alert alert-info small">
                    <strong>Gunakan Variabel (Tag) berikut di dalam teks:</strong><br>
                    <code>{no_npb}</code>, <code>{tgl_npb}</code>, <code>{no_spb}</code>, <code>{tgl_spb}</code>, <code>{no_sppb}</code>, <code>{tgl_sppb}</code>, <code>{no_bast}</code>, <code>{tgl_bast}</code>, <code>{pemohon}</code>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Teks Dasar SPB (Surat Permintaan)</label>
                    <textarea name="teks_spb_dasar" class="form-control" rows="4"><?= htmlspecialchars(isset($kop['teks_spb_dasar']) ? $kop['teks_spb_dasar'] : '') ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold">Teks Dasar SPPB (Surat Perintah Penyaluran)</label>
                    <textarea name="teks_sppb_dasar" class="form-control" rows="4"><?= htmlspecialchars(isset($kop['teks_sppb_dasar']) ? $kop['teks_sppb_dasar'] : '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Teks BAST Pembuka</label>
                    <textarea name="teks_bast_pembuka" class="form-control" rows="4"><?= htmlspecialchars(isset($kop['teks_bast_pembuka']) ? $kop['teks_bast_pembuka'] : '') ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Teks BAST Penutup</label>
                    <textarea name="teks_bast_penutup" class="form-control" rows="2"><?= htmlspecialchars(isset($kop['teks_bast_penutup']) ? $kop['teks_bast_penutup'] : '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Teks</button>
            </form>
        </div>
    </div>
  </div>
  
  <!-- TAB RESET SISTEM -->
  <div class="tab-pane fade <?= $active_tab == 'reset' ? 'show active' : '' ?>" id="reset" role="tabpanel">
    <div class="card w-100 border-danger mb-4" style="max-width: 900px;">
        <div class="card-header bg-danger text-white fw-bold">
            <i class="bi bi-exclamation-triangle-fill"></i> Hapus Data & Reset Sistem
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong>Peringatan Keras!</strong><br>
                Tindakan ini bersifat permanen dan data yang dihapus <strong>tidak dapat dikembalikan</strong>. Pastikan Anda telah membackup atau mencetak laporan yang diperlukan sebelum melakukan penghapusan.
            </div>
            
            <form method="post" onsubmit="return confirm('PERINGATAN TERAKHIR!\n\nApakah Anda YAKIN ingin menghapus data sesuai pilihan Anda?\nSemua data yang dihapus akan HILANG SELAMANYA.');">
                <input type="hidden" name="action" value="reset_data">
                <input type="hidden" name="tab" value="reset">
                
                <div class="mb-4">
                    <label class="fw-bold mb-2">Pilih Tipe Penghapusan Data:</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="tipe_reset" id="reset1" value="transaksi_master" required>
                        <label class="form-check-label" for="reset1">
                            <strong>1. Hapus Hanya Data Barang & Transaksi</strong><br>
                            <span class="text-muted">(Menghapus semua isi Master Barang dan Riwayat Transaksi. <em>Pengaturan Kop Surat, Daftar Pejabat, Kodefikasi, dan Plotting Tanda Tangan TIDAK akan dihapus</em>). Cocok untuk pergantian tahun ajaran baru.</span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tipe_reset" id="reset2" value="full_reset" required>
                        <label class="form-check-label text-danger" for="reset2">
                            <strong>2. Reset Pabrik (Hapus SELURUH Data)</strong><br>
                            <span class="text-muted">(Sistem akan dikembalikan seperti baru diinstal. Semua Barang, Transaksi, Master Pejabat, Kodefikasi, dan Pengaturan Kop Surat akan dikosongkan total).</span>
                        </label>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="fw-bold">Konfirmasi Penghapusan</label>
                    <p class="small text-muted mb-1">Untuk mencegah penghapusan tidak disengaja, silakan ketik kata <strong>HAPUS</strong> pada kotak di bawah ini.</p>
                    <input type="text" name="konfirmasi" class="form-control border-danger text-danger fw-bold" style="max-width: 200px;" required placeholder="Ketik HAPUS disini">
                </div>
                
                <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-trash-fill"></i> Eksekusi Penghapusan Data</button>
            </form>
        </div>
    </div>
  </div>
</div>

<script>
// JS to toggle select off if "dinamis" is checked
document.querySelectorAll('.dinamis-check').forEach(function(check) {
    check.addEventListener('change', function() {
        var select = this.closest('.col-md-3').querySelector('.pejabat-select');
        if (this.checked) {
            select.value = '';
            select.disabled = true;
        } else {
            select.disabled = false;
        }
    });
    // Trigger on load
    if (check.checked) {
        check.closest('.col-md-3').querySelector('.pejabat-select').disabled = true;
    }
});
</script>

<?php include 'layout_footer.php'; ?>
