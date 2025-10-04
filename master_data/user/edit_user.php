<?php
include '../../koneksi.php';

$error = "";
$alert = null; // Flag SweetAlert
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: user_list.php");
    exit;
}

// Ambil data user lama
$res = mysqli_query($conn, "SELECT * FROM user WHERE id_user = $id");
$user = mysqli_fetch_assoc($res);

if (!$user) {
    die("User tidak ditemukan!");
}

$username = $user['username'];
$nama_lengkap = $user['nama_lengkap'];
$role = $user['role'];

// List role tetap sama
$roleList = ['Super Admin', 'Admin', 'User'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password_raw = $_POST['password'];
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $role = $_POST['role'];

    if ($username == "" || $nama_lengkap == "" || $role == "") {
        $error = "Isi semua field wajib (kecuali password jika tidak diganti).";
    } else {
        // cek username unik (kecuali dirinya sendiri)
        $cek = mysqli_query($conn, "SELECT id_user FROM user WHERE username='$username' AND id_user!=$id");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Username sudah terpakai!";
        } else {
            if (!empty($password_raw)) {
                $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
                $sql = "UPDATE user 
                        SET username='$username', password='$password_hash', nama_lengkap='$nama_lengkap', role='$role' 
                        WHERE id_user=$id";
            } else {
                $sql = "UPDATE user 
                        SET username='$username', nama_lengkap='$nama_lengkap', role='$role' 
                        WHERE id_user=$id";
            }

            if (mysqli_query($conn, $sql)) {
                // Set alert sukses
                $alert = ['success', 'Berhasil', 'User berhasil diperbarui!'];
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
<title>Edit User</title>
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
  <h2 class="mb-4">Edit User</h2>

  <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Password (kosongkan jika tidak diganti)</label>
      <input type="password" name="password" class="form-control">
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

    <button type="submit" class="btn btn-warning">Update</button>
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
