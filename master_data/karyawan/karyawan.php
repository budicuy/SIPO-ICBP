<?php
include '../../koneksi.php';
include '../../sidebar.php';

// ================================================================
// Fungsi format No HP
// ================================================================
function formatNoHP($no) {
    $no = trim($no);
    if (!$no) return '-';
    // Hilangkan spasi dan karakter non-digit kecuali +
    $no = preg_replace('/[^\d\+]/', '', $no);

    if (strpos($no, '+62') === 0) {
        return $no; // sudah format internasional
    } elseif (strpos($no, '0') === 0) {
        return $no; // sudah diawali 0
    } else {
        return '0' . $no; // tambahkan 0 di depan
    }
}

// ================================================================
// Ambil filter dari GET
// ================================================================
$filter_dept = $_GET['dept'] ?? '';

// Query dasar
$where = [];
if ($filter_dept) {
    $where[] = "k.id_departemen = '" . mysqli_real_escape_string($conn, $filter_dept) . "'";
}
$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Ambil data karyawan
$sql = "SELECT 
          k.id_karyawan, k.nik_karyawan, k.nama_karyawan, d.nama_departemen, 
          k.no_hp, k.tanggal_lahir, k.alamat, k.jenis_kelamin
        FROM karyawan k
        LEFT JOIN departemen d ON k.id_departemen = d.id_departemen
        $whereSql
        ORDER BY k.nama_karyawan ASC";
$result = mysqli_query($conn, $sql);

// Ambil list departemen untuk dropdown
$departemen = mysqli_query($conn, "SELECT * FROM departemen ORDER BY nama_departemen ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Karyawan</title>
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
  <h2 class="mb-4">DATA KARYAWAN</h2>

  <a href="tambah_karyawan.php" class="btn btn-success mb-3">+ Tambah Karyawan</a>

  <!-- Filter -->
  <form method="get" class="row g-2 mb-4">
    <div class="col-md-6">
      <select name="dept" class="form-control">
        <option value="">-- Semua Departemen --</option>
        <?php while($d = mysqli_fetch_assoc($departemen)) { ?>
          <option value="<?= $d['id_departemen']; ?>" <?= ($filter_dept==$d['id_departemen'])?'selected':''; ?>>
            <?= htmlspecialchars($d['nama_departemen']); ?>
          </option>
        <?php } ?>
      </select>
    </div>
    <div class="col-md-6 d-flex gap-2">
      <button class="btn btn-primary">Filter</button>
      <a href="karyawan.php" class="btn btn-secondary">Reset</a>
    </div>
  </form>

  <form id="formKaryawan" method="post">
    <div class="mb-3">
      <button type="button" id="btnEdit" class="btn btn-warning">Edit Terpilih</button>
      <button type="button" id="btnHapus" class="btn btn-danger">Hapus Terpilih</button>
    </div>

    <div class="table-responsive">
      <table id="tabelKaryawan" class="table table-striped table-bordered align-middle">
        <thead class="table-dark text-center">
          <tr>
            <th><input type="checkbox" id="checkAll"></th>
            <th>No</th>
            <th>NIK</th>
            <th>Nama</th>
            <th>Jenis Kelamin</th>
            <th>Departemen</th>
            <th>No HP</th>
            <th>Tanggal Lahir</th>
            <th>Alamat</th>
            <th style="width:180px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
<?php
if (mysqli_num_rows($result) > 0) {
  while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td class='text-center'><input type='checkbox' name='id_karyawan[]' value='{$row['id_karyawan']}'></td>";
    echo "<td></td>"; // auto numbering DataTables
    echo "<td>".htmlspecialchars($row['nik_karyawan'])."</td>";
    echo "<td>".htmlspecialchars($row['nama_karyawan'])."</td>";
    echo "<td>".htmlspecialchars($row['jenis_kelamin'])."</td>";
    echo "<td>".htmlspecialchars($row['nama_departemen'])."</td>";
    echo "<td>".formatNoHP($row['no_hp'])."</td>";
    echo "<td>".(!empty($row['tanggal_lahir']) ? date('d-m-Y', strtotime($row['tanggal_lahir'])) : "-")."</td>";
    echo "<td>".htmlspecialchars($row['alamat'])."</td>";
    echo "<td class='text-center'>
            <a href='edit_karyawan.php?id=".$row['id_karyawan']."' class='btn btn-warning btn-sm'>Edit</a>
            <a href='hapus_karyawan.php?id=".$row['id_karyawan']."' class='btn btn-danger btn-sm btn-hapus'>Hapus</a>
          </td>";
    echo "</tr>";
  }
}
?>
        </tbody>
      </table>
    </div>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
  var t = $('#tabelKaryawan').DataTable({
    columnDefs: [
      { targets: [0, -1], orderable: false, searchable: false },
    ],
    order: [[2, 'asc']],
    pageLength: 50,
    lengthChange: true,
    lengthMenu: [ [50, 100, 200, 500], [50, 100, 200, 500] ]
  });

  // Auto numbering kolom No
  t.on('order.dt search.dt draw.dt', function(){
      let i = 1;
      t.cells(null, 1, { search: 'applied', order: 'applied' }).every(function(){
          this.data(i++);
      });
  }).draw();

  // Check all
  $('#checkAll').click(function(){
      $('input[name="id_karyawan[]"]').prop('checked', this.checked);
  });

  // Tombol Multiple Edit
  $('#btnEdit').click(function(){
      var ids = $('input[name="id_karyawan[]"]:checked').map(function(){ return this.value; }).get();
      if (ids.length === 0) {
          Swal.fire('Pilih data dulu!', 'Minimal 1 karyawan dipilih untuk edit.', 'warning');
      } else {
          $('<form>', {
              "method": "POST",
              "action": "multiple/multiple_edit_karyawan.php"
          }).append($.map(ids, function(id){
              return $('<input>', { "type": "hidden", "name": "id_karyawan[]", "value": id });
          })).appendTo('body').submit();
      }
  });

  // Tombol Multiple Hapus (pakai AJAX)
  $('#btnHapus').click(function(){
      var ids = $('input[name="id_karyawan[]"]:checked').map(function(){ return this.value; }).get();
      if (ids.length === 0) {
          Swal.fire('Pilih data dulu!', 'Minimal 1 karyawan dipilih untuk dihapus.', 'warning');
      } else {
          Swal.fire({
              title: 'Yakin hapus karyawan terpilih?',
              text: "Data karyawan tidak bisa dikembalikan!",
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Ya, hapus!'
          }).then((result)=>{
              if(result.isConfirmed){
                  $.ajax({
                      url: 'multiple/multiple_hapus_karyawan.php',
                      type: 'POST',
                      data: { id_karyawan: ids },
                      dataType: 'json',
                      success: function(response) {
                          if (response.status === "success") {
                              Swal.fire('Berhasil!', response.message, 'success').then(()=>{
                                  location.reload();
                              });
                          } else {
                              Swal.fire('Gagal!', response.message, 'error');
                          }
                      },
                      error: function() {
                          Swal.fire('Error!', 'Terjadi kesalahan pada server.', 'error');
                      }
                  });
              }
          });
      }
  });

  // SweetAlert2 untuk hapus single
  $(document).on('click', '.btn-hapus', function(e){
      e.preventDefault();
      var url = $(this).attr('href');
      Swal.fire({
          title: 'Yakin hapus karyawan?',
          text: "Data karyawan tidak bisa dikembalikan!",
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
