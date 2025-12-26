<?php
require 'function.php';
require 'cek.php';

// ==== CEK NOTIFIKASI DARI TABEL BARANG ====
$notifCount = 0;
$notifItems = [];

$q = mysqli_query($conn, "SELECT nama_barang, stok, stok_minimum, rata_pakai_harian, lead_time FROM barang");

while($row = mysqli_fetch_assoc($q)){
    $rop = hitungROP($row['rata_pakai_harian'],$row['lead_time']);

    if($row['stok'] <= $rop){
        $notifCount++;
        $notifItems[] = $row['nama_barang'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Aplikasi Stok Barang | CV. Dwiasta Konstruksi</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>
    
    <body class="sb-nav-fixed">
        <!-- Navbar -->
         
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">CV. Dwiasta Konstruksi</a>
    <button class="btn btn-link btn-sm" id="sidebarToggle"><i class="fas fa-bars"></i></button>


    <ul class="navbar-nav ms-auto align-items-center">

        <!-- SEARCH GLOBAL -->
        <li class="nav-item me-3">
    <form action="search.php" method="GET">
        <div class="input-group">
            <input class="form-control form-control-sm" name="q" type="text" placeholder="Search...">
            <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        </div>
    </form>
</li>


        <!-- BELL NOTIFICATION -->
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown me-3">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                <i class="fas fa-bell fa-lg text-warning"></i>
                <?php if($notifCount>0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $notifCount ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-end p-2" style="width:250px;">
                <h6 class="text-center">Notifikasi</h6>
                <?php if($notifCount==0): ?>
                    <p class="text-center text-muted">Tidak ada notifikasi</p>
                <?php else: foreach($notifItems as $item): ?>
                    <div class="alert alert-warning py-1 mb-2">
                        ⚠ Stok <b><?= $item ?></b> hampir habis
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </li>

        <!-- PROFILE DROPDOWN -->
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
            <!-- Sidebar -->
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <a class="nav-link active" href="index.php">Dashboard</a>
                            <div class="sb-sidenav-menu-heading">Master</div>
                            <a class="nav-link" href="barang.php">Data Barang</a>
                            <a class="nav-link" href="satuan.php">Kategori</a>
                            <div class="sb-sidenav-menu-heading">Transaksi</div>
                            <a class="nav-link" href="barangmasuk.php">Barang Masuk</a>
                            <a class="nav-link" href="barangkeluar.php">Barang Keluar</a>
                            <div class="sb-sidenav-menu-heading">Laporan</div>
                            <a class="nav-link" href="laporan_stok.php">Laporan Stok</a>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Content -->
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4">
                        <h1 class="mt-4">Dashboard Admin</h1>

                        <!-- ======= Kartu Ringkasan Data ======= -->
                        <?php
                        $data_barang = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM barang"));
                        $data_masuk = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM barang_masuk"));
                        $data_keluar = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM barang_keluar"));
                        $data_satuan = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM satuan"));
                        ?>

                        <div class="row my-4">

    <!-- Data Barang -->
    <div class="col-xl-2 col-md-4 mb-4">
        <a href="barang.php" style="text-decoration: none;">
            <div class="card bg-light shadow text-center py-3">
                <i class="fas fa-box fa-2x text-primary mb-2"></i>
                <div class="text-dark">Data Barang</div>
                <h4 class="text-dark"><?= $data_barang; ?></h4>
            </div>
        </a>
    </div>

    <!-- Barang Masuk -->
    <div class="col-xl-2 col-md-4 mb-4">
        <a href="barangmasuk.php" style="text-decoration: none;">
            <div class="card bg-success text-white shadow text-center py-3">
                <i class="fas fa-arrow-down fa-2x mb-2"></i>
                <div>Barang Masuk</div>
                <h4><?= $data_masuk; ?></h4>
            </div>
        </a>
    </div>

    <!-- Barang Keluar -->
    <div class="col-xl-2 col-md-4 mb-4">
        <a href="barangkeluar.php" style="text-decoration: none;">
            <div class="card bg-warning text-white shadow text-center py-3">
                <i class="fas fa-arrow-up fa-2x mb-2"></i>
                <div>Barang Keluar</div>
                <h4><?= $data_keluar; ?></h4>
            </div>
        </a>
    </div>

    <!-- Satuan -->
    <div class="col-xl-2 col-md-4 mb-4">
        <a href="satuan.php" style="text-decoration: none;">
            <div class="card bg-info text-white shadow text-center py-3">
                <i class="fas fa-layer-group fa-2x mb-2"></i>
                <div>Kategori</div>
                <h4><?= $data_satuan; ?></h4>
            </div>
        </a>
</div>
<div class="col-xl-2 col-md-4 mb-4">
        <a href="laporan_stok.php" style="text-decoration: none;">
            <div class="card bg-success text-white shadow text-center py-3">
                <i class="fas fa-arrow-down fa-2x mb-2"></i>
                <div>Laporan Stok</div>
            </div>
        </a>
    </div>
</div>


                        <!-- ======= Tabel Data Stok & ROP ======= -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-table me-1"></i> Data Stok Barang & Reorder Point
                            </div>
                            <div class="card-body">
                                <table id="datatablesSimple" class="table table-bordered table-striped">
                                    <thead class="table-dark text-center">
                                        <tr>
                                            <th>id barang</th>
                                            <th>Nama Barang</th>
                                            <th>Stok Saat Ini</th>
                                            <th>Stok Minimum</th>
                                            <th>Rata Pakai Harian</th>
                                            <th>Lead Time (Hari)</th>
                                            <th>ROP</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $ambilsemuadata = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang ASC");
                                    while ($data = mysqli_fetch_array($ambilsemuadata)) {
                                        $id = $data['id_barang'];
                                        $nama = $data['nama_barang'];
                                        $stok = $data['stok'];
                                        $stok_minimum = $data['stok_minimum'];
                                        $rata = $data['rata_pakai_harian'];
                                        $lead = $data['lead_time'];
                                        
                                        // Hitung ROP
                                        $rop = hitungROP($rata, $lead);
                                        
                                        // Tentukan status
                                        if ($stok <= $rop) {
                                            $status = "<span class='badge bg-danger'>Perlu Restok</span>";
                                        } else {
                                            $status = "<span class='badge bg-success'>Aman</span>";
                                        }

                                        echo "
                                        <tr class='text-center'>
                                            <td>$id</td>
                                            <td>$nama</td>
                                            <td>$stok</td>
                                            <td>$stok_minimum</td>
                                            <td>$rata</td>
                                            <td>$lead</td>
                                            <td>$rop</td>
                                            <td>$status</td>
                                        </tr>
                                        ";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </main>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/scripts.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"></script>
        <script>
            new simpleDatatables.DataTable("#datatablesSimple");
        </script>
    </body>
</html>
