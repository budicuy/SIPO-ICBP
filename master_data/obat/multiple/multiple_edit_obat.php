<?php
include '../../../koneksi.php';
include '../../../sidebar.php';

// Ambil data jenis & satuan
$jenisResult  = mysqli_query($conn, "SELECT * FROM jenis_obat");
$satuanResult = mysqli_query($conn, "SELECT * FROM satuan_obat");

// ===================================================
// PROSES UPDATE
// ===================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_obat'])) {
    $ids            = $_POST['id_obat'];
    $nama_obat      = $_POST['nama_obat'];
    $keterangan     = $_POST['keterangan'];
    $id_jenis_obat  = $_POST['id_jenis_obat'];
    $id_satuan      = $_POST['id_satuan'];
    $stok_awal      = $_POST['stok_awal'];
    $stok_masuk     = $_POST['stok_masuk'];
    $stok_keluar    = $_POST['stok_keluar'];
    $jumlah_kemasan = $_POST['jumlah_per_kemasan'];
    $harga_kemasan  = $_POST['harga_per_kemasan_decimal'];

    for ($i=0; $i<count($ids); $i++) {
        $id     = intval($ids[$i]);
        $nama   = mysqli_real_escape_string($conn, $nama_obat[$i]);
        $ket    = mysqli_real_escape_string($conn, $keterangan[$i]);
        $jenis  = intval($id_jenis_obat[$i]);
        $satuan = intval($id_satuan[$i]);
        $awal   = intval($stok_awal[$i]);
        $masuk  = intval($stok_masuk[$i]);
        $keluar = intval($stok_keluar[$i]);
        $jml    = intval($jumlah_kemasan[$i]) ?: 1;
        $hargaK = floatval($harga_kemasan[$i]);

        $akhir  = $awal + $masuk - $keluar;
        $hargaS = $jml > 0 ? $hargaK / $jml : 0;

        $update = "UPDATE obat SET 
                    nama_obat='$nama',
                    keterangan='$ket',
                    id_jenis_obat=$jenis,
                    id_satuan=$satuan,
                    stok_awal=$awal,
                    stok_masuk=$masuk,
                    stok_keluar=$keluar,
                    stok_akhir=$akhir,
                    jumlah_per_kemasan=$jml,
                    harga_per_satuan=$hargaS,
                    harga_per_kemasan=$hargaK
                   WHERE id_obat=$id";
        mysqli_query($conn, $update) or die(mysqli_error($conn));
    }

    echo "<script>alert('Semua data obat berhasil diperbarui'); window.location='../obat.php';</script>";
    exit;
}

// ===================================================
// SAAT MASUK DARI obat.php
// ===================================================
$ids = $_POST['id'] ?? ($_GET['id'] ?? []);
if (!is_array($ids)) { $ids = explode(",", $ids); }
if (empty($ids)) {
    echo "<script>alert('Tidak ada data obat dipilih'); window.location='../obat.php';</script>";
    exit;
}
$id_list = implode(",", array_map('intval', $ids));
$result  = mysqli_query($conn, "SELECT * FROM obat WHERE id_obat IN ($id_list)");

// Format Rupiah
function formatRupiah($angka){ return 'Rp ' . number_format($angka,0,',','.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Multiple Edit Obat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { margin:0; font-family: Arial, sans-serif; background:#f8f9fa; }
.main-content { margin-left:250px; padding:20px; }
.card-form { background:#fff; padding:20px; border-radius:8px; margin-bottom:20px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
</style>
<script>
function hitungHargaPerSatuan(idx) {
    let jumlah = parseFloat(document.getElementById('jumlah_per_kemasan_'+idx).value) || 1;
    let hargaKemasan = parseFloat(document.getElementById('harga_per_kemasan_decimal_'+idx).value) || 0;
    let hargaSatuan = jumlah > 0 ? hargaKemasan / jumlah : 0;
    document.getElementById('harga_per_satuan_'+idx).value = formatRupiah(hargaSatuan.toString());
}
function formatRupiah(angka) {
    let number_string = angka.replace(/\D/g,'') + '';
    let sisa = number_string.length % 3;
    let rupiah = number_string.substr(0, sisa);
    let ribuan = number_string.substr(sisa).match(/\d{3}/g);
    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    return 'Rp ' + rupiah;
}
function updateHargaKemasan(el, idx) {
    let raw = el.value.replace(/\D/g,'');
    document.getElementById('harga_per_kemasan_decimal_'+idx).value = raw;
    el.value = formatRupiah(raw);
    hitungHargaPerSatuan(idx);
}
</script>
</head>
<body>
<div class="main-content">
  <h2 class="mb-4">Multiple Edit Obat</h2>
  <form method="post">
    <?php $index=0; while ($obat = mysqli_fetch_assoc($result)) { ?>
    <div class="card-form">
      <input type="hidden" name="id_obat[]" value="<?= $obat['id_obat'] ?>">

      <h5>Edit Obat: <?= htmlspecialchars($obat['nama_obat']) ?></h5><hr>

      <div class="mb-3">
        <label class="form-label">Nama Obat</label>
        <input type="text" name="nama_obat[]" class="form-control" value="<?= htmlspecialchars($obat['nama_obat']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Keterangan</label>
        <textarea name="keterangan[]" class="form-control"><?= htmlspecialchars($obat['keterangan']) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Jenis Obat</label>
        <select name="id_jenis_obat[]" class="form-control" required>
          <option value="">-- Pilih Jenis Obat --</option>
          <?php 
          mysqli_data_seek($jenisResult, 0);
          while ($row = mysqli_fetch_assoc($jenisResult)) {
              $selected = ($row['id_jenis_obat'] == $obat['id_jenis_obat']) ? 'selected' : '';
              echo "<option value='{$row['id_jenis_obat']}' $selected>".htmlspecialchars($row['nama_jenis'])."</option>";
          }
          ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Satuan</label>
        <select name="id_satuan[]" class="form-control" required>
          <option value="">-- Pilih Satuan --</option>
          <?php 
          mysqli_data_seek($satuanResult, 0);
          while ($row = mysqli_fetch_assoc($satuanResult)) {
              $selected = ($row['id_satuan'] == $obat['id_satuan']) ? 'selected' : '';
              echo "<option value='{$row['id_satuan']}' $selected>".htmlspecialchars($row['nama_satuan'])."</option>";
          }
          ?>
        </select>
      </div>

      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label">Stok Awal</label>
          <input type="number" name="stok_awal[]" class="form-control" value="<?= $obat['stok_awal'] ?>" required>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Stok Masuk</label>
          <input type="number" name="stok_masuk[]" class="form-control" value="<?= $obat['stok_masuk'] ?>" required>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Stok Keluar</label>
          <input type="number" name="stok_keluar[]" class="form-control" value="<?= $obat['stok_keluar'] ?>" required>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label">Stok Akhir</label>
          <input type="number" class="form-control" value="<?= $obat['stok_awal']+$obat['stok_masuk']-$obat['stok_keluar'] ?>" readonly>
        </div>
      </div>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label class="form-label">Jumlah per Kemasan</label>
          <input type="number" id="jumlah_per_kemasan_<?= $index ?>" name="jumlah_per_kemasan[]" class="form-control" 
                 value="<?= $obat['jumlah_per_kemasan'] ?>" min="1" 
                 oninput="hitungHargaPerSatuan(<?= $index ?>)" required>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Harga per Kemasan</label>
          <input type="text" id="harga_per_kemasan_<?= $index ?>" 
                 name="harga_per_kemasan_display[]" 
                 class="form-control" 
                 value="<?= formatRupiah($obat['harga_per_kemasan']) ?>" 
                 oninput="updateHargaKemasan(this, <?= $index ?>)" required>
          <input type="hidden" id="harga_per_kemasan_decimal_<?= $index ?>" 
                 name="harga_per_kemasan_decimal[]" value="<?= $obat['harga_per_kemasan'] ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Harga per Satuan</label>
          <input type="text" id="harga_per_satuan_<?= $index ?>" 
                 class="form-control" 
                 value="<?= formatRupiah($obat['jumlah_per_kemasan']>0?$obat['harga_per_kemasan']/$obat['jumlah_per_kemasan']:0) ?>" readonly>
        </div>
      </div>
    </div>
    <?php $index++; } ?>

    <button type="submit" class="btn btn-warning">Update Semua</button>
    <a href="../obat.php" class="btn btn-secondary">Batal</a>
  </form>
</div>
</body>
</html>
