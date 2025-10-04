<?php
include '../koneksi.php';
include '../sidebar.php';

// ----------------------
// Ambil parameter
// ----------------------
$id_pasien = null;
$pasien = null;

if (isset($_GET['id_pasien'])) {
    // Jika dipanggil dari daftar_rekam_medis
    $id_pasien = (int) $_GET['id_pasien'];

    $qPasien = mysqli_query($conn, "
        SELECT p.no_rm, p.nama_pasien, p.hubungan, 
               p.tanggal_lahir, p.jenis_kelamin, p.alamat
        FROM pasien p
        WHERE p.id_pasien = '$id_pasien'
    ");
    $pasien = mysqli_fetch_assoc($qPasien);

} elseif (isset($_GET['id_kunjungan'])) {
    // Jika dipanggil dari daftar_kunjungan
    $id_kunjungan = (int) $_GET['id_kunjungan'];

    $qPasien = mysqli_query($conn, "
        SELECT p.id_pasien, p.no_rm, p.nama_pasien, p.hubungan, 
               p.tanggal_lahir, p.jenis_kelamin, p.alamat
        FROM kunjungan k
        JOIN pasien p ON k.id_pasien = p.id_pasien
        WHERE k.id_kunjungan = '$id_kunjungan'
    ");
    $pasien = mysqli_fetch_assoc($qPasien);
    if ($pasien) {
        $id_pasien = $pasien['id_pasien'];
    }
}

if (!$id_pasien || !$pasien) {
    die("Parameter tidak valid atau data pasien tidak ditemukan.");
}

// ----------------------
// Ambil riwayat rekam medis pasien
// ----------------------
$qData = "
SELECT 
    rm.id_rekam,
    rm.tanggal AS tanggal_kunjungan,
    py.nama_penyakit,
    o.nama_obat,
    ro.jumlah,
    ro.harga_satuan,
    (ro.jumlah * ro.harga_satuan) AS subtotal,
    ro.keterangan,
    rm.total_biaya
FROM rekam_medis rm
LEFT JOIN penyakit py ON rm.id_penyakit = py.id_penyakit
LEFT JOIN resep_obat ro ON rm.id_rekam = ro.id_rekam
LEFT JOIN obat o ON ro.id_obat = o.id_obat
WHERE rm.id_pasien = '$id_pasien'
ORDER BY rm.tanggal DESC, rm.id_rekam DESC, o.nama_obat ASC
";

$res = mysqli_query($conn, $qData);

// Group data per rekam medis
$riwayat = [];
while ($row = mysqli_fetch_assoc($res)) {
    $id = $row['id_rekam'];
    if (!isset($riwayat[$id])) {
        $riwayat[$id] = [
            'tanggal_kunjungan' => $row['tanggal_kunjungan'],
            'nama_penyakit' => $row['nama_penyakit'],
            'total_biaya' => $row['total_biaya'],
            'obat' => []
        ];
    }
    if ($row['nama_obat']) {
        $riwayat[$id]['obat'][] = [
            'nama_obat' => $row['nama_obat'],
            'jumlah' => $row['jumlah'],
            'harga_satuan' => $row['harga_satuan'],
            'subtotal' => $row['subtotal'],
            'keterangan' => $row['keterangan']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail Riwayat Pasien</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { margin:0; padding:0; background-color:#f8f9fa; }
.main-content { margin-left:220px; padding:30px; }
@media (max-width: 992px) { .main-content { margin-left:0; } }
.table thead { background:#343a40; color:#fff; }
.table tbody tr { background:#fff !important; }
</style>
</head>
<body>

<div class="main-content">
  <h2 class="mb-4">Detail Riwayat Pasien</h2>

  <!-- Identitas pasien -->
  <table class="table table-bordered bg-white">
    <tr><th>No RM</th><td><?= htmlspecialchars($pasien['no_rm']) ?></td></tr>
    <tr><th>Nama Pasien</th><td><?= htmlspecialchars($pasien['nama_pasien']) ?></td></tr>
    <tr><th>Hubungan</th><td><?= htmlspecialchars($pasien['hubungan']) ?></td></tr>
    <tr><th>Tanggal Lahir</th><td><?= $pasien['tanggal_lahir'] ? date('d-m-Y', strtotime($pasien['tanggal_lahir'])) : '-' ?></td></tr>
    <tr><th>Jenis Kelamin</th><td><?= htmlspecialchars($pasien['jenis_kelamin'] ?? '-') ?></td></tr>
    <tr><th>Alamat</th><td><?= htmlspecialchars($pasien['alamat']) ?></td></tr>
  </table>

  <h4 class="mt-4">Riwayat Kunjungan & Resep Obat</h4>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="text-center">
        <tr>
          <th>No</th>
          <th>Tanggal Kunjungan</th>
          <th>Diagnosa/Penyakit</th>
          <th>Nama Obat</th>
          <th>Jumlah</th>
          <th>Keterangan</th>
          <th>Harga Satuan</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($riwayat)): ?>
          <?php $no = 1; ?>
          <?php foreach ($riwayat as $rm): ?>
            <?php 
              $rowspan = max(1, count($rm['obat']));
              $first = true;
            ?>
            <?php if (!empty($rm['obat'])): ?>
              <?php foreach ($rm['obat'] as $obat): ?>
                <tr>
                  <?php if ($first): ?>
                    <td rowspan="<?= $rowspan ?>" class="text-center"><?= $no ?></td>
                    <td rowspan="<?= $rowspan ?>"><?= date('d-m-Y', strtotime($rm['tanggal_kunjungan'])) ?></td>
                    <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($rm['nama_penyakit'] ?? '-') ?></td>
                  <?php $first = false; endif; ?>
                  <td><?= htmlspecialchars($obat['nama_obat']) ?></td>
                  <td class="text-center"><?= $obat['jumlah'] ?></td>
                  <td><?= htmlspecialchars($obat['keterangan'] ?? '-') ?></td>
                  <td>Rp <?= number_format($obat['harga_satuan'],0,',','.') ?></td>
                  <td>Rp <?= number_format($obat['subtotal'],0,',','.') ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="fw-bold">
                <td colspan="6" class="text-end">Total Biaya</td>
                <td colspan="2">Rp <?= number_format($rm['total_biaya'],0,',','.') ?></td>
              </tr>
            <?php else: ?>
              <tr>
                <td class="text-center"><?= $no ?></td>
                <td><?= date('d-m-Y', strtotime($rm['tanggal_kunjungan'])) ?></td>
                <td><?= htmlspecialchars($rm['nama_penyakit'] ?? '-') ?></td>
                <td colspan="5" class="text-center">Tidak ada obat</td>
              </tr>
            <?php endif; ?>
          <?php $no++; endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8" class="text-center">Belum ada riwayat kunjungan</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <a href="javascript:history.back()" class="btn btn-secondary mt-3">Kembali</a>
</div>

</body>
</html>
