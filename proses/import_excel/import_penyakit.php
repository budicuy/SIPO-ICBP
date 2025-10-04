<?php
include '../../koneksi.php';
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_excel'])) {
    if (is_uploaded_file($_FILES['file_excel']['tmp_name'])) {
        $filePath = $_FILES['file_excel']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $sukses = 0;
            $gagal = 0;

            // Lewati baris pertama (header)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $nama_penyakit = trim($row[0] ?? '');
                $deskripsi     = trim($row[1] ?? '');
                $nama_obat     = trim($row[2] ?? '');

                if ($nama_penyakit == '' || $nama_obat == '') {
                    $gagal++;
                    continue;
                }

                // Cek apakah penyakit sudah ada
                $qPenyakit = mysqli_query($conn, "SELECT id_penyakit FROM penyakit WHERE nama_penyakit='" . mysqli_real_escape_string($conn, $nama_penyakit) . "'");
                if (mysqli_num_rows($qPenyakit) > 0) {
                    $penyakit = mysqli_fetch_assoc($qPenyakit);
                    $id_penyakit = $penyakit['id_penyakit'];
                    // update deskripsi jika kosong sebelumnya
                    mysqli_query($conn, "UPDATE penyakit SET deskripsi='" . mysqli_real_escape_string($conn, $deskripsi) . "' WHERE id_penyakit=$id_penyakit");
                } else {
                    mysqli_query($conn, "INSERT INTO penyakit (nama_penyakit, deskripsi) VALUES ('" . mysqli_real_escape_string($conn, $nama_penyakit) . "', '" . mysqli_real_escape_string($conn, $deskripsi) . "')");
                    $id_penyakit = mysqli_insert_id($conn);
                }

                // Cari id_obat berdasarkan nama_obat
                $qObat = mysqli_query($conn, "SELECT id_obat FROM obat WHERE nama_obat='" . mysqli_real_escape_string($conn, $nama_obat) . "'");
                if (mysqli_num_rows($qObat) > 0) {
                    $obat = mysqli_fetch_assoc($qObat);
                    $id_obat = $obat['id_obat'];

                    // Cek apakah relasi sudah ada
                    $cekRelasi = mysqli_query($conn, "SELECT * FROM penyakit_obat WHERE id_penyakit=$id_penyakit AND id_obat=$id_obat");
                    if (mysqli_num_rows($cekRelasi) == 0) {
                        mysqli_query($conn, "INSERT INTO penyakit_obat (id_penyakit, id_obat) VALUES ($id_penyakit, $id_obat)");
                    }

                    $sukses++;
                } else {
                    $gagal++; // obat tidak ditemukan
                }
            }

            header("Location: ../../master_data/penyakit/penyakit.php?status=success&msg=Import selesai. Berhasil: $sukses, Gagal: $gagal");
            exit;

        } catch (Exception $e) {
            header("Location: ../../master_data/penyakit/penyakit.php?status=error&msg=" . urlencode("Gagal membaca file: " . $e->getMessage()));
            exit;
        }
    } else {
        header("Location: ../../master_data/penyakit/penyakit.php?status=error&msg=File tidak ditemukan");
        exit;
    }
} else {
    header("Location: ../../master_data/penyakit/penyakit.php");
    exit;
}
?>
