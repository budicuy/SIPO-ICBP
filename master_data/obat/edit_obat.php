<?php
include '../../koneksi.php';
include '../../sidebar.php';

// Ambil data jenis obat
$jenisResult = mysqli_query($conn, "SELECT id_jenis_obat, nama_jenis FROM jenis_obat");

// Ambil data satuan
$satuanResult = mysqli_query($conn, "SELECT id_satuan, nama_satuan FROM satuan_obat");

// Ambil ID obat dari URL
$id = $_GET['id'] ?? 0;

// Ambil data obat berdasarkan ID
$query = "SELECT * FROM obat WHERE id_obat = $id";
$result = mysqli_query($conn, $query);
$obat = mysqli_fetch_assoc($result);

// Jika data obat tidak ditemukan
if (!$obat) {
    echo "<script>alert('Data obat tidak ditemukan'); window.location='obat.php';</script>";
    exit;
}

// Fungsi format Rupiah
function formatRupiah($angka){
    return 'Rp. ' . number_format($angka,0,',','.');
}

// Flag SweetAlert
$alert = null;

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_obat   = mysqli_real_escape_string($conn, $_POST['nama_obat']);
    $keterangan  = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $id_jenis    = intval($_POST['id_jenis_obat']);
    $id_satuan   = intval($_POST['id_satuan']);
    $stok_awal   = intval($_POST['stok_awal']);
    $stok_masuk  = intval($_POST['stok_masuk']);
    $stok_keluar = intval($_POST['stok_keluar']);
    $jumlah_per_kemasan = intval($_POST['jumlah_per_kemasan']);
    $harga_per_kemasan = floatval($_POST['harga_per_kemasan_decimal']);

    if ($id_jenis == 0 || $id_satuan == 0) {
        $alert = ['error', 'Gagal', 'Jenis dan Satuan obat harus dipilih!'];
    } else {
        $stok_akhir = $stok_awal + $stok_masuk - $stok_keluar;
        $harga_per_satuan = $jumlah_per_kemasan > 0 ? $harga_per_kemasan / $jumlah_per_kemasan : 0;

        $update = "UPDATE obat 
                   SET nama_obat='$nama_obat', 
                       keterangan='$keterangan', 
                       id_jenis_obat='$id_jenis', 
                       id_satuan='$id_satuan', 
                       stok_awal='$stok_awal', 
                       stok_masuk='$stok_masuk', 
                       stok_keluar='$stok_keluar', 
                       stok_akhir='$stok_akhir',
                       jumlah_per_kemasan='$jumlah_per_kemasan',
                       harga_per_satuan='$harga_per_satuan',
                       harga_per_kemasan='$harga_per_kemasan'
                   WHERE id_obat='$id'";

        if (mysqli_query($conn, $update)) {
            $alert = ['success', 'Berhasil', 'Data obat berhasil diperbarui!'];
        } else {
            $err = mysqli_error($conn);
            $alert = ['error', 'Gagal', "Terjadi kesalahan: $err"];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Obat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { margin:0; font-family: Arial, sans-serif; background-color: #f8f9fa; }
.main-content { margin-left: 250px; padding: 20px; }
</style>
<script>
// Hitung harga per satuan
function hitungHargaPerSatuan() {
    let jumlah = parseFloat(document.getElementById('jumlah_per_kemasan').value) || 1;
    let hargaKemasan = parseFloat(document.getElementById('harga_per_kemasan_decimal').value) || 0;
    let hargaSatuan = jumlah > 0 ? hargaKemasan / jumlah : 0;
    document.getElementById('harga_per_satuan').value = formatRupiah(hargaSatuan.toString());
}

// Format Rupiah untuk ditampilkan
function formatRupiah(angka) {
    let number_string = angka.replace(/\D/g,'') + '';
    let sisa = number_string.length % 3;
    let rupiah = number_string.substr(0, sisa);
    let ribuan = number_string.substr(sisa).match(/\d{3}/g);
    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    return 'Rp. ' + rupiah;
}

// Update input tampilan dan hidden decimal
function updateHargaKemasan(el) {
    let raw = el.value.replace(/\D/g,'');
    document.getElementById('harga_per_kemasan_decimal').value = raw;
    el.value = formatRupiah(raw);
    hitungHargaPerSatuan();
}
</script>
</head>
<body>

<div class="main-content">
  <h2 class="mb-4">Edit Obat</h2>

  <form method="post">
    <div class="mb-3">
      <label class="form-label">Nama Obat</label>
      <input type="text" name="nama_obat" class="form-control" value="<?= htmlspecialchars($obat['nama_obat'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Keterangan</label>
      <textarea name="keterangan" class="form-control"><?= htmlspecialchars($obat['keterangan'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Jenis Obat</label>
      <select name="id_jenis_obat" class="form-control" required>
        <option value="">-- Pilih Jenis Obat --</option>
        <?php 
        mysqli_data_seek($jenisResult, 0); 
        while ($row = mysqli_fetch_assoc($jenisResult)) { 
          $selected = ($row['id_jenis_obat'] == ($obat['id_jenis_obat'] ?? '')) ? 'selected' : '';
        ?>
          <option value="<?= $row['id_jenis_obat'] ?>" <?= $selected ?>><?= htmlspecialchars($row['nama_jenis'] ?? '') ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Satuan</label>
      <select name="id_satuan" class="form-control" required>
        <option value="">-- Pilih Satuan --</option>
        <?php 
        mysqli_data_seek($satuanResult, 0); 
        while ($row = mysqli_fetch_assoc($satuanResult)) { 
          $selected = ($row['id_satuan'] == ($obat['id_satuan'] ?? '')) ? 'selected' : '';
        ?>
          <option value="<?= $row['id_satuan'] ?>" <?= $selected ?>><?= htmlspecialchars($row['nama_satuan'] ?? '') ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="row">
      <div class="col-md-3 mb-3">
        <label class="form-label">Stok Awal</label>
        <input type="number" name="stok_awal" class="form-control" value="<?= htmlspecialchars($obat['stok_awal'] ?? 0) ?>" required>
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Stok Masuk</label>
        <input type="number" name="stok_masuk" class="form-control" value="<?= htmlspecialchars($obat['stok_masuk'] ?? 0) ?>" required>
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Stok Keluar</label>
        <input type="number" name="stok_keluar" class="form-control" value="<?= htmlspecialchars($obat['stok_keluar'] ?? 0) ?>" required>
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Stok Akhir</label>
        <input type="number" class="form-control" value="<?= htmlspecialchars(($obat['stok_awal']+$obat['stok_masuk']-$obat['stok_keluar']) ?? 0) ?>" readonly>
        <small class="text-muted">Otomatis dihitung</small>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Jumlah per Kemasan</label>
        <input type="number" id="jumlah_per_kemasan" name="jumlah_per_kemasan" class="form-control" value="<?= htmlspecialchars($obat['jumlah_per_kemasan'] ?? 1) ?>" min="1" oninput="hitungHargaPerSatuan()" required>
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Harga per Kemasan</label>
        <input type="text" id="harga_per_kemasan" name="harga_per_kemasan_display" class="form-control" 
               value="<?= formatRupiah($obat['harga_per_kemasan'] ?? 0) ?>" 
               oninput="updateHargaKemasan(this)" required>
        <input type="hidden" id="harga_per_kemasan_decimal" name="harga_per_kemasan_decimal" value="<?= $obat['harga_per_kemasan'] ?? 0 ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Harga per Satuan</label>
        <input type="text" id="harga_per_satuan" class="form-control" value="<?= formatRupiah($obat['jumlah_per_kemasan']>0?$obat['harga_per_kemasan']/$obat['jumlah_per_kemasan']:0) ?>" readonly>
        <small class="text-muted">Dihitung otomatis</small>
      </div>
    </div>

    <button type="submit" class="btn btn-warning">Update</button>
    <a href="obat.php" class="btn btn-secondary">Batal</a>
  </form>
</div>

<?php if ($alert): ?>
<script>
Swal.fire({
    icon: '<?= $alert[0] ?>',
    title: '<?= $alert[1] ?>',
    text: '<?= $alert[2] ?>'
}).then(() => {
    <?php if($alert[0] == 'success') echo "window.location.href='obat.php';"; ?>
});
</script>
<?php endif; ?>

</body>
</html>
