<?php
include '../../koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    mysqli_query($conn, "DELETE FROM departemen WHERE id_departemen='$id'");
}

header("Location: departemen.php");
exit;
