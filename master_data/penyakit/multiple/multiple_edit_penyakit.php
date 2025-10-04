<?php
// master_data/penyakit/multiple/multiple_edit_penyakit.php
include '../../../koneksi.php';

// Proses penyimpanan (submit dari form multiple edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['penyakit']) && is_array($_POST['penyakit'])) {
    $errors = [];
    foreach ($_POST['penyakit'] as $id_penyakit_str => $row) {
        $id_penyakit = (int)$id_penyakit_str;
        if ($id_penyakit <= 0) continue;

        $nama = mysqli_real_escape_string($conn, $row['nama_penyakit'] ?? '');
        $deskripsi = mysqli_real_escape_string($conn, $row['deskripsi'] ?? '');
        $obat_terpilih = $row['obat'] ?? [];

        // Update penyakit
        $ok = mysqli_query($conn, "
            UPDATE penyakit SET
                nama_penyakit = '$nama',
                deskripsi = '$deskripsi'
            WHERE id_penyakit = $id_penyakit
        ");
        if (!$ok) {
            $errors[] = "Gagal update id {$id_penyakit}: " . mysqli_error($conn);
            continue;
        }

        // Hapus relasi lama
        mysqli_query($conn, "DELETE FROM penyakit_obat WHERE id_penyakit = $id_penyakit");

        // Insert relasi baru (jika ada)
        if (is_array($obat_terpilih) && count($obat_terpilih) > 0) {
            foreach ($obat_terpilih as $id_obat) {
                $id_obat = (int)$id_obat;
                if ($id_obat <= 0) continue;
                mysqli_query($conn, "INSERT INTO penyakit_obat (id_penyakit, id_obat) VALUES ($id_penyakit, $id_obat)");
            }
        }
    }

    if (empty($errors)) {
        // sukses semua
        echo "<script src='//cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            Swal.fire({icon:'success',title:'Berhasil',text:'Semua perubahan berhasil disimpan'}).then(()=>{ window.location='../penyakit.php'; });
        </script>";
    } else {
        // Ada error; tampilkan ringkasan
        $msg = implode('\\n', array_map(function($e){ return addslashes($e); }, $errors));
        echo "<script src='//cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            Swal.fire({icon:'error',title:'Selesai dengan Error',html:'".nl2br(htmlspecialchars($msg))."'}).then(()=>{ window.location='../penyakit.php'; });
        </script>";
    }
    exit;
}

// Mode tampil form edit: terima id_penyakit[] (dari page daftar yang mengirim checkbox)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_penyakit']) && is_array($_POST['id_penyakit'])) {
    // sanitize ids
    $ids = array_map('intval', $_POST['id_penyakit']);
    $ids = array_values(array_filter($ids, function($v){ return $v>0; }));
    if (empty($ids)) {
        echo "<script>alert('Tidak ada data dipilih untuk diedit'); window.location='../penyakit.php';</script>";
        exit;
    }

    // Ambil data penyakit terpilih
    $ids_list = implode(',', $ids);
    $penyakitRes = mysqli_query($conn, "SELECT * FROM penyakit WHERE id_penyakit IN ($ids_list) ORDER BY nama_penyakit ASC");

    // Ambil semua obat (simpan ke array agar bisa di-loop berkali-kali)
    $obatList = [];
    $resObat = mysqli_query($conn, "SELECT id_obat, nama_obat FROM obat ORDER BY nama_obat ASC");
    while ($r = mysqli_fetch_assoc($resObat)) $obatList[] = $r;

    // Ambil relasi untuk id terpilih, buat map id_penyakit => [id_obat,...]
    $relasiMap = [];
    $resRel = mysqli_query($conn, "SELECT id_penyakit, id_obat FROM penyakit_obat WHERE id_penyakit IN ($ids_list)");
    while ($r = mysqli_fetch_assoc($resRel)) {
        $relasiMap[$r['id_penyakit']][] = $r['id_obat'];
    }

    // Tampilkan form edit multiple
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="utf-8">
      <title>Multiple Edit Diagnosa</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <style> body{font-family:Arial,Helvetica,sans-serif;} .main{margin-left:240px;padding:20px;} </style>
    </head>
    <body>
    <?php include '../../../sidebar.php'; ?>

    <div class="main">
      <h2 class="mb-4">Edit Banyak Diagnosa</h2>

      <form method="post" class="mb-5">
        <?php
        // Loop setiap penyakit
        while ($p = mysqli_fetch_assoc($penyakitRes)):
            $pid = (int)$p['id_penyakit'];
        ?>
          <div class="card mb-4 shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="mb-0">ID <?= $pid ?> — <?= htmlspecialchars($p['nama_penyakit']) ?></h5>
            </div>

            <div class="mb-3">
              <label class="form-label">Nama Diagnosa</label>
              <input type="text" name="penyakit[<?= $pid ?>][nama_penyakit]" class="form-control"
                     value="<?= htmlspecialchars($p['nama_penyakit']) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Deskripsi</label>
              <textarea name="penyakit[<?= $pid ?>][deskripsi]" class="form-control" rows="2"><?= htmlspecialchars($p['deskripsi']) ?></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Obat yang Direkomendasikan</label>
              <div class="row">
                <?php foreach ($obatList as $o): 
                    $checked = (isset($relasiMap[$pid]) && in_array($o['id_obat'], $relasiMap[$pid])) ? 'checked' : '';
                ?>
                  <div class="col-md-4">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox"
                             name="penyakit[<?= $pid ?>][obat][]"
                             id="p<?= $pid ?>o<?= $o['id_obat'] ?>"
                             value="<?= $o['id_obat'] ?>" <?= $checked ?>>
                      <label class="form-check-label" for="p<?= $pid ?>o<?= $o['id_obat'] ?>"><?= htmlspecialchars($o['nama_obat']) ?></label>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">Simpan Semua Perubahan</button>
          <a href="../penyakit.php" class="btn btn-secondary">Batal</a>
        </div>
      </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
} // end mode tampil

// Jika bukan POST dengan id_penyakit atau bukan submit penyimpanan, arahkan kembali
echo "<script>alert('Akses tidak valid atau tidak ada data yang dikirim.'); window.location='../penyakit.php';</script>";
exit;
