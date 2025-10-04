<?php
include '../../koneksi.php';
include '../../sidebar.php';

if (isset($_POST['simpan'])) {
    $nama_departemen = $_POST['nama_departemen'];
    mysqli_query($conn, "INSERT INTO departemen (nama_departemen) VALUES ('$nama_departemen')");
    echo "<script>alert('Departemen berhasil ditambahkan!'); window.location='departemen.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Departemen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .main-content { margin-left:220px; padding:30px; }
    
  </style>
</head>
<body>
<div class="main-content">
  <div>
    <div>
      <h2 class="mb-4">Tambah Departemen</h2>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Nama Departemen</label>
          <input type="text" name="nama_departemen" class="form-control" required>
        </div>
        <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
        <a href="departemen.php" class="btn btn-secondary">Batal</a>
      </form>
    </div>
  </div>
</div>
</body>
</html>
