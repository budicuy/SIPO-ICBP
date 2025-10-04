<?php
session_start();
include '../koneksi.php';
include '../sidebar.php';

// =======================
// SWEETALERT NOTIFIKASI
// =======================
if (isset($_SESSION['rekam_sukses'])) {
    $no_rm = $_SESSION['rekam_sukses'];
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Rekam medis No RM: $no_rm berhasil ditambahkan'
        });
    </script>";
    unset($_SESSION['rekam_sukses']);
}

if (isset($_SESSION['hapus_sukses'])) {
    $msg = $_SESSION['hapus_sukses'];
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '$msg'
        });
    </script>";
    unset($_SESSION['hapus_sukses']);
}

if (isset($_SESSION['hapus_error'])) {
    $msg = $_SESSION['hapus_error'];
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: '$msg'
        });
    </script>";
    unset($_SESSION['hapus_error']);
}

// =======================
// FILTER TANGGAL
// =======================
$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';

$where = [];
if ($tanggal_awal) {
    $where[] = "DATE(rm.tanggal) >= '$tanggal_awal'";
}
if ($tanggal_akhir) {
    $where[] = "DATE(rm.tanggal) <= '$tanggal_akhir'";
}
$whereSql = $where ? "WHERE ".implode(" AND ", $where) : "";

// =======================
// QUERY DATA
// =======================
$query = "
SELECT
    rm.id_rekam,
    rm.id_pasien,
    p.no_rm,
    k.nik_karyawan AS nik,
    p.nama_pasien,
    p.hubungan,
    COALESCE(py.nama_penyakit, '-') AS penyakit,
    rm.terapi,
    COALESCE(GROUP_CONCAT(o.nama_obat SEPARATOR ', '), '-') AS nama_obat,
    COALESCE(rm.keterangan, '-') AS keterangan,
    rm.tanggal AS tanggal,
    CASE 
        WHEN (SELECT MAX(rm2.tanggal) 
              FROM rekam_medis rm2 
              WHERE rm2.id_pasien = p.id_pasien) >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) 
        THEN 'Aktif'
        ELSE 'Nonaktif'
    END AS status_pasien
FROM rekam_medis rm
LEFT JOIN pasien p ON rm.id_pasien = p.id_pasien
LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan
LEFT JOIN penyakit py ON rm.id_penyakit = py.id_penyakit
LEFT JOIN resep_obat ro ON rm.id_rekam = ro.id_rekam
LEFT JOIN obat o ON ro.id_obat = o.id_obat
$whereSql
GROUP BY rm.id_rekam
ORDER BY rm.tanggal DESC
";
$result = mysqli_query($conn, $query) or die('Query error: '.mysqli_error($conn));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Rekam Medis</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { background-color:#f8f9fa; }
.main-content { margin-left:220px; padding:30px; }
.table-responsive { overflow-x:auto; }
.btn-sm { padding:4px 10px !important; font-size:0.85rem !important; }
@media (max-width: 992px) { .main-content { margin-left:0; } }
</style>
</head>
<body>

<div class="main-content">
<h2 class="mb-4">Daftar Rekam Medis</h2>

<a href="tambah_rekam_medis.php" class="btn btn-success mb-3">+ Tambah Rekam Medis</a>

<!-- Filter -->
<form method="GET" class="row g-3 mb-3">
  <div class="col-auto">
    <label class="form-label">Dari Tanggal:</label>
    <input type="date" name="tanggal_awal" class="form-control" value="<?= htmlspecialchars($tanggal_awal) ?>">
  </div>
  <div class="col-auto">
    <label class="form-label">Sampai Tanggal:</label>
    <input type="date" name="tanggal_akhir" class="form-control" value="<?= htmlspecialchars($tanggal_akhir) ?>">
  </div>
  <div class="col-auto align-self-end">
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="daftar_rekam_medis.php" class="btn btn-secondary">Reset</a>
  </div>
</form>

<div class="table-responsive">
<table id="tabelRekamMedis" class="table table-striped table-bordered align-middle">
  <thead class="table-dark text-center">
    <tr>
      <th>No</th>
      <th>No RM</th>
      <th>NIK</th>
      <th>Keterangan NIK</th>
      <th>Nama Pasien</th>
      <th>Penyakit</th>
      <th>Terapi</th>
      <th>Obat</th>
      <th>Catatan</th>
      <th>Tanggal</th>
      <th>Detail</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php while($row = mysqli_fetch_assoc($result)): ?>
    <tr>
      <td></td>
      <td><?= htmlspecialchars($row['no_rm'] ?? '-') ?></td>
      <td><?= htmlspecialchars($row['nik'] ?? '-') ?></td>
      <td><?= htmlspecialchars($row['hubungan'] ?? '-') ?></td>
      <td><?= htmlspecialchars($row['nama_pasien'] ?? '-') ?></td>
      <td><?= htmlspecialchars($row['penyakit']) ?></td>
      <td><?= htmlspecialchars($row['terapi'] ?? '-') ?></td>
      <td><?= htmlspecialchars($row['nama_obat'] ?? '-') ?></td>
      <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
      <td><?= ($row['tanggal'] ? date('d-m-Y', strtotime($row['tanggal'])) : '-') ?></td>
      <td class="text-center">
        <a href="../kunjungan/detail_kunjungan.php?id_pasien=<?= $row['id_pasien'] ?>" class="btn btn-sm btn-info">
          <i class="bi bi-eye"></i>
        </a>
      </td>
      <td class="text-center">
        <?= $row['status_pasien']==='Aktif' 
            ? "<span class='badge bg-success'>Aktif</span>" 
            : "<span class='badge bg-secondary'>Nonaktif</span>" ?>
<td class="text-center" style="white-space:nowrap;">
  <div class="d-flex justify-content-center gap-2">
    <a href="edit_rekam_medis.php?id=<?= $row['id_rekam'] ?>" class="btn btn-warning btn-sm">Edit</a>
    <a href="hapus_rekam_medis.php?id=<?= $row['id_rekam'] ?>" class="btn btn-danger btn-sm btn-hapus">Hapus</a>
  </div>
</td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
  var t = $('#tabelRekamMedis').DataTable({
    responsive: true,
    lengthMenu: [[50,100,200,500,-1],[50,100,200,500,"Semua"]],
    columnDefs: [{ orderable:false, targets:0 }],
    order: [[9,'desc']],
    drawCallback: function(){
      var api = this.api();
      api.column(0,{search:'applied',order:'applied'}).nodes().each(function(cell,i){
        cell.innerHTML = i+1;
      });
    }
  });

  // KONFIRMASI HAPUS
  $(document).on('click', '.btn-hapus', function(e){
    e.preventDefault();
    var url = $(this).attr('href');
    Swal.fire({
      title: 'Yakin hapus data?',
      text: 'Data tidak bisa dikembalikan!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya, hapus!'
    }).then((result)=>{
      if(result.isConfirmed){
        window.location.href = url;
      }
    });
  });

    <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted') { ?>
    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Data rekam medis berhasil dihapus',
      showConfirmButton: false,
      timer: 2000
    });
  <?php } ?>


});



</script>
</body>
</html>
