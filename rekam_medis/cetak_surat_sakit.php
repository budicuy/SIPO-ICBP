<?php
include '../koneksi.php';

// Fungsi konversi angka ke kata (1-20 cukup untuk surat sakit)
function angkaKeKata($angka) {
    $kata = ["nol","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan","sepuluh",
             "sebelas","dua belas","tiga belas","empat belas","lima belas","enam belas","tujuh belas","delapan belas","sembilan belas","dua puluh"];
    if($angka <= 20) return $kata[$angka];
    return $angka; // fallback jika lebih dari 20
}

// Ambil data dari POST
$id_karyawan    = (int)$_POST['id_karyawan'];
$lama_istirahat = (int)$_POST['lama_istirahat'];

// Ambil detail karyawan + departemen
$sql = "SELECT k.nik_karyawan, k.nama_karyawan, d.nama_departemen 
        FROM karyawan k
        LEFT JOIN departemen d ON k.id_departemen = d.id_departemen
        WHERE k.id_karyawan = $id_karyawan";
$result = mysqli_query($conn, $sql);
$karyawan = mysqli_fetch_assoc($result);

// Jika data tidak ditemukan
if (!$karyawan) {
    echo "<script>alert('Data karyawan tidak ditemukan'); window.close();</script>";
    exit;
}

// Tanggal hari ini
$tanggal = date("d-m-Y");

// Konversi lama istirahat ke kata
$lama_kata = angkaKeKata($lama_istirahat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan Sakit</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding-bottom: 10px; 
            margin-bottom: 5px; 
        }
        .header-left { display: flex; align-items: center; }
        .header-left img { width: 120px; margin-right: 20px; }
        .header-left h2 { margin: 0; font-size: 20px; }
        .header-left p { margin: 2px 0; font-size: 14px; }
        .header-right img { width: 100px; }
        .line { border-bottom: 2px solid #000; margin: 10px 0 20px 0; }
        .surat { padding: 20px; }
        .judul { text-align: center; font-size: 20px; font-weight: bold; text-decoration: underline; margin-bottom: 20px; }
        .content { margin-top: 20px; font-size: 16px; line-height: 1.6; }
        .ttd { margin-top: 60px; text-align: right; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <img src="../assets/img/Indofood_CBP.png" alt="Indofood Logo">
        <div>
            <h2>PT. Indofood CBP Sukses Makmur Tbk.</h2>
            <p>Jalan Ayani KM. 32 Liang Anggang, Pandahan, Kec. Bati Bati,<br>
               Kabupaten Tanah Laut, Kalimantan Selatan 70723</p>
        </div>
    </div>
    <div class="header-right">
        <img src="../assets/img/logo_K3.png" alt="Logo K3">
    </div>
</div>

<div class="line"></div>

<div class="surat">
    <div class="judul">SURAT KETERANGAN SAKIT</div>

    <div class="content">
        <p>Dengan ini menerangkan bahwa:</p>

        <table>
            <tr>
                <td style="width:150px;">NIK</td>
                <td>: <?= htmlspecialchars($karyawan['nik_karyawan']); ?></td>
            </tr>
            <tr>
                <td>Nama</td>
                <td>: <?= htmlspecialchars($karyawan['nama_karyawan']); ?></td>
            </tr>
            <tr>
                <td>Departemen</td>
                <td>: <?= htmlspecialchars($karyawan['nama_departemen'] ?? '-'); ?></td>
            </tr>
        </table>

        <p>
            Berdasarkan pemeriksaan, yang bersangkutan dinyatakan perlu beristirahat 
            selama <b><?= $lama_istirahat; ?> (<?= $lama_kata; ?>) hari</b>, 
            terhitung mulai tanggal <?= $tanggal; ?>.
        </p>

        <p>Demikian surat keterangan sakit ini dibuat agar dapat digunakan sebagaimana mestinya.</p>
    </div>

    <div class="ttd">
        <p>Liang Anggang, <?= $tanggal; ?></p>
        <br><br><br>
        <p><b>Dokter Poliklinik</b></p>
    </div>
</div>

<script>
    window.print();
</script>

</body>
</html>
