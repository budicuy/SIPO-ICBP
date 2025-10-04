<?php
// tambah_user.php
include '../../koneksi.php';

$error = "";
$alert = null; // Flag SweetAlert
$username = $nama_lengkap = $role = "";

// Ambil list role (jika nanti mau fleksibel, bisa dari DB)
$roleList = ['Super Admin', 'Admin', 'User'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password_raw = $_POST['password'];
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $role = $_POST['role'];

    if ($username == "" || $password_raw == "" || $nama_lengkap == "" || $role == "") {
        $error = "Isi semua field wajib.";
    } else {
        $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);

        // cek username unik
        $cek = mysqli_query($conn, "SELECT id_user FROM user WHERE username='$username'");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Username sudah terpakai!";
        } else {
            $sql = "INSERT INTO user (username, password, nama_lengkap, role) 
                    VALUES ('$username','$password_hash','$nama_lengkap','$role')";
            if (mysqli_query($conn, $sql)) {
                // Set alert sukses
                $alert = ['success', 'Berhasil', 'User berhasil ditambahkan!'];
            } else {
                $error = mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tambah User</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
  }
  .sidebar {
    width: 250px;
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
  }
  .main-content {
    margin-left: 250px;
    padding: 20px;
  }
</style>
</head>
<body>

<?php include '../../sidebar.php'; ?>

<div class="main-content">
  <h2 class="mb-4">Tambah User</h2>

  <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Nama Lengkap</label>
      <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($nama_lengkap) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Role</label>
      <select name="role" class="form-control" required>
        <option value="">-- Pilih Role --</option>
        <?php foreach ($roleList as $r): ?>
          <option value="<?= $r ?>" <?= $r == $role ? 'selected' : '' ?>><?= $r ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="user_list.php" class="btn btn-secondary">Batal</a>
  </form>
</div>

<?php if ($alert): ?>
<script>
Swal.fire({
    icon: '<?= $alert[0] ?>',
    title: '<?= $alert[1] ?>',
    text: '<?= $alert[2] ?>',
    confirmButtonText: 'OK'
}).then(() => {
    window.location.href = 'user_list.php';
});
</script>
<?php endif; ?>

</body>
</html>
