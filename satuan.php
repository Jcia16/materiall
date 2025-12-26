<?php
require 'function.php';
require 'cek.php';

// ======================== HANDLE ADD ==========================
if(isset($_POST['addnewsatuan'])){
    $kategori = mysqli_real_escape_string($conn, $_POST['nama_satuan']);
    $insert = mysqli_query($conn, "INSERT INTO satuan (Kategori) VALUES ('$kategori')");
    if($insert){ header("Location: satuan.php"); exit; }
    else { $error = "Gagal tambah: ".mysqli_error($conn); }
}

// ======================== HANDLE EDIT ==========================
if(isset($_POST['editKategori'])){
    $id = (int)$_POST['id_kategori'];
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $up = mysqli_query($conn, "UPDATE satuan SET Kategori='$kategori' WHERE id_kategori=$id");
    if($up){ header("Location: satuan.php"); exit; }
    else { $error = "Gagal edit: ".mysqli_error($conn); }
}

// ======================== HANDLE DELETE ==========================
if(isset($_POST['deleteKategori'])){
    $id = (int)$_POST['id_kategori'];
    $del = mysqli_query($conn, "DELETE FROM satuan WHERE id_kategori=$id");
    if($del){ header("Location: satuan.php"); exit; }
    else { $error = "Gagal hapus: ".mysqli_error($conn); }
}

// ======================== NOTIFIKASI STOK MINIMUM ==========================
$notifCount = 0;
$notifItems = [];
$nq = mysqli_query($conn, "SELECT * FROM barang");
while($n = mysqli_fetch_assoc($nq)){
    if($n['stok'] <= $n['stok_minimum']){
        $notifCount++;
        $notifItems[] = $n['nama_barang'];
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kategori Barang</title>
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
<link href="css/styles.css" rel="stylesheet" />
<script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script>

<style>
table.dataTable th, table.dataTable td {
    text-align: center !important;
    vertical-align: middle !important;
}
#datatablesSimple thead th {
    background: #616161;
    color: white;
}
</style>
</head>

<body class="sb-nav-fixed">

<!-- ================= NAVBAR ================= -->
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">CV. Dwiasta Konstruksi</a>
    <button class="btn btn-link btn-sm" id="sidebarToggle"><i class="fas fa-bars"></i></button>

    <ul class="navbar-nav ms-auto align-items-center">

        <!-- Search -->
        <li class="nav-item me-3">
            <form action="search.php" method="GET">
                <div class="input-group">
                    <input class="form-control form-control-sm" name="q" type="text" placeholder="Search...">
                    <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </li>

        <!-- Notifikasi -->
        <li class="nav-item dropdown me-3">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                <i class="fas fa-bell text-warning"></i>
                <?php if($notifCount > 0): ?>
                <span class="badge bg-danger rounded-pill"><?= $notifCount ?></span>
                <?php endif; ?>
            </a>

            <div class="dropdown-menu dropdown-menu-end p-2" style="width:250px;">
                <h6 class="text-center">Notifikasi</h6>
                <?php if($notifCount == 0): ?>
                    <p class="text-center text-muted">Tidak ada notif</p>
                <?php else: foreach($notifItems as $nm): ?>
                    <div class="alert alert-warning py-1 mb-2">⚠ Stok <b><?= $nm ?></b> menipis</div>
                <?php endforeach; endif; ?>
            </div>
        </li>

        <!-- Profile -->
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

<!-- ================= SIDEBAR + CONTENT ================= -->
<div id="layoutSidenav">
    
    <!-- SIDEBAR -->
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    <a class="nav-link" href="index.php">Dashboard</a>

                    <div class="sb-sidenav-menu-heading">Master</div>
                    <a class="nav-link" href="barang.php">Data Barang</a>
                    <a class="nav-link active" href="satuan.php">Kategori</a>

                    <div class="sb-sidenav-menu-heading">Transaksi</div>
                    <a class="nav-link" href="barangmasuk.php">Barang Masuk</a>
                    <a class="nav-link" href="barangkeluar.php">Barang Keluar</a>

                    <div class="sb-sidenav-menu-heading">Laporan</div>
                    <a class="nav-link" href="laporan_stok.php">Laporan Stok</a>
                </div>
            </div>
        </nav>
    </div>

    <!-- CONTENT -->
    <div id="layoutSidenav_content">
        <main class="container-fluid px-4">

            <h2 class="mt-4 mb-3">📦 Kategori Barang</h2>

            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
                + Tambah Kategori
            </button>

            <div class="card mb-4">
                <div class="card-body">

                    <table id="datatablesSimple" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kategori</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php
                        $q = mysqli_query($conn, "SELECT * FROM satuan ORDER BY id_kategori ASC");
                        $no = 1;
                        while($row = mysqli_fetch_assoc($q)):
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['Kategori']) ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#edit<?= $row['id_kategori'] ?>">Edit</button>

                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#delete<?= $row['id_kategori'] ?>">Hapus</button>
                                </td>
                            </tr>

                            <!-- EDIT MODAL -->
                            <div class="modal fade" id="edit<?= $row['id_kategori'] ?>">
                                <div class="modal-dialog">
                                    <form method="POST">
                                        <div class="modal-content">
                                            <div class="modal-header bg-dark text-white"><strong>Edit Kategori</strong></div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_kategori" value="<?= $row['id_kategori'] ?>">
                                                <input type="text" name="kategori" class="form-control"
                                                    value="<?= htmlspecialchars($row['Kategori']) ?>" required>
                                            </div>
                                            <div class="modal-footer">
                                                <button class="btn btn-success" name="editKategori">Simpan</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- DELETE MODAL -->
                            <div class="modal fade" id="delete<?= $row['id_kategori'] ?>">
                                <div class="modal-dialog">
                                    <form method="POST">
                                        <div class="modal-content">
                                            <div class="modal-body">
                                                Hapus kategori <b><?= htmlspecialchars($row['Kategori']) ?></b>?
                                                <input type="hidden" name="id_kategori" value="<?= $row['id_kategori'] ?>">
                                            </div>
                                            <div class="modal-footer">
                                                <button class="btn btn-danger" name="deleteKategori">Hapus</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        <?php endwhile; ?>
                        </tbody>

                    </table>

                </div>
            </div>

        </main>
    </div>

</div>

<!-- MODAL ADD -->
<div class="modal fade" id="addModal">
    <div class="modal-dialog">
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white"><strong>Tambah Kategori</strong></div>
                <div class="modal-body">
                    <input type="text" name="nama_satuan" class="form-control" required placeholder="Nama kategori...">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" name="addnewsatuan">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"></script>
<script> new simpleDatatables.DataTable('#datatablesSimple'); </script>

</body>
</html>
