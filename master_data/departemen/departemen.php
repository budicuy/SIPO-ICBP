<?php
include "../../koneksi.php";
include "../../sidebar.php";

// Ambil semua data departemen
$query = mysqli_query($conn, "SELECT * FROM departemen ORDER BY id_departemen ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Departemen</title>
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { background-color: #f8f9fa; }
    .main-content { margin-left: 220px; padding: 30px; }
  </style>
</head>
<body>

<div class="main-content">
  <h2 class="mb-4">DATA DEPARTEMEN</h2>

  <!-- Tombol Tambah -->
  <a href="tambah_departemen.php" class="btn btn-success mb-3">+ Tambah Departemen</a>

  <div class="table-responsive">
    <table id="tabelDepartemen" class="table table-striped table-bordered align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th style="width:50px;">No</th>
          <th>Nama Departemen</th>
          <th style="width:180px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_assoc($query)) {
                echo "<tr>";
                echo "<td></td>"; // Auto numbering DataTables
                echo "<td>".htmlspecialchars($row['nama_departemen'])."</td>";
                echo "<td class='text-center'>
                        <a href='edit_departemen.php?id=".$row['id_departemen']."' class='btn btn-warning btn-sm'>Edit</a>
                        <a href='hapus_departemen.php?id=".$row['id_departemen']."' class='btn btn-danger btn-sm btn-hapus'>Hapus</a>
                      </td>";
                echo "</tr>";
            }
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {
  var t = $('#tabelDepartemen').DataTable({
    columnDefs: [
      { targets: 0, orderable: false, searchable: false },
      { targets: -1, orderable: false }
    ],
    order: [[1, 'asc']],
    pageLength: 50,
    lengthChange: false // hilangkan "show entries"
  });

  // Auto numbering kolom No
  t.on('order.dt search.dt', function(){
      let i = 1;
      t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function(){
          this.data(i++);
      });
  }).draw();

  // Konfirmasi hapus pakai SweetAlert2
  $(document).on('click', '.btn-hapus', function(e){
      e.preventDefault();
      var url = $(this).attr('href');
      Swal.fire({
          title: 'Yakin hapus data?',
          text: "Data yang sudah dihapus tidak bisa dikembalikan!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Ya, hapus!',
          cancelButtonText: 'Batal'
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
