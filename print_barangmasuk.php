<?php
require 'function.php';
require 'cek.php';

// Ambil filter tanggal (jika ada)
$dari = isset($_GET['dari']) ? $_GET['dari'] : '';
$sampai = isset($_GET['sampai']) ? $_GET['sampai'] : '';

$filter = "";
if($dari && $sampai){
    $filter = "WHERE tanggal BETWEEN '$dari' AND '$sampai'";
}

$q = mysqli_query($conn, "
    SELECT bm.*, b.nama_barang, b.kategori
    FROM barang_masuk bm
    JOIN barang b ON bm.id_barang = b.id_barang
    $filter
    ORDER BY bm.id_masuk DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Barang Masuk</title>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 30px;
}

h2, h4 {
    text-align: center;
    margin: 0;
    padding: 0;
}

.header-info {
    text-align: center;
    margin-bottom: 20px;
    font-size: 14px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
}

table, th, td {
    border: 1px solid #000;
}

th {
    background: #e0e0e0;
    padding: 8px;
    font-size: 14px;
}

td {
    padding: 6px;
    font-size: 13px;
}

.text-center { text-align: center; }
.text-end { text-align: right; }

@media print {
    body { margin: 0; }
}
</style>
</head>
<body>

<h2>LAPORAN BARANG MASUK</h2>
<h4>CV. Dwiasta Konstruksi</h4>
<br>

<div class="header-info">
    <?php if($dari && $sampai): ?>
        Periode: <b><?= htmlspecialchars($dari) ?></b> s/d 
        <b><?= htmlspecialchars($sampai) ?></b>
    <?php else: ?>
        Periode: <b>Semua Data</b>
    <?php endif; ?>
    <br>
    Dicetak pada: <b><?= date("d-m-Y H:i") ?></b>
</div>

<table>
<thead>
<tr>
    <th>No</th>
    <th>Nama Barang</th>
    <th>Kategori</th>
    <th>Tanggal</th>
    <th>Jumlah</th>
    <th>Keterangan</th>
</tr>
</thead>

<tbody>
<?php
$no = 1;
while($row = mysqli_fetch_assoc($q)):
?>
<tr>
    <td class="text-center"><?= $no++ ?></td>
    <td><?= htmlspecialchars($row['nama_barang']) ?></td>
    <td><?= htmlspecialchars($row['kategori']) ?></td>
    <td class="text-center"><?= htmlspecialchars($row['tanggal']) ?></td>
    <td class="text-end"><?= number_format($row['jumlah']) ?></td>
    <td><?= htmlspecialchars($row['keterangan'] ?? '') ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<script>
// Auto print saat halaman dibuka
window.onload = function() {
    window.print();
}
</script>

</body>
</html>
