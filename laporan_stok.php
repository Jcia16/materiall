<?php
require 'function.php';
require 'cek.php';

// capture input
$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$qsearch = trim($_GET['q'] ?? '');
$report = $_GET['report'] ?? 'rekap'; // rekap | masuk | keluar

// normalize dates
$hasRange = false;
if ($dari !== '' && $sampai !== '') {
    $d = mysqli_real_escape_string($conn, $dari);
    $s = mysqli_real_escape_string($conn, $sampai);
    $hasRange = true;
} else {
    $d = ''; $s = '';
}

// search filter for barang
$searchWhere = "";
if ($qsearch !== '') {
    $qs = mysqli_real_escape_string($conn, $qsearch);
    $searchWhere = " AND (b.nama_barang LIKE '%$qs%' OR b.kategori LIKE '%$qs%') ";
}

// ---------- EXPORT EXCEL ----------
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=laporan_stok_{$report}_" . date('Ymd_His') . ".xls");
    echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";

    if ($report === 'masuk' || $report === 'keluar') {
        // detail transactions
        if ($report === 'masuk') {
            if ($hasRange) {
                $trsQ = mysqli_query($conn, "SELECT bm.*, b.nama_barang, b.kategori, b.stok AS stok_real
                    FROM barang_masuk bm JOIN barang b ON bm.id_barang=b.id_barang
                    WHERE bm.tanggal BETWEEN '$d' AND '$s' ORDER BY bm.tanggal ASC, bm.id_masuk ASC");
            } else {
                $trsQ = mysqli_query($conn, "SELECT bm.*, b.nama_barang, b.kategori, b.stok AS stok_real
                    FROM barang_masuk bm JOIN barang b ON bm.id_barang=b.id_barang
                    ORDER BY bm.tanggal ASC, bm.id_masuk ASC");
            }
            echo "<table border='1'><tr><th>No</th><th>Tanggal</th><th>Nama Barang</th><th>Kategori</th><th>Stok Sebelum</th><th>Jumlah Masuk</th><th>Stok Sesudah</th><th>Keterangan</th></tr>";
            $no=1;
            while($r = mysqli_fetch_assoc($trsQ)){
                $stok_after = (int)$r['stok_real'];
                $jumlah = (int)$r['jumlah'];
                $stok_before = $stok_after - $jumlah;
                echo "<tr>
                    <td>".$no++."</td>
                    <td>".$r['tanggal']."</td>
                    <td>".htmlspecialchars($r['nama_barang'])."</td>
                    <td>".htmlspecialchars($r['kategori'])."</td>
                    <td>".$stok_before."</td>
                    <td>".$jumlah."</td>
                    <td>".$stok_after."</td>
                    <td>".htmlspecialchars($r['keterangan'] ?? '')."</td>
                </tr>";
            }
            echo "</table>";
            exit;
        } else {
            // keluar
            if ($hasRange) {
                $trsQ = mysqli_query($conn, "SELECT bk.*, b.nama_barang, b.kategori, b.stok AS stok_real
                    FROM barang_keluar bk JOIN barang b ON bk.id_barang=b.id_barang
                    WHERE bk.tanggal BETWEEN '$d' AND '$s' ORDER BY bk.tanggal ASC, bk.id_keluar ASC");
            } else {
                $trsQ = mysqli_query($conn, "SELECT bk.*, b.nama_barang, b.kategori, b.stok AS stok_real
                    FROM barang_keluar bk JOIN barang b ON bk.id_barang=b.id_barang
                    ORDER BY bk.tanggal ASC, bk.id_keluar ASC");
            }
            echo "<table border='1'><tr><th>No</th><th>Tanggal</th><th>Nama Barang</th><th>Kategori</th><th>Stok Sebelum</th><th>Jumlah Keluar</th><th>Stok Sesudah</th></tr>";
            $no=1;
            while($r = mysqli_fetch_assoc($trsQ)){
                $stok_after = (int)$r['stok_real'];
                $jumlah = (int)$r['jumlah'];
                $stok_before = $stok_after + $jumlah;
                echo "<tr>
                    <td>".$no++."</td>
                    <td>".$r['tanggal']."</td>
                    <td>".htmlspecialchars($r['nama_barang'])."</td>
                    <td>".htmlspecialchars($r['kategori'])."</td>
                    <td>".$stok_before."</td>
                    <td>".$jumlah."</td>
                    <td>".$stok_after."</td>
                </tr>";
            }
            echo "</table>";
            exit;
        }
    } else {
        // rekap per barang (full columns)
        $listQ = mysqli_query($conn, "SELECT b.* FROM barang b WHERE 1 $searchWhere ORDER BY b.nama_barang ASC");
        echo "<table border='1'><tr>
            <th>No</th><th>Nama Barang</th><th>Kategori</th><th>Stok Min</th><th>Stok Real-Time</th>
            <th>Total Masuk (Periode)</th><th>Total Keluar (Periode)</th><th>Stok Awal (Periode)</th><th>Stok Akhir (Periode)</th><th>Status</th>
        </tr>";
        $no=1;
        while($b = mysqli_fetch_assoc($listQ)){
            $idb = (int)$b['id_barang'];
            if ($hasRange) {
                $rm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS masuk FROM barang_masuk WHERE id_barang=$idb AND tanggal BETWEEN '$d' AND '$s'"));
                $rk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS keluar FROM barang_keluar WHERE id_barang=$idb AND tanggal BETWEEN '$d' AND '$s'"));
            } else {
                $rm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS masuk FROM barang_masuk WHERE id_barang=$idb"));
                $rk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS keluar FROM barang_keluar WHERE id_barang=$idb"));
            }
            $masuk = (int)$rm['masuk'];
            $keluar = (int)$rk['keluar'];
            $stok_real = (int)$b['stok'];
            $stok_awal = $stok_real - $masuk + $keluar;
            $stok_akhir = $stok_awal + $masuk - $keluar;
            $status = ($stok_real == 0 ? 'Habis' : (($stok_real <= (int)$b['stok_minimum']) ? 'Menipis' : 'Aman'));
            echo "<tr>
                <td>".$no++."</td>
                <td>".htmlspecialchars($b['nama_barang'])."</td>
                <td>".htmlspecialchars($b['kategori'])."</td>
                <td>".(int)$b['stok_minimum']."</td>
                <td>".(int)$stok_real."</td>
                <td>".$masuk."</td>
                <td>".$keluar."</td>
                <td>".$stok_awal."</td>
                <td>".$stok_akhir."</td>
                <td>".$status."</td>
            </tr>";
        }
        echo "</table>";
        exit;
    }
}

// ---------- EXPORT PRINT (PDF view) ----------
if (isset($_GET['export']) && $_GET['export'] === 'print') {
    // keep same structure as excel but nicer style and window.print()
    ?>
    <!doctype html>
    <html lang="id">
    <head>
      <meta charset="utf-8">
      <title>Print Laporan Stok - <?= htmlspecialchars($report) ?></title>
      <style>
        body{font-family:Arial, sans-serif;margin:20px}
        h2{text-align:center;margin:0 0 6px}
        .meta{text-align:center;margin-bottom:12px}
        table{width:100%;border-collapse:collapse;margin-top:12px}
        th,td{border:1px solid #000;padding:8px;font-size:12px}
        th{background:#333;color:#fff}
        .text-center{text-align:center}
      </style>
    </head>
    <body onload="window.print()">
      <h2>LAPORAN STOK - <?= strtoupper(htmlspecialchars($report)) ?></h2>
      <div class="meta">
        CV. Dwiasta Konstruksi<br>
        Dicetak: <?= date('d-m-Y H:i') ?><br>
        <?php if ($hasRange): ?>
          Periode: <strong><?=htmlspecialchars($dari)?></strong> s/d <strong><?=htmlspecialchars($sampai)?></strong>
        <?php else: ?>
          Periode: Semua Data
        <?php endif; ?>
      </div>

    <?php
    if ($report === 'masuk') {
        if ($hasRange) {
            $trsQ = mysqli_query($conn, "SELECT bm.*, b.nama_barang, b.kategori, b.stok AS stok_real
                FROM barang_masuk bm JOIN barang b ON bm.id_barang=b.id_barang
                WHERE bm.tanggal BETWEEN '$d' AND '$s' ORDER BY bm.tanggal ASC, bm.id_masuk ASC");
        } else {
            $trsQ = mysqli_query($conn, "SELECT bm.*, b.nama_barang, b.kategori, b.stok AS stok_real
                FROM barang_masuk bm JOIN barang b ON bm.id_barang=b.id_barang
                ORDER BY bm.tanggal ASC, bm.id_masuk ASC");
        }
        echo "<table><thead><tr><th>No</th><th>Tanggal</th><th>Nama Barang</th><th>Kategori</th><th>Stok Sebelum</th><th>Jumlah Masuk</th><th>Stok Sesudah</th><th>Keterangan</th></tr></thead><tbody>";
        $no=1;
        while($r = mysqli_fetch_assoc($trsQ)){
            $stok_after = (int)$r['stok_real'];
            $jumlah = (int)$r['jumlah'];
            $stok_before = $stok_after - $jumlah;
            echo "<tr>
                <td class='text-center'>".$no++."</td>
                <td>".$r['tanggal']."</td>
                <td>".htmlspecialchars($r['nama_barang'])."</td>
                <td>".htmlspecialchars($r['kategori'])."</td>
                <td class='text-center'>".$stok_before."</td>
                <td class='text-center'>".$jumlah."</td>
                <td class='text-center'>".$stok_after."</td>
                <td>".htmlspecialchars($r['keterangan'] ?? '')."</td>
            </tr>";
        }
        echo "</tbody></table>";
    } elseif ($report === 'keluar') {
        if ($hasRange) {
            $trsQ = mysqli_query($conn, "SELECT bk.*, b.nama_barang, b.kategori, b.stok AS stok_real
                FROM barang_keluar bk JOIN barang b ON bk.id_barang=b.id_barang
                WHERE bk.tanggal BETWEEN '$d' AND '$s' ORDER BY bk.tanggal ASC, bk.id_keluar ASC");
        } else {
            $trsQ = mysqli_query($conn, "SELECT bk.*, b.nama_barang, b.kategori, b.stok AS stok_real
                FROM barang_keluar bk JOIN barang b ON bk.id_barang=b.id_barang
                ORDER BY bk.tanggal ASC, bk.id_keluar ASC");
        }
        echo "<table><thead><tr><th>No</th><th>Tanggal</th><th>Nama Barang</th><th>Kategori</th><th>Stok Sebelum</th><th>Jumlah Keluar</th><th>Stok Sesudah</th></tr></thead><tbody>";
        $no=1;
        while($r = mysqli_fetch_assoc($trsQ)){
            $stok_after = (int)$r['stok_real'];
            $jumlah = (int)$r['jumlah'];
            $stok_before = $stok_after + $jumlah;
            echo "<tr>
                <td class='text-center'>".$no++."</td>
                <td>".$r['tanggal']."</td>
                <td>".htmlspecialchars($r['nama_barang'])."</td>
                <td>".htmlspecialchars($r['kategori'])."</td>
                <td class='text-center'>".$stok_before."</td>
                <td class='text-center'>".$jumlah."</td>
                <td class='text-center'>".$stok_after."</td>
            </tr>";
        }
        echo "</tbody></table>";
    } else {
        // rekap
        $listQ = mysqli_query($conn, "SELECT b.* FROM barang b WHERE 1 $searchWhere ORDER BY b.nama_barang ASC");
        echo "<table><thead><tr><th>No</th><th>Nama Barang</th><th>Kategori</th><th>Stok Min</th><th>Stok Real-Time</th><th>Total Masuk (Periode)</th><th>Total Keluar (Periode)</th><th>Stok Awal</th><th>Stok Akhir</th><th>Status</th></tr></thead><tbody>";
        $no=1;
        while($b = mysqli_fetch_assoc($listQ)){
            $idb = (int)$b['id_barang'];
            if ($hasRange) {
                $rm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS masuk FROM barang_masuk WHERE id_barang=$idb AND tanggal BETWEEN '$d' AND '$s'"));
                $rk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS keluar FROM barang_keluar WHERE id_barang=$idb AND tanggal BETWEEN '$d' AND '$s'"));
            } else {
                $rm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS masuk FROM barang_masuk WHERE id_barang=$idb"));
                $rk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS keluar FROM barang_keluar WHERE id_barang=$idb"));
            }
            $masuk = (int)$rm['masuk'];
            $keluar = (int)$rk['keluar'];
            $stok_real = (int)$b['stok'];
            $stok_awal = $stok_real - $masuk + $keluar;
            $stok_akhir = $stok_awal + $masuk - $keluar;
            $status = ($stok_real == 0 ? 'Habis' : (($stok_real <= (int)$b['stok_minimum']) ? 'Menipis' : 'Aman'));
            echo "<tr>
                <td class='text-center'>".$no++."</td>
                <td>".htmlspecialchars($b['nama_barang'])."</td>
                <td>".htmlspecialchars($b['kategori'])."</td>
                <td class='text-center'>".(int)$b['stok_minimum']."</td>
                <td class='text-center'>".$stok_real."</td>
                <td class='text-center'>".$masuk."</td>
                <td class='text-center'>".$keluar."</td>
                <td class='text-center'>".$stok_awal."</td>
                <td class='text-center'>".$stok_akhir."</td>
                <td class='text-center'>".$status."</td>
            </tr>";
        }
        echo "</tbody></table>";
    }

    echo "</body></html>";
    exit;
}

// ---------- MAIN PAGE DATA ----------
$listQ = mysqli_query($conn, "SELECT b.* FROM barang b WHERE 1 $searchWhere ORDER BY b.nama_barang ASC");

// notification (optional same as other pages)
$notifCount = 0; $notifItems = [];
$notifQ = mysqli_query($conn, "SELECT nama_barang, stok, stok_minimum FROM barang");
if ($notifQ) {
    while($n = mysqli_fetch_assoc($notifQ)){
        if ((int)$n['stok'] <= (int)$n['stok_minimum']) {
            $notifCount++;
            $notifItems[] = $n['nama_barang'];
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Laporan Stok - Rekap / Detail</title>

<link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
<link href="css/styles.css" rel="stylesheet" />
<script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script>

<style>
.table thead th { background:#343a40; color:#fff; }
.table-actions { display:flex; gap:6px; justify-content:center; }
.small-muted { font-size:12px; color:#666; }
</style>
</head>
<body class="sb-nav-fixed">

<!-- NAVBAR -->
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">CV. Dwiasta Konstruksi</a>
    <button class="btn btn-link btn-sm" id="sidebarToggle"><i class="fas fa-bars"></i></button>

    <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-3">
            <form action="search.php" method="GET"><div class="input-group">
                <input class="form-control form-control-sm" name="q" type="text" placeholder="Search...">
                <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            </div></form>
        </li>
        <li class="nav-item dropdown me-3">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                <i class="fas fa-bell fa-lg text-warning"></i>
                <?php if ($notifCount>0): ?><span class="badge bg-danger rounded-pill"><?=$notifCount?></span><?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-end p-2" style="width:250px;">
                <h6 class="text-center">Notifikasi</h6>
                <?php if ($notifCount==0): ?>
                    <p class="text-center text-muted">Tidak ada notifikasi</p>
                <?php else: foreach($notifItems as $it): ?>
                    <div class="alert alert-warning py-1 mb-2">⚠ Stok <b><?=htmlspecialchars($it)?></b> hampir habis</div>
                <?php endforeach; endif; ?>
            </div>
        </li>

        <li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
        <i class="fas fa-user-circle fa-lg"></i>
        <span>Akun</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end shadow">
        <li><a class="dropdown-item" href="profil_toko.php">Profil Toko</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger fw-bold" href="logout.php">Logout</a></li>
    </ul>
</li>

    </ul>
</nav>

<div id="layoutSidenav">
  <div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
      <div class="sb-sidenav-menu"><div class="nav">
        <a class="nav-link" href="index.php">Dashboard</a>
        <div class="sb-sidenav-menu-heading">Master</div>
        <a class="nav-link" href="barang.php">Data Barang</a>
        <a class="nav-link" href="satuan.php">Kategori</a>
        <div class="sb-sidenav-menu-heading">Transaksi</div>
        <a class="nav-link" href="barangmasuk.php">Barang Masuk</a>
        <a class="nav-link" href="barangkeluar.php">Barang Keluar</a>
        <div class="sb-sidenav-menu-heading">Laporan</div>
        <a class="nav-link active" href="laporan_stok.php">Laporan Stok</a>
      </div></div>
    </nav>
  </div>

  <div id="layoutSidenav_content">
    <main class="container-fluid px-4 mt-3">
      <h2 class="mb-3">Laporan Stok</h2>

      <div class="d-flex gap-2 align-items-center mb-3">
        <form method="GET" class="d-flex gap-2 align-items-end" id="filterForm">
          <div>
            <label class="small mb-1">Dari</label>
            <input type="date" name="dari" class="form-control form-control-sm" value="<?=htmlspecialchars($dari)?>">
          </div>
          <div>
            <label class="small mb-1">Sampai</label>
            <input type="date" name="sampai" class="form-control form-control-sm" value="<?=htmlspecialchars($sampai)?>">
          </div>

          <div>
            <label class="small mb-1">Cari</label>
            <input type="search" name="q" class="form-control form-control-sm" placeholder="Nama atau kategori" value="<?=htmlspecialchars($qsearch)?>">
          </div>

          <div>
            <label class="small mb-1">Jenis Laporan</label>
            <select name="report" class="form-select form-select-sm">
              <option value="rekap" <?= $report==='rekap' ? 'selected' : '' ?>>Stok Akhir (Rekap)</option>
              <option value="masuk" <?= $report==='masuk' ? 'selected' : '' ?>>Laporan Masuk</option>
              <option value="keluar" <?= $report==='keluar' ? 'selected' : '' ?>>Laporan Keluar</option>
            </select>
          </div>

          <div class="align-self-end">
            <button class="btn btn-warning btn-sm">Terapkan</button>
            <a href="laporan_stok.php" class="btn btn-secondary btn-sm">Reset</a>
          </div>
        </form>

        <div class="ms-auto d-flex gap-2">
          <a href="laporan_stok.php?export=excel<?= $dari ? '&dari='.urlencode($dari) : '' ?><?= $sampai ? '&sampai='.urlencode($sampai) : '' ?><?= $qsearch ? '&q='.urlencode($qsearch) : '' ?><?= $report ? '&report='.urlencode($report) : '' ?>" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Excel
          </a>

          <a href="laporan_stok.php?export=print<?= $dari ? '&dari='.urlencode($dari) : '' ?><?= $sampai ? '&sampai='.urlencode($sampai) : '' ?><?= $qsearch ? '&q='.urlencode($qsearch) : '' ?><?= $report ? '&report='.urlencode($report) : '' ?>" target="_blank" class="btn btn-danger btn-sm">
            <i class="fas fa-file-pdf"></i> PDF
          </a>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <div class="mb-2 small-muted">
            <?php
            if ($report === 'masuk') echo "Menampilkan transaksi barang masuk (detail) dalam periode yang dipilih.";
            elseif ($report === 'keluar') echo "Menampilkan transaksi barang keluar (detail) dalam periode yang dipilih.";
            else echo "Rekap stok akhir per barang. Menampilkan stok real-time dan pergerakan pada periode.";
            ?>
          </div>

          <div class="table-responsive">
            <?php if ($report === 'masuk'): ?>
              <table id="tblLaporan" class="table table-striped table-bordered">
                <thead class="table-dark text-center">
                  <tr><th>No</th><th>Tanggal</th><th>Nama Barang</th><th>Kategori</th><th>Stok Sebelum</th><th>Jumlah Masuk</th><th>Stok Sesudah</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                  <?php
                  if ($hasRange) {
                      $trsQ = mysqli_query($conn, "SELECT bm.*, b.nama_barang, b.kategori, b.stok AS stok_real
                          FROM barang_masuk bm JOIN barang b ON bm.id_barang=b.id_barang
                          WHERE bm.tanggal BETWEEN '$d' AND '$s' ORDER BY bm.tanggal ASC, bm.id_masuk ASC");
                  } else {
                      $trsQ = mysqli_query($conn, "SELECT bm.*, b.nama_barang, b.kategori, b.stok AS stok_real
                          FROM barang_masuk bm JOIN barang b ON bm.id_barang=b.id_barang
                          ORDER BY bm.tanggal ASC, bm.id_masuk ASC");
                  }
                  $no = 1;
                  while($r = mysqli_fetch_assoc($trsQ)):
                    $stok_after = (int)$r['stok_real'];
                    $jumlah = (int)$r['jumlah'];
                    $stok_before = $stok_after - $jumlah;
                  ?>
                  <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= htmlspecialchars($r['tanggal']) ?></td>
                    <td><?= htmlspecialchars($r['nama_barang']) ?></td>
                    <td><?= htmlspecialchars($r['kategori']) ?></td>
                    <td class="text-center"><?= $stok_before ?></td>
                    <td class="text-end"><?= number_format($jumlah) ?></td>
                    <td class="text-center"><?= $stok_after ?></td>
                    <td><?= htmlspecialchars($r['keterangan'] ?? '') ?></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>

            <?php elseif ($report === 'keluar'): ?>
              <table id="tblLaporan" class="table table-striped table-bordered">
                <thead class="table-dark text-center">
                  <tr><th>No</th><th>Tanggal</th><th>Nama Barang</th><th>Kategori</th><th>Stok Sebelum</th><th>Jumlah Keluar</th><th>Stok Sesudah</th></tr>
                </thead>
                <tbody>
                  <?php
                  if ($hasRange) {
                      $trsQ = mysqli_query($conn, "SELECT bk.*, b.nama_barang, b.kategori, b.stok AS stok_real
                          FROM barang_keluar bk JOIN barang b ON bk.id_barang=b.id_barang
                          WHERE bk.tanggal BETWEEN '$d' AND '$s' ORDER BY bk.tanggal ASC, bk.id_keluar ASC");
                  } else {
                      $trsQ = mysqli_query($conn, "SELECT bk.*, b.nama_barang, b.kategori, b.stok AS stok_real
                          FROM barang_keluar bk JOIN barang b ON bk.id_barang=b.id_barang
                          ORDER BY bk.tanggal ASC, bk.id_keluar ASC");
                  }
                  $no = 1;
                  while($r = mysqli_fetch_assoc($trsQ)):
                    $stok_after = (int)$r['stok_real'];
                    $jumlah = (int)$r['jumlah'];
                    $stok_before = $stok_after + $jumlah;
                  ?>
                  <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= htmlspecialchars($r['tanggal']) ?></td>
                    <td><?= htmlspecialchars($r['nama_barang']) ?></td>
                    <td><?= htmlspecialchars($r['kategori']) ?></td>
                    <td class="text-center"><?= $stok_before ?></td>
                    <td class="text-end"><?= number_format($jumlah) ?></td>
                    <td class="text-center"><?= $stok_after ?></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>

            <?php else: // rekap ?>
              <table id="tblLaporan" class="table table-striped table-bordered">
                <thead class="table-dark text-center">
                  <tr>
                    <th>No</th><th>Nama Barang</th><th>Kategori</th><th>Stok Min</th><th>Stok Real-Time</th>
                    <th>Total Masuk (Periode)</th><th>Total Keluar (Periode)</th><th>Stok Awal (Periode)</th><th>Stok Akhir (Periode)</th><th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $no = 1;
                  mysqli_data_seek($listQ,0);
                  while($b = mysqli_fetch_assoc($listQ)):
                    $idb = (int)$b['id_barang'];
                    if ($hasRange) {
                        $rm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS masuk FROM barang_masuk WHERE id_barang=$idb AND tanggal BETWEEN '$d' AND '$s'"));
                        $rk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS keluar FROM barang_keluar WHERE id_barang=$idb AND tanggal BETWEEN '$d' AND '$s'"));
                    } else {
                        $rm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS masuk FROM barang_masuk WHERE id_barang=$idb"));
                        $rk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(jumlah),0) AS keluar FROM barang_keluar WHERE id_barang=$idb"));
                    }
                    $masuk = (int)$rm['masuk'];
                    $keluar = (int)$rk['keluar'];
                    $stok_real = (int)$b['stok'];
                    $stok_awal = $stok_real - $masuk + $keluar;
                    $stok_akhir = $stok_awal + $masuk - $keluar;
                    $status = ($stok_real == 0 ? '<span style="color:#dc3545;font-weight:700">Habis</span>' : (($stok_real <= (int)$b['stok_minimum']) ? '<span style="color:#d97706;font-weight:600">Menipis</span>' : '<span style="color:green;font-weight:600">Aman</span>'));
                  ?>
                    <tr>
                      <td class="text-center"><?= $no++ ?></td>
                      <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                      <td><?= htmlspecialchars($b['kategori']) ?></td>
                      <td class="text-center"><?= (int)$b['stok_minimum'] ?></td>
                      <td class="text-center"><?= $stok_real ?></td>
                      <td class="text-center"><?= $masuk ?></td>
                      <td class="text-center"><?= $keluar ?></td>
                      <td class="text-center"><?= $stok_awal ?></td>
                      <td class="text-center"><?= $stok_akhir ?></td>
                      <td class="text-center"><?= $status ?></td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

        </div>
      </div>

    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"></script>
<script>
new simpleDatatables.DataTable("#tblLaporan", {
    perPage: 15,
    perPageSelect: [10,15,25,50],
    searchable: true,
    fixedHeight: false,
    labels: { placeholder: "Cari..." }
});
</script>
</body>
</html>
