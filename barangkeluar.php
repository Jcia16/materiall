<?php
require 'function.php'; // harus sesuai file yang kamu kirim sebelumnya
require 'cek.php';

// ----------------- EXPORT EXCEL -----------------
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $dari = $_GET['dari'] ?? '';
    $sampai = $_GET['sampai'] ?? '';
    $where = "";
    if ($dari !== '' && $sampai !== '') {
        $d = mysqli_real_escape_string($conn, $dari);
        $s = mysqli_real_escape_string($conn, $sampai);
        $where = "WHERE bk.tanggal BETWEEN '$d' AND '$s'";
    }

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=barang_keluar_".date('Ymd_His').".xls");
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";

    $exportQ = mysqli_query($conn, "
        SELECT bk.*, b.nama_barang, b.kategori
        FROM barang_keluar bk
        JOIN barang b ON bk.id_barang = b.id_barang
        $where
        ORDER BY bk.id_keluar DESC
    ");

    echo "<table border='1'>
            <tr>
                <th>No</th><th>Nama Barang</th><th>Kategori</th><th>Tanggal</th><th>Jumlah</th><th>Keterangan</th>
            </tr>";
    $no = 1;
    while ($r = mysqli_fetch_assoc($exportQ)) {
        echo "<tr>
                <td>".$no++."</td>
                <td>".htmlspecialchars($r['nama_barang'])."</td>
                <td>".htmlspecialchars($r['kategori'])."</td>
                <td>".$r['tanggal']."</td>
                <td>".$r['jumlah']."</td>
                <td>".htmlspecialchars($r['keterangan'] ?? '')."</td>
            </tr>";
    }
    echo "</table>";
    exit;
}

// ----------------- EXPORT PRINT / PDF VIEW -----------------
if (isset($_GET['export']) && $_GET['export'] === 'print') {
    $dari = $_GET['dari'] ?? '';
    $sampai = $_GET['sampai'] ?? '';
    $where = "";
    if ($dari !== '' && $sampai !== '') {
        $d = mysqli_real_escape_string($conn, $dari);
        $s = mysqli_real_escape_string($conn, $sampai);
        $where = "WHERE bk.tanggal BETWEEN '$d' AND '$s'";
    }

    $printQ = mysqli_query($conn, "
        SELECT bk.*, b.nama_barang, b.kategori
        FROM barang_keluar bk
        JOIN barang b ON bk.id_barang = b.id_barang
        $where
        ORDER BY bk.id_keluar DESC
    ");
    ?>
    <!doctype html>
    <html lang="id">
    <head>
      <meta charset="utf-8">
      <title>Cetak Barang Keluar</title>
      <style>
        body{font-family:Arial, sans-serif; margin:20px}
        h2{text-align:center; margin:0 0 6px}
        .meta{text-align:center; font-size:13px; margin-bottom:12px}
        table{width:100%; border-collapse:collapse; margin-top:12px}
        th,td{border:1px solid #000; padding:8px; font-size:13px}
        th{background:#eee}
        .text-center{text-align:center}
        .text-right{text-align:right}
      </style>
    </head>
    <body onload="window.print()">
      <h2>LAPORAN BARANG KELUAR</h2>
      <div class="meta">
        CV. Dwiasta Konstruksi<br>
        Dicetak: <?= date('d-m-Y H:i') ?><br>
        <?php if ($dari !== '' && $sampai !== ''): ?>
          Periode: <strong><?=htmlspecialchars($dari)?></strong> s/d <strong><?=htmlspecialchars($sampai)?></strong>
        <?php else: ?>
          Periode: Semua Data
        <?php endif; ?>
      </div>

      <table>
        <thead>
          <tr><th>No</th><th>Nama Barang</th><th>Kategori</th><th>Tanggal</th><th>Jumlah</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
          <?php $no=1; while($r=mysqli_fetch_assoc($printQ)): ?>
            <tr>
              <td class="text-center"><?=$no++?></td>
              <td><?=htmlspecialchars($r['nama_barang'])?></td>
              <td><?=htmlspecialchars($r['kategori'])?></td>
              <td class="text-center"><?=htmlspecialchars($r['tanggal'])?></td>
              <td class="text-right"><?=number_format($r['jumlah'])?></td>
              <td><?=htmlspecialchars($r['keterangan'] ?? '')?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </body>
    </html>
    <?php
    exit;
}

// ----------------- AJAX: getBarang (stok/kategori) -----------------
if (isset($_GET['getBarang']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = mysqli_query($conn, "SELECT stok, COALESCE(kategori,'') AS kategori, stok_minimum FROM barang WHERE id_barang = $id LIMIT 1");
    $row = mysqli_fetch_assoc($res) ?: [];
    header('Content-Type: application/json');
    echo json_encode($row);
    exit;
}

// ----------------- FILTER tanggal untuk tampilan -----------------
$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$where = "";
if ($dari !== '' && $sampai !== '') {
    $d = mysqli_real_escape_string($conn, $dari);
    $s = mysqli_real_escape_string($conn, $sampai);
    $where = "WHERE bk.tanggal BETWEEN '$d' AND '$s'";
}

// ----------------- MAIN QUERY (JOIN barang) -----------------
$q = mysqli_query($conn, "
    SELECT bk.*, b.nama_barang, b.kategori
    FROM barang_keluar bk
    JOIN barang b ON bk.id_barang = b.id_barang
    $where
    ORDER BY bk.id_keluar DESC
");

// ----------------- NOTIF stok <= stok_minimum -----------------
$notifCount = 0;
$notifItems = [];
$notifQ = mysqli_query($conn, "SELECT nama_barang, stok, stok_minimum FROM barang");
if ($notifQ) {
    while($n = mysqli_fetch_assoc($notifQ)) {
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
<title>Barang Keluar - Final</title>

<link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
<link href="css/styles.css" rel="stylesheet" />
<script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script>

<style>
.modal-content { background:#fff;border-radius:8px; }
.table-actions { display:flex; gap:6px; justify-content:center; }
</style>
</head>
<body class="sb-nav-fixed">

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
        <a class="nav-link active" href="barangkeluar.php">Barang Keluar</a>
        <div class="sb-sidenav-menu-heading">Laporan</div>
        <a class="nav-link" href="laporan_stok.php">Laporan Stok</a>
      </div></div>
    </nav>
  </div>

  <div id="layoutSidenav_content">
    <main class="container-fluid px-4 mt-3">
      <h2 class="mb-3">Barang Keluar</h2>

      <div class="d-flex gap-2 align-items-center mb-3">
        <form method="GET" class="d-flex gap-2 align-items-end">
          <div><label class="small mb-1">Dari</label><input type="date" name="dari" class="form-control form-control-sm" value="<?=htmlspecialchars($dari)?>"></div>
          <div><label class="small mb-1">Sampai</label><input type="date" name="sampai" class="form-control form-control-sm" value="<?=htmlspecialchars($sampai)?>"></div>
          <div class="align-self-end"><button class="btn btn-warning btn-sm">Filter</button> <a href="barangkeluar.php" class="btn btn-secondary btn-sm">Reset</a></div>
        </form>

        <div class="ms-auto d-flex gap-2">
          <a href="barangkeluar.php?export=excel<?= $dari ? '&dari='.urlencode($dari) : '' ?><?= $sampai ? '&sampai='.urlencode($sampai) : '' ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Excel</a>
          <a href="barangkeluar.php?export=print<?= $dari ? '&dari='.urlencode($dari) : '' ?><?= $sampai ? '&sampai='.urlencode($sampai) : '' ?>" target="_blank" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
          <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdd"><i class="fas fa-plus"></i> Tambah</button>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <table id="tblKeluar" class="table table-striped table-bordered">
            <thead class="table-dark text-center"><tr><th>No</th><th>Nama Barang</th><th>Kategori</th><th>Tanggal</th><th class="text-end">Jumlah</th><th>Keterangan</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php $no=1; mysqli_data_seek($q,0); while($row = mysqli_fetch_assoc($q)): ?>
                <tr>
                  <td class="text-center"><?=$no++?></td>
                  <td><?=htmlspecialchars($row['nama_barang'])?></td>
                  <td><?=htmlspecialchars($row['kategori'])?></td>
                  <td class="text-center"><?=htmlspecialchars($row['tanggal'])?></td>
                  <td class="text-end"><?=number_format($row['jumlah'])?></td>
                  <td><?=htmlspecialchars($row['keterangan'] ?? '')?></td>
                  <td class="table-actions">
                    <!-- edit button: pass necessary data attributes -->
                    <button type="button" class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $row['id_keluar'] ?>"
                        data-id_barang="<?= $row['id_barang'] ?>"
                        data-tanggal="<?= htmlspecialchars($row['tanggal']) ?>"
                        data-jumlah="<?= $row['jumlah'] ?>"
                        data-keterangan="<?= htmlspecialchars($row['keterangan'] ?? '') ?>"
                        data-namabarang="<?= htmlspecialchars($row['nama_barang']) ?>"
                        data-kategori="<?= htmlspecialchars($row['kategori']) ?>">
                      <i class="fas fa-edit"></i>
                    </button>

                    <!-- delete button -->
                    <button type="button" class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $row['id_keluar'] ?>"
                        data-namabarang="<?= htmlspecialchars($row['nama_barang']) ?>">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- SINGLE EDIT MODAL -->
<div class="modal fade" id="singleEditModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content" id="formEditKeluar">
      <input type="hidden" name="id_keluar" id="edit_id_keluar" value="">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Edit Barang Keluar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <div class="col-md-6">
          <label class="form-label">Pilih Barang</label>
          <select name="id_barang" id="edit_id_barang" class="form-select" required>
            <option value="">-- Pilih Barang --</option>
            <?php
            $bb = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang ASC");
            while($b = mysqli_fetch_assoc($bb)):
                $kat = htmlspecialchars($b['kategori'] ?? '');
            ?>
            <option value="<?=$b['id_barang']?>" data-kategori="<?=$kat?>" data-stok="<?= (int)$b['stok'] ?>">
                <?=htmlspecialchars($b['nama_barang'])?> (<?=$kat?>)
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Kategori</label>
          <input type="text" id="edit_kategori" class="form-control" readonly>
        </div>

        <div class="col-md-4">
          <label class="form-label">Stok Sekarang</label>
          <input type="text" id="edit_stokawal" class="form-control" readonly>
        </div>

        <div class="col-md-4">
          <label class="form-label">Tanggal</label>
          <input type="date" name="tanggal" id="edit_tanggal" class="form-control" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Jumlah Keluar</label>
          <input type="number" name="jumlah" id="edit_jumlah" class="form-control" required>
        </div>

        <div class="col-md-12">
          <label class="form-label">Keterangan</label>
          <input type="text" name="keterangan" id="edit_keterangan" class="form-control">
          <div class="form-text">Periksa stok sebelum menyimpan. Sistem akan melakukan rollback stok lama lalu kurangi stok baru.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="editbarangkeluar" class="btn btn-success">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<!-- SINGLE DELETE MODAL -->
<div class="modal fade" id="singleDeleteModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="id_keluar" id="del_id_keluar" value="">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Hapus Data</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">Hapus data barang keluar <b id="del_nama_barang"></b> ?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
        <button type="submit" name="deletebarangkeluar" class="btn btn-danger">Hapus</button>
      </div>
    </form>
  </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="modalAdd" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content" id="formAddKeluar">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Tambah Barang Keluar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label small">Pilih Barang</label>
        <select id="add_barang" name="id_barang" class="form-select form-select-sm" required>
          <option value="">-- Pilih Barang --</option>
          <?php
          $bq = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang ASC");
          while($b = mysqli_fetch_assoc($bq)):
            $kat = htmlspecialchars($b['kategori'] ?? '');
          ?>
            <option value="<?=$b['id_barang']?>" data-kategori="<?=$kat?>" data-stok="<?= (int)$b['stok'] ?>">
                <?=htmlspecialchars($b['nama_barang'])?> (<?=$kat?>)
            </option>
          <?php endwhile; ?>
        </select>

        <label class="form-label small mt-2">Stok Sekarang</label>
        <input id="add_stok_awal" type="text" class="form-control form-control-sm" readonly>

        <label class="form-label small mt-2">Kategori</label>
        <input id="add_kategori" type="text" class="form-control form-control-sm" readonly>

        <label class="form-label small mt-2">Tanggal</label>
        <input type="date" name="tanggal" class="form-control form-control-sm" required>

        <label class="form-label small mt-2">Jumlah Keluar</label>
        <input type="number" name="jumlah" class="form-control form-control-sm" required>

        <label class="form-label small mt-2">Keterangan</label>
        <textarea name="keterangan" class="form-control form-control-sm"></textarea>
        <div class="form-text">Sistem akan menolak jika jumlah lebih besar dari stok.</div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="addbarangkeluar" class="btn btn-primary btn-sm">Tambah</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"></script>

<script>
// init datatable
const dt = new simpleDatatables.DataTable("#tblKeluar", {
    perPage: 10,
    perPageSelect: [5,10,15,25,50],
    searchable: true,
    fixedHeight: false,
    labels: { placeholder: "Cari..." }
});

// EDIT / DELETE single-modal handlers
document.addEventListener('click', function(e){
    // edit
    const editBtn = e.target.closest && e.target.closest('.btn-edit');
    if(editBtn){
        const id = editBtn.dataset.id;
        const id_barang = editBtn.dataset.id_barang;
        const tanggal = editBtn.dataset.tanggal || '';
        const jumlah = editBtn.dataset.jumlah || '';
        const keterangan = editBtn.dataset.keterangan || '';
        const kategori = editBtn.dataset.kategori || '';

        document.getElementById('edit_id_keluar').value = id;
        document.getElementById('edit_id_barang').value = id_barang;
        document.getElementById('edit_tanggal').value = tanggal;
        document.getElementById('edit_jumlah').value = jumlah;
        document.getElementById('edit_keterangan').value = keterangan;
        document.getElementById('edit_kategori').value = kategori;

        // set stok from option if present
        const sel = document.getElementById('edit_id_barang');
        const opt = sel.querySelector('option[value="'+id_barang+'"]');
        if(opt){
            document.getElementById('edit_stokawal').value = opt.dataset.stok || '';
        } else {
            fetch('barangkeluar.php?getBarang=1&id='+id_barang).then(r=>r.json()).then(data=>{
                document.getElementById('edit_stokawal').value = data.stok ?? '';
                document.getElementById('edit_kategori').value = data.kategori ?? document.getElementById('edit_kategori').value;
            }).catch(()=>{});
        }

        new bootstrap.Modal(document.getElementById('singleEditModal')).show();
    }

    // delete
    const delBtn = e.target.closest && e.target.closest('.btn-delete');
    if(delBtn){
        const id = delBtn.dataset.id;
        const nama = delBtn.dataset.namabarang || '';
        document.getElementById('del_id_keluar').value = id;
        document.getElementById('del_nama_barang').textContent = nama;
        new bootstrap.Modal(document.getElementById('singleDeleteModal')).show();
    }
});

// when edit modal's select changes, update kategori/stok
document.getElementById('edit_id_barang').addEventListener('change', function(){
    const opt = this.options[this.selectedIndex];
    document.getElementById('edit_kategori').value = opt.dataset.kategori || '';
    document.getElementById('edit_stokawal').value = opt.dataset.stok || '';
});

// add modal: when select changes
const addSel = document.getElementById('add_barang');
if(addSel){
    addSel.addEventListener('change', function(){
        const opt = this.options[this.selectedIndex];
        document.getElementById('add_stok_awal').value = opt.dataset.stok || '';
        document.getElementById('add_kategori').value = opt.dataset.kategori || '';

        // fetch fresh server data optionally
        const id = this.value;
        if(id){
            fetch('barangkeluar.php?getBarang=1&id=' + id)
            .then(r=>r.json()).then(data=>{
                document.getElementById('add_stok_awal').value = data.stok ?? document.getElementById('add_stok_awal').value;
                document.getElementById('add_kategori').value = data.kategori ?? document.getElementById('add_kategori').value;
            }).catch(()=>{});
        }
    });
}

// Optional: client-side pre-check before submitting "add" to avoid immediate server error (helpful UX)
document.getElementById('formAddKeluar').addEventListener('submit', function(e){
    const stok = parseInt(document.getElementById('add_stok_awal').value || '0',10);
    const jumlah = parseInt(this.querySelector('input[name="jumlah"]').value || '0',10);
    if(jumlah <= 0){ e.preventDefault(); alert('Jumlah harus > 0'); return; }
    if(jumlah > stok){ e.preventDefault(); alert('Jumlah melebihi stok! Cek stok terlebih dahulu.'); return; }
});

// Optional: client-side pre-check before submitting "edit"
document.getElementById('formEditKeluar').addEventListener('submit', function(e){
    // Note: server will perform authoritative checks; this is UX guard only
    const stok = parseInt(document.getElementById('edit_stokawal').value || '0',10);
    const jumlah = parseInt(this.querySelector('input[name="jumlah"]').value || '0',10);
    if(jumlah <= 0){ e.preventDefault(); alert('Jumlah harus > 0'); return; }
    // For edit, remember server will rollback old qty then apply new qty.
    // We can't easily predict final stok client-side if item changed; still warn if new jumlah > current stok + old_jumlah
});

</script>
</body>
</html>
