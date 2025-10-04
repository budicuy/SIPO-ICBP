<?php
include '../../koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Hapus data obat
    $query = "DELETE FROM obat WHERE id_obat = $id";
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Data obat berhasil dihapus'); window.location='obat.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data obat'); window.location='obat.php';</script>";
    }
} else {
    header("Location: obat.php");
}
?>
