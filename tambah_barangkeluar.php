<?php
require 'function.php';
require 'cek.php';

if (isset($_POST['tambah'])) {
    $id_barang = $_POST['id_barang'];
    $tanggal = $_POST['tanggal'];
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];

    // Tambah ke tabel barang_keluar
    $tambah = mysqli_query($conn, "INSERT INTO barang_keluar (id_barang, tanggal, jumlah, keterangan)
                                   VALUES ('$id_barang','$tanggal','$jumlah','$keterangan')");
    if ($tambah) {
        // Kurangi stok barang
        mysqli_query($conn, "UPDATE barang SET stok = stok - $jumlah WHERE id_barang = '$id_barang'");
        header('location:barangkeluar.php');
    } else {
        echo "<script>alert('Gagal menambahkan data');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tambah Barang Keluar</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="p-4">
    <div class="container mt-4">
        <h3>Tambah Barang Keluar</h3>
        <form method="POST">
            <div class="mb-3">
                <label>Nama Barang</label>
                <select name="id_barang" class="form-control" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php
                    $barang = mysqli_query($conn, "SELECT * FROM barang");
                    while ($b = mysqli_fetch_assoc($barang)) {
                        echo "<option value='{$b['id_barang']}'>{$b['nama_barang']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Tanggal Keluar</label>
                <input type="date" name="tanggal" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Jumlah</label>
                <input type="number" name="jumlah" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Keterangan</label>
                <textarea name="keterangan" class="form-control"></textarea>
            </div>
            <button type="submit" name="tambah" class="btn btn-success">Simpan</button>
            <a href="barangkeluar.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</body>
</html>
