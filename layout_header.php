<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaris BHP Sekolah</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- TomSelect CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        body { padding-top: 70px; background-color: #f8f9fa; }
        .sidebar { min-height: calc(100vh - 70px); background-color: #fff; border-right: 1px solid #dee2e6; }
        .nav-link { color: #333; }
        .nav-link.active { font-weight: bold; color: #0d6efd; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top d-print-none">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Aplikasi BHP Sekolah</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse d-print-none" id="navbarNav">
      <div class="position-sticky pt-3">
        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>" href="index.php">
              <i class="bi bi-house-door-fill me-2"></i> Dashboard
            </a>
          </li>
          
          <li class="nav-item mt-3">
            <span class="nav-link text-muted fw-bold small text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Aktivitas Utama</span>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'transaksi.php' || basename($_SERVER['PHP_SELF']) == 'import_transaksi.php') ? 'active' : '' ?>" href="transaksi.php">
              <i class="bi bi-arrow-left-right me-2"></i> Transaksi Mutasi
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'dokumen.php') ? 'active' : '' ?>" href="dokumen.php">
              <i class="bi bi-folder-fill me-2"></i> Manajemen Dokumen
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'laporan.php') ? 'active' : '' ?>" href="laporan.php">
              <i class="bi bi-printer-fill me-2"></i> Laporan & Rekap
            </a>
          </li>

          <li class="nav-item mt-3">
            <span class="nav-link text-muted fw-bold small text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Database Master</span>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'master.php' || basename($_SERVER['PHP_SELF']) == 'import_master.php') ? 'active' : '' ?>" href="master.php">
              <i class="bi bi-box-seam me-2"></i> Master Barang
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'kodefikasi.php') ? 'active' : '' ?>" href="kodefikasi.php">
              <i class="bi bi-upc-scan me-2"></i> Kodefikasi Barang
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'referensi.php') ? 'active' : '' ?>" href="referensi.php">
              <i class="bi bi-tags-fill me-2"></i> Data Referensi
            </a>
          </li>

          <li class="nav-item mt-3">
            <span class="nav-link text-muted fw-bold small text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Bantuan & Sistem</span>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'roadmap.php') ? 'active' : '' ?>" href="roadmap.php">
              <i class="bi bi-map-fill me-2"></i> SOP & Roadmap
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'updater.php') ? 'active' : '' ?>" href="updater.php">
              <i class="bi bi-cloud-arrow-down-fill me-2 text-primary"></i> Update Sistem
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'pengaturan.php') ? 'active' : '' ?> text-success fw-bold" href="pengaturan.php">
              <i class="bi bi-gear-fill me-2"></i> Pengaturan
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3">
