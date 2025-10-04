<?php
include '../../koneksi.php';

if (isset($_GET['id'])) {
    $id_user = (int) $_GET['id'];

    // Cek apakah user ada
    $cek = mysqli_query($conn, "SELECT * FROM user WHERE id_user = $id_user");
    if (mysqli_num_rows($cek) > 0) {
        // Hapus user
        mysqli_query($conn, "DELETE FROM user WHERE id_user = $id_user");
    }

    header("Location: user_list.php");
    exit;
} else {
    header("Location: user_list.php");
    exit;
}
?>
