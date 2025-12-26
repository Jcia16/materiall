<?php
require 'function.php';
require 'cek.php';

/* =============================
   CRUD HANDLER
=============================*/

// ADD
if(isset($_POST['addnewbarang'])){
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $stok = 0; // stok awal selalu 0
    $min  = (int)$_POST['stok_minimum'];
    $rata = (int)$_POST['rata_pakai_harian'];
    $lead = (int)$_POST['lead_time'];
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);

    $q = mysqli_query($conn,"INSERT INTO barang (nama_barang,stok,stok_minimum,rata_pakai_harian,lead_time,kategori)
                            VALUES ('$nama',$stok,$min,$rata,$lead,'$kategori')");
    header("Location: barang.php");
    exit;
}

// EDIT
if(isset($_POST['editbarang'])){
    $id   = (int)$_POST['id_barang'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $min  = (int)$_POST['stok_minimum'];
    $rata = (int)$_POST['rata_pakai_harian'];
    $lead = (int)$_POST['lead_time'];
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);

    mysqli_query($conn,"UPDATE barang SET
        nama_barang='$nama',
        stok_minimum=$min,
        rata_pakai_harian=$rata,
        lead_time=$lead,
        kategori='$kategori'
        WHERE id_barang=$id");

    header("Location: barang.php");
    exit;
}

// DELETE
if(isset($_POST['deletebarang'])){
    $id = (int)$_POST['id_barang'];
    mysqli_query($conn,"DELETE FROM barang WHERE id_barang=$id");
    header("Location: barang.php");
    exit;
}

/* =============================
   NOTIFIKASI STOK MINIMUM
=============================*/
$notifCount = 0;
$notifItems = [];

$qnotif = mysqli_query($conn,"SELECT nama_barang,stok,stok_minimum FROM barang");
while($r = mysqli_fetch_assoc($qnotif)){
    if($r['stok'] <= $r['stok_minimum']){
        $notifCount++;
        $notifItems[] = $r['nama_barang'];
    }
}

?>
<!doctype html>
<html lang="id">
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

<!-- NAVBAR -->
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="index.php">CV. Dwiasta Konstruksi</a>
    <button class="btn btn-link btn-sm" id="sidebarToggle"><i class="fas fa-bars"></i></button>


    <ul class="navbar-nav ms-auto align-items-center">

        <!-- SEARCH -->
        <li class="nav-item me-3">
            <form action="search.php" method="GET">
                <div class="input-group">
                    <input class="form-control form-control-sm" name="q" type="text" placeholder="Search...">
                    <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </li>

        <!-- NOTIF -->
        <li class="nav-item dropdown me-3">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-bell text-warning"></i>
                <?php if($notifCount>0): ?>
                <span class="badge bg-danger"><?= $notifCount ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-end p-2">
                <h6 class="text-center">Notifikasi</h6>
                <?php if($notifCount==0): ?>
                    <p class="text-center text-muted">Tidak ada notifikasi</p>
                <?php else: foreach($notifItems as $n): ?>
                    <div class="alert alert-warning py-1">⚠ Stok <b><?= $n ?></b> rendah</div>
                <?php endforeach; endif; ?>
            </div>
        </li>

        <!-- PROFILE -->
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
    <!-- SIDEBAR -->
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    <a class="nav-link" href="index.php">Dashboard</a>
                    <div class="sb-sidenav-menu-heading">Master</div>
                    <a class="nav-link active" href="barang.php">Data Barang</a>
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

    <!-- CONTENT -->
    <div id="layoutSidenav_content">
        <main class="container-fluid px-4">

            <h2 class="mt-4 mb-3">Data Barang</h2>

            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalAdd">
                <i class="fas fa-plus"></i> Tambah Barang
            </button>

            <div class="card mb-4">
                <div class="card-body">
                    <table id="datatablesSimple" class="table table-bordered table-striped">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Stok</th>
                                <th>Min</th>
                                <th>ROP</th>
                                <th>Kategori</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $no=1;
                            $q = mysqli_query($conn,"SELECT * FROM barang ORDER BY nama_barang ASC");
                            while($row = mysqli_fetch_assoc($q)):
                                $rop = hitungROP($row['rata_pakai_harian'], $row['lead_time']);
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td class="text-end"><?= $row['stok'] ?></td>
                                <td class="text-end"><?= $row['stok_minimum'] ?></td>
                                <td class="text-end"><?= $rop ?></td>
                                <td><?= htmlspecialchars($row['kategori']) ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#edit<?= $row['id_barang'] ?>">
                                            <i class="fas fa-edit"></i></button>

                                    <button class="btn btn-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#del<?= $row['id_barang'] ?>">
                                            <i class="fas fa-trash"></i></button>
                                </td>
                            </tr>

                            <!-- EDIT MODAL -->
                            <div class="modal fade" id="edit<?= $row['id_barang'] ?>">
                              <div class="modal-dialog">
                                <form method="POST">
                                  <input type="hidden" name="id_barang" value="<?= $row['id_barang'] ?>">
                                  <div class="modal-content">
                                    <div class="modal-header bg-dark text-white"><strong>Edit Barang</strong></div>
                                    <div class="modal-body">

                                      <label>Nama Barang</label>
                                      <input class="form-control" name="nama_barang" value="<?= htmlspecialchars($row['nama_barang']) ?>" required>

                                      <label>Stok Minimum</label>
                                      <input class="form-control" type="number" name="stok_minimum" value="<?= $row['stok_minimum'] ?>">

                                      <label>Rata Pakai Harian</label>
                                      <input class="form-control" type="number" name="rata_pakai_harian" value="<?= $row['rata_pakai_harian'] ?>">

                                      <label>Lead Time</label>
                                      <input class="form-control" type="number" name="lead_time" value="<?= $row['lead_time'] ?>">

                                      <label>Kategori</label>
                                      <select class="form-control" name="kategori">
                                          <option value="">-- Pilih Kategori --</option>
                                          <?php
                                          $s = mysqli_query($conn,"SELECT Kategori FROM satuan");
                                          while($c = mysqli_fetch_assoc($s)){
                                              $sel = ($c['Kategori'] == $row['kategori']) ? 'selected' : '';
                                              echo "<option value='{$c['Kategori']}' $sel>{$c['Kategori']}</option>";
                                          }
                                          ?>
                                      </select>

                                    </div>
                                    <div class="modal-footer">
                                      <button class="btn btn-success" name="editbarang">Simpan</button>
                                    </div>
                                  </div>
                                </form>
                              </div>
                            </div>

                            <!-- DELETE MODAL -->
                            <div class="modal fade" id="del<?= $row['id_barang'] ?>">
                              <div class="modal-dialog">
                                <form method="POST">
                                  <input type="hidden" name="id_barang" value="<?= $row['id_barang'] ?>">
                                  <div class="modal-content">
                                    <div class="modal-body">
                                        Hapus <b><?= htmlspecialchars($row['nama_barang']) ?></b>?
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-danger" name="deletebarang">Hapus</button>
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
<div class="modal fade" id="modalAdd">
  <div class="modal-dialog">
    <form method="POST">
      <div class="modal-content">

        <div class="modal-header bg-dark text-white"><strong>Tambah Barang</strong></div>
        <div class="modal-body">

          <label>Nama Barang</label>
          <input class="form-control" name="nama_barang" required>

          <label>Stok Minimum</label>
          <input class="form-control" type="number" name="stok_minimum" required>

          <label>Rata Pakai Harian</label>
          <input class="form-control" type="number" name="rata_pakai_harian" required>

          <label>Lead Time</label>
          <input class="form-control" type="number" name="lead_time" required>

          <label>Kategori</label>
          <select class="form-control" name="kategori" required>
              <option value="">-- Pilih Kategori --</option>
              <?php
              $s = mysqli_query($conn,"SELECT Kategori FROM satuan ORDER BY Kategori");
              while($r = mysqli_fetch_assoc($s)){
                  echo "<option value='{$r['Kategori']}'>{$r['Kategori']}</option>";
              }
              ?>
          </select>

        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" name="addnewbarang">Tambah</button>
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
