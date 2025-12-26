<?php
require 'function.php';
require 'cek.php';

$q = isset($_GET['q']) ? mysqli_real_escape_string($conn,$_GET['q']) : '';

function cari($conn,$table,$fields){
    global $q;
    $where = [];
    foreach ($fields as $f){
        $where[] = "$f LIKE '%$q%'";
    }
    $where = implode(" OR ",$where);
    return mysqli_query($conn,"SELECT * FROM $table WHERE $where");
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Global Search</title>
<link href="css/styles.css" rel="stylesheet">
</head>

<body class="sb-nav-fixed">



<div id="layoutSidenav_content">
<main class="container-fluid px-4 mt-4">

<h2>Hasil Pencarian: <b><?= $q ?></b></h2>
<hr>

<!-- ================= BARANG ================= -->
<h4>📦 Data Barang</h4>
<table class="table table-bordered">
<tr class="table-dark">
    <th>Nama</th><th>Stok</th><th>Kategori</th>
</tr>
<?php
$res = cari($conn,"barang",["nama_barang","stok","kategori"]);
if(mysqli_num_rows($res)==0) echo "<tr><td colspan=3>Tidak ditemukan</td></tr>";
while($b=mysqli_fetch_assoc($res)):
?>
<tr>
    <td><?= $b['nama_barang'] ?></td>
    <td><?= $b['stok'] ?></td>
    <td><?= $b['kategori'] ?></td>
</tr>
<?php endwhile; ?>
</table>
<hr>

<!-- ================= KATEGORI ================= -->
<h4>📏 Kategori Barang</h4>
<table class="table table-bordered">
<tr class="table-dark">
    <th>Nama Kategori</th>
</tr>
<?php
$res = cari($conn,"satuan",["kategori"]);
if(mysqli_num_rows($res)==0) echo "<tr><td>Tidak ditemukan</td></tr>";
while($b=mysqli_fetch_assoc($res)):
?>
<tr><td><?= $b['Kategori'] ?></td></tr>
<?php endwhile; ?>
</table>
<hr>

<!-- ================= BARANG MASUK ================= -->
<h4>⬇ Barang Masuk</h4>
<table class="table table-bordered">
<tr class="table-dark">
    <th>Nama</th><th>Jumlah</th><th>Tanggal</th>
</tr>
<?php
$res = mysqli_query($conn,"SELECT barang_masuk.*,barang.nama_barang FROM barang_masuk
      LEFT JOIN barang ON barang.id_barang=barang_masuk.id_barang
      WHERE barang.nama_barang LIKE '%$q%' OR jumlah LIKE '%$q%' OR tanggal LIKE '%$q%'");

if(mysqli_num_rows($res)==0) echo "<tr><td colspan=3>Tidak ditemukan</td></tr>";
while($b=mysqli_fetch_assoc($res)):
?>
<tr>
    <td><?= $b['nama_barang'] ?></td>
    <td><?= $b['jumlah'] ?></td>
    <td><?= $b['tanggal'] ?></td>
</tr>
<?php endwhile; ?>
</table>
<hr>

<!-- ================= BARANG KELUAR ================= -->
<h4>⬆ Barang Keluar</h4>
<table class="table table-bordered">
<tr class="table-dark">
    <th>Nama</th><th>Jumlah</th><th>Tanggal</th>
</tr>
<?php
$res = mysqli_query($conn,"SELECT barang_keluar.*,barang.nama_barang FROM barang_keluar
      LEFT JOIN barang ON barang.id_barang=barang_keluar.id_barang
      WHERE barang.nama_barang LIKE '%$q%' OR jumlah LIKE '%$q%' OR tanggal LIKE '%$q%'");

if(mysqli_num_rows($res)==0) echo "<tr><td colspan=3>Tidak ditemukan</td></tr>";
while($b=mysqli_fetch_assoc($res)):
?>
<tr>
    <td><?= $b['nama_barang'] ?></td>
    <td><?= $b['jumlah'] ?></td>
    <td><?= $b['tanggal'] ?></td>
</tr>
<?php endwhile; ?>
</table>

</main>
</div>

</body>
</html>
