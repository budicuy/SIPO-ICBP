<?php
  $current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
/* Highlight menu aktif */
.nav-link.active {
  background-color: #0d6efd !important;
  color: white !important;
  font-weight: bold;
}
</style>

<!-- Sidebar -->
<div class="d-flex flex-column flex-shrink-0 p-3 bg-dark text-white" 
     style="width: 220px; height: 100vh; position: fixed; top: 0; left: 0;">
  
  <!-- Logo Indofood -->
  <div class="text-center mb-3">
    <img src="/SISTEM%20INFORMASI%20KLINIK/assets/img/logo_indoofood.png" 
     alt="Indofood Logo" style="max-width: 80%; height: auto; margin-top: 20px;">
  </div>
  
  <hr>

  <ul class="nav nav-pills flex-column mb-auto">

    <li class="nav-item">
      <a href="/SISTEM INFORMASI KLINIK/dashboard.php" 
         class="nav-link text-white <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
         <i class="bi bi-speedometer2"></i> Dashboard
      </a>
    </li>

    <!-- Master Data -->
    <li>
      <a class="nav-link text-white d-flex justify-content-between align-items-center <?= in_array($current_page, ['karyawan.php', 'pasien.php', 'user_list.php', 'obat.php', 'penyakit.php', 'departemen.php']) ? '' : 'collapsed' ?>" 
         data-bs-toggle="collapse" href="#masterData" role="button" 
         aria-expanded="<?= in_array($current_page, ['karyawan.php','pasien.php','user_list.php','obat.php','penyakit.php','departemen.php']) ? 'true' : 'false' ?>" 
         aria-controls="masterData">
        <span><i class="bi bi-folder"></i> Master Data</span>
        <i class="bi bi-caret-down-fill"></i>
      </a>
      <div class="collapse ps-3 <?= in_array($current_page, ['karyawan.php','pasien.php','user_list.php','obat.php','penyakit.php','departemen.php']) ? 'show' : '' ?>" id="masterData">
        <ul class="nav flex-column">
          <li><a href="/SISTEM INFORMASI KLINIK/master_data/karyawan/karyawan.php" class="nav-link text-white <?= $current_page == 'karyawan.php' ? 'active' : '' ?>">Data Karyawan</a></li>
          <li><a href="/SISTEM INFORMASI KLINIK/master_data/pasien/pasien.php" class="nav-link text-white <?= $current_page == 'pasien.php' ? 'active' : '' ?>">Data Pasien</a></li>
          <li><a href="/SISTEM INFORMASI KLINIK/master_data/user/user_list.php" class="nav-link text-white <?= $current_page == 'user_list.php' ? 'active' : '' ?>">Data User</a></li>
          <li><a href="/SISTEM INFORMASI KLINIK/master_data/obat/obat.php" class="nav-link text-white <?= $current_page == 'obat.php' ? 'active' : '' ?>">Data Obat</a></li>
          <li><a href="/SISTEM INFORMASI KLINIK/master_data/penyakit/penyakit.php" class="nav-link text-white <?= $current_page == 'penyakit.php' ? 'active' : '' ?>">Data Diagnosa</a></li>
          <li><a href="/SISTEM INFORMASI KLINIK/master_data/departemen/departemen.php" class="nav-link text-white <?= $current_page == 'departemen.php' ? 'active' : '' ?>">Departemen</a></li>
        </ul>
      </div>
    </li>

    <!-- Rekam Medis -->
    <li>
      <a class="nav-link text-white d-flex justify-content-between align-items-center <?= in_array($current_page, ['tambah_rekam_medis.php', 'daftar_rekam_medis.php', 'surat_sakit.php']) ? '' : 'collapsed' ?>" 
         data-bs-toggle="collapse" href="#rekamMedis" role="button" 
         aria-expanded="<?= in_array($current_page, ['tambah_rekam_medis.php','daftar_rekam_medis.php','surat_sakit.php']) ? 'true' : 'false' ?>" 
         aria-controls="rekamMedis">
        <span><i class="bi bi-file-earmark-medical"></i> Rekam Medis</span>
        <i class="bi bi-caret-down-fill"></i>
      </a>
      <div class="collapse ps-3 <?= in_array($current_page, ['tambah_rekam_medis.php','daftar_rekam_medis.php','surat_sakit.php']) ? 'show' : '' ?>" id="rekamMedis">
        <ul class="nav flex-column">
          <li><a href="/SISTEM INFORMASI KLINIK/rekam_medis/tambah_rekam_medis.php" class="nav-link text-white <?= $current_page == 'tambah_rekam_medis.php' ? 'active' : '' ?>">Tambah Rekam Medis</a></li>
          <li><a href="/SISTEM INFORMASI KLINIK/rekam_medis/daftar_rekam_medis.php" class="nav-link text-white <?= $current_page == 'daftar_rekam_medis.php' ? 'active' : '' ?>">Daftar Rekam Medis</a></li>
          <li><a href="/SISTEM INFORMASI KLINIK/rekam_medis/surat_sakit.php" class="nav-link text-white <?= $current_page == 'surat_sakit.php' ? 'active' : '' ?>">Surat Sakit</a></li>
        </ul>
      </div>
    </li>

    <!-- Report -->
    <li>
      <a class="nav-link text-white d-flex justify-content-between align-items-center <?= in_array($current_page, ['report.php','daftar_kunjungan.php']) ? '' : 'collapsed' ?>" 
         data-bs-toggle="collapse" href="#reportMenu" role="button" 
         aria-expanded="<?= in_array($current_page, ['report.php','daftar_kunjungan.php']) ? 'true' : 'false' ?>" 
         aria-controls="reportMenu">
        <span><i class="bi bi-bar-chart-line"></i> Report</span>
        <i class="bi bi-caret-down-fill"></i>
      </a>
      <div class="collapse ps-3 <?= in_array($current_page, ['report.php','daftar_kunjungan.php']) ? 'show' : '' ?>" id="reportMenu">
        <ul class="nav flex-column">
          <li><a href="/SISTEM INFORMASI KLINIK/report/report.php" class="nav-link text-white <?= $current_page == 'report.php' ? 'active' : '' ?>">Laporan Transaksi</a></li>
          <li><a href="/SISTEM INFORMASI KLINIK/kunjungan/daftar_kunjungan.php" class="nav-link text-white <?= $current_page == 'daftar_kunjungan.php' ? 'active' : '' ?>">Daftar Kunjungan</a></li>
        </ul>
      </div>
    </li>

    <!-- Logout -->
    <li>
      <a href="/SISTEM INFORMASI KLINIK/logout.php" class="nav-link text-white">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </li>
  </ul>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const logoutLink = document.querySelector('a[href="/SISTEM INFORMASI KLINIK/logout.php"]');
    if (logoutLink) {
        logoutLink.addEventListener("click", function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: "Apakah Anda yakin ingin keluar dari sistem?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = logoutLink.getAttribute("href");
                }
            });
        });
    }
});
</script>
