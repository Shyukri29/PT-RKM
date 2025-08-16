<?php
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/functions.php';
$u = current_user();

$emp_count = (int) db()->query('SELECT COUNT(*) FROM employees')->fetchColumn();
$pay_last = db()->query('SELECT net_pay, created_at FROM payrolls ORDER BY id DESC LIMIT 5')->fetchAll();

$fin = db()->prepare('SELECT account_name, balance FROM v_account_balances WHERE user_id=? ORDER BY account_name');
$fin->execute([$u['id']]);
$balances = $fin->fetchAll();
?>
<h1>Dashboard</h1>

<div class="grid">
  <div class="card">
    <h3>Total Karyawan</h3>
    <div class="big"><?= $emp_count ?></div>
  </div>
  <div class="card">
    <h3>Ringkasan Akun Saya</h3>
    <?php if (!$balances): ?>
      <p>Belum ada akun keuangan.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($balances as $b): ?>
          <li><strong><?= e($b['account_name']) ?>:</strong> <?= e(money_id((float)$b['balance'])) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>Payroll Terakhir</h3>
    <?php if (!$pay_last): ?>
      <p>Belum ada data payroll.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($pay_last as $p): ?>
          <li><?= e(money_id((float)$p['net_pay'])) ?> <small>(<?= e($p['created_at']) ?>)</small></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>