<?php
include '../../koneksi.php';

// Ambil ID penyakit dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validasi ID
if ($id <= 0) {
    echo "<script>
        alert('ID penyakit tidak valid!');
        window.location='penyakit.php';
    </script>";
    exit;
}

// Pastikan data penyakit ada
$cek = mysqli_query($conn, "SELECT * FROM penyakit WHERE id_penyakit = $id");
if (mysqli_num_rows($cek) == 0) {
    echo "<script>
        alert('Data penyakit tidak ditemukan!');
        window.location='penyakit.php';
    </script>";
    exit;
}

// Hapus relasi penyakit dengan obat dulu
mysqli_query($conn, "DELETE FROM penyakit_obat WHERE id_penyakit = $id");

// Hapus data penyakit
if (mysqli_query($conn, "DELETE FROM penyakit WHERE id_penyakit = $id")) {
    // Redirect ke penyakit.php dengan parameter sukses
    header("Location: penyakit.php?hapus=success");
    exit;
} else {
    $err = mysqli_error($conn);
    echo "<script>
        alert('Gagal menghapus data: $err');
        window.location='penyakit.php';
    </script>";
}
?>
