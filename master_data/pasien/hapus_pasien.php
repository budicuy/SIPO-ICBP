<?php
include '../../koneksi.php';

if (isset($_GET['id'])) {
  $id = $_GET['id']; // gunakan $id, bukan $nik

  // cek apakah pasien punya rekam medis
  $cek = mysqli_query($conn, "SELECT * FROM rekam_medis WHERE id_pasien = '$id'");
  if (mysqli_num_rows($cek) > 0) {
    header("Location: pasien.php?gagal=1");
    exit;
  }

  // hapus pasien
  mysqli_query($conn, "DELETE FROM pasien WHERE id_pasien = '$id'") or die(mysqli_error($conn));
  header("Location: pasien.php");
  exit;
}
?>
