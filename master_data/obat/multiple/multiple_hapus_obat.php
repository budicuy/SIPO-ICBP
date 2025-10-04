<?php
include '../../../koneksi.php';

// Cek apakah form dikirim dan ada checkbox yang dipilih
if (isset($_POST['id']) && is_array($_POST['id']) && count($_POST['id']) > 0) {

    $ids = array_map('intval', $_POST['id']);
    $idList = implode(',', $ids);

    $query = "DELETE FROM obat WHERE id_obat IN ($idList)";
    if (mysqli_query($conn, $query)) {
        ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data obat berhasil dihapus',
                confirmButtonText: 'OK'
            }).then(()=>{ window.location='../obat.php'; });
        </script>
        <?php
    } else {
        $error = mysqli_error($conn);
        ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Gagal menghapus data obat: <?= addslashes($error) ?>',
                confirmButtonText: 'OK'
            }).then(()=>{ window.location='../obat.php'; });
        </script>
        <?php
    }

} else {
    ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            icon: 'warning',
            title: 'Oops...',
            text: 'Tidak ada data obat yang dipilih!',
            confirmButtonText: 'OK'
        }).then(()=>{ window.location='../obat.php'; });
    </script>
    <?php
}
?>
