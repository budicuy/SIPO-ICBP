<?php
include 'koneksi.php';

$id_rekam = $_GET['id_rekam'] ?? 0;

// Jika form disubmit
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlah = $_POST['jumlah'];
    $harga_satuan = $_POST['harga_satuan'];
    $total_biaya = 0;

    foreach($jumlah as $id_obat => $jml) {
        $harga = $harga_satuan[$id_obat];
        $subtotal = $jml * $harga;
        $total_biaya += $subtotal;

        mysqli_query($conn, "
            INSERT INTO resep_obat (id_rekam, id_obat, jumlah, harga_satuan)
            VALUES ('$id_rekam', '$id_obat', '$jml', '$harga')
        ");

        // Update stok obat
        mysqli_query($conn, "UPDATE obat SET stok_akhir = stok_akhir - $jml WHERE id_obat='$id_obat'");
    }

    // Update total biaya di rekam_medis
    mysqli_query($conn, "UPDATE rekam_medis SET total_biaya='$total_biaya' WHERE id_rekam='$id_rekam'");

    echo "<div class='alert alert-success'>Resep berhasil disimpan. Total biaya: Rp ".number_format($total_biaya)."</div>";
}

// Ambil id_penyakit dari rekam medis
$query_rekam = mysqli_query($conn, "SELECT id_penyakit FROM rekam_medis WHERE id_rekam='$id_rekam'");
$rekam = mysqli_fetch_assoc($query_rekam);
$id_penyakit = $rekam['id_penyakit'];

// Ambil daftar obat sesuai diagnosa
$query_obat = mysqli_query($conn, "
    SELECT o.id_obat, o.nama_obat, o.stok_akhir AS stok, o.harga_satuan
    FROM obat o
    JOIN penyakit_obat po ON o.id_obat = po.id_obat
    WHERE po.id_penyakit = '$id_penyakit'
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Resep Obat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<h2>Resep Obat</h2>
<form method="post" id="formResep">
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Obat</th>
            <th>Stok</th>
            <th>Harga Satuan</th>
            <th>Jumlah</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = mysqli_fetch_assoc($query_obat)) { ?>
        <tr>
            <td><?= $row['nama_obat'] ?></td>
            <td><?= $row['stok'] ?></td>
            <td><?= number_format($row['harga_satuan']) ?></td>
            <td>
                <input type="number" class="form-control jumlah" name="jumlah[<?= $row['id_obat'] ?>]" 
                    value="1" min="1" max="<?= $row['stok'] ?>" data-harga="<?= $row['harga_satuan'] ?>">
                <input type="hidden" name="harga_satuan[<?= $row['id_obat'] ?>]" value="<?= $row['harga_satuan'] ?>">
            </td>
            <td class="subtotal"><?= number_format($row['harga_satuan']) ?></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="4" class="text-end">Total Biaya</th>
            <th id="totalBiaya">0</th>
        </tr>
    </tfoot>
</table>
<button type="submit" class="btn btn-primary">Simpan Resep</button>
</form>

<script>
function updateSubtotal() {
    let total = 0;
    document.querySelectorAll('tbody tr').forEach(row => {
        const jumlahInput = row.querySelector('.jumlah');
        const harga = parseFloat(jumlahInput.dataset.harga);
        const jumlah = parseInt(jumlahInput.value) || 0;
        const subtotal = harga * jumlah;
        row.querySelector('.subtotal').textContent = subtotal.toLocaleString();
        total += subtotal;
    });
    document.getElementById('totalBiaya').textContent = total.toLocaleString();
}

// Update subtotal saat input jumlah berubah
document.querySelectorAll('.jumlah').forEach(input => {
    input.addEventListener('input', updateSubtotal);
});

// Hitung total saat halaman load
window.onload = updateSubtotal;
</script>

</body>
</html>
