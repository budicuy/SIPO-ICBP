<?php
include '../../../koneksi.php';

// Inisialisasi variabel
$karyawanList = [];
$errorMessages = [];

// === Proses Update Data ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $submittedData = $_POST['data'] ?? [];

    foreach ($submittedData as $id => $data) {
        $id  = (int)$id;
        $nik = mysqli_real_escape_string($conn, $data['nik_karyawan']);
        $nama = mysqli_real_escape_string($conn, $data['nama_karyawan']);
        $dept = (int)$data['id_departemen'];
        $hp   = mysqli_real_escape_string($conn, $data['no_hp']);
        $alamat = mysqli_real_escape_string($conn, $data['alamat']);
        $tgl = !empty($data['tanggal_lahir']) ? "'" . mysqli_real_escape_string($conn, $data['tanggal_lahir']) . "'" : "NULL";

        // Validasi unik NIK
        $cekNik = mysqli_query($conn, "SELECT id_karyawan FROM karyawan 
                                       WHERE nik_karyawan='$nik' 
                                       AND id_karyawan != $id");
        if (mysqli_num_rows($cekNik) > 0) {
            $errorMessages[] = "NIK <b>$nik</b> sudah digunakan oleh karyawan lain.";
            continue;
        }

        // Update data
        $update = mysqli_query($conn, "UPDATE karyawan SET 
            nik_karyawan='$nik',
            nama_karyawan='$nama',
            id_departemen=$dept,
            no_hp='$hp',
            alamat='$alamat',
            tanggal_lahir=$tgl
            WHERE id_karyawan=$id
        ");

        if (!$update) {
            $errorMessages[] = "Gagal update ID $id: " . mysqli_error($conn);
        }
    }

    // Kalau tidak ada error → kasih status
    if (empty($errorMessages)) {
        header("Location: multiple_edit_karyawan.php?status=success");
        exit;
    } else {
        // Jika ada error, ambil ulang data berdasarkan ID yang dikirim
        $ids = array_keys($submittedData);
        $idsStr = implode(",", array_map('intval', $ids));
        $result = mysqli_query($conn, "SELECT * FROM karyawan WHERE id_karyawan IN ($idsStr)");
        while ($row = mysqli_fetch_assoc($result)) {
            $karyawanList[] = $row;
        }
    }
}
// === Pertama kali masuk dari form pilih data ===
elseif (isset($_POST['id_karyawan'])) {
    $ids = $_POST['id_karyawan'];
    if (empty($ids)) {
        header("Location: ../karyawan.php");
        exit;
    }
    $idsStr = implode(",", array_map('intval', $ids));
    $result = mysqli_query($conn, "SELECT * FROM karyawan WHERE id_karyawan IN ($idsStr)");
    while ($row = mysqli_fetch_assoc($result)) {
        $karyawanList[] = $row;
    }
} else {
    // Kalau akses langsung tanpa pilih data
    header("Location: ../karyawan.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Multiple Edit Karyawan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<style>
  .main-content { margin-left:220px; padding:30px; }
  .card { margin-bottom:20px; }
</style>
<?php include '../../../sidebar.php'; ?>

<div class="main-content">
  <h2 class="mb-4">Edit Beberapa Karyawan</h2>

  <?php if (!empty($errorMessages)): ?>
    <div class="alert alert-danger">
      <ul>
        <?php foreach ($errorMessages as $err): ?>
          <li><?= $err ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST">
    <?php foreach ($karyawanList as $k): ?>
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">NIK: <?= htmlspecialchars($k['nik_karyawan']) ?> - <?= htmlspecialchars($k['nama_karyawan']) ?></h5>
          <input type="hidden" name="data[<?= $k['id_karyawan'] ?>][id_karyawan]" value="<?= $k['id_karyawan'] ?>">

          <div class="mb-3">
            <label>NIK</label>
            <input type="text" name="data[<?= $k['id_karyawan'] ?>][nik_karyawan]" class="form-control" value="<?= htmlspecialchars($k['nik_karyawan']) ?>" required>
          </div>
          <div class="mb-3">
            <label>Nama Karyawan</label>
            <input type="text" name="data[<?= $k['id_karyawan'] ?>][nama_karyawan]" class="form-control" value="<?= htmlspecialchars($k['nama_karyawan']) ?>" required>
          </div>
          <div class="mb-3">
            <label>Departemen</label>
            <select name="data[<?= $k['id_karyawan'] ?>][id_departemen]" class="form-control" required>
              <option value="">-- Pilih Departemen --</option>
              <?php
              $departemenRes = mysqli_query($conn, "SELECT * FROM departemen ORDER BY nama_departemen ASC");
              while ($d = mysqli_fetch_assoc($departemenRes)): ?>
                <option value="<?= $d['id_departemen'] ?>" <?= $d['id_departemen']==$k['id_departemen']?'selected':'' ?>>
                  <?= $d['nama_departemen'] ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="mb-3">
            <label>No HP</label>
            <input type="text" name="data[<?= $k['id_karyawan'] ?>][no_hp]" class="form-control" value="<?= htmlspecialchars($k['no_hp']) ?>">
          </div>
          <div class="mb-3">
            <label>Alamat</label>
            <textarea name="data[<?= $k['id_karyawan'] ?>][alamat]" class="form-control"><?= htmlspecialchars($k['alamat']) ?></textarea>
          </div>
          <div class="mb-3">
            <label>Tanggal Lahir</label>
            <input type="date" name="data[<?= $k['id_karyawan'] ?>][tanggal_lahir]" class="form-control" value="<?= $k['tanggal_lahir'] ?>">
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <button type="submit" name="update" class="btn btn-warning">Update Semua</button>
    <a href="../karyawan.php" class="btn btn-secondary">Batal</a>
  </form>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
<script>
Swal.fire({
    title: 'Berhasil!',
    text: 'Data beberapa karyawan berhasil diupdate.',
    icon: 'success',
    confirmButtonText: 'OK'
}).then(() => {
    window.location.href = "../karyawan.php";
});
</script>
<?php endif; ?>

</body>
</html>
