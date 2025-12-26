<?php
// ==========================================
//  Database Connection
// ==========================================
$conn = mysqli_connect("localhost", "root", "", "db_material");
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// ==========================================
//   FUNCTION: Hitung ROP
// ==========================================
function hitungROP($rata, $lead) {
    return (int)$rata * (int)$lead;
}

// ==========================================
//               CRUD BARANG
// ==========================================

// --- ADD BARANG ---
if (isset($_POST['addnewbarang'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $stok = (int)$_POST['stok'];
    $min  = (int)$_POST['stok_minimum'];
    $rata = (int)$_POST['rata_pakai_harian'];
    $lead = (int)$_POST['lead_time'];
    $kat  = mysqli_real_escape_string($conn, $_POST['kategori']);

    mysqli_query($conn, "INSERT INTO barang (nama_barang, stok, stok_minimum, rata_pakai_harian, lead_time, kategori)
                         VALUES ('$nama', $stok, $min, $rata, $lead, '$kat')");
    header("Location: barang.php");
    exit;
}

// --- EDIT BARANG ---
if (isset($_POST['editbarang'])) {
    $id   = (int)$_POST['id_barang'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $min  = (int)$_POST['stok_minimum'];
    $rata = (int)$_POST['rata_pakai_harian'];
    $lead = (int)$_POST['lead_time'];
    $kat  = mysqli_real_escape_string($conn, $_POST['kategori']);

    mysqli_query($conn, "UPDATE barang SET 
        nama_barang='$nama',
        stok_minimum=$min,
        rata_pakai_harian=$rata,
        lead_time=$lead,
        kategori='$kat'
        WHERE id_barang=$id");

    header("Location: barang.php");
    exit;
}

// --- DELETE BARANG ---
if (isset($_POST['deletebarang'])) {
    $id = (int)$_POST['id_barang'];
    mysqli_query($conn, "DELETE FROM barang WHERE id_barang=$id");
    header("Location: barang.php");
    exit;
}



// ==========================================
//            CRUD BARANG MASUK
// ==========================================

// --- ADD BARANG MASUK ---
if (isset($_POST['addbarangmasuk'])) {
    $id_barang = (int)$_POST['id_barang'];
    $tanggal   = $_POST['tanggal'];
    $jumlah    = (int)$_POST['jumlah'];
    $ket       = mysqli_real_escape_string($conn, $_POST['keterangan']);

    mysqli_begin_transaction($conn);

    try {
        mysqli_query($conn, "INSERT INTO barang_masuk (id_barang, tanggal, jumlah, keterangan)
                             VALUES ($id_barang, '$tanggal', $jumlah, '$ket')");

        mysqli_query($conn, "UPDATE barang SET stok = stok + $jumlah WHERE id_barang=$id_barang");

        mysqli_commit($conn);
        header("Location: barangmasuk.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "ERROR: ".$e->getMessage();
    }
}

// --- EDIT BARANG MASUK ---
if (isset($_POST['editbarangmasuk'])) {
    $id_masuk  = (int)$_POST['id_masuk'];
    $id_barang = (int)$_POST['id_barang'];
    $tanggal   = $_POST['tanggal'];
    $jumlah    = (int)$_POST['jumlah'];
    $ket       = mysqli_real_escape_string($conn, $_POST['keterangan']);

    mysqli_begin_transaction($conn);

    try {
        $old = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM barang_masuk WHERE id_masuk=$id_masuk"));
        $old_id  = $old['id_barang'];
        $old_jml = $old['jumlah'];

        mysqli_query($conn,"UPDATE barang SET stok = stok - $old_jml WHERE id_barang=$old_id");

        mysqli_query($conn,"UPDATE barang_masuk SET
            id_barang=$id_barang,
            tanggal='$tanggal',
            jumlah=$jumlah,
            keterangan='$ket'
            WHERE id_masuk=$id_masuk");

        mysqli_query($conn,"UPDATE barang SET stok = stok + $jumlah WHERE id_barang=$id_barang");

        mysqli_commit($conn);
        header("Location: barangmasuk.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "ERROR: ".$e->getMessage();
    }
}

// --- DELETE BARANG MASUK ---
if (isset($_POST['deletebarangmasuk'])) {
    $id_masuk = (int)$_POST['id_masuk'];

    mysqli_begin_transaction($conn);

    try {
        $old = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM barang_masuk WHERE id_masuk=$id_masuk"));
        $id_barang = $old['id_barang'];
        $jumlah    = $old['jumlah'];

        mysqli_query($conn,"UPDATE barang SET stok = stok - $jumlah WHERE id_barang=$id_barang");
        mysqli_query($conn,"DELETE FROM barang_masuk WHERE id_masuk=$id_masuk");

        mysqli_commit($conn);
        header("Location: barangmasuk.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "ERROR: ".$e->getMessage();
    }
}



// ==========================================
//            CRUD BARANG KELUAR
// ==========================================

// --- ADD ---
if (isset($_POST['addbarangkeluar'])) {
    $id_barang = (int)$_POST['id_barang'];
    $tanggal   = $_POST['tanggal'];
    $jumlah    = (int)$_POST['jumlah'];

    $cek = mysqli_fetch_assoc(mysqli_query($conn,"SELECT stok FROM barang WHERE id_barang=$id_barang"));

    if ($cek['stok'] < $jumlah) {
        echo "Stok tidak mencukupi!";
        exit;
    }

    mysqli_begin_transaction($conn);

    try {
        mysqli_query($conn, "INSERT INTO barang_keluar (id_barang, tanggal, jumlah)
                             VALUES ($id_barang, '$tanggal', $jumlah)");

        mysqli_query($conn, "UPDATE barang SET stok = stok - $jumlah WHERE id_barang=$id_barang");

        mysqli_commit($conn);
        header("Location: barangkeluar.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "ERROR: ".$e->getMessage();
    }
}

// --- EDIT ---
if (isset($_POST['editbarangkeluar'])) {
    $id_keluar = (int)$_POST['id_keluar'];
    $id_barang = (int)$_POST['id_barang'];
    $tanggal   = $_POST['tanggal'];
    $jumlah    = (int)$_POST['jumlah'];

    mysqli_begin_transaction($conn);

    try {
        $old = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM barang_keluar WHERE id_keluar=$id_keluar"));
        $old_id  = $old['id_barang'];
        $old_jml = $old['jumlah'];

        mysqli_query($conn,"UPDATE barang SET stok = stok + $old_jml WHERE id_barang=$old_id");

        $cek = mysqli_fetch_assoc(mysqli_query($conn,"SELECT stok FROM barang WHERE id_barang=$id_barang"));
        if ($cek['stok'] < $jumlah) {
            throw new Exception("Stok tidak mencukupi!");
        }

        mysqli_query($conn,"UPDATE barang_keluar SET
            id_barang=$id_barang,
            tanggal='$tanggal',
            jumlah=$jumlah
            WHERE id_keluar=$id_keluar");

        mysqli_query($conn,"UPDATE barang SET stok = stok - $jumlah WHERE id_barang=$id_barang");

        mysqli_commit($conn);
        header("Location: barangkeluar.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "ERROR: ".$e->getMessage();
    }
}

// --- DELETE ---
if (isset($_POST['deletebarangkeluar'])) {
    $id_keluar = (int)$_POST['id_keluar'];

    mysqli_begin_transaction($conn);

    try {
        $old = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM barang_keluar WHERE id_keluar=$id_keluar"));
        $id_barang = $old['id_barang'];
        $jumlah    = $old['jumlah'];

        mysqli_query($conn,"UPDATE barang SET stok = stok + $jumlah WHERE id_barang=$id_barang");
        mysqli_query($conn,"DELETE FROM barang_keluar WHERE id_keluar=$id_keluar");

        mysqli_commit($conn);
        header("Location: barangkeluar.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "ERROR: ".$e->getMessage();
    }
}



// ==========================================
//            CRUD KATEGORI (SATUAN)
// ==========================================

if (isset($_POST['addnewsatuan'])) {
    $kat = mysqli_real_escape_string($conn, $_POST['nama_satuan']);
    mysqli_query($conn, "INSERT INTO satuan (Kategori) VALUES ('$kat')");
    header("Location: satuan.php");
    exit;
}

if (isset($_POST['editSatuan'])) {
    $id  = (int)$_POST['id_satuan'];
    $kat = mysqli_real_escape_string($conn, $_POST['nama_satuan']);

    mysqli_query($conn, "UPDATE satuan SET Kategori='$kat' WHERE id_kategori=$id");
    header("Location: satuan.php");
    exit;
}

if (isset($_POST['deleteSatuan'])) {
    $id = (int)$_POST['id_satuan'];
    mysqli_query($conn, "DELETE FROM satuan WHERE id_kategori=$id");
    header("Location: satuan.php");
    exit;
}
function logAktivitas($conn, $aktivitas, $modul){
    if(!isset($_SESSION['username'])) return;

    $user = $_SESSION['username'];
    mysqli_query($conn,"INSERT INTO log_aktivitas (username, aktivitas, modul)
        VALUES ('$user','$aktivitas','$modul')");
}


?>
