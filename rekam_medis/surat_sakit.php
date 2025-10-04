<?php
include '../koneksi.php';
include '../sidebar.php';

// Ambil semua karyawan untuk dropdown (hanya karyawan)
$sql_karyawan = "SELECT id_karyawan, nik_karyawan, nama_karyawan FROM karyawan";
$result_karyawan = mysqli_query($conn, $sql_karyawan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Surat Sakit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; margin:0; padding:0; }
        .main-content { margin-left:220px; padding:30px; }
        h3 { margin-bottom:20px; }
    </style>
</head>
<body>

<div class="main-content">
    <h3>Buat Surat Sakit</h3>
    <form method="post" action="cetak_surat_sakit.php" target="_blank" class="w-50">
        <div class="mb-3">
            <label class="form-label">Pilih NIK Karyawan</label>
            <select name="id_karyawan" class="form-control" required>
                <option value="">-- Pilih NIK Karyawan --</option>
                <?php while ($row = mysqli_fetch_assoc($result_karyawan)) { ?>
                    <option value="<?= $row['id_karyawan']; ?>">
                        <?= $row['nik_karyawan'] . " - " . $row['nama_karyawan']; ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Lama Istirahat (hari)</label>
            <input type="number" name="lama_istirahat" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-success">
            Cetak Surat Sakit
        </button>
    </form>
</div>

</body>
</html>
