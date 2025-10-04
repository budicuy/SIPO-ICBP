<?php
include '../../koneksi.php';

// Ambil data karyawan
$karyawanResult = mysqli_query($conn, "SELECT id_karyawan, nik_karyawan, nama_karyawan, tanggal_lahir, jenis_kelamin, alamat FROM karyawan");

// Array hubungan
$hubunganList = ['Karyawan', 'Istri', 'Suami', 'Anak'];

// Fungsi generate No RM
function generateNoRM($conn) {
    $bulan = date('m');
    $tahun = date('Y');
    $q = "SELECT MAX(CAST(SUBSTRING_INDEX(no_rm, '/', 1) AS UNSIGNED)) AS last_num FROM pasien";
    $result = mysqli_query($conn, $q);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    $lastNum = ($row && $row['last_num']) ? intval($row['last_num']) : 0;
    $newNum = $lastNum + 1;
    return str_pad($newNum, 4, '0', STR_PAD_LEFT) . "/NDL/BJM/$bulan/$tahun";
}

// Flag untuk SweetAlert
$successMsg = false;
$errorMsg   = "";

// Proses simpan data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_karyawan = (int)($_POST['id_karyawan'] ?? 0);
    $hubungan = mysqli_real_escape_string($conn, $_POST['hubungan'] ?? '');
    $tanggal_daftar = $_POST['tanggal_daftar'] ?? date('Y-m-d');

    // Ambil data pasien sesuai hubungan
    if ($hubungan === 'Karyawan') {
        $resKar = mysqli_query($conn, "SELECT nama_karyawan, tanggal_lahir, jenis_kelamin, alamat 
                                       FROM karyawan WHERE id_karyawan = $id_karyawan LIMIT 1");
        if (!$resKar || mysqli_num_rows($resKar) === 0) {
            $errorMsg = "Data karyawan tidak ditemukan.";
        } else {
            $kar = mysqli_fetch_assoc($resKar);
            $nama_pasien   = mysqli_real_escape_string($conn, $kar['nama_karyawan']);
            $tanggal_lahir = $kar['tanggal_lahir'];
            $jenis_kelamin = $kar['jenis_kelamin'];
            $alamat        = mysqli_real_escape_string($conn, $kar['alamat']);
        }
    } else {
        $nama_pasien   = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
        $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
        $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin'] ?? '');
        $alamat        = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
    }

    // Validasi enum jenis kelamin
    if (!in_array($jenis_kelamin, ['Laki - Laki','Perempuan'])) {
        $jenis_kelamin = null;
    }

    if (!$errorMsg) {
        // Cek apakah pasien sudah ada
        $cekSql = "SELECT id_pasien, no_rm FROM pasien 
                WHERE id_karyawan=$id_karyawan 
                AND hubungan='$hubungan' 
                AND nama_pasien='$nama_pasien' 
                LIMIT 1";
        $cekRes = mysqli_query($conn, $cekSql);

        if ($cekRes && mysqli_num_rows($cekRes) > 0) {
            // Pasien lama
            $row = mysqli_fetch_assoc($cekRes);
            $id_pasien = $row['id_pasien'];
        } else {
            // Pasien baru
            $no_rm = generateNoRM($conn);
            $sql = "INSERT INTO pasien (no_rm, id_karyawan, nama_pasien, tanggal_lahir, jenis_kelamin, alamat, hubungan, tanggal_daftar) 
                    VALUES (
                        '$no_rm',
                        '$id_karyawan',
                        '$nama_pasien',
                        " . ($tanggal_lahir ? "'$tanggal_lahir'" : "NULL") . ",
                        " . ($jenis_kelamin ? "'$jenis_kelamin'" : "NULL") . ",
                        '$alamat',
                        '$hubungan',
                        '$tanggal_daftar'
                    )";
            if (!mysqli_query($conn, $sql)) {
                $errorMsg = "Error insert pasien: " . mysqli_error($conn);
            } else {
                $id_pasien = mysqli_insert_id($conn);
            }
        }

        if (!$errorMsg) {
            // Ambil NIK karyawan
            $qNik = mysqli_query($conn, "SELECT nik_karyawan FROM karyawan WHERE id_karyawan=$id_karyawan LIMIT 1");
            $dNik = mysqli_fetch_assoc($qNik);
            $nik = $dNik['nik_karyawan'] ?? '';

            // Buat kode transaksi: NIK-DMY
            $kode_transaksi = $nik . "-" . date('dmY', strtotime($tanggal_daftar));

            // Insert kunjungan
            $sqlKunjungan = "INSERT INTO kunjungan (id_pasien, kode_transaksi, tanggal_kunjungan) 
                            VALUES ($id_pasien, '$kode_transaksi', '$tanggal_daftar')";
            if (mysqli_query($conn, $sqlKunjungan)) {
                $successMsg = true;
            } else {
                $errorMsg = "Error insert kunjungan: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Pasien</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  body { margin: 0; font-family: Arial, sans-serif; }
  .main-content { margin-left: 250px; padding: 20px; }
  input[readonly], textarea[readonly] { background-color: #e9ecef !important; }
  select:disabled { background-color: #e9ecef !important; }
</style>
<script>
function updateFieldsFromKaryawan() {
    const selK = document.getElementById('id_karyawan');
    const opt = selK.selectedOptions[0];
    if (!opt) return;
    document.getElementById('nama').value = opt.getAttribute('data-nama') || '';
    document.getElementById('tanggal_lahir').value = opt.getAttribute('data-tgl') || '';
    document.getElementById('jenis_kelamin_view').value = opt.getAttribute('data-jk') || '';
    document.getElementById('jenis_kelamin').value = opt.getAttribute('data-jk') || '';
    document.getElementById('alamat').value = opt.getAttribute('data-alamat') || '';
}
function setModeByHubungan() {
    const hubungan = document.getElementById('hubungan').value;
    const nama = document.getElementById('nama');
    const tgl = document.getElementById('tanggal_lahir');
    const alamat = document.getElementById('alamat');
    const selJKView = document.getElementById('jenis_kelamin_view');
    const jkHidden = document.getElementById('jenis_kelamin');

    if (hubungan === 'Karyawan') {
        updateFieldsFromKaryawan();
        nama.readOnly = true;
        tgl.readOnly = true;
        alamat.readOnly = true;
        selJKView.disabled = true;
        jkHidden.value = selJKView.value;
    } else {
        nama.readOnly = false;
        tgl.readOnly = false;
        alamat.readOnly = false;
        selJKView.disabled = false;
        jkHidden.value = '';
        nama.value = '';
        tgl.value = '';
        selJKView.value = '';
        alamat.value = '';
    }
}
function onKaryawanChange() {
    if (document.getElementById('hubungan').value === 'Karyawan') {
        updateFieldsFromKaryawan();
    }
}
window.addEventListener('DOMContentLoaded', function() {
    document.getElementById('hubungan').addEventListener('change', setModeByHubungan);
    document.getElementById('id_karyawan').addEventListener('change', onKaryawanChange);
    document.getElementById('jenis_kelamin_view').addEventListener('change', function(){
        document.getElementById('jenis_kelamin').value = this.value;
    });
    setModeByHubungan();
});
</script>
</head>
<body>
<?php include '../../sidebar.php'; ?>
<div class="main-content">
  <h2 class="mb-4">Tambah Pasien</h2>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">NIK Karyawan (Penanggung Jawab)</label>
      <select name="id_karyawan" id="id_karyawan" class="form-control" required>
        <option value="">-- Pilih Karyawan --</option>
        <?php
        if ($karyawanResult) {
            mysqli_data_seek($karyawanResult, 0);
            while ($row = mysqli_fetch_assoc($karyawanResult)) {
                $dataNama = htmlspecialchars($row['nama_karyawan'], ENT_QUOTES);
                $dataTgl  = htmlspecialchars($row['tanggal_lahir'], ENT_QUOTES);
                $dataJk   = htmlspecialchars($row['jenis_kelamin'], ENT_QUOTES);
                $dataAl   = htmlspecialchars($row['alamat'], ENT_QUOTES);
                echo "<option value=\"{$row['id_karyawan']}\" data-nama=\"{$dataNama}\" data-tgl=\"{$dataTgl}\" data-jk=\"{$dataJk}\" data-alamat=\"{$dataAl}\">"
                   . htmlspecialchars($row['nik_karyawan'] . " - " . $row['nama_karyawan'], ENT_QUOTES)
                   . "</option>";
            }
        }
        ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Hubungan</label>
      <select name="hubungan" id="hubungan" class="form-control" required>
        <option value="">-- Pilih Hubungan --</option>
        <?php foreach ($hubunganList as $h) {
            echo '<option value="' . htmlspecialchars($h, ENT_QUOTES) . '">' . htmlspecialchars($h) . '</option>';
        } ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Nama Pasien</label>
      <input type="text" id="nama" name="nama" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Jenis Kelamin</label>
      <select id="jenis_kelamin_view" name="jenis_kelamin_view" class="form-control" required>
        <option value="">-- Pilih Jenis Kelamin --</option>
        <option value="Laki - Laki">Laki - Laki</option>
        <option value="Perempuan">Perempuan</option>
      </select>
      <input type="hidden" name="jenis_kelamin" id="jenis_kelamin" value="">
    </div>

    <div class="mb-3">
      <label class="form-label">Tanggal Lahir</label>
      <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control">
    </div>

    <div class="mb-3">
      <label class="form-label">Alamat</label>
      <textarea id="alamat" name="alamat" class="form-control"></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Tanggal Daftar</label>
      <input type="date" name="tanggal_daftar" id="tanggal_daftar" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>

    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="pasien.php" class="btn btn-secondary">Batal</a>
  </form>
</div>

<?php if ($successMsg): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: 'Pasien baru berhasil ditambahkan!',
}).then(() => {
    window.location.href = 'pasien.php';
});
</script>
<?php elseif ($errorMsg): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Gagal!',
    text: '<?= addslashes($errorMsg) ?>',
});
</script>
<?php endif; ?>

</body>
</html>
