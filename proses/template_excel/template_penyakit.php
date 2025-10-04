<?php
require '../../vendor/autoload.php'; 
include '../../koneksi.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Template Penyakit");

// ==== HEADER ====
$headers = [
    "Nama Penyakit",
    "Deskripsi",
    "Obat yang Direkomendasikan" // dropdown dari tabel obat
];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// Autosize
$lastCol = chr(ord('A') + count($headers) - 1);
foreach (range('A', $lastCol) as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// ==== SHEET REFERENSI: Obat ====
$refSheet = $spreadsheet->createSheet();
$refSheet->setTitle("Ref Obat");
$refSheet->setCellValue('A1', 'Nama Obat');

$qObat = mysqli_query($conn, "SELECT nama_obat FROM obat ORDER BY nama_obat ASC");
$row = 2;
while ($o = mysqli_fetch_assoc($qObat)) {
    $refSheet->setCellValue("A{$row}", $o['nama_obat']);
    $row++;
}
$refObatLast = $row - 1;

// ==== DROPDOWN untuk kolom C (Obat) ====
for ($i = 2; $i <= 500; $i++) {
    $validationObat = $sheet->getCell("C{$i}")->getDataValidation();
    $validationObat->setType(DataValidation::TYPE_LIST);
    $validationObat->setErrorStyle(DataValidation::STYLE_STOP);
    $validationObat->setAllowBlank(true);
    $validationObat->setShowInputMessage(true);
    $validationObat->setShowErrorMessage(true);
    $validationObat->setShowDropDown(true);
    $validationObat->setErrorTitle('Input salah');
    $validationObat->setError('Pilih Obat dari daftar.');
    $validationObat->setPromptTitle('Obat');
    $validationObat->setPrompt('Silakan pilih dari daftar.');
    $validationObat->setFormula1("'Ref Obat'!\$A\$2:\$A\${$refObatLast}");
}

// ==== DOWNLOAD ====
$filename = "template_penyakit.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
