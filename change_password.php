<?php
require 'function.php';
require 'cek.php';

// ambil username dari session
$username = $_SESSION['username'];

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old = trim($_POST['password_lama'] ?? '');
    $new = trim($_POST['password_baru'] ?? '');
    $confirm = trim($_POST['konfirmasi_password'] ?? '');

    if ($old === '' || $new === '' || $confirm === '') {
        $errors[] = "Semua field wajib diisi.";
    } elseif ($new !== $confirm) {
        $errors[] = "Password baru dan konfirmasi tidak sama.";
    } else {

        // 🔎 CEK PASSWORD LAMA
        $cek = mysqli_query(
            $conn,
            "SELECT password FROM login WHERE username='$username' LIMIT 1"
        );
        $data = mysqli_fetch_assoc($cek);

        if (!$data) {
            $errors[] = "User tidak ditemukan.";
        } elseif ($old !== $data['password']) {
            $errors[] = "Password lama salah.";
        } else {

            // 🔄 UPDATE PASSWORD
            $upd = mysqli_query(
                $conn,
                "UPDATE login SET password='$new' WHERE username='$username'"
            );

            if ($upd) {
                $messages[] = "Password berhasil diubah.";
            } else {
                $errors[] = "Gagal mengubah password.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Ubah Password</title>
<link href="css/styles.css" rel="stylesheet">
<script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script>
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
        <a class="nav-link" href="laporan_stok.php">Laporan Stok</a>
      </div></div>
    </nav>
  </div>
<div id="layoutSidenav_content">
<main class="container-fluid px-4 mt-4">

<h3 class="mb-3">Ubah Password</h3>

<?php foreach($messages as $m): ?>
<div class="alert alert-success"><?= htmlspecialchars($m) ?></div>
<?php endforeach; ?>

<?php foreach($errors as $e): ?>
<div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="card col-md-6">
<div class="card-body">
<form method="POST">
    <div class="mb-3">
        <label class="form-label">Password Lama</label>
        <input type="password" name="password_lama" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Password Baru</label>
        <input type="password" name="password_baru" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Konfirmasi Password Baru</label>
        <input type="password" name="konfirmasi_password" class="form-control" required>
    </div>

    <button class="btn btn-primary">
        <i class="fas fa-key"></i> Simpan Perubahan
    </button>
    <a href="profil_toko.php" class="btn btn-secondary">Batal</a>
</form>
</div>
</div>

</main>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
