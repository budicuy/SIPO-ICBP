<?php
include '../../koneksi.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah Diagnosa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  body { margin: 0; font-family: Arial, sans-serif; background:#f8f9fa; }
  .main-content { margin-left: 240px; padding: 20px; }
</style>
</head>
<body>

<div class="d-flex">
    <?php include '../../sidebar.php'; ?>

    <div class="main-content">
        <h2 class="mb-4">Tambah Diagnosa</h2>

        <!-- Import Excel -->
        <div class="card mb-4">
            <div class="card-header bg-light"><strong>Import Data Diagnosa</strong></div>
            <div class="card-body">
                <form action="../../proses/import_excel/import_penyakit.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input type="file" name="file_excel" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                    <button type="submit" class="btn btn-success">Import</button>
                    <a href="../../proses/template_excel/template_penyakit.php" class="btn btn-warning">Download Template</a>
                </form>
            </div>
        </div>

        <!-- Form manual -->
        <h4 class="mb-3"><strong>Tambah Manual</strong></h4>
        <form method="POST" class="mb-5">
            <div class="mb-3">
                <label class="form-label">Nama Diagnosa</label>
                <input type="text" name="nama_penyakit" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Obat yang Direkomendasikan</label>
                <div class="row">
                    <?php
                    $obat = mysqli_query($conn, "SELECT * FROM obat ORDER BY nama_obat ASC");
                    while ($row = mysqli_fetch_assoc($obat)) {
                        echo '<div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="obat[]" value="'.$row['id_obat'].'">
                                    <label class="form-check-label">'.htmlspecialchars($row['nama_obat']).'</label>
                                </div>
                              </div>';
                    }
                    ?>
                </div>
            </div>
            <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
            <a href="penyakit.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<?php
// Proses simpan manual
if (isset($_POST['simpan'])) {
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama_penyakit']));
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $obat_terpilih = $_POST['obat'] ?? [];

    // Cek apakah penyakit sudah ada
    $cek = mysqli_query($conn, "SELECT 1 FROM penyakit WHERE nama_penyakit = '$nama' LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>
            Swal.fire({
                icon: 'warning',
                title: 'Diagnosa sudah ada',
                text: 'Nama diagnosa \"$nama\" sudah terdaftar!',
                confirmButtonText: 'OK'
            });
        </script>";
    } else {
        $sql = "INSERT INTO penyakit (nama_penyakit, deskripsi) VALUES ('$nama', '$deskripsi')";
        if (mysqli_query($conn, $sql)) {
            $id_penyakit = mysqli_insert_id($conn);

            foreach ($obat_terpilih as $id_obat) {
                $id_obat = intval($id_obat);
                mysqli_query($conn, "INSERT INTO penyakit_obat (id_penyakit, id_obat) VALUES ('$id_penyakit', '$id_obat')");
            }

            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data diagnosa berhasil disimpan',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location='penyakit.php';
                });
            </script>";
        } else {
            $err = mysqli_error($conn);
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Gagal menyimpan data: $err',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    }
}
?>

</body>
</html>
