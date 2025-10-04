<?php
include '../../koneksi.php';
session_start(); // perlu untuk flash message
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil ID penyakit
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['swal'] = ['icon'=>'error','title'=>'Gagal','text'=>'ID diagnosa tidak ditemukan'];
    header('Location: penyakit.php');
    exit;
}

// Ambil data penyakit
$res  = mysqli_query($conn, "SELECT * FROM penyakit WHERE id_penyakit = $id");
$data = mysqli_fetch_assoc($res);
if (!$data) {
    $_SESSION['swal'] = ['icon'=>'error','title'=>'Gagal','text'=>'Data diagnosa tidak ditemukan'];
    header('Location: penyakit.php');
    exit;
}

// Ambil semua obat
$allObat = mysqli_query($conn, "SELECT * FROM obat ORDER BY nama_obat");

// Ambil obat yang sudah direlasikan
$relasi = mysqli_query($conn, "SELECT id_obat FROM penyakit_obat WHERE id_penyakit = $id");
$relasi_ids = [];
while ($r = mysqli_fetch_assoc($relasi)) {
    $relasi_ids[] = $r['id_obat'];
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_penyakit']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $obat_terpilih = $_POST['obat'] ?? [];

    $ok = mysqli_query($conn, "UPDATE penyakit SET nama_penyakit='$nama', deskripsi='$deskripsi' WHERE id_penyakit=$id");

    if ($ok) {
        mysqli_query($conn, "DELETE FROM penyakit_obat WHERE id_penyakit=$id");
        foreach ($obat_terpilih as $id_obat) {
            mysqli_query($conn, "INSERT INTO penyakit_obat (id_penyakit,id_obat) VALUES ($id,$id_obat)");
        }

        $_SESSION['swal'] = ['icon'=>'success','title'=>'Berhasil','text'=>'Data diagnosa berhasil diperbarui'];
        header('Location: edit_penyakit.php?id='.$id);
        exit;
    } else {
        $_SESSION['swal'] = ['icon'=>'error','title'=>'Gagal','text'=>'Terjadi kesalahan: '.mysqli_error($conn)];
        header('Location: edit_penyakit.php?id='.$id);
        exit;
    }
}
?>

<div class="d-flex">
    <?php include '../../sidebar.php'; ?>

    <div class="container-fluid" style="margin-left: 240px; padding: 20px;">
        <h2 class="mb-4">Edit Diagnosa</h2>
        <div class="card shadow-sm p-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Diagnosa</label>
                    <input type="text" name="nama_penyakit" class="form-control" 
                           value="<?= htmlspecialchars($data['nama_penyakit']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($data['deskripsi']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Obat yang Direkomendasikan</label>
                    <div class="row">
                        <?php
                        mysqli_data_seek($allObat, 0);
                        while ($o = mysqli_fetch_assoc($allObat)): ?>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="obat[]" 
                                           value="<?= $o['id_obat']; ?>"
                                           <?= in_array($o['id_obat'], $relasi_ids) ? 'checked' : '' ?>>
                                    <label class="form-check-label"><?= htmlspecialchars($o['nama_obat']); ?></label>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                <a href="penyakit.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap & SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Tampilkan SweetAlert dari session
if (isset($_SESSION['swal'])) {
    $swal = $_SESSION['swal'];
    echo "<script>
        Swal.fire({
            icon: '{$swal['icon']}',
            title: '{$swal['title']}',
            text: '{$swal['text']}'
        });
    </script>";
    unset($_SESSION['swal']);
}
?>
