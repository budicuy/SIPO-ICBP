<?php
include '../koneksi.php';
include '../sidebar.php';

// Ambil filter dari form
$filterNoRM = $_GET['no_rm'] ?? '';
$filterNIK  = $_GET['nik'] ?? '';

// Base query
$query = "
  SELECT k.id_kunjungan, k.kode_transaksi, k.tanggal_kunjungan,
         p.no_rm, p.nama_pasien, p.hubungan,
         kry.nik_karyawan AS nik
  FROM kunjungan k
  JOIN pasien p ON k.id_pasien = p.id_pasien
  JOIN karyawan kry ON p.id_karyawan = kry.id_karyawan
  WHERE 1=1
";

// Tambahkan kondisi jika ada filter
if (!empty($filterNoRM)) {
  $no_rm = mysqli_real_escape_string($conn, $filterNoRM);
  $query .= " AND p.no_rm LIKE '%$no_rm%'";
}
if (!empty($filterNIK)) {
  $nik = mysqli_real_escape_string($conn, $filterNIK);
  $query .= " AND kry.nik_karyawan LIKE '%$nik%'";
}

$query .= " ORDER BY k.tanggal_kunjungan DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Kunjungan Pasien</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> 
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <style>
    body { margin:0; padding:0; background-color:#f8f9fa; }
    .main-content { margin-left:220px; padding:30px; }
    .table-responsive { overflow-x:auto; }
    @media (max-width: 992px) { .main-content { margin-left:0; } }
  </style>
</head>
<body>

<div class="main-content">
  <h2 class="mb-4">Daftar Kunjungan Pasien</h2>

  <!-- Form filter -->
  <form class="row mb-3" method="get">
    <div class="col-md-3">
      <input type="text" name="no_rm" class="form-control" placeholder="Cari No RM" value="<?= htmlspecialchars($filterNoRM); ?>">
    </div>
    <div class="col-md-3">
      <input type="text" name="nik" class="form-control" placeholder="Cari NIK" value="<?= htmlspecialchars($filterNIK); ?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
    <div class="col-md-2">
      <a href="daftar_kunjungan.php" class="btn btn-secondary w-100">Reset</a>
    </div>
  </form>

  <!-- Filter tanggal (DataTables) -->
  <form class="row mb-3">
    <div class="col-md-3">
      <input type="date" id="minDate" class="form-control" placeholder="Dari Tanggal">
    </div>
    <div class="col-md-3">
      <input type="date" id="maxDate" class="form-control" placeholder="Sampai Tanggal">
    </div>
  </form>

  <div class="table-responsive">
    <table id="tabelKunjungan" class="table table-striped table-bordered align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th style="width:50px;">No</th>
          <th>Kode Transaksi</th>
          <th>No RM</th>
          <th>NIK</th>
          <th>Nama Pasien</th>
          <th>Hubungan</th>
          <th>Tanggal Kunjungan</th>
          <th style="width:100px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
          <td></td>
          <td><?= htmlspecialchars($row['kode_transaksi']); ?></td>
          <td><?= htmlspecialchars($row['no_rm']); ?></td>
          <td><?= htmlspecialchars($row['nik']); ?></td>
          <td><?= htmlspecialchars($row['nama_pasien']); ?></td>
          <td><?= htmlspecialchars($row['hubungan']); ?></td>
          <td data-order="<?= $row['tanggal_kunjungan']; ?>">
            <?= date('d-m-Y', strtotime($row['tanggal_kunjungan'])); ?>
          </td>
          <td class="text-center">
            <a href="detail_kunjungan.php?id_kunjungan=<?= $row['id_kunjungan']; ?>" class="btn btn-info btn-sm">Detail</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {
  var t = $('#tabelKunjungan').DataTable({
    columnDefs: [
      { targets: 0, orderable: false, searchable: false },
      { targets: -1, orderable: false }
    ],
    order: [[6, 'desc']],
    pageLength: 50,
    lengthChange: true,
    lengthMenu: [[50, 100, 200, 500], [50, 100, 200, 500]]
  });

  // Auto numbering kolom No
  t.on('order.dt search.dt draw.dt', function(){
      let i = 1;
      t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function(){
          this.data(i++);
      });
  }).draw();

  // Filter tanggal
  $.fn.dataTable.ext.search.push(
    function(settings, data, dataIndex) {
      var min = $('#minDate').val();
      var max = $('#maxDate').val();
      var date = data[6]; // kolom tanggal kunjungan (dd-mm-yyyy)

      if (!date) return false;
      var parts = date.split("-");
      if (parts.length === 3) {
          var d = new Date(parts[2], parts[1] - 1, parts[0]);
          var dateObj = d.getTime();
          var minDate = min ? new Date(min).getTime() : null;
          var maxDate = max ? new Date(max).getTime() : null;

          if ((minDate === null || dateObj >= minDate) &&
              (maxDate === null || dateObj <= maxDate)) {
              return true;
          }
          return false;
      }
      return true;
    }
  );

  $('#minDate, #maxDate').on('change', function() {
      t.draw();
  });
});
</script>

</body>
</html>
