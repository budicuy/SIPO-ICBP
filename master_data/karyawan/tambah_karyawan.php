<?php
include '../../koneksi.php';

$error = "";
$nik_karyawan = $nama_karyawan = $id_departemen = $no_hp = $alamat = $tanggal_lahir = $jenis_kelamin = "";

// Ambil list departemen
$departemenList = mysqli_query($conn, "SELECT * FROM departemen ORDER BY nama_departemen ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nik_karyawan'])) {
    $nik_karyawan   = mysqli_real_escape_string($conn, $_POST['nik_karyawan']);
    $nama_karyawan  = mysqli_real_escape_string($conn, $_POST['nama_karyawan']);
    $id_departemen  = $_POST['id_departemen'];
    $no_hp          = $_POST['no_hp'];
    $alamat         = mysqli_real_escape_string($conn, $_POST['alamat']);
    $tanggal_lahir  = $_POST['tanggal_lahir'] ?: null;
    $jenis_kelamin  = $_POST['jenis_kelamin'];

    // Validasi jenis_kelamin
    if (!in_array($jenis_kelamin, ['Laki - Laki', 'Perempuan'])) {
        $jenis_kelamin = null;
    }

    // Cek NIK unik
    $cek = mysqli_query($conn, "SELECT * FROM karyawan WHERE nik_karyawan='$nik_karyawan'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "NIK sudah terdaftar!";
    } else {
        $sql = "INSERT INTO karyawan 
                (nik_karyawan,nama_karyawan,id_departemen,no_hp,alamat,tanggal_lahir,jenis_kelamin) 
                VALUES 
                ('$nik_karyawan','$nama_karyawan','$id_departemen','$no_hp','$alamat'," . ($tanggal_lahir ? "'$tanggal_lahir'" : "NULL") . ",'" . ($jenis_kelamin ?? '') . "')";
        if (mysqli_query($conn, $sql)) {
            header("Location: tambah_karyawan.php?msg=success");
            exit;
        } else {
            $error = mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Karyawan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  body { margin: 0; font-family: Arial, sans-serif; }
  .main-content { margin-left: 250px; padding: 20px; }
</style>
</head>
<body>

<?php include '../../sidebar.php'; ?>

<div class="main-content">
  <h2 class="mb-4">Tambah Karyawan</h2>

  <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

  <!-- Import Excel -->
  <div class="card mb-4">
    <div class="card-header bg-light"><strong>Import Data Karyawan</strong></div>
    <div class="card-body">
      <form action='../../proses/import_excel/import_karyawan.php' method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <input type="file" name="file_excel" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Import</button>
        <a href="../../proses/template_excel/template_karyawan.php" class="btn btn-warning">Download Template</a>
      </form>
    </div>
  </div>

  <!-- Form manual -->
  <h4 class="mb-3">Tambah Manual</h4>
  <form method="POST" class="mb-5">
    <div class="mb-3">
      <label class="form-label">NIK</label>
      <input type="text" name="nik_karyawan" class="form-control" value="<?= htmlspecialchars($nik_karyawan) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Nama Karyawan</label>
      <input type="text" name="nama_karyawan" class="form-control" value="<?= htmlspecialchars($nama_karyawan) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Departemen</label>
      <select name="id_departemen" class="form-control" required>
        <option value="">-- Pilih Departemen --</option>
        <?php mysqli_data_seek($departemenList, 0); ?>
        <?php while($d = mysqli_fetch_assoc($departemenList)): ?>
          <option value="<?= $d['id_departemen'] ?>" <?= $d['id_departemen']==$id_departemen?'selected':'' ?>>
            <?= htmlspecialchars($d['nama_departemen']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Jenis Kelamin</label>
      <select name="jenis_kelamin" class="form-control" required>
        <option value="">-- Pilih Jenis Kelamin --</option>
        <option value="Laki - Laki" <?= $jenis_kelamin=='Laki - Laki'?'selected':'' ?>>Laki - Laki</option>
        <option value="Perempuan" <?= $jenis_kelamin=='Perempuan'?'selected':'' ?>>Perempuan</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">No HP</label>
      <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($no_hp) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Alamat</label>
      <textarea name="alamat" class="form-control"><?= htmlspecialchars($alamat) ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Tanggal Lahir</label>
      <input type="date" name="tanggal_lahir" class="form-control" value="<?= htmlspecialchars($tanggal_lahir) ?>">
    </div>
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="karyawan.php" class="btn btn-secondary">Batal</a>
  </form>
</div>

<!-- SweetAlert success -->
<?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
<script>
document.addEventListener("DOMContentLoaded", function(){
  Swal.fire({
      title: 'Berhasil!',
      text: 'Data karyawan berhasil ditambahkan.',
      icon: 'success',
      confirmButtonColor: '#3085d6',
      confirmButtonText: 'OK'
  }).then((result)=>{
      if(result.isConfirmed){
          window.location.href = "karyawan.php";
      }
  });
});
</script>
<?php endif; ?>

</body>
</html>
