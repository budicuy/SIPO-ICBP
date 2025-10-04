<?php
require '../vendor/autoload.php';
include '../koneksi.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Filter tanggal
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-t');

// Query laporan harian (satu baris per rekam_medis, tidak dobel)
$result_harian = mysqli_query($conn, "
    SELECT 
        rm.id_rekam,
        k.kode_transaksi,
        p.no_rm,
        rm.tanggal,
        kar.nik_karyawan AS nik,
        p.nama_pasien,
        p.hubungan AS status,
        py.nama_penyakit AS diagnosa,
        COALESCE(GROUP_CONCAT(DISTINCT o.nama_obat SEPARATOR ', '), '-') AS obat,
        COALESCE(SUM(ro.jumlah * ro.harga_satuan),0) AS biaya
    FROM rekam_medis rm
    JOIN pasien p ON rm.id_pasien = p.id_pasien
    JOIN karyawan kar ON p.id_karyawan = kar.id_karyawan
    LEFT JOIN kunjungan k ON rm.id_kunjungan = k.id_kunjungan
    LEFT JOIN penyakit py ON rm.id_penyakit = py.id_penyakit
    LEFT JOIN resep_obat ro ON rm.id_rekam = ro.id_rekam
    LEFT JOIN obat o ON ro.id_obat = o.id_obat
    WHERE DATE(rm.tanggal) BETWEEN '$start' AND '$end'
    GROUP BY rm.id_rekam
    ORDER BY rm.tanggal ASC, rm.id_rekam ASC
");

// Buat spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Laporan Harian");

// Header laporan harian
$headers = ['Kode Transaksi','No RM','Tanggal','NIK','Nama Pasien','Status','Diagnosa','Obat','Biaya'];
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col.'1', $h);
    $col++;
}

// Isi data
$row = 2;
while($data = mysqli_fetch_assoc($result_harian)){
    $tanggal = $data['tanggal'] ? date('d-m-Y', strtotime($data['tanggal'])) : '-';
    $sheet->setCellValue("A$row", $data['kode_transaksi'])
          ->setCellValue("B$row", $data['no_rm'])
          ->setCellValue("C$row", $tanggal)
          ->setCellValue("D$row", $data['nik'])
          ->setCellValue("E$row", $data['nama_pasien'])
          ->setCellValue("F$row", $data['status'])
          ->setCellValue("G$row", $data['diagnosa'] ?? '-')
          ->setCellValue("H$row", $data['obat'] ?? '-')
          ->setCellValue("I$row", $data['biaya']);
    $row++;
}

// Tambahkan total biaya di baris terakhir
$sheet->setCellValue("H{$row}", 'TOTAL');
$sheet->setCellValue("I{$row}", "=SUM(I2:I" . ($row-1) . ")");

// Style total
$sheet->getStyle("H{$row}:I{$row}")->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_RIGHT,
        'vertical'   => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'top' => ['borderStyle' => Border::BORDER_MEDIUM],
    ]
]);

// Format angka total biaya ke Rp
$sheet->getStyle("I{$row}")
      ->getNumberFormat()
      ->setFormatCode('"Rp"#,##0');

// Tentukan range tabel
$lastRow = $row - 1;
$range = "A1:I{$lastRow}";

// Style header
$sheet->getStyle("A1:I1")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'color' => ['rgb' => '4CAF50']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER,
    ]
]);

// Border tabel
$sheet->getStyle($range)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
]);

// Auto width untuk semua kolom
foreach(range('A','I') as $col){
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Format angka untuk biaya
$sheet->getStyle("I2:I{$lastRow}")
      ->getNumberFormat()
      ->setFormatCode('"Rp"#,##0');

// Output file Excel
$filename = "Laporan_Harian_{$start}_sd_{$end}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
