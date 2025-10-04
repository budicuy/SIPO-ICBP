<?php
include '../../koneksi.php';
include '../../sidebar.php';

// Ambil data user
$sql = "SELECT * FROM user ORDER BY id_user DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data User Klinik</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { background-color: #f8f9fa; }
    .main-content { margin-left: 220px; padding: 30px; }
    @media (max-width: 992px) { .main-content { margin-left:0; } }
  </style>
</head>
<body>

<div class="main-content">
  <h2 class="mb-4">DATA USER</h2>

  <a href="tambah_user.php" class="btn btn-success mb-3">+ Tambah User</a>

  <div class="table-responsive">
    <table id="tabelUser" class="table table-bordered table-striped align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th style="width:50px;">No</th>
          <th>Username</th>
          <th>Nama Lengkap</th>
          <th>Role</th>
          <th style="width:180px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td class='text-center'></td>"; // auto numbering
            echo "<td>".htmlspecialchars($row['username'])."</td>";
            echo "<td>".htmlspecialchars($row['nama_lengkap'])."</td>";
            echo "<td>".htmlspecialchars($row['role'])."</td>";
            echo "<td class='text-center'>
                    <a href='edit_user.php?id=".$row['id_user']."' class='btn btn-warning btn-sm'>Edit</a>
                    <a href='hapus_user.php?id=".$row['id_user']."' class='btn btn-danger btn-sm btn-hapus'>Hapus</a>
                  </td>";
            echo "</tr>";
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

<script>
$(document).ready(function () {
  var t = $('#tabelUser').DataTable({
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
  t.on('order.dt search.dt', function(){
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
          title: 'Yakin hapus user?',
          text: "Data user tidak bisa dikembalikan!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Ya, hapus!'
      }).then((result) => {
          if(result.isConfirmed){
              window.location.href = url;
          }
      });
  });
});
</script>

</body>
</html>
