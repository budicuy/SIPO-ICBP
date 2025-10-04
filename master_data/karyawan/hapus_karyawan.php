<?php
include '../../koneksi.php';

// Jika hapus single via GET
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "DELETE FROM karyawan WHERE id_karyawan = $id";
    mysqli_query($conn, $query);
    header("Location: karyawan.php");
    exit;
}

// Jika hapus massal via POST
if (isset($_POST['selected']) && is_array($_POST['selected'])) {
    $ids = array_map('intval', $_POST['selected']);
    $idList = implode(",", $ids);

    if (!empty($idList)) {
        $query = "DELETE FROM karyawan WHERE id_karyawan IN ($idList)";
        mysqli_query($conn, $query);
    }

    header("Location: karyawan.php");
    exit;
}

// Jika tidak ada parameter -> kembali
header("Location: karyawan.php");
exit;
?>
