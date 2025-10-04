<?php
include "../../koneksi.php";
include "../../sidebar.php";

// Ambil semua data penyakit
$query = mysqli_query($conn, "SELECT * FROM penyakit ORDER BY id_penyakit ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Diagnosa</title>
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
  <h2 class="mb-4">DATA DIAGNOSA</h2>

  <!-- Tombol Tambah -->
  <a href="tambah_penyakit.php" class="btn btn-success mb-3">+ Tambah Diagnosa</a>

  <!-- Form untuk multiple action -->
  <form id="formPenyakit" method="POST">
    <div class="mb-3">
      <button type="submit" formaction="multiple/multiple_edit_penyakit.php" class="btn btn-warning">Edit Terpilih</button>
      <button type="submit" formaction="multiple/multiple_hapus_penyakit.php" class="btn btn-danger" id="btnHapusTerpilih">Hapus Terpilih</button>
    </div>

    <div class="table-responsive">
      <table id="tabelPenyakit" class="table table-striped table-bordered align-middle">
        <thead class="table-dark text-center">
          <tr>
            <th><input type="checkbox" id="checkAll"></th>
            <th style="width:50px;">No</th>
            <th>Nama Diagnosa</th>
            <th>Deskripsi</th>
            <th style="width:180px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if (mysqli_num_rows($query) > 0) {
              while ($row = mysqli_fetch_assoc($query)) {
                  echo "<tr>";
                  echo "<td class='text-center'><input type='checkbox' name='id_penyakit[]' value='".$row['id_penyakit']."'></td>";
                  echo "<td></td>"; // Auto numbering DataTables
                  echo "<td>".htmlspecialchars($row['nama_penyakit'])."</td>";
                  echo "<td>".htmlspecialchars($row['deskripsi'])."</td>";
                  echo "<td class='text-center'>
                          <a href='edit_penyakit.php?id=".$row['id_penyakit']."' class='btn btn-warning btn-sm'>Edit</a>
                          <a href='hapus_penyakit.php?id=".$row['id_penyakit']."' class='btn btn-danger btn-sm btn-hapus'>Hapus</a>
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

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {
  var t = $('#tabelPenyakit').DataTable({
      columnDefs: [
        { targets: 1, orderable: false, searchable: false },
        { targets: -1, orderable: false }
      ],
      order: [[2, 'asc']],
      pageLength: 50,
      lengthChange: true,
      lengthMenu: [ [50, 100, 200, 500], [50, 100, 200, 500] ]
  });

  // Auto numbering kolom No
  t.on('order.dt search.dt', function(){
      let i = 1;
      t.cells(null, 1, { search: 'applied', order: 'applied' }).every(function(){
          this.data(i++);
      });
  }).draw();

  // Check/uncheck semua
  $('#checkAll').on('click', function(){
      $('input[name="id_penyakit[]"]').prop('checked', this.checked);
  });

  // Konfirmasi hapus pakai SweetAlert2 untuk tombol per baris
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

  // Konfirmasi hapus terpilih
  $('#btnHapusTerpilih').on('click', function(e){
      e.preventDefault();
      if($('input[name="id_penyakit[]"]:checked').length === 0){
          Swal.fire('Peringatan','Pilih minimal satu data untuk dihapus!','warning');
          return false;
      }
      Swal.fire({
          title: 'Yakin hapus data terpilih?',
          text: "Data yang sudah dihapus tidak bisa dikembalikan!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Ya, hapus!',
          cancelButtonText: 'Batal'
      }).then((result)=>{
          if(result.isConfirmed){
              $('#formPenyakit').attr('action','multiple/multiple_hapus_penyakit.php').submit();
          }
      });
  });

  // ✅ Pop-up berhasil hapus
  <?php if (isset($_GET['hapus']) && $_GET['hapus'] === 'success'): ?>
  Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Data diagnosa berhasil dihapus',
      confirmButtonText: 'OK'
  });
  <?php endif; ?>

});
</script>

</body>
</html>
