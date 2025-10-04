<?php
session_start();
include '../koneksi.php';

// ==== SIMPAN DATA ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pasien         = (int)$_POST['id_pasien'];
    $id_penyakit       = (int)$_POST['id_penyakit'];
    $terapi            = !empty($_POST['terapi']) ? $_POST['terapi'] : '-';
    $obatList          = !empty($_POST['obat']) ? $_POST['obat'] : [];
    $catatan           = !empty($_POST['catatan']) ? mysqli_real_escape_string($conn, $_POST['catatan']) : null;
    $tanggal_kunjungan = $_POST['tanggal_kunjungan'];
    $id_user           = 1; // TODO: sesuaikan dengan user login

    // Ambil data pasien
    $pasien = mysqli_query($conn, "SELECT no_rm FROM pasien WHERE id_pasien='$id_pasien'");
    $rowPasien = mysqli_fetch_assoc($pasien);
    if (!$rowPasien) {
        $_SESSION['error'] = "Pasien tidak ditemukan";
        header("Location: tambah_rekam_medis.php");
        exit;
    }

    // Cari id_kunjungan terakhir
    $qKunjungan = mysqli_query($conn, "
        SELECT id_kunjungan 
        FROM kunjungan 
        WHERE id_pasien='$id_pasien' AND DATE(tanggal_kunjungan)='$tanggal_kunjungan'
        ORDER BY id_kunjungan DESC LIMIT 1
    ");
    $rowKunjungan = mysqli_fetch_assoc($qKunjungan);
    $id_kunjungan = $rowKunjungan['id_kunjungan'] ?? null;

    // Insert rekam medis
    $sql = "INSERT INTO rekam_medis 
            (id_pasien, id_kunjungan, id_user, id_penyakit, terapi, keterangan, tanggal, created_at) 
            VALUES ('$id_pasien', " . ($id_kunjungan ? "'$id_kunjungan'" : "NULL") . ", 
                    '$id_user', '$id_penyakit', '$terapi', " . ($catatan ? "'$catatan'" : "NULL") . ",
                    '$tanggal_kunjungan', NOW())";
    $query = mysqli_query($conn, $sql);

    if ($query) {
        $id_rekam = mysqli_insert_id($conn); 
        $total_biaya = 0;

        // Jika terapi obat
        if ($terapi === 'Obat' && !empty($obatList)) {
            foreach ($obatList as $id_obat => $detail) {
                if (!isset($detail['id'])) continue;

                $id_obat_val = (int) $detail['id'];
                $jumlah      = !empty($detail['jumlah']) ? (int)$detail['jumlah'] : 1;
                $ket         = !empty($detail['keterangan']) ? mysqli_real_escape_string($conn, $detail['keterangan']) : null;

                // Cek stok & harga
                $qHarga = mysqli_query($conn, "SELECT harga_per_satuan, stok_akhir FROM obat WHERE id_obat='$id_obat_val' LIMIT 1");
                $rowHarga = mysqli_fetch_assoc($qHarga);
                if (!$rowHarga) continue;

                if ($rowHarga['stok_akhir'] < $jumlah) {
                    $_SESSION['error'] = "Stok obat tidak mencukupi! Sisa stok: {$rowHarga['stok_akhir']}";
                    header("Location: tambah_rekam_medis.php");
                    exit;
                }

                $harga_satuan = $rowHarga['harga_per_satuan'];
                $subtotal = $jumlah * $harga_satuan;
                $total_biaya += $subtotal;

                // Insert resep_obat
                mysqli_query($conn, "INSERT INTO resep_obat 
                    (id_rekam, id_obat, jumlah, harga_satuan, keterangan) 
                    VALUES ('$id_rekam', '$id_obat_val', '$jumlah', '$harga_satuan', " . ($ket ? "'$ket'" : "NULL") . ")");
            }

            // Update total biaya
            mysqli_query($conn, "UPDATE rekam_medis SET total_biaya = $total_biaya WHERE id_rekam = '$id_rekam'");
        }

        $_SESSION['success'] = "Rekam medis berhasil disimpan";
        header("Location: daftar_rekam_medis.php");
        exit;
    } else {
        $_SESSION['error'] = "Gagal menyimpan rekam medis: " . mysqli_error($conn);
        header("Location: tambah_rekam_medis.php");
        exit;
    }
}

// ==== AJAX GET DATA PASIEN ====
if (isset($_GET['get_pasien'])) {
    $id_pasien = $_GET['get_pasien'];
    $res = ['nama_pasien' => '', 'tanggal_lahir' => '', 'no_rm' => '', 'tanggal_kunjungan' => ''];
    
    $q = mysqli_query($conn, "SELECT nama_pasien, tanggal_lahir, no_rm FROM pasien WHERE id_pasien = '$id_pasien'");
    if ($row = mysqli_fetch_assoc($q)) {
        $res['nama_pasien']   = $row['nama_pasien'];
        $res['tanggal_lahir'] = $row['tanggal_lahir'];
        $res['no_rm']         = $row['no_rm'];
    }

    $q2 = mysqli_query($conn, "SELECT DATE(tanggal_kunjungan) as tgl 
                               FROM kunjungan 
                               WHERE id_pasien = '$id_pasien'
                               ORDER BY tanggal_kunjungan DESC LIMIT 1");
    if ($row2 = mysqli_fetch_assoc($q2)) {
        $res['tanggal_kunjungan'] = $row2['tgl'];
    }

    echo json_encode($res);
    exit;
}

// ==== AJAX GET OBAT ====
if (isset($_GET['get_obat'])) {
    $id_penyakit = (int) $_GET['get_obat'];
    $res = [];
    $q = mysqli_query($conn, "SELECT o.id_obat, o.nama_obat 
                              FROM obat o
                              JOIN penyakit_obat po ON o.id_obat = po.id_obat
                              WHERE po.id_penyakit = $id_penyakit
                              ORDER BY o.nama_obat ASC");
    while ($row = mysqli_fetch_assoc($q)) {
        $res[] = $row;
    }
    echo json_encode($res);
    exit;
}

// ==== AMBIL DATA UNTUK DROPDOWN ====
$pasien = mysqli_query($conn, "SELECT p.id_pasien, k.nik_karyawan, p.hubungan 
                              FROM pasien p 
                              JOIN karyawan k ON p.id_karyawan = k.id_karyawan 
                              ORDER BY k.nik_karyawan ASC");

$penyakit = mysqli_query($conn, "SELECT * FROM penyakit ORDER BY nama_penyakit ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tambah Rekam Medis</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .swal2-popup { font-family: inherit !important; }
    .main-content { margin-left: 250px; padding: 20px; }
  </style>
</head>
<body>

<?php include '../sidebar.php'; ?>

<div class="main-content">
  <h2 class="mb-4">Tambah Rekam Medis</h2>

  <?php if (isset($_SESSION['error'])): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: '<?= $_SESSION['error'] ?>'
      });
    </script>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <form method="POST" action="">
    <!-- No RM -->
    <div class="mb-3">
      <label class="form-label">No RM</label>
      <input type="text" id="no_rm_display" class="form-control" readonly>
      <input type="hidden" name="no_rm" id="no_rm">
    </div>

    <!-- Pasien -->
    <div class="mb-3">
      <label class="form-label">Pilih Pasien</label>
      <select name="id_pasien" id="id_pasien" class="form-select" onchange="getPasien(this.value)" required>
        <option value="">-- Pilih Pasien --</option>
        <?php while ($p = mysqli_fetch_assoc($pasien)): ?>
          <option value="<?= $p['id_pasien'] ?>"><?= $p['nik_karyawan'] ?> - <?= $p['hubungan'] ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <!-- Nama Pasien -->
    <div class="mb-3">
      <label class="form-label">Nama Pasien</label>
      <input type="text" id="nama" class="form-control" readonly>
    </div>

    <!-- Tanggal Lahir -->
    <div class="mb-3">
      <label class="form-label">Tanggal Lahir</label>
      <input type="date" id="tgl_lahir" class="form-control" readonly>
    </div>

    <!-- Diagnosa -->
    <div class="mb-3">
      <label class="form-label">Diagnosa / Penyakit</label>
      <select name="id_penyakit" id="diagnosa" class="form-select" required onchange="loadObat(this.value)">
        <option value="">-- Pilih Diagnosa --</option>
        <?php while ($d = mysqli_fetch_assoc($penyakit)): ?>
          <option value="<?= $d['id_penyakit'] ?>"><?= $d['nama_penyakit'] ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <!-- Terapi -->
    <div class="mb-3">
      <label class="form-label">Terapi</label>
      <select name="terapi" id="terapi" class="form-select" onchange="toggleObat()" required>
        <option value="">-- Pilih Terapi --</option>
        <option value="Obat">Obat</option>
        <option value="Lab">Lab</option>
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
      <textarea name="catatan" class="form-control" rows="3"></textarea>
    </div>

    <!-- Tanggal Kunjungan -->
    <div class="mb-3">
      <label class="form-label">Tanggal Kunjungan</label>
      <input type="date" name="tanggal_kunjungan" id="tanggal_kunjungan" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="daftar_rekam_medis.php" class="btn btn-secondary">Batal</a>
  </form>
</div>

<script>
function getPasien(id) {
  if (id === '') return;
  $.get("tambah_rekam_medis.php?get_pasien=" + id, function(data) {
    let res = JSON.parse(data);
    if (res) {
      $('#nama').val(res.nama_pasien);
      $('#tgl_lahir').val(res.tanggal_lahir);
      $('#no_rm_display').val(res.no_rm);
      $('#no_rm').val(res.no_rm);
      if (res.tanggal_kunjungan) {
        $('#tanggal_kunjungan').val(res.tanggal_kunjungan);
      }
    }
  });
}

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

  $.get('tambah_rekam_medis.php?get_obat=' + id_penyakit, function(data) {
    let obatList = JSON.parse(data);
    let inputs = '';
    obatList.forEach(function(o) {
      inputs += `
        <div class="row mb-2 align-items-center">
          <div class="col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="obat[${o.id_obat}][id]" value="${o.id_obat}" id="obat_${o.id_obat}">
              <label class="form-check-label" for="obat_${o.id_obat}">${o.nama_obat}</label>
            </div>
          </div>
          <div class="col-md-2">
            <input type="number" name="obat[${o.id_obat}][jumlah]" class="form-control" placeholder="Jumlah" min="1">
          </div>
          <div class="col-md-4">
            <input type="text" name="obat[${o.id_obat}][keterangan]" class="form-control" placeholder="Aturan pakai">
          </div>
        </div>
      `;
    });
    $('#obat-list').html(inputs);
    $('#obat-wrapper').show();
  });
}
</script>

</body>
</html>
