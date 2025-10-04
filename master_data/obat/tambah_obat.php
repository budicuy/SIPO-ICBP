<?php
include '../../koneksi.php';
include '../../sidebar.php';

// Ambil data jenis obat
$jenisResult = mysqli_query($conn, "SELECT id_jenis_obat, nama_jenis FROM jenis_obat");

// Ambil data satuan
$satuanResult = mysqli_query($conn, "SELECT id_satuan, nama_satuan FROM satuan_obat");

// Inisialisasi variabel default
$nama_obat = '';
$keterangan = '';
$id_jenis_obat = '';
$id_satuan = '';
$stok_awal = 0;
$stok_masuk = 0;
$stok_keluar = 0;
$jumlah_per_kemasan = 1;
$harga_per_kemasan = 0;

// Flag SweetAlert
$alert = null;

// Fungsi format Rupiah
function formatRupiah($angka){
    return 'Rp. ' . number_format($angka,0,',','.');
}

// Proses simpan data obat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_manual'])) {
    $nama_obat = mysqli_real_escape_string($conn, trim($_POST['nama_obat']));
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $id_jenis_obat = intval($_POST['id_jenis_obat']);
    $id_satuan = intval($_POST['id_satuan']);
    $stok_awal = intval($_POST['stok_awal']);
    $stok_masuk = intval($_POST['stok_masuk']);
    $stok_keluar = intval($_POST['stok_keluar']);
    $jumlah_per_kemasan = intval($_POST['jumlah_per_kemasan']);
    $harga_per_kemasan = floatval($_POST['harga_per_kemasan_decimal']);

    if ($id_jenis_obat == 0 || $id_satuan == 0) {
        $alert = ['error', 'Gagal', 'Jenis dan Satuan obat harus dipilih!'];
    } else {
        // ✅ Cek apakah nama obat sudah ada
        $cek = mysqli_query($conn, "SELECT 1 FROM obat WHERE nama_obat = '$nama_obat' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $alert = ['warning', 'Obat sudah ada', "Nama obat \"$nama_obat\" sudah terdaftar dalam database!"];
        } else {
            $stok_akhir = $stok_awal + $stok_masuk - $stok_keluar;
            $harga_per_satuan = $jumlah_per_kemasan > 0 ? $harga_per_kemasan / $jumlah_per_kemasan : 0;

            $query = "INSERT INTO obat 
                      (nama_obat, keterangan, id_jenis_obat, id_satuan, stok_awal, stok_masuk, stok_keluar, stok_akhir, jumlah_per_kemasan, harga_per_satuan, harga_per_kemasan)
                      VALUES 
                      ('$nama_obat', '$keterangan', '$id_jenis_obat', '$id_satuan', '$stok_awal', '$stok_masuk', '$stok_keluar', '$stok_akhir', '$jumlah_per_kemasan', '$harga_per_satuan', '$harga_per_kemasan')";

            if (mysqli_query($conn, $query)) {
                $alert = ['success', 'Berhasil', 'Data obat berhasil ditambahkan!'];
            } else {
                $err = mysqli_error($conn);
                $alert = ['error', 'Gagal', "Terjadi kesalahan: $err"];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Obat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { margin: 0; font-family: Arial, sans-serif; background-color: #f8f9fa; }
.main-content { margin-left: 250px; padding: 20px; }
</style>
<script>
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
function updateHargaKemasan(el) {
    let raw = el.value.replace(/\D/g,'');
    document.getElementById('harga_per_kemasan_decimal').value = raw;
    el.value = formatRupiah(raw);
    hitungHargaPerSatuan();
}
function hitungHargaPerSatuan() {
    let jumlah = parseFloat(document.getElementById('jumlah_per_kemasan').value) || 1;
    let hargaKemasan = parseFloat(document.getElementById('harga_per_kemasan_decimal').value) || 0;
    let hargaSatuan = jumlah > 0 ? hargaKemasan / jumlah : 0;
    document.getElementById('harga_per_satuan').value = formatRupiah(hargaSatuan.toString());
}
</script>
</head>
<body>

<div class="main-content">
  <h2 class="mb-4">Tambah Obat</h2>

  <!-- Import Excel -->
  <div class="card mb-4">
    <div class="card-header bg-light"><strong>Import Data Obat</strong></div>
    <div class="card-body">
      <form action="../../proses/import_excel/import_obat.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <input type="file" name="file_excel" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Import</button>
        <a href="../../proses/template_excel/template_obat.php" class="btn btn-warning">Download Template</a>
      </form>
    </div>
  </div>

  <!-- Input Manual -->
  <h4>Tambah Manual</h4>
  <form method="post">
    <input type="hidden" name="simpan_manual" value="1">

    <div class="mb-3">
      <label class="form-label">Nama Obat</label>
      <input type="text" name="nama_obat" class="form-control" value="<?= htmlspecialchars($nama_obat) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Keterangan</label>
      <textarea name="keterangan" class="form-control"><?= htmlspecialchars($keterangan) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Jenis Obat</label>
      <select name="id_jenis_obat" class="form-control" required>
        <option value="">-- Pilih Jenis Obat --</option>
        <?php 
        mysqli_data_seek($jenisResult, 0);
        while ($row = mysqli_fetch_assoc($jenisResult)) { ?>
          <option value="<?= $row['id_jenis_obat'] ?>" <?= $row['id_jenis_obat']==$id_jenis_obat?'selected':'' ?>><?= htmlspecialchars($row['nama_jenis']) ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Satuan</label>
      <select name="id_satuan" class="form-control" required>
        <option value="">-- Pilih Satuan --</option>
        <?php 
        mysqli_data_seek($satuanResult, 0);
        while ($row = mysqli_fetch_assoc($satuanResult)) { ?>
          <option value="<?= $row['id_satuan'] ?>" <?= $row['id_satuan']==$id_satuan?'selected':'' ?>><?= htmlspecialchars($row['nama_satuan']) ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="row">
      <div class="col-md-3 mb-3">
        <label class="form-label">Stok Awal</label>
        <input type="number" name="stok_awal" class="form-control" value="<?= $stok_awal ?>" required>
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Stok Masuk</label>
        <input type="number" name="stok_masuk" class="form-control" value="<?= $stok_masuk ?>" required>
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Stok Keluar</label>
        <input type="number" name="stok_keluar" class="form-control" value="<?= $stok_keluar ?>" required>
      </div>
      <div class="col-md-3 mb-3">
        <label class="form-label">Stok Akhir</label>
        <input type="number" class="form-control" value="<?= $stok_awal + $stok_masuk - $stok_keluar ?>" readonly>
        <small class="text-muted">Otomatis dihitung</small>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Jumlah per Kemasan</label>
        <input type="number" id="jumlah_per_kemasan" name="jumlah_per_kemasan" class="form-control" value="<?= $jumlah_per_kemasan ?>" min="1" oninput="hitungHargaPerSatuan()" required>
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Harga per Kemasan</label>
        <input type="text" id="harga_per_kemasan" name="harga_per_kemasan_display" class="form-control" value="<?= formatRupiah($harga_per_kemasan) ?>" oninput="updateHargaKemasan(this)" required>
        <input type="hidden" id="harga_per_kemasan_decimal" name="harga_per_kemasan_decimal" value="<?= $harga_per_kemasan ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Harga per Satuan</label>
        <input type="text" id="harga_per_satuan" class="form-control" value="<?= formatRupiah($jumlah_per_kemasan>0?$harga_per_kemasan/$jumlah_per_kemasan:0) ?>" readonly>
        <small class="text-muted">Dihitung otomatis</small>
      </div>
    </div>

    <button type="submit" class="btn btn-primary">Simpan</button>
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
