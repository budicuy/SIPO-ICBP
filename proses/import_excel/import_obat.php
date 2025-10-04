<?php
include '../../koneksi.php';

// Load PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;
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

                $nama_obat        = mysqli_real_escape_string($conn, trim($row[0]));
                $keterangan       = mysqli_real_escape_string($conn, trim($row[1]));
                $jenis_obat_nama  = mysqli_real_escape_string($conn, trim($row[2]));
                $satuan_obat_nama = mysqli_real_escape_string($conn, trim($row[3]));
                $stok_awal        = (int) trim($row[4]);
                $stok_masuk       = (int) trim($row[5]);
                $stok_keluar      = (int) trim($row[6]);
                $stok_akhir       = (int) trim($row[7]);
                $jumlah_kemasan   = (int) trim($row[8]);
                $harga_satuan     = (int) trim($row[9]);
                $harga_kemasan    = (int) trim($row[10]);

                // --- Cari id_jenis_obat dari nama ---
                $qJenis = mysqli_query($conn, "SELECT id_jenis_obat FROM jenis_obat WHERE nama_jenis='$jenis_obat_nama' LIMIT 1");
                $id_jenis_obat = ($qJenis && mysqli_num_rows($qJenis) > 0) ? mysqli_fetch_assoc($qJenis)['id_jenis_obat'] : 0;

                // --- Cari id_satuan dari nama ---
                $qSatuan = mysqli_query($conn, "SELECT id_satuan FROM satuan_obat WHERE nama_satuan='$satuan_obat_nama' LIMIT 1");
                $id_satuan = ($qSatuan && mysqli_num_rows($qSatuan) > 0) ? mysqli_fetch_assoc($qSatuan)['id_satuan'] : 0;

                // --- Cek duplikat berdasarkan nama_obat & id_jenis_obat & id_satuan ---
                $cek = mysqli_query($conn, "
                    SELECT id_obat FROM obat 
                    WHERE nama_obat='$nama_obat' 
                    AND id_jenis_obat=$id_jenis_obat 
                    AND id_satuan=$id_satuan
                ");

                if (mysqli_num_rows($cek) == 0 && !empty($nama_obat) && $id_jenis_obat > 0 && $id_satuan > 0) {
                    $sql = "INSERT INTO obat 
                            (nama_obat, keterangan, id_jenis_obat, id_satuan, stok_awal, stok_masuk, stok_keluar, stok_akhir, jumlah_per_kemasan, harga_per_satuan, harga_per_kemasan) 
                            VALUES 
                            ('$nama_obat','$keterangan',$id_jenis_obat,$id_satuan,$stok_awal,$stok_masuk,$stok_keluar,$stok_akhir,$jumlah_kemasan,$harga_satuan,$harga_kemasan)";

                    if (mysqli_query($conn, $sql)) {
                        $sukses++;
                    } else {
                        error_log('Error Insert Obat: ' . mysqli_error($conn));
                        $gagal++;
                    }
                } else {
                    $gagal++;
                }
            }

            // Redirect ke halaman obat dengan notifikasi
            header("Location: ../../master_data/obat/obat.php?status=success&msg=Import selesai. Berhasil: $sukses, Gagal: $gagal");
            exit;

        } catch (Exception $e) {
            header("Location: ../../master_data/obat/obat.php?status=error&msg=" . urlencode("Gagal membaca file: " . $e->getMessage()));
            exit;
        }
    } else {
        header("Location: ../../master_data/obat/obat.php?status=error&msg=File tidak ditemukan");
        exit;
    }
} else {
    header("Location: ../../master_data/obat/obat.php");
    exit;
}
?>
