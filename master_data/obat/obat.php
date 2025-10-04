<?php
include '../../koneksi.php';
include '../../sidebar.php';

// Ambil semua data obat dengan join ke jenis_obat dan satuan_obat
$sql = "
    SELECT o.id_obat, o.nama_obat, o.keterangan, o.stok_awal, o.stok_masuk, 
           o.stok_keluar, o.stok_akhir, o.tanggal_update,
           o.jumlah_per_kemasan, o.harga_per_satuan, o.harga_per_kemasan,
           j.nama_jenis,
           s.nama_satuan
    FROM obat o
    LEFT JOIN jenis_obat j ON o.id_jenis_obat = j.id_jenis_obat
    LEFT JOIN satuan_obat s ON o.id_satuan = s.id_satuan
    ORDER BY o.id_obat ASC
";
$query = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Obat</title>
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { background-color: #f8f9fa; }
    .main-content { margin-left: 220px; padding: 30px; }
    @media (max-width: 992px) { .main-content { margin-left: 0; } }
  </style>
</head>
<body>

<div class="main-content">
  <h2 class="mb-4">DATA OBAT</h2>

  <!-- Tombol Tambah -->
  <a href="tambah_obat.php" class="btn btn-success mb-3">+ Tambah Obat</a>

  <!-- Form Multiple Action -->
  <form method="POST" id="formMultiple">
    <div class="mb-3">
      <button type="submit" formaction="multiple/multiple_edit_obat.php" class="btn btn-warning btn-sm">Edit Terpilih</button>
      <button type="submit" formaction="multiple/multiple_hapus_obat.php" class="btn btn-danger btn-sm">Hapus Terpilih</button>
    </div>

    <div class="table-responsive">
      <table id="tabelObat" class="table table-striped table-bordered align-middle">
        <thead class="table-dark text-center">
          <tr>
            <th><input type="checkbox" id="checkAll"></th>
            <th style="width:50px;">No</th>
            <th>Nama Obat</th>
            <th>Kategori Obat</th>
            <th>Satuan</th>
            <th>Keterangan</th>
            <th>Stok Awal</th>
            <th>Stok Masuk</th>
            <th>Stok Keluar</th>
            <th>Stok Akhir</th>
            <th>Jumlah/Kemasan</th>
            <th>Harga Satuan</th>
            <th>Harga Kemasan</th>
            <th>Tanggal Update</th>
            <th style="width:180px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          if (mysqli_num_rows($query) > 0) {
              while ($row = mysqli_fetch_assoc($query)) {
                  ?>
                  <tr>
                    <td class="text-center">
                      <input type="checkbox" name="id[]" value="<?= (int)$row['id_obat'] ?>">
                    </td>
                    <td></td> <!-- auto numbering DataTables -->
                    <td><?= htmlspecialchars($row['nama_obat']) ?></td>
                    <td><?= htmlspecialchars($row['nama_jenis'] ?? '-') ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['nama_satuan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                    <td class="text-center"><?= (int)($row['stok_awal'] ?? 0) ?></td>
                    <td class="text-center"><?= (int)($row['stok_masuk'] ?? 0) ?></td>
                    <td class="text-center"><?= (int)($row['stok_keluar'] ?? 0) ?></td>
                    <td class="text-center"><?= (int)($row['stok_akhir'] ?? 0) ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['jumlah_per_kemasan'] ?? '-') ?></td>
                    <td class="text-end">Rp <?= number_format($row['harga_per_satuan'] ?? 0,0,",",".") ?></td>
                    <td class="text-end">Rp <?= number_format($row['harga_per_kemasan'] ?? 0,0,",",".") ?></td>
                    <td class="text-center">
                      <?php 
                        if (!empty($row['tanggal_update']) && $row['tanggal_update'] != '0000-00-00') {
                            echo date('d-m-Y', strtotime($row['tanggal_update']));
                        } else {
                            echo "-";
                        }
                      ?>
                    </td>
                    <td class="text-center">
                      <a href="edit_obat.php?id=<?= (int)$row['id_obat'] ?>" class="btn btn-warning btn-sm">Edit</a>
                      <a href="hapus_obat.php?id=<?= (int)$row['id_obat'] ?>" class="btn btn-danger btn-sm btn-hapus">Hapus</a>
                    </td>
                  </tr>
                  <?php
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
  var t = $('#tabelObat').DataTable({
      columnDefs: [
        { targets: 0, orderable: false, searchable: false }, // checkbox
        { targets: 1, orderable: false, searchable: false }, // nomor
        { targets: -1, orderable: false } // aksi
      ],
      order: [[2, 'asc']], // urutkan berdasarkan nama obat
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

  // SweetAlert2 untuk hapus single
  $(document).on('click', '.btn-hapus', function(e){
      e.preventDefault();
      var url = $(this).attr('href');
      Swal.fire({
          title: 'Yakin hapus data?',
          text: "Data obat tidak bisa dikembalikan!",
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

  // Checkbox Select All
  $('#checkAll').on('click', function(){
      $('input[name="id[]"]').prop('checked', this.checked);
  });

  // Validasi multiple action sebelum submit
  $('#formMultiple').on('submit', function(e){
      if($('input[name="id[]"]:checked').length === 0){
          e.preventDefault();
          Swal.fire({
              icon: 'warning',
              title: 'Oops...',
              text: 'Pilih minimal satu data obat dulu!'
          });
      }
  });
});
</script>

</body>
</html>
