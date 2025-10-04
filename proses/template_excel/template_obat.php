<?php
require '../../vendor/autoload.php'; 
include '../../koneksi.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

$headers = [
    "Nama Obat",
    "Keterangan",
    "Jenis Obat",         // dropdown
    "Satuan Obat",        // dropdown
    "Stok Awal",
    "Stok Masuk",
    "Stok Keluar",
    "Stok Akhir",
    "Jumlah per Kemasan",
    "Harga per Satuan",
    "Harga per Kemasan"
];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// auto size kolom
$lastCol = chr(ord('A') + count($headers) - 1);
foreach (range('A', $lastCol) as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// ==== SHEET REFERENSI: Jenis Obat ====
$refSheet1 = $spreadsheet->createSheet();
$refSheet1->setTitle("Ref Jenis Obat");
$refSheet1->setCellValue('A1', 'Nama Jenis Obat');

$qJenis = mysqli_query($conn, "SELECT nama_jenis FROM jenis_obat ORDER BY nama_jenis ASC");
$row = 2;
while ($d = mysqli_fetch_assoc($qJenis)) {
    $refSheet1->setCellValue("A{$row}", $d['nama_jenis']);
    $row++;
}
$refJenisLast = $row - 1; // baris terakhir data jenis

// ==== SHEET REFERENSI: Satuan Obat ====
$refSheet2 = $spreadsheet->createSheet();
$refSheet2->setTitle("Ref Satuan Obat");
$refSheet2->setCellValue('A1', 'Nama Satuan');

$qSatuan = mysqli_query($conn, "SELECT nama_satuan FROM satuan_obat ORDER BY nama_satuan ASC");
$row = 2;
while ($s = mysqli_fetch_assoc($qSatuan)) {
    $refSheet2->setCellValue("A{$row}", $s['nama_satuan']);
    $row++;
}
$refSatuanLast = $row - 1; // baris terakhir data satuan

// ==== BUAT DROPDOWN (DATA VALIDATION) ====
// Kolom "Jenis Obat" = kolom C
// Kolom "Satuan Obat" = kolom D
for ($i = 2; $i <= 500; $i++) { // sediakan sampai 500 baris input
    // Data Validation untuk Jenis Obat
    $validationJenis = $sheet->getCell("C{$i}")->getDataValidation();
    $validationJenis->setType(DataValidation::TYPE_LIST);
    $validationJenis->setErrorStyle(DataValidation::STYLE_STOP);
    $validationJenis->setAllowBlank(true);
    $validationJenis->setShowInputMessage(true);
    $validationJenis->setShowErrorMessage(true);
    $validationJenis->setShowDropDown(true);
    $validationJenis->setErrorTitle('Input salah');
    $validationJenis->setError('Pilih Jenis Obat dari daftar.');
    $validationJenis->setPromptTitle('Jenis Obat');
    $validationJenis->setPrompt('Silakan pilih dari daftar.');
    $validationJenis->setFormula1("'Ref Jenis Obat'!\$A\$2:\$A\${$refJenisLast}");

    // Data Validation untuk Satuan Obat
    $validationSatuan = $sheet->getCell("D{$i}")->getDataValidation();
    $validationSatuan->setType(DataValidation::TYPE_LIST);
    $validationSatuan->setErrorStyle(DataValidation::STYLE_STOP);
    $validationSatuan->setAllowBlank(true);
    $validationSatuan->setShowInputMessage(true);
    $validationSatuan->setShowErrorMessage(true);
    $validationSatuan->setShowDropDown(true);
    $validationSatuan->setErrorTitle('Input salah');
    $validationSatuan->setError('Pilih Satuan Obat dari daftar.');
    $validationSatuan->setPromptTitle('Satuan Obat');
    $validationSatuan->setPrompt('Silakan pilih dari daftar.');
    $validationSatuan->setFormula1("'Ref Satuan Obat'!\$A\$2:\$A\${$refSatuanLast}");
}

// ==== ATUR NAMA FILE DAN DOWNLOAD ====
$filename = "template_obat.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
