<?php
include '../koneksi.php';
include '../sidebar.php';

// -----------------------------
// Ambil filter bulan & tahun
// -----------------------------
$bulan = $_GET['bulan'] ?? date('n');
$tahun = $_GET['tahun'] ?? date('Y');

// -----------------------------
// Ringkasan bulanan
// -----------------------------
$total_pemeriksaan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM rekam_medis 
    WHERE MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$tahun'
"))['total'];

$nilai_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(ro.jumlah * ro.harga_satuan),0) AS total
    FROM resep_obat ro
    JOIN obat o ON ro.id_obat = o.id_obat
    JOIN rekam_medis rm ON ro.id_rekam = rm.id_rekam
    WHERE MONTH(rm.tanggal)='$bulan' AND YEAR(rm.tanggal)='$tahun'
"))['total'];

// -----------------------------
// Data chart per bulan (Jan - Des)
// -----------------------------
$pemeriksaan_bulanan = [];
$transaksi_bulanan   = [];
for ($i=1; $i<=12; $i++) {
    $p = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS jml
        FROM rekam_medis
        WHERE MONTH(tanggal)='$i' AND YEAR(tanggal)='$tahun'
    "))['jml'] ?? 0;

    $t = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COALESCE(SUM(ro.jumlah * ro.harga_satuan),0) AS total
        FROM resep_obat ro
        JOIN obat o ON ro.id_obat = o.id_obat
        JOIN rekam_medis rm ON ro.id_rekam = rm.id_rekam
        WHERE MONTH(rm.tanggal)='$i' AND YEAR(rm.tanggal)='$tahun'
    "))['total'] ?? 0;

    $pemeriksaan_bulanan[] = (int)$p;
    $transaksi_bulanan[]   = (int)$t;
}

// -----------------------------
// Filter tanggal laporan harian
// -----------------------------
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-t');

// -----------------------------
// Query laporan harian
// -----------------------------
$laporan_harian = mysqli_query($conn, "
SELECT
    rm.id_rekam,
    k.kode_transaksi,
    p.no_rm,
    rm.tanggal,
    kar.nik_karyawan AS nik,
    p.nama_pasien,
    p.hubungan AS status,
    (SELECT GROUP_CONCAT(DISTINCT py.nama_penyakit SEPARATOR ', ')
     FROM penyakit py
     WHERE py.id_penyakit = rm.id_penyakit) AS diagnosa,
    (SELECT GROUP_CONCAT(DISTINCT o.nama_obat SEPARATOR ', ')
     FROM resep_obat ro
     JOIN obat o ON ro.id_obat = o.id_obat
     WHERE ro.id_rekam = rm.id_rekam) AS obat,
    (SELECT COALESCE(SUM(ro.jumlah * ro.harga_satuan),0)
     FROM resep_obat ro
     JOIN obat o ON ro.id_obat = o.id_obat
     WHERE ro.id_rekam = rm.id_rekam) AS biaya
FROM rekam_medis rm
JOIN pasien p ON rm.id_pasien = p.id_pasien
JOIN karyawan kar ON p.id_karyawan = kar.id_karyawan
JOIN kunjungan k ON rm.id_kunjungan = k.id_kunjungan
WHERE DATE(rm.tanggal) BETWEEN '$start' AND '$end'
ORDER BY rm.tanggal ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Klinik</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background:#f8f9fa; }
.main-content { margin-left:220px; padding:20px; }
.summary-card { border-radius:10px; padding:20px; color:#fff; font-weight:bold; }
.scroll-area { max-height: 400px; overflow-y: auto; overflow-x: auto; border:1px solid #dee2e6; }
.scroll-area table thead th { position: sticky; top: 0; z-index: 2; background: #212529; color: #fff; }
table th, table td { text-align:center; }
#chartPemeriksaan, #chartTransaksi { width: 100% !important; height: 300px !important; }
</style>
</head>
<body>

<div class="main-content">
<h3 class="mb-4">Dashboard Laporan Transaksi</h3>

<!-- Ringkasan Bulanan -->
<div class="card p-3 mb-4">
<form method="GET" class="row g-3 mb-3">
  <div class="col-md-2">
    <select name="bulan" class="form-control">
      <?php 
      $namaBulan = [
        1=>"Januari",2=>"Februari",3=>"Maret",4=>"April",5=>"Mei",6=>"Juni",
        7=>"Juli",8=>"Agustus",9=>"September",10=>"Oktober",11=>"November",12=>"Desember"
      ];
      for($i=1;$i<=12;$i++): ?>
        <option value="<?= $i ?>" <?= ($i==$bulan?'selected':'') ?>>
          <?= $namaBulan[$i] ?>
        </option>
      <?php endfor; ?>
    </select>
  </div>
  <div class="col-md-2">
    <select name="tahun" class="form-control">
      <?php for($y=date('Y')-3;$y<=date('Y');$y++): ?>
        <option value="<?= $y ?>" <?= ($y==$tahun?'selected':'') ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
  </div>
  <div class="col-md-2">
    <button type="submit" class="btn btn-success">Filter</button>
  </div>
</form>

<div class="row g-3 text-center mb-4">
  <div class="col-md-6"><div class="summary-card bg-primary">Total Pemeriksaan<br><?= $total_pemeriksaan ?></div></div>
  <div class="col-md-6"><div class="summary-card bg-info">Total Biaya<br>Rp<?= number_format($nilai_transaksi,0,',','.') ?></div></div>
</div>

<!-- Grafik -->
<div class="row">
  <div class="col-md-6">
    <canvas id="chartPemeriksaan"></canvas>
  </div>
  <div class="col-md-6">
    <canvas id="chartTransaksi"></canvas>
  </div>
</div>
</div>

<!-- Tombol Export -->
<div class="mb-3">
  <a href="../proses/export_laporan_excel.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&start=<?= $start ?>&end=<?= $end ?>" 
     class="btn btn-success">
     Export ke Excel
  </a>
</div>

<!-- Laporan Harian -->
<div class="card p-3">
<h5>Laporan Harian 📊</h5>
<form method="GET" class="row g-3 mb-3">
  <div class="col-md-3">
    <label>Dari Tanggal</label>
    <input type="date" name="start" class="form-control" value="<?= $start ?>">
  </div>
  <div class="col-md-3">
    <label>Sampai Tanggal</label>
    <input type="date" name="end" class="form-control" value="<?= $end ?>">
  </div>
  <div class="col-md-2 align-self-end">
    <button type="submit" class="btn btn-primary">Sort</button>
  </div>
</form>

<div class="scroll-area">
<table class="table table-bordered table-striped">
  <thead class="table-dark">
    <tr>
      <th>Kode Transaksi</th>
      <th>No RM</th>
      <th>Tanggal</th>
      <th>NIK</th>
      <th>Nama</th>
      <th>Status</th>
      <th>Diagnosa</th>
      <th>Obat</th>
      <th>Biaya</th>
    </tr>
  </thead>
  <tbody>
    <?php while($row=mysqli_fetch_assoc($laporan_harian)): ?>
    <tr>
      <td><?= $row['kode_transaksi'] ?></td>
      <td><?= $row['no_rm'] ?></td>
      <td><?= ($row['tanggal'] ? date('d-m-Y', strtotime($row['tanggal'])) : '-') ?></td>
      <td><?= $row['nik'] ?></td>
      <td><?= $row['nama_pasien'] ?></td>
      <td><?= $row['status'] ?></td>
      <td><?= $row['diagnosa'] ?? '-' ?></td>
      <td><?= $row['obat'] ?? '-' ?></td>
      <td>Rp<?= number_format($row['biaya'],0,',','.') ?></td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>
</div>

<script>
const bulanLabels = ["Januari","Februari","Maret","April","Mei","Juni",
                     "Juli","Agustus","September","Oktober","November","Desember"];
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: true }, title: { display: false } },
  scales: { y: { beginAtZero: true } }
};

// Chart Pemeriksaan
new Chart(document.getElementById('chartPemeriksaan'), {
  type: 'line',
  data: {
    labels: bulanLabels,
    datasets: [{
      label: 'Jumlah Pemeriksaan',
      data: <?= json_encode($pemeriksaan_bulanan) ?>,
      borderColor: 'teal',
      backgroundColor: 'rgba(0,128,128,0.2)',
      tension: 0.3,
      fill: true
    }]
  },
  options: chartOptions
});

// Chart Transaksi
new Chart(document.getElementById('chartTransaksi'), {
  type: 'line',
  data: {
    labels: bulanLabels,
    datasets: [{
      label: 'Total Biaya',
      data: <?= json_encode($transaksi_bulanan) ?>,
      borderColor: 'red',
      backgroundColor: 'rgba(255,0,0,0.2)',
      tension: 0.3,
      fill: true
    }]
  },
  options: chartOptions
});
</script>

</body>
</html>
