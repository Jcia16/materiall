<?php
require 'function.php';
require 'cek.php';
?>
<!doctype html><html><head><meta charset="utf-8"><title>Data Barang</title>
<link href="css/styles.css" rel="stylesheet">
<style>
  table{width:100%;border-collapse:collapse}
  th,td{border:1px solid #ccc;padding:6px;font-size:13px}
  th{background:#f2f2f2}
  @media print{ .no-print{display:none} }
</style>
</head><body>
  <h3>Data Barang CV.DWIASTA KONSTRUKSI</h3>
  <p> Jl. Kihajar Dewantoro Jalan Gondrong No.78 Blok A, RT.003/RW.001, Gondrong, 
    Kec. Cipondoh, Kota Tangerang.</p>
  <p>Dicetak: <?= date('Y-m-d H:i:s') ?></p>
  <button class="no-print" onclick="window.print()">Print / Save as PDF</button>
  <table><thead><tr><th>No</th><th>Nama</th><th>Stok</th><th>Min</th><th>ROP</th><th>Kategori</th></tr></thead><tbody>
  <?php
  $no=1;
  $q = mysqli_query($conn,"SELECT * FROM barang ORDER BY nama_barang ASC");
  while($r=mysqli_fetch_assoc($q)){
    $rop = hitungROP($r['rata_pakai_harian'] ?? 0, $r['lead_time'] ?? 0);
    echo "<tr>";
    echo "<td>{$no}</td>";
    echo "<td>".htmlspecialchars($r['nama_barang'] ?? '')."</td>";
    echo "<td>".($r['stok'] ?? '')."</td>";
    echo "<td>".($r['stok_minimum'] ?? '')."</td>";
    echo "<td>{$rop}</td>";
    echo "<td>".htmlspecialchars($r['kategori'] ?? '')."</td>";
    echo "</tr>";
    $no++;
  }
  ?>
  </tbody></table>
</body></html>
