<?php
// multiple_hapus_karyawan.php
header('Content-Type: application/json');
include '../../../koneksi.php';

// Pastikan request POST & ada data id_karyawan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_karyawan'])) {
    $ids = $_POST['id_karyawan'];

    if (!empty($ids)) {
        // Convert ke integer untuk keamanan
        $idList = implode(",", array_map('intval', $ids));

        // Query hapus
        $sql = "DELETE FROM karyawan WHERE id_karyawan IN ($idList)";
        $delete = mysqli_query($conn, $sql);

        if ($delete) {
            echo json_encode([
                "status" => "success",
                "message" => "Data karyawan berhasil dihapus."
            ]);
            exit;
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Gagal menghapus data: " . mysqli_error($conn)
            ]);
            exit;
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Tidak ada karyawan yang dipilih."
        ]);
        exit;
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Permintaan tidak valid."
    ]);
    exit;
}
