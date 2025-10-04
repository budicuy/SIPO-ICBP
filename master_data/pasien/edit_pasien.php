<?php
include '../../koneksi.php';

// Ambil ID pasien dari URL
$id = $_GET['id'] ?? 0;

// Ambil data pasien
$pasienResult = mysqli_query($conn, "SELECT * FROM pasien WHERE id_pasien = '$id'") or die(mysqli_error($conn));
$pasien = mysqli_fetch_assoc($pasienResult);
if (!$pasien) {
    die("Pasien tidak ditemukan.");
}

// Ambil data karyawan untuk dropdown (readonly)
$karyawanResult = mysqli_query($conn, "SELECT id_karyawan, nik_karyawan, nama_karyawan FROM karyawan");

// Ambil tanggal kunjungan terakhir
$qKunjungan = mysqli_query($conn, "SELECT tanggal_kunjungan FROM kunjungan WHERE id_pasien='$id' ORDER BY tanggal_kunjungan DESC LIMIT 1");
$kunjungan = mysqli_fetch_assoc($qKunjungan);
$tanggal_kunjungan = $kunjungan['tanggal_kunjungan'] ?? date('Y-m-d');

// Flag SweetAlert
$alert = null;

// POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isKaryawan = $pasien['hubungan'] === 'Karyawan';
    $tanggal_kunjungan_baru = $_POST['tanggal_kunjungan'] ?? $tanggal_kunjungan;

    // Ambil NIK untuk kode transaksi
    $nik_pasien = '';
    if ($isKaryawan && isset($pasien['id_karyawan'])) {
        $qNik = mysqli_query($conn, "SELECT nik_karyawan FROM karyawan WHERE id_karyawan = '{$pasien['id_karyawan']}' LIMIT 1");
        $rowNik = mysqli_fetch_assoc($qNik);
        $nik_pasien = $rowNik['nik_karyawan'];
    } else {
        $nik_pasien = $pasien['nik_pasien'] ?? $pasien['no_rm']; // fallback jika bukan karyawan
    }

    // Ambil tanggal kunjungan terakhir dari DB
    $qKunjunganLast = mysqli_query($conn, "SELECT tanggal_kunjungan FROM kunjungan WHERE id_pasien='$id' ORDER BY tanggal_kunjungan DESC LIMIT 1");
    $lastKunjungan = mysqli_fetch_assoc($qKunjunganLast);
    $tanggal_terakhir = $lastKunjungan['tanggal_kunjungan'] ?? $tanggal_kunjungan;

    // Format kode transaksi
    $kode_transaksi = $nik_pasien . '-' . date('dmY', strtotime($tanggal_terakhir));

    if ($isKaryawan) {
        // Insert kunjungan baru untuk karyawan
        $insertKunjungan = mysqli_query($conn, "INSERT INTO kunjungan (id_pasien, kode_transaksi, tanggal_kunjungan) 
                                                VALUES ('$id','$kode_transaksi','$tanggal_kunjungan_baru')");
        if ($insertKunjungan) {
            $alert = ['success', 'Berhasil', 'Tanggal kunjungan berhasil diperbarui!'];
        } else {
            $alert = ['error', 'Gagal', 'Kesalahan DB: ' . mysqli_error($conn)];
        }
    } else {
        // Update data pasien (tanpa tanggal daftar)
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $tgl_lahir = $_POST['tanggal_lahir'] ?: null;
        $jk = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
        $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

        $queryUpdate = "UPDATE pasien SET 
                        nama_pasien = '$nama',
                        tanggal_lahir = " . ($tgl_lahir ? "'$tgl_lahir'" : "NULL") . ",
                        jenis_kelamin = '$jk',
                        alamat = '$alamat',
                        updated_at = NOW()
                        WHERE id_pasien = '$id'";
        $ok = mysqli_query($conn, $queryUpdate);

        // Insert kunjungan baru
        $insertKunjungan = mysqli_query($conn, "INSERT INTO kunjungan (id_pasien, kode_transaksi, tanggal_kunjungan) 
                                                VALUES ('$id','$kode_transaksi','$tanggal_kunjungan_baru')");

        if ($ok && $insertKunjungan) {
            $alert = ['success', 'Berhasil', 'Data pasien berhasil diperbarui!'];
        } else {
            $alert = ['error', 'Gagal', 'Kesalahan DB: ' . mysqli_error($conn)];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Pasien</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background-color:#f8f9fa; }
    .main-content { margin-left: 250px; padding: 20px; }
    input[readonly], textarea[readonly], select[disabled] {
      background-color: #e9ecef !important;
      pointer-events: none;
    }
  </style>
</head>
<body>
<?php include '../../sidebar.php'; ?>

<div class="main-content">
  <h2 class="mb-4">Edit Pasien</h2>

  <form method="post">
    <div class="mb-3">
      <label class="form-label">NO RM</label>
      <input type="text" class="form-control" value="<?= $pasien['no_rm'] ?>" readonly>
    </div>

    <div class="mb-3">
      <label class="form-label">NIK Karyawan (Penanggung Jawab)</label>
      <select class="form-control" disabled>
        <?php mysqli_data_seek($karyawanResult, 0);
        while ($row = mysqli_fetch_assoc($karyawanResult)) { ?>
          <option value="<?= $row['id_karyawan'] ?>" <?= $row['id_karyawan'] == $pasien['id_karyawan'] ? 'selected' : '' ?>>
            <?= $row['nik_karyawan'] . " - " . $row['nama_karyawan'] ?>
          </option>
        <?php } ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Hubungan</label>
      <input type="text" class="form-control" value="<?= $pasien['hubungan'] ?>" readonly>
    </div>

    <div class="mb-3">
      <label class="form-label">Nama Pasien</label>
      <input type="text" name="nama" class="form-control"
             value="<?= htmlspecialchars($pasien['nama_pasien']) ?>"
             <?= ($pasien['hubungan'] === 'Karyawan') ? 'readonly' : '' ?>>
    </div>

    <div class="mb-3">
      <label class="form-label">Jenis Kelamin</label>
      <select name="jenis_kelamin" class="form-control" <?= ($pasien['hubungan'] === 'Karyawan') ? 'disabled' : '' ?>>
        <option value="">-- Pilih Jenis Kelamin --</option>
        <option value="Laki - Laki" <?= $pasien['jenis_kelamin'] == 'Laki - Laki' ? 'selected' : '' ?>>Laki - Laki</option>
        <option value="Perempuan" <?= $pasien['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
      </select>
      <?php if ($pasien['hubungan'] === 'Karyawan') { ?>
        <input type="hidden" name="jenis_kelamin" value="<?= htmlspecialchars($pasien['jenis_kelamin']) ?>">
      <?php } ?>
    </div>

    <div class="mb-3">
      <label class="form-label">Tanggal Lahir</label>
      <input type="date" name="tanggal_lahir" class="form-control"
             value="<?= $pasien['tanggal_lahir'] ?>"
             <?= ($pasien['hubungan'] === 'Karyawan') ? 'readonly' : '' ?>>
    </div>

    <div class="mb-3">
      <label class="form-label">Alamat</label>
      <textarea name="alamat" class="form-control" rows="2" <?= ($pasien['hubungan'] === 'Karyawan') ? 'readonly' : '' ?>><?= htmlspecialchars($pasien['alamat']) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Tanggal Kunjungan</label>
      <input type="date" name="tanggal_kunjungan" class="form-control" value="<?= $tanggal_kunjungan ?>" required>
    </div>

    <div class="mb-3 d-flex gap-2">
      <button type="submit" class="btn btn-warning">Update</button>
      <a href="pasien.php" class="btn btn-secondary">Batal</a>
    </div>
  </form>
</div>

<?php if ($alert): ?>
<script>
Swal.fire({
  icon: '<?= $alert[0] ?>',
  title: '<?= $alert[1] ?>',
  text: '<?= $alert[2] ?>',
  confirmButtonText: 'OK'
}).then(() => {
  if ('<?= $alert[0] ?>' === 'success') {
    window.location.href = "pasien.php";
  }
});
</script>
<?php endif; ?>

</body>
</html>
