<?php
include '../../koneksi.php';
include '../../sidebar.php';

$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM departemen WHERE id_departemen='$id'");
$row = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {
    $nama_departemen = $_POST['nama_departemen'];
    mysqli_query($conn, "UPDATE departemen SET nama_departemen='$nama_departemen' WHERE id_departemen='$id'");
    echo "<script>alert('Departemen berhasil diperbarui!'); window.location='departemen.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Departemen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .main-content { margin-left:220px; padding:30px; }
    
  </style>
</head>
<body>
<div class="main-content">
  <div>
    <div>
      <h2 class="mb-4">Edit Departemen</h2>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Nama Departemen</label>
          <input type="text" name="nama_departemen" class="form-control" value="<?= $row['nama_departemen']; ?>" required>
        </div>
        <button type="submit" name="update" class="btn btn-warning">Update</button>
        <a href="departemen.php" class="btn btn-secondary">Batal</a>
      </form>
    </div>
  </div>
</div>
</body>
</html>
