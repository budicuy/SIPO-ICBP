<?php
include '../../koneksi.php';

// Load PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
require '../../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_excel'])) {
    if (is_uploaded_file($_FILES['file_excel']['tmp_name'])) {
        $filePath = $_FILES['file_excel']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $sukses = 0;
            $gagal = 0;

            // Lewati baris pertama (header Excel)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                $nik_karyawan  = mysqli_real_escape_string($conn, trim($row[0]));
                $nama_karyawan = mysqli_real_escape_string($conn, trim($row[1]));
                $nama_dept     = strtolower(trim($row[2])); // nama departemen
                $jenis_kelamin = mysqli_real_escape_string($conn, trim($row[3]));
                $no_hp         = mysqli_real_escape_string($conn, trim($row[4]));
                $alamat        = mysqli_real_escape_string($conn, trim($row[5]));
                $tglExcel      = trim($row[6]);

                // --- Cari id_departemen ---
                $qDept = mysqli_query($conn, "SELECT id_departemen FROM departemen WHERE LOWER(TRIM(nama_departemen))='$nama_dept' LIMIT 1");
                if (mysqli_num_rows($qDept) > 0) {
                    $deptData = mysqli_fetch_assoc($qDept);
                    $id_departemen = $deptData['id_departemen'];
                } else {
                    $gagal++;
                    continue; // skip baris kalau departemen tidak ditemukan
                }

                // --- Konversi tanggal lahir ---
                $tanggal_lahir = null;
                if (!empty($tglExcel)) {
                    if (is_numeric($tglExcel)) {
                        // Format serial number Excel
                        $tanggal_lahir = Date::excelToDateTimeObject($tglExcel)->format('Y-m-d');
                    } else {
                        // Format string (20/09/1998 atau 20-09-1998)
                        $tglExcel = str_replace("/", "-", $tglExcel);
                        $parts = explode("-", $tglExcel);

                        if (count($parts) == 3) {
                            // pastikan urutan d-m-Y
                            $tanggal_lahir = date("Y-m-d", strtotime($parts[2] . "-" . $parts[1] . "-" . $parts[0]));
                        }
                    }
                }

                // --- Cek NIK unik ---
                $cek = mysqli_query($conn, "SELECT id_karyawan FROM karyawan WHERE nik_karyawan='$nik_karyawan'");
                if (mysqli_num_rows($cek) == 0 && !empty($nik_karyawan)) {
                    $sql = "INSERT INTO karyawan 
                            (nik_karyawan, nama_karyawan, id_departemen, no_hp, alamat, tanggal_lahir, jenis_kelamin) 
                            VALUES 
                            ('$nik_karyawan','$nama_karyawan','$id_departemen','$no_hp','$alamat'," . 
                            ($tanggal_lahir ? "'$tanggal_lahir'" : "NULL") . ",'$jenis_kelamin')";
                    
                    if (mysqli_query($conn, $sql)) {
                        $sukses++;
                    } else {
                        error_log("Error Insert: " . mysqli_error($conn));
                        $gagal++;
                    }
                } else {
                    $gagal++;
                }
            }

            // Redirect ke halaman karyawan dengan notifikasi
            header("Location: ../../master_data/karyawan/karyawan.php?status=success&msg=Import selesai. Berhasil: $sukses, Gagal: $gagal");
            exit;

        } catch (Exception $e) {
            header("Location: ../../master_data/karyawan/karyawan.php?status=error&msg=" . urlencode("Gagal membaca file: " . $e->getMessage()));
            exit;
        }
    } else {
        header("Location: ../../master_data/karyawan/karyawan.php?status=error&msg=File tidak ditemukan");
        exit;
    }
} else {
    header("Location: ../../master_data/karyawan/karyawan.php");
    exit;
}
?>
