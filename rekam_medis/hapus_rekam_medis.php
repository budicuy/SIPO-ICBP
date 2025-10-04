<?php
include '../koneksi.php';

if (isset($_GET['id'])) {
    $id_rekam = (int) $_GET['id'];

    // Cek apakah data ada
    $cek = mysqli_query($conn, "SELECT * FROM rekam_medis WHERE id_rekam = $id_rekam");
    if (mysqli_num_rows($cek) > 0) {
        // Hapus resep_obat (jika ada relasi)
        mysqli_query($conn, "DELETE FROM resep_obat WHERE id_rekam = $id_rekam");
        // Hapus rekam medis
        mysqli_query($conn, "DELETE FROM rekam_medis WHERE id_rekam = $id_rekam");
    }

    // Redirect dengan status berhasil
    header("Location: daftar_rekam_medis.php?status=deleted");
    exit;
} else {
    header("Location: daftar_rekam_medis.php");
    exit;
}
?>
