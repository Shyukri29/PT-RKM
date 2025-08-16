<?php
require_once __DIR__.'/../../inc/db.php';
require_once __DIR__.'/../../inc/functions.php';

if (current_user()) redirect(BASE_URL.'/index.php');

$error = null;
$success = null;

if (is_post()) {
  csrf_verify();
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  $role = 'admin';

  // User pertama akan dibuat admin; berikutnya staff
  $count = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
  if ($count > 0) $role = 'staff';

  if (!$name || !$email || strlen($pass) < 6) {
    $error = 'Lengkapi data dan gunakan password minimal 6 karakter';
  } else {
    try {
      $stmt = db()->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)');
      $stmt->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
      $success = 'Registrasi berhasil. Silakan login.';
    } catch (PDOException $e) {
      $error = 'Email sudah terdaftar';
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register</title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css">
</head>
<body class="auth">
  <div class="auth-card">
    <h2>Daftar</h2>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <label>Nama</label>
      <input type="text" name="name" required>
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required minlength="6">
      <button type="submit">Register</button>
    </form>
    <p>Sudah punya akun? <a href="<?= e(BASE_URL) ?>/index.php?page=login">Login</a></p>
  </div>
</body>
</html>