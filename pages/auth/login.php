<?php
require_once __DIR__.'/../../inc/db.php';
require_once __DIR__.'/../../inc/functions.php';

if (current_user()) redirect(BASE_URL.'/index.php');

$error = null;

if (is_post()) {
  csrf_verify();
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  if ($user && password_verify($pass, $user['password_hash'])) {
    $_SESSION['user'] = [
      'id' => $user['id'],
      'name' => $user['name'],
      'email' => $user['email'],
      'role' => $user['role'],
    ];
    redirect(BASE_URL.'/index.php');
  } else {
    $error = 'Email atau password salah';
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css">
</head>
<body class="auth">
  <div class="auth-card">
    <h2>Masuk</h2>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <button type="submit">Login</button>
    </form>
    <p>Belum punya akun? <a href="<?= e(BASE_URL) ?>/index.php?page=register">Register</a></p>
  </div>
</body>
</html>