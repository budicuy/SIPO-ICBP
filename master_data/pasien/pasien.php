<?php
include '../../koneksi.php';
include '../../sidebar.php';

// Ambil filter dari GET
$filter_hubungan = $_GET['hubungan'] ?? '';

// Kondisi filter
$where = [];
if ($filter_hubungan) {
    $where[] = "p.hubungan = '" . mysqli_real_escape_string($conn, $filter_hubungan) . "'";
}
$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Query pasien + kunjungan terakhir
$query = "
  SELECT p.id_pasien, 
         p.no_rm,
         k.nik_karyawan AS nik, 
         COALESCE(p.nama_pasien, k.nama_karyawan) AS nama,
         p.tanggal_lahir, 
         p.jenis_kelamin, 
         p.alamat, 
         p.hubungan,
         kj.kode_transaksi,
         kj.tanggal_kunjungan
  FROM pasien p
  JOIN karyawan k ON p.id_karyawan = k.id_karyawan
  LEFT JOIN (
      SELECT k1.*
      FROM kunjungan k1
      INNER JOIN (
          SELECT id_pasien, MAX(id_kunjungan) AS last_id
          FROM kunjungan
          GROUP BY id_pasien
      ) k2 ON k1.id_pasien = k2.id_pasien AND k1.id_kunjungan = k2.last_id
  ) kj ON kj.id_pasien = p.id_pasien
  $whereSql
  ORDER BY nama ASC
";


$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Pasien Klinik</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { background-color: #f8f9fa; }
    .main-content { margin-left: 220px; padding: 30px; }
    @media (max-width: 992px) { .main-content { margin-left: 0; } }
  </style>
</head>
<body>

<div class="main-content">
  <h2 class="mb-4">DATA PASIEN</h2>

  <a href="tambah_pasien.php" class="btn btn-success mb-3">+ Tambah Pasien</a>

  <!-- Filter -->
  <form method="get" class="row g-2 mb-4">
    <div class="col-md-6">
      <select name="hubungan" class="form-control">
        <option value="">-- Semua Hubungan --</option>
        <option value="Karyawan" <?= ($filter_hubungan=="Karyawan" ? "selected" : "") ?>>Karyawan</option>
        <option value="Istri" <?= ($filter_hubungan=="Istri" ? "selected" : "") ?>>Istri</option>
        <option value="Suami" <?= ($filter_hubungan=="Suami" ? "selected" : "") ?>>Suami</option>
        <option value="Anak" <?= ($filter_hubungan=="Anak" ? "selected" : "") ?>>Anak</option>
      </select>
    </div>
    <div class="col-md-6 d-flex gap-2">
      <button class="btn btn-primary">Filter</button>
      <a href="pasien.php" class="btn btn-secondary">Reset</a>
    </div>
  </form>

  <div class="table-responsive">
    <table id="tabelPasien" class="table table-striped table-bordered align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th style="width:50px;">No</th>
          <th>No RM</th>
          <th>Kode Transaksi (Terakhir)</th>
          <th>NIK</th>
          <th>Nama Pasien</th>
          <th>Hubungan</th>
          <th>Tanggal Lahir</th>
          <th>Jenis Kelamin</th>
          <th>Alamat</th>
          <th>Tanggal Kunjungan Terakhir</th>
          <th style="width:180px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
<?php
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td></td>"; // auto numbering DataTables
        echo "<td>".htmlspecialchars($row['no_rm'] ?? '-', ENT_QUOTES)."</td>";
        echo "<td>".htmlspecialchars($row['kode_transaksi'] ?? '-', ENT_QUOTES)."</td>";
        echo "<td>".htmlspecialchars($row['nik'] ?? '-', ENT_QUOTES)."</td>";
        echo "<td>".htmlspecialchars($row['nama'] ?? '-', ENT_QUOTES)."</td>";
        echo "<td>".htmlspecialchars($row['hubungan'] ?? '-', ENT_QUOTES)."</td>";
        echo "<td>".(!empty($row['tanggal_lahir']) ? date('d-m-Y', strtotime($row['tanggal_lahir'])) : "-")."</td>";
        echo "<td>".htmlspecialchars($row['jenis_kelamin'] ?? '-', ENT_QUOTES)."</td>";
        echo "<td>".htmlspecialchars($row['alamat'] ?? '-', ENT_QUOTES)."</td>";
        echo "<td>".(!empty($row['tanggal_kunjungan']) ? date('d-m-Y', strtotime($row['tanggal_kunjungan'])) : "-")."</td>";
        echo "<td class='text-center'>
                <a href='edit_pasien.php?id=".$row['id_pasien']."' class='btn btn-warning btn-sm'>Edit</a>
                <a href='hapus_pasien.php?id=".$row['id_pasien']."' class='btn btn-danger btn-sm btn-hapus'>Hapus</a>
              </td>";
        echo "</tr>";
    }
}
?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
  var t = $('#tabelPasien').DataTable({
    columnDefs: [
      { targets: 0, orderable: false, searchable: false },
      { targets: -1, orderable: false }
    ],
    order: [[1, 'asc']],
    pageLength: 50,
    lengthChange: true,
    lengthMenu: [ [50, 100, 200, 500], [50, 100, 200, 500] ]
  });

  // Auto numbering kolom No
  t.on('order.dt search.dt draw.dt', function(){
      let i = 1;
      t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function(){
          this.data(i++);
      });
  }).draw();

  // SweetAlert2 untuk hapus
  $(document).on('click', '.btn-hapus', function(e){
      e.preventDefault();
      var url = $(this).attr('href');
      Swal.fire({
          title: 'Yakin hapus pasien?',
          text: "Data pasien tidak bisa dikembalikan!",
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
});
</script>

</body>
</html>
