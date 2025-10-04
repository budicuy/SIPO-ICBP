<?php
require '../../vendor/autoload.php';
include '../../koneksi.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ==== HEADER KARYAWAN ====
$headers = [
    "NIK",
    "Nama Karyawan",
    "Departemen",            // dropdown
    "Jenis Kelamin (L/P)",   // dropdown
    "No HP",
    "Alamat",
    "Tanggal Lahir (d-m-Y)"
];

// Tulis header di baris 1
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// Auto size kolom biar rapi
$lastCol = chr(ord('A') + count($headers) - 1);
foreach (range('A', $lastCol) as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// ==== SHEET REFERENSI: Departemen ====
$refSheet = $spreadsheet->createSheet();
$refSheet->setTitle("Ref Data");
$refSheet->setCellValue('A1', 'Nama Departemen');
$refSheet->setCellValue('C1', 'Jenis Kelamin');

// Ambil daftar departemen dari database
$q = mysqli_query($conn, "SELECT nama_departemen FROM departemen ORDER BY nama_departemen ASC");
$row = 2;
while ($d = mysqli_fetch_assoc($q)) {
    $refSheet->setCellValue("A{$row}", $d['nama_departemen']);
    $row++;
}
$refLastDept = $row - 1; // baris terakhir departemen

// Isi referensi jenis kelamin
$refSheet->setCellValue("C2", "Laki - Laki");
$refSheet->setCellValue("C3", "Perempuan");

// Auto size
$refSheet->getColumnDimension('A')->setAutoSize(true);
$refSheet->getColumnDimension('C')->setAutoSize(true);

// ==== BUAT DROPDOWN (DATA VALIDATION) ====
// Kolom C = Departemen
// Kolom D = Jenis Kelamin
for ($i = 2; $i <= 500; $i++) { // sediakan sampai 500 baris input
    // Validasi Departemen
    $validationDept = $sheet->getCell("C{$i}")->getDataValidation();
    $validationDept->setType(DataValidation::TYPE_LIST);
    $validationDept->setErrorStyle(DataValidation::STYLE_STOP);
    $validationDept->setAllowBlank(true);
    $validationDept->setShowInputMessage(true);
    $validationDept->setShowErrorMessage(true);
    $validationDept->setShowDropDown(true);
    $validationDept->setErrorTitle('Input salah');
    $validationDept->setError('Pilih Departemen dari daftar.');
    $validationDept->setPromptTitle('Departemen');
    $validationDept->setPrompt('Silakan pilih dari daftar.');
    $validationDept->setFormula1("'Ref Data'!\$A\$2:\$A\${$refLastDept}");

    // Validasi Jenis Kelamin
    $validationJK = $sheet->getCell("D{$i}")->getDataValidation();
    $validationJK->setType(DataValidation::TYPE_LIST);
    $validationJK->setErrorStyle(DataValidation::STYLE_STOP);
    $validationJK->setAllowBlank(true);
    $validationJK->setShowInputMessage(true);
    $validationJK->setShowErrorMessage(true);
    $validationJK->setShowDropDown(true);
    $validationJK->setErrorTitle('Input salah');
    $validationJK->setError('Pilih Jenis Kelamin dari daftar.');
    $validationJK->setPromptTitle('Jenis Kelamin');
    $validationJK->setPrompt('Silakan pilih dari daftar.');
    $validationJK->setFormula1("'Ref Data'!\$C\$2:\$C\$3");
}

// ==== ATUR NAMA FILE DAN DOWNLOAD ====
$filename = "template_karyawan.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

// Simpan ke output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
