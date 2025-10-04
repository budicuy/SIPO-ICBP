<?php
include '../../koneksi.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: karyawan.php"); exit; }

$error = "";
$success = false;

// Ambil data karyawan
$res = mysqli_query($conn, "SELECT * FROM karyawan WHERE id_karyawan=$id");
$karyawan = mysqli_fetch_assoc($res);

if (!$karyawan) {
    die("Data karyawan tidak ditemukan!");
}

$nik_karyawan   = $karyawan['nik_karyawan'];
$nama_karyawan  = $karyawan['nama_karyawan'];
$id_departemen  = $karyawan['id_departemen'];
$no_hp          = $karyawan['no_hp'];
$alamat         = $karyawan['alamat'];
$tanggal_lahir  = $karyawan['tanggal_lahir'];

// Ambil list departemen
$departemenList = mysqli_query($conn, "SELECT * FROM departemen ORDER BY nama_departemen ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik_karyawan   = mysqli_real_escape_string($conn, $_POST['nik_karyawan']);
    $nama_karyawan  = mysqli_real_escape_string($conn, $_POST['nama_karyawan']);
    $id_departemen  = $_POST['id_departemen'];
    $no_hp          = $_POST['no_hp'];
    $alamat         = $_POST['alamat'];
    $tanggal_lahir  = $_POST['tanggal_lahir'] ?: null;

    // Cek NIK unik (kecuali milik sendiri)
    $cek = mysqli_query($conn, "SELECT * FROM karyawan WHERE nik_karyawan='$nik_karyawan' AND id_karyawan<>$id");
    if (mysqli_num_rows($cek) > 0) {
        $error = "NIK sudah terdaftar!";
    } else {
        $sql = "UPDATE karyawan SET 
                    nik_karyawan='$nik_karyawan', 
                    nama_karyawan='$nama_karyawan', 
                    id_departemen='$id_departemen', 
                    no_hp='$no_hp', 
                    alamat='$alamat', 
                    tanggal_lahir=" . ($tanggal_lahir ? "'$tanggal_lahir'" : "NULL") . "
                WHERE id_karyawan=$id";
        if (mysqli_query($conn, $sql)) {
            $success = true;
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
<title>Edit Karyawan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  .main-content { margin-left: 220px; padding: 30px; }
</style>
</head>
<body>
<?php include '../../sidebar.php'; ?>

<div class="main-content">
<h2 class="mb-4">Edit Karyawan</h2>

<?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

<form method="POST">
  <div class="mb-3">
    <label>NIK</label>
    <input type="text" name="nik_karyawan" class="form-control" value="<?= htmlspecialchars($nik_karyawan) ?>" required>
  </div>
  <div class="mb-3">
    <label>Nama Karyawan</label>
    <input type="text" name="nama_karyawan" class="form-control" value="<?= htmlspecialchars($nama_karyawan) ?>" required>
  </div>
  <div class="mb-3">
    <label>Departemen</label>
    <select name="id_departemen" class="form-control" required>
      <option value="">-- Pilih Departemen --</option>
      <?php mysqli_data_seek($departemenList, 0); ?>
      <?php while($d = mysqli_fetch_assoc($departemenList)): ?>
        <option value="<?= $d['id_departemen'] ?>" <?= $d['id_departemen']==$id_departemen?'selected':'' ?>>
            <?= $d['nama_departemen'] ?>
        </option>
      <?php endwhile; ?>
    </select>
  </div>
  <div class="mb-3">
    <label>No HP</label>
    <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($no_hp) ?>">
  </div>
  <div class="mb-3">
    <label>Alamat</label>
    <textarea name="alamat" class="form-control"><?= htmlspecialchars($alamat) ?></textarea>
  </div>
  <div class="mb-3">
    <label>Tanggal Lahir</label>
    <input type="date" name="tanggal_lahir" class="form-control" value="<?= $tanggal_lahir ?>">
  </div>
  <button type="submit" class="btn btn-warning">Update</button>
  <a href="karyawan.php" class="btn btn-secondary">Batal</a>
</form>
</div>

<?php if ($success): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Berhasil',
    text: 'Data karyawan berhasil diperbarui!'
}).then(() => {
    window.location.href = 'karyawan.php';
});
</script>
<?php endif; ?>

</body>
</html>
