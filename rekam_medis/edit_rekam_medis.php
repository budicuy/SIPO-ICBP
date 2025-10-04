<?php
include '../koneksi.php';

// ================================================================
// AJAX: ambil obat berdasarkan penyakit
// ================================================================
if (isset($_GET['get_obat'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id_penyakit = (int) $_GET['get_obat'];
    $res = [];

    $q = mysqli_query($conn, "
        SELECT o.id_obat, o.nama_obat
        FROM obat o
        JOIN penyakit_obat po ON o.id_obat = po.id_obat
        WHERE po.id_penyakit = $id_penyakit
        ORDER BY o.nama_obat ASC
    ");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $res[] = $r;
        }
    }
    echo json_encode($res);
    exit;
}

// ================================================================
// Ambil data rekam medis untuk edit
// ================================================================
if (!isset($_GET['id'])) {
    echo "<script>alert('ID Rekam Medis tidak ditemukan'); window.location='daftar_rekam_medis.php';</script>";
    exit;
}
$id_rekam = (int) $_GET['id'];

$sql = "SELECT rm.*, p.nama_pasien, p.tanggal_lahir, p.no_rm
        FROM rekam_medis rm
        JOIN pasien p ON rm.id_pasien = p.id_pasien
        WHERE rm.id_rekam = $id_rekam
        LIMIT 1";
$q = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($q);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan'); window.location='daftar_rekam_medis.php';</script>";
    exit;
}

// Ambil resep lama (untuk prefill)
$obat_lama = [];
$ro = mysqli_query($conn, "SELECT id_obat, jumlah, keterangan FROM resep_obat WHERE id_rekam = $id_rekam");
while ($r = mysqli_fetch_assoc($ro)) {
    $obat_lama[$r['id_obat']] = [
        'jumlah' => $r['jumlah'],
        'keterangan' => $r['keterangan'] ?? ''
    ];
}

// ================================================================
// Update data ketika form di-submit
// ================================================================
$popup_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pasien   = (int)($_POST['id_pasien'] ?? 0);
    $id_penyakit = (int)($_POST['id_penyakit'] ?? 0);
    $terapi      = mysqli_real_escape_string($conn, $_POST['terapi'] ?? '-');
    $obatList    = $_POST['obat'] ?? []; 
    $catatan_raw = $_POST['catatan'] ?? '';
    $catatan     = mysqli_real_escape_string($conn, $catatan_raw);
    $tanggal_kunjungan = $_POST['tanggal_kunjungan'] ?? date('Y-m-d');

    $sql_upd = "UPDATE rekam_medis SET
                    id_pasien = $id_pasien,
                    id_penyakit = $id_penyakit,
                    tanggal = '$tanggal_kunjungan',
                    terapi = '$terapi',
                    keterangan = '$catatan'
                WHERE id_rekam = $id_rekam";
    $query = mysqli_query($conn, $sql_upd);

    if ($query) {
        // Hapus resep lama
        mysqli_query($conn, "DELETE FROM resep_obat WHERE id_rekam = $id_rekam");

        $total_biaya = 0;

        // Insert resep baru
        if ($terapi === "Obat" && !empty($obatList)) {
            foreach ($obatList as $id_obat => $detail) {
                if (!isset($detail['id'])) continue;

                $id_obat_val = (int)$detail['id'];
                $jumlah      = !empty($detail['jumlah']) ? (int)$detail['jumlah'] : 1;
                $ket         = isset($detail['keterangan']) ? mysqli_real_escape_string($conn, $detail['keterangan']) : '';

                $getHarga = mysqli_query($conn, "SELECT harga_per_satuan, stok_akhir 
                                                 FROM obat WHERE id_obat = $id_obat_val LIMIT 1");
                $hargaRow = mysqli_fetch_assoc($getHarga);
                if (!$hargaRow) continue;

                if ($hargaRow['stok_akhir'] < $jumlah) {
                    echo "<script>alert('Stok obat $id_obat_val tidak mencukupi! Sisa stok: ".$hargaRow['stok_akhir']."'); window.location='edit_rekam_medis.php?id=$id_rekam';</script>";
                    exit;
                }

                $harga = (int)$hargaRow['harga_per_satuan'];
                $subtotal = $jumlah * $harga;
                $total_biaya += $subtotal;

                mysqli_query($conn, "INSERT INTO resep_obat 
                    (id_rekam, id_obat, jumlah, harga_satuan, keterangan) 
                    VALUES ($id_rekam, $id_obat_val, $jumlah, $harga, '$ket')");
            }
        }

        mysqli_query($conn, "UPDATE rekam_medis SET total_biaya = $total_biaya WHERE id_rekam = $id_rekam");

        $popup_success = true; // ✅ trigger sweetalert sukses
    } else {
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($conn) . "');</script>";
    }
}

// ================================================================
// Dropdown data pasien & penyakit
// ================================================================
$pasien_q = mysqli_query($conn, "SELECT p.id_pasien, k.nik_karyawan, p.hubungan, p.nama_pasien
                                FROM pasien p
                                JOIN karyawan k ON p.id_karyawan = k.id_karyawan
                                ORDER BY k.nik_karyawan ASC");
$pasien_list = [];
while ($r = mysqli_fetch_assoc($pasien_q)) $pasien_list[] = $r;

$penyakit_q = mysqli_query($conn, "SELECT * FROM penyakit ORDER BY nama_penyakit ASC");
$penyakit_list = [];
while ($r = mysqli_fetch_assoc($penyakit_q)) $penyakit_list[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Edit Rekam Medis</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    body { margin: 0; font-family: Arial, sans-serif; }
    .main-content { margin-left: 250px; padding: 20px; }
    .obat-row { border-bottom: 1px solid #ddd; padding-bottom: 8px; margin-bottom: 8px; }
  </style>
</head>
<body>

<?php include '../sidebar.php'; ?>

<div class="main-content">
  <h2 class="mb-4">Edit Rekam Medis</h2>

  <form method="POST" action="">
    <!-- No RM -->
    <div class="mb-3">
      <label class="form-label">No RM</label>
      <input type="text" value="<?= htmlspecialchars($data['no_rm']) ?>" class="form-control" readonly>
    </div>

    <!-- Pasien -->
    <div class="mb-3">
      <label class="form-label">Pilih Pasien</label>
      <select name="id_pasien" id="id_pasien" class="form-select" required>
        <option value="">-- Pilih Pasien --</option>
        <?php foreach ($pasien_list as $p): ?>
          <option value="<?= $p['id_pasien'] ?>" <?= $p['id_pasien'] == $data['id_pasien'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['nik_karyawan'] . ' - ' . $p['hubungan'] . ' (' . $p['nama_pasien'] . ')') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Nama Pasien -->
    <div class="mb-3">
      <label class="form-label">Nama Pasien</label>
      <input type="text" value="<?= htmlspecialchars($data['nama_pasien']) ?>" class="form-control" readonly>
    </div>

    <!-- Tanggal Lahir -->
    <div class="mb-3">
      <label class="form-label">Tanggal Lahir</label>
      <input type="date" value="<?= htmlspecialchars($data['tanggal_lahir']) ?>" class="form-control" readonly>
    </div>

    <!-- Diagnosa -->
    <div class="mb-3">
      <label class="form-label">Diagnosa / Penyakit</label>
      <select name="id_penyakit" id="diagnosa" class="form-select" required onchange="loadObat(this.value)">
        <option value="">-- Pilih Diagnosa --</option>
        <?php foreach ($penyakit_list as $d): ?>
          <option value="<?= $d['id_penyakit'] ?>" <?= $d['id_penyakit'] == $data['id_penyakit'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['nama_penyakit']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Terapi -->
    <div class="mb-3">
      <label class="form-label">Terapi</label>
      <select name="terapi" id="terapi" class="form-select" onchange="toggleObat()" required>
        <option value="">-- Pilih Terapi --</option>
        <option value="Obat" <?= $data['terapi'] == "Obat" ? 'selected' : '' ?>>Obat</option>
        <option value="Lab" <?= $data['terapi'] == "Lab" ? 'selected' : '' ?>>Lab</option>
      </select>
    </div>

    <!-- Obat -->
    <div class="mb-3" id="obat-wrapper" style="display:none;">
      <label class="form-label">Obat (pilih lebih dari 1)</label>
      <div id="obat-list"></div>
    </div>

    <!-- Catatan -->
    <div class="mb-3">
      <label class="form-label">Catatan</label>
      <textarea name="catatan" class="form-control" rows="3" placeholder="Tambahkan catatan jika ada"><?= htmlspecialchars($data['keterangan'] ?? '') ?></textarea>
    </div>

    <!-- Tanggal Kunjungan -->
    <div class="mb-3">
      <label class="form-label">Tanggal Kunjungan</label>
      <input type="date" name="tanggal_kunjungan" class="form-control" value="<?= htmlspecialchars($data['tanggal'] ?? date('Y-m-d')) ?>" required>
    </div>

    <!-- Tombol -->
    <button type="submit" class="btn btn-warning">Update</button>
    <a href="daftar_rekam_medis.php" class="btn btn-secondary">Batal</a>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let resepLama = <?= json_encode($obat_lama) ?>;

function toggleObat() {
  let terapi = $('#terapi').val();
  if (terapi === 'Obat') {
    $('#obat-wrapper').show();
    let diagnosa = $('#diagnosa').val();
    if (diagnosa) loadObat(diagnosa);
  } else {
    $('#obat-wrapper').hide();
    $('#obat-list').html('');
  }
}

function loadObat(id_penyakit) {
  let terapi = $('#terapi').val();
  if (!id_penyakit || terapi !== 'Obat') {
    $('#obat-wrapper').hide();
    $('#obat-list').html('');
    return;
  }

  $.get('<?= basename(__FILE__) ?>?get_obat=' + encodeURIComponent(id_penyakit), function(data) {
    if (!Array.isArray(data) || data.length === 0) {
      $('#obat-list').html('<div class="text-muted">Tidak ada obat untuk diagnosa ini.</div>');
      $('#obat-wrapper').show();
      return;
    }
    let inputs = '';
    data.forEach(function(o) {
      let checked = resepLama[o.id_obat] ? 'checked' : '';
      let jumlah = resepLama[o.id_obat] ? resepLama[o.id_obat].jumlah : '';
      let ket    = resepLama[o.id_obat] ? resepLama[o.id_obat].keterangan : '';
      inputs += `
        <div class="row obat-row align-items-center">
          <div class="col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="obat[${o.id_obat}][id]" value="${o.id_obat}" id="obat_${o.id_obat}" ${checked}>
              <label class="form-check-label" for="obat_${o.id_obat}">${o.nama_obat}</label>
            </div>
          </div>
          <div class="col-md-2">
            <input type="number" name="obat[${o.id_obat}][jumlah]" class="form-control" placeholder="Jumlah" min="1" value="${jumlah}">
          </div>
          <div class="col-md-6">
            <input type="text" name="obat[${o.id_obat}][keterangan]" class="form-control" placeholder="Aturan pakai" value="${ket}">
          </div>
        </div>
      `;
    });
    $('#obat-list').html(inputs);
    $('#obat-wrapper').show();
  }, 'json');
}

$(document).ready(function() {
  if ($('#terapi').val() === 'Obat' && $('#diagnosa').val()) {
    loadObat($('#diagnosa').val());
  }
});
</script>

<?php if ($popup_success): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Data rekam medis berhasil diperbarui',
        showConfirmButton: false,
        timer: 2000
    }).then(() => {
        window.location.href = "daftar_rekam_medis.php";
    });
});
</script>
<?php endif; ?>
</body>
</html>
