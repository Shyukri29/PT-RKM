<?php
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/functions.php';
require_once __DIR__.'/../inc/middleware.php';

$page = $_GET['page'] ?? 'dashboard';
$public_pages = ['login','register'];

if (!in_array($page, $public_pages, true)) {
  require_login();
}

function render_header(string $title='HRM App'): void {
  $u = current_user();
  ?>
  <!doctype html>
  <html lang="id">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css">
  </head>
  <body>
    <nav class="topnav">
      <div class="brand"><a href="<?= e(BASE_URL) ?>/index.php">HRM</a></div>
      <?php if ($u): ?>
      <ul>
        <li><a href="<?= e(BASE_URL) ?>/index.php?page=dashboard">Dashboard</a></li>
        <li><a href="<?= e(BASE_URL) ?>/index.php?page=employees">Karyawan</a></li>
        <li><a href="<?= e(BASE_URL) ?>/index.php?page=payroll">Payroll</a></li>
        <li><a href="<?= e(BASE_URL) ?>/index.php?page=finance">Keuangan</a></li>
        <?php if ($u['role']==='admin'): ?>
          <li><span class="badge">Admin</span></li>
        <?php endif; ?>
      </ul>
      <div class="userbox">
        <span><?= e($u['name']) ?></span>
        <a class="btn-link" href="<?= e(BASE_URL) ?>/index.php?page=logout">Logout</a>
      </div>
      <?php endif; ?>
    </nav>
    <main class="container">
  <?php
}

function render_footer(): void {
  ?>
    </main>
    <footer class="footer">© <?= date('Y') ?> HRM App</footer>
  </body></html>
  <?php
}

switch ($page) {
  case 'login': require __DIR__.'/../pages/auth/login.php'; break;
  case 'register': require __DIR__.'/../pages/auth/register.php'; break;
  case 'logout':
    session_destroy();
    redirect(BASE_URL.'/index.php?page=login');
    break;
  case 'dashboard': render_header('Dashboard'); require __DIR__.'/../pages/dashboard.php'; render_footer(); break;
  case 'employees': render_header('Karyawan'); require __DIR__.'/../pages/employees.php'; render_footer(); break;
  case 'payroll': render_header('Payroll'); require __DIR__.'/../pages/payroll.php'; render_footer(); break;
  case 'finance': render_header('Keuangan'); require __DIR__.'/../pages/finance.php'; render_footer(); break;
  default:
    http_response_code(404);
    echo "Not Found";
}