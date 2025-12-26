<?php
// profil_toko.php - Halaman Profil Toko (FINAL)
// REQUIRE: function.php (berisi $conn) dan cek.php (session/auth)
// Save this file next to function.php & cek.php

require 'function.php';
require 'cek.php';

// --- Ensure uploads folder exists
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// --- Create table profil_toko jika belum ada (satu row hanya)
$createSql = "
CREATE TABLE IF NOT EXISTS profil_toko (
    id INT PRIMARY KEY DEFAULT 1,
    nama_toko VARCHAR(255) DEFAULT '',
    alamat TEXT,
    telepon VARCHAR(100) DEFAULT '',
    email VARCHAR(150) DEFAULT '',
    jam_operasional VARCHAR(100) DEFAULT '',
    owner_nama VARCHAR(150) DEFAULT '',
    owner_telepon VARCHAR(100) DEFAULT '',
    admin_nama VARCHAR(150) DEFAULT '',
    admin_telepon VARCHAR(100) DEFAULT '',
    logo_filename VARCHAR(255) DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
// run create (silent if exists)
mysqli_query($conn, $createSql);

// ensure there is at least one row (id=1)
$exists = mysqli_query($conn, "SELECT COUNT(*) AS c FROM profil_toko WHERE id=1");
if ($exists) {
    $r = mysqli_fetch_assoc($exists);
    if ((int)$r['c'] === 0) {
        mysqli_query($conn, "INSERT INTO profil_toko (id, nama_toko) VALUES (1, 'Nama Toko Anda')");
    }
}

// --- Handle POST: update info or upload logo
$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update info
    if (isset($_POST['save_info'])) {
        $nama_toko = mysqli_real_escape_string($conn, trim($_POST['nama_toko'] ?? ''));
        $alamat = mysqli_real_escape_string($conn, trim($_POST['alamat'] ?? ''));
        $telepon = mysqli_real_escape_string($conn, trim($_POST['telepon'] ?? ''));
        $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
        $jam = mysqli_real_escape_string($conn, trim($_POST['jam_operasional'] ?? ''));
        $owner_nama = mysqli_real_escape_string($conn, trim($_POST['owner_nama'] ?? ''));
        $owner_telepon = mysqli_real_escape_string($conn, trim($_POST['owner_telepon'] ?? ''));
        $admin_nama = mysqli_real_escape_string($conn, trim($_POST['admin_nama'] ?? ''));
        $admin_telepon = mysqli_real_escape_string($conn, trim($_POST['admin_telepon'] ?? ''));

        $upd = mysqli_query($conn, "UPDATE profil_toko SET
            nama_toko='$nama_toko',
            alamat='$alamat',
            telepon='$telepon',
            email='$email',
            jam_operasional='$jam',
            owner_nama='$owner_nama',
            owner_telepon='$owner_telepon',
            admin_nama='$admin_nama',
            admin_telepon='$admin_telepon'
            WHERE id=1
        ");
        if ($upd) {
            $messages[] = "Informasi toko berhasil diperbarui.";
        } else {
            $errors[] = "Gagal memperbarui informasi: ".mysqli_error($conn);
        }
    }

    // Upload logo
    if (isset($_POST['upload_logo'])) {
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = "Silakan pilih file logo.";
        } else {
            $f = $_FILES['logo'];
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Upload gagal (code {$f['error']}).";
            } else {
                // validate type and size
                $allowed = ['image/jpeg','image/png','image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $f['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime, $allowed)) {
                    $errors[] = "Format file tidak didukung. Gunakan JPG / PNG / WEBP.";
                } elseif ($f['size'] > 2 * 1024 * 1024) {
                    $errors[] = "Ukuran file terlalu besar. Maksimum 2MB.";
                } else {
                    // generate safe filename
                    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                    $safe = 'logo_'.time().'_'.bin2hex(random_bytes(6)).'.'.$ext;
                    $dest = $uploadDir . '/' . $safe;
                    if (move_uploaded_file($f['tmp_name'], $dest)) {
                        // optionally remove old file
                        $oldQ = mysqli_query($conn, "SELECT logo_filename FROM profil_toko WHERE id=1");
                        $old = mysqli_fetch_assoc($oldQ);
                        if (!empty($old['logo_filename'])) {
                            $oldpath = $uploadDir . '/' . $old['logo_filename'];
                            if (is_file($oldpath)) @unlink($oldpath);
                        }
                        // store new filename
                        $ins = mysqli_query($conn, "UPDATE profil_toko SET logo_filename='".mysqli_real_escape_string($conn,$safe)."' WHERE id=1");
                        if ($ins) {
                            $messages[] = "Logo berhasil diunggah.";
                        } else {
                            $errors[] = "Gagal menyimpan data logo: ".mysqli_error($conn);
                            // cleanup file
                            @unlink($dest);
                        }
                    } else {
                        $errors[] = "Gagal memindahkan file upload.";
                    }
                }
            }
        }
    }
}

// fetch data
$r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profil_toko WHERE id=1 LIMIT 1"));
if (!$r) {
    // fallback
    $r = [
        'nama_toko'=>'Nama Toko Anda','alamat'=>'','telepon'=>'','email'=>'','jam_operasional'=>'',
        'owner_nama'=>'','owner_telepon'=>'','admin_nama'=>'','admin_telepon'=>'','logo_filename'=>null
    ];
}

// helper for logo url
$logoUrl = $r['logo_filename'] ? 'uploads/'.rawurlencode($r['logo_filename']) : 'https://via.placeholder.com/160x120?text=Logo';

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profil Toko - <?= htmlspecialchars($r['nama_toko'] ?? 'Toko') ?></title>

<link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
<link href="css/styles.css" rel="stylesheet" />
<script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script>

<style>
.card-profile { display:flex; gap:20px; align-items:center; }
.card-profile .logo { width:160px; height:120px; background:#f4f4f4; display:flex; align-items:center; justify-content:center; border-radius:6px; overflow:hidden; border:1px solid #e6e6e6; }
.info-line { font-size:14px; color:#333; }
.small-muted { font-size:13px; color:#666; }
.table-actions { display:flex; gap:8px; }
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
    <main class="container-fluid px-4 mt-3">
      <h2 class="mb-3">Profil Toko</h2>

      <?php if (!empty($messages)): foreach($messages as $m): ?>
        <div class="alert alert-success"><?= htmlspecialchars($m) ?></div>
      <?php endforeach; endif; ?>

      <?php if (!empty($errors)): foreach($errors as $e): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; endif; ?>

      <!-- CARD: PROFILE HEADER -->
      <div class="card mb-4">
        <div class="card-body card-profile">
          <div class="logo">
            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" style="max-width:100%; max-height:100%; object-fit:contain;">
          </div>
          <div style="flex:1">
            <h4 style="margin:0"><?= htmlspecialchars($r['nama_toko'] ?: 'Nama Toko Anda') ?></h4>
            <div class="small-muted mb-2"><?= htmlspecialchars($r['alamat'] ?: 'Alamat belum diatur') ?></div>
            <div class="d-flex gap-3">
              <div class="info-line"><strong>Telepon:</strong> <?= htmlspecialchars($r['telepon'] ?: '-') ?></div>
              <div class="info-line"><strong>Email:</strong> <?= htmlspecialchars($r['email'] ?: '-') ?></div>
              <div class="info-line"><strong>Jam:</strong> <?= htmlspecialchars($r['jam_operasional'] ?: '-') ?></div>
            </div>

            <div class="mt-3 table-actions">
              <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditInfo"><i class="fas fa-edit"></i> Edit Informasi</button>

              <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUploadLogo"><i class="fas fa-image"></i> Ganti Logo</button>

              <a href="change_password.php" class="btn btn-warning btn-sm"><i class="fas fa-key"></i> Ubah Password</a>
            </div>
          </div>
        </div>
      </div>

      <!-- CARD: CONTACT / PENANGGUNG JAWAB -->
      <div class="row">
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header bg-light"><strong>Penanggung Jawab</strong></div>
            <div class="card-body">
              <table class="table table-borderless mb-0">
                <tbody>
                  <tr>
                    <th style="width:160px">Owner</th>
                    <td>
                      <div><strong><?= htmlspecialchars($r['owner_nama'] ?: '-') ?></strong></div>
                      <div class="small-muted"><?= htmlspecialchars($r['owner_telepon'] ?: '-') ?></div>
                    </td>
                  </tr>
                  <tr>
                    <th>Admin</th>
                    <td>
                      <div><strong><?= htmlspecialchars($r['admin_nama'] ?: '-') ?></strong></div>
                      <div class="small-muted"><?= htmlspecialchars($r['admin_telepon'] ?: '-') ?></div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

  
      </div>

    </main>
  </div>
</div>

<!-- MODAL: EDIT INFO -->
<div class="modal fade" id="modalEditInfo" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-dark text-white"><h5 class="modal-title">Edit Informasi Toko</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nama Toko</label><input type="text" name="nama_toko" class="form-control" value="<?= htmlspecialchars($r['nama_toko']) ?>" required></div>
          <div class="col-md-6"><label class="form-label">Telepon</label><input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($r['telepon']) ?>"></div>
          <div class="col-12"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($r['alamat']) ?></textarea></div>
          <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($r['email']) ?>"></div>
          <div class="col-md-6"><label class="form-label">Jam Operasional</label><input type="text" name="jam_operasional" class="form-control" value="<?= htmlspecialchars($r['jam_operasional']) ?>"></div>

          <hr style="width:100%">

          <div class="col-md-6"><label class="form-label">Nama Owner</label><input type="text" name="owner_nama" class="form-control" value="<?= htmlspecialchars($r['owner_nama']) ?>"></div>
          <div class="col-md-6"><label class="form-label">Telepon Owner</label><input type="text" name="owner_telepon" class="form-control" value="<?= htmlspecialchars($r['owner_telepon']) ?>"></div>
          <div class="col-md-6"><label class="form-label">Nama Admin</label><input type="text" name="admin_nama" class="form-control" value="<?= htmlspecialchars($r['admin_nama']) ?>"></div>
          <div class="col-md-6"><label class="form-label">Telepon Admin</label><input type="text" name="admin_telepon" class="form-control" value="<?= htmlspecialchars($r['admin_telepon']) ?>"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="save_info" class="btn btn-success">Simpan</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: UPLOAD LOGO -->
<div class="modal fade" id="modalUploadLogo" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header bg-dark text-white"><h5 class="modal-title">Ganti Logo Toko</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="small-muted">Pilih file JPG/PNG/WEBP (maks 2MB).</p>
        <input type="file" accept=".jpg,.jpeg,.png,.webp" name="logo" class="form-control">
      </div>
      <div class="modal-footer">
        <button type="submit" name="upload_logo" class="btn btn-primary">Unggah</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
