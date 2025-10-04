<?php
include 'koneksi.php';

// Total pasien
$total_pasien = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM pasien"))['total'];

// Total rekam medis
$total_rekam_medis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM rekam_medis"))['total'];

// Kunjungan hari ini
$kunjungan_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM rekam_medis 
    WHERE DATE(tanggal) = CURDATE()
"))['total'];

// Pasien aktif (< 2 bulan terakhir)
$pasien_aktif = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(DISTINCT id_pasien) AS total 
    FROM rekam_medis 
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH)
"))['total'];

$pasien_nonaktif = $total_pasien - $pasien_aktif;

// Ambil bulan & tahun dari filter (default bulan & tahun sekarang)
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : date('n');
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');

$nama_bulan = [
    1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April',
    5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus',
    9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'
];

// ===== Grafik Harian (tanggal 1 - akhir bulan) =====
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$data_harian = ['labels'=>[], 'data'=>[]];
for($d=1; $d<=$daysInMonth; $d++){
    $data_harian['labels'][] = $d;
    $tgl = sprintf("%04d-%02d-%02d", $tahun, $bulan, $d);
    $total = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS total FROM rekam_medis WHERE DATE(tanggal)='$tgl'
    "))['total'] ?? 0;
    $data_harian['data'][] = (int)$total;
}

// ===== Grafik Mingguan (per minggu di bulan terpilih) =====
$data_mingguan = ['labels'=>[], 'data'=>[]];

// Tentukan tanggal pertama & terakhir bulan
$firstDay = strtotime("$tahun-$bulan-01");
$lastDay  = strtotime("$tahun-$bulan-$daysInMonth");

// Hitung minggu
$startWeek = $firstDay;
while($startWeek <= $lastDay){
    $endWeek = strtotime("+6 days", $startWeek);
    if($endWeek > $lastDay) $endWeek = $lastDay;
    
    // Ambil nama bulan untuk label
    $bulan_label = $nama_bulan[date('n', $startWeek)];
    
    $label = date('d', $startWeek) . ' - ' . date('d', $endWeek) . ' ' . $bulan_label;
    $data_mingguan['labels'][] = $label;
    
    $start = date('Y-m-d', $startWeek);
    $end   = date('Y-m-d', $endWeek);
    
    $total = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS total FROM rekam_medis WHERE tanggal BETWEEN '$start' AND '$end'
    "))['total'] ?? 0;
    
    $data_mingguan['data'][] = (int)$total;
    
    $startWeek = strtotime("+1 day", $endWeek); // minggu berikutnya
}

// ===== Grafik Bulanan (1 tahun) =====
$data_bulanan = ['labels'=>[], 'data'=>[]];
for($m=1; $m<=12; $m++){
    $data_bulanan['labels'][] = $nama_bulan[$m]; // nama bulan lengkap
    $total = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS total FROM rekam_medis WHERE MONTH(tanggal)='$m' AND YEAR(tanggal)='$tahun'
    "))['total'] ?? 0;
    $data_bulanan['data'][] = (int)$total;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Klinik</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { background:#f8f9fa; margin:0; padding:0; }
.main-content { margin-left:220px; padding:30px; }
.card { border:none; border-radius:12px; box-shadow:0 3px 6px rgba(0,0,0,0.1); }
.header { background:#fff; padding:20px; border-bottom:1px solid #dee2e6; margin-bottom:30px; }
canvas { height:300px !important; }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
<div class="header">
<h2>Selamat Datang di Sistem Informasi Klinik Indofood</h2>
</div>

<!-- Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white"><div class="card-body">
            <h6>Total Pasien</h6><p class="fs-4 mb-0"><?= $total_pasien ?></p>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white"><div class="card-body">
            <h6>Total Rekam Medis</h6><p class="fs-4 mb-0"><?= $total_rekam_medis ?></p>
        </div></div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white"><div class="card-body">
            <h6>Kunjungan Hari Ini</h6><p class="fs-4 mb-0"><?= $kunjungan_hari_ini ?></p>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white"><div class="card-body">
            <h6>On Progress</h6><p class="fs-4 mb-0"><?= $pasien_aktif ?></p>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white"><div class="card-body">
            <h6>Close</h6><p class="fs-4 mb-0"><?= $pasien_nonaktif ?></p>
        </div></div>
    </div>
</div>

<!-- Filter Bulan Tahun -->
<div class="card mb-4">
<div class="card-body">
<form method="GET" class="row g-3">
    <div class="col-md-2">
        <select name="bulan" class="form-control">
        <?php foreach($nama_bulan as $num => $nama): ?>
            <option value="<?= $num ?>" <?= ($num==$bulan?'selected':'') ?>><?= $nama ?></option>
        <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="tahun" class="form-control">
        <?php for($y=date('Y')-3; $y<=date('Y'); $y++): ?>
            <option value="<?= $y ?>" <?= ($y==$tahun?'selected':'') ?>><?= $y ?></option>
        <?php endfor; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>
</div>
</div>

<!-- Grafik -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card"><div class="card-body">
            <h5>Kunjungan Harian (<?= $nama_bulan[$bulan] ?> <?= $tahun ?>)</h5>
            <canvas id="chartHarian"></canvas>
        </div></div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card"><div class="card-body">
            <h5>Kunjungan Mingguan (per minggu bulan <?= $nama_bulan[$bulan] ?>)</h5>
            <canvas id="chartMingguan"></canvas>
        </div></div>
    </div>
    <div class="col-md-12 mb-4">
        <div class="card"><div class="card-body">
            <h5>Kunjungan Bulanan (<?= $tahun ?>)</h5>
            <canvas id="chartBulanan"></canvas>
        </div></div>
    </div>
</div>

<script>
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 500 },
  plugins: { 
    legend: { display: true }, 
    title: { display: false } 
  },
  scales: { 
    y: { 
      beginAtZero: true,
      ticks: {
        precision: 0, // bulat
        callback: function(value) {
          return Number.isInteger(value) ? value : null;
        }
      }
    }
  }
};

// Harian
new Chart(document.getElementById('chartHarian'), {
  type: 'line',
  data: {
    labels: <?= json_encode($data_harian['labels']) ?>,
    datasets: [{
      label:'Harian', 
      data: <?= json_encode($data_harian['data']) ?>,
      borderColor:'teal', 
      backgroundColor:'rgba(0,128,128,0.2)', 
      fill:true, 
      tension:0.3
    }]
  }, 
  options: chartOptions
});

// Mingguan
new Chart(document.getElementById('chartMingguan'), {
  type: 'line',
  data: {
    labels: <?= json_encode($data_mingguan['labels']) ?>,
    datasets: [{
      label:'Mingguan', 
      data: <?= json_encode($data_mingguan['data']) ?>,
      borderColor:'red', 
      backgroundColor:'rgba(255,0,0,0.2)', 
      fill:true, 
      tension:0.3
    }]
  }, 
  options: chartOptions
});

// Bulanan
new Chart(document.getElementById('chartBulanan'), {
  type: 'line',
  data: {
    labels: <?= json_encode($data_bulanan['labels']) ?>,
    datasets: [{
      label:'Bulanan', 
      data: <?= json_encode($data_bulanan['data']) ?>,
      borderColor:'blue', 
      backgroundColor:'rgba(0,0,255,0.2)', 
      fill:true, 
      tension:0.3
    }]
  }, 
  options: chartOptions
});
</script>

</body>
</html>
