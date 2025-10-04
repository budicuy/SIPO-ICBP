<?php
include '../../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_penyakit'])) {
    $ids = $_POST['id_penyakit'];
    $sukses = 0;
    $gagal = 0;

    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id <= 0) continue;

        // Pastikan data ada
        $cek = mysqli_query($conn, "SELECT id_penyakit FROM penyakit WHERE id_penyakit = $id");
        if (mysqli_num_rows($cek) > 0) {
            // Hapus relasi dulu
            mysqli_query($conn, "DELETE FROM penyakit_obat WHERE id_penyakit = $id");

            // Hapus penyakit
            if (mysqli_query($conn, "DELETE FROM penyakit WHERE id_penyakit = $id")) {
                $sukses++;
            } else {
                $gagal++;
            }
        } else {
            $gagal++;
        }
    }

    echo "<script>
        alert('Hapus selesai. Berhasil: $sukses, Gagal: $gagal');
        window.location='../penyakit.php';
    </script>";
    exit;
} else {
    echo "<script>
        alert('Tidak ada data yang dipilih!');
        window.location='../penyakit.php';
    </script>";
    exit;
}
?>
