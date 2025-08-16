<?php
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/functions.php';
$u = current_user();

$tab = $_GET['tab'] ?? 'summary';

// Create account
if ($tab === 'accounts' && is_post()) {
  csrf_verify();
  $name = trim($_POST['name'] ?? '');
  $type = $_POST['type'] ?? 'other';
  if ($name) {
    $stmt = db()->prepare('INSERT INTO finance_accounts (user_id, name, type) VALUES (?,?,?)');
    $stmt->execute([$u['id'], $name, $type]);
    flash_set('ok','Akun ditambahkan');
  }
  redirect(BASE_URL.'/index.php?page=finance&tab=accounts');
}

// Create category
if ($tab === 'categories' && is_post()) {
  csrf_verify();
  $name = trim($_POST['name'] ?? '');
  $kind = $_POST['kind'] ?? 'expense';
  if ($name) {
    $stmt = db()->prepare('INSERT INTO finance_categories (user_id, name, kind) VALUES (?,?,?)');
    $stmt->execute([$u['id'], $name, $kind]);
    flash_set('ok','Kategori ditambahkan');
  }
  redirect(BASE_URL.'/index.php?page=finance&tab=categories');
}

// Create transaction
if ($tab === 'transactions' && is_post()) {
  csrf_verify();
  $account_id = (int) ($_POST['account_id'] ?? 0);
  $category_id = (int) ($_POST['category_id'] ?? 0);
  $trx_date = $_POST['trx_date'] ?? date('Y-m-d');
  $amount = (float) ($_POST['amount'] ?? 0);
  $note = trim($_POST['note'] ?? '');

  // Pastikan account & category milik user
  $chk1 = db()->prepare('SELECT id FROM finance_accounts WHERE id=? AND user_id=?');
  $chk1->execute([$account_id, $u['id']]);
  $chk2 = db()->prepare('SELECT id FROM finance_categories WHERE id=? AND user_id=?');
  $chk2->execute([$category_id, $u['id']]);
  if ($chk1->fetch() && $chk2->fetch() && $amount > 0) {
    $stmt = db()->prepare('INSERT INTO finance_transactions (user_id, account_id, category_id, trx_date, amount, note) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$u['id'], $account_id, $category_id, $trx_date, $amount, $note]);
    flash_set('ok','Transaksi dicatat');
  } else {
    flash_set('ok','Gagal: data tidak valid');
  }
  redirect(BASE_URL.'/index.php?page=finance&tab=transactions');
}

?>
<h1>Keuangan Saya</h1>

<div class="tabs">
  <a class="<?= $tab==='summary'?'active':'' ?>" href="<?= e(BASE_URL) ?>/index.php?page=finance&tab=summary">Ringkasan</a>
  <a class="<?= $tab==='accounts'?'active':'' ?>" href="<?= e(BASE_URL) ?>/index.php?page=finance&tab=accounts">Akun</a>
  <a class="<?= $tab==='categories'?'active':'' ?>" href="<?= e(BASE_URL) ?>/index.php?page=finance&tab=categories">Kategori</a>
  <a class="<?= $tab==='transactions'?'active':'' ?>" href="<?= e(BASE_URL) ?>/index.php?page=finance&tab=transactions">Transaksi</a>
</div>

<?php if ($msg = flash_get('ok')): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>

<?php if ($tab==='summary'):
  $stmt = db()->prepare('SELECT account_name, account_type, balance FROM v_account_balances WHERE user_id=? ORDER BY account_name');
  $stmt->execute([$u['id']]);
  $balances = $stmt->fetchAll();

  $month = $_GET['m'] ?? date('Y-m');
  $stmt2 = db()->prepare("
    SELECT fc.kind, SUM(ft.amount) total
    FROM finance_transactions ft
    JOIN finance_categories fc ON fc.id=ft.category_id
    WHERE ft.user_id=? AND DATE_FORMAT(ft.trx_date,'%Y-%m')=?
    GROUP BY fc.kind
  ");
  $stmt2->execute([$u['id'], $month]);
  $sum = ['income'=>0,'expense'=>0];
  foreach ($stmt2 as $r) $sum[$r['kind']] = (float)$r['total'];
?>
  <div class="card">
    <h3>Saldo Akun</h3>
    <?php if (!$balances): ?><p>Belum ada akun.</p><?php else: ?>
      <table class="tbl">
        <thead><tr><th>Akun</th><th>Tipe</th><th>Saldo</th></tr></thead>
        <tbody>
          <?php foreach ($balances as $b): ?>
            <tr>
              <td><?= e($b['account_name']) ?></td>
              <td><?= e($b['account_type']) ?></td>
              <td><?= e(money_id((float)$b['balance'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <div class="grid two">
    <div class="card">
      <h3>Ringkasan Bulan (<?= e($month) ?>)</h3>
      <p><strong>Pemasukan:</strong> <?= e(money_id($sum['income'])) ?></p>
      <p><strong>Pengeluaran:</strong> <?= e(money_id($sum['expense'])) ?></p>
      <p><strong>Selisih:</strong> <?= e(money_id($sum['income'] - $sum['expense'])) ?></p>
      <form method="get" class="inline">
        <input type="hidden" name="page" value="finance">
        <input type="hidden" name="tab" value="summary">
        <label>Bulan</label>
        <input type="month" name="m" value="<?= e($month) ?>">
        <button type="submit">Terapkan</button>
      </form>
    </div>
    <div class="card">
      <h3>Transaksi Terakhir</h3>
      <?php
      $stmt3 = db()->prepare("
        SELECT ft.*, fa.name account, fc.name category, fc.kind
        FROM finance_transactions ft
        JOIN finance_accounts fa ON fa.id=ft.account_id
        JOIN finance_categories fc ON fc.id=ft.category_id
        WHERE ft.user_id=? ORDER BY ft.id DESC LIMIT 10
      ");
      $stmt3->execute([$u['id']]);
      $tx = $stmt3->fetchAll();
      ?>
      <table class="tbl">
        <thead><tr><th>Tanggal</th><th>Akun</th><th>Kategori</th><th>Tipe</th><th>Jumlah</th></tr></thead>
        <tbody>
        <?php foreach ($tx as $t): ?>
          <tr>
            <td><?= e($t['trx_date']) ?></td>
            <td><?= e($t['account']) ?></td>
            <td><?= e($t['category']) ?></td>
            <td><?= e($t['kind']) ?></td>
            <td><?= e(money_id((float)$t['amount'] * ($t['kind']==='expense'?-1:1))) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php elseif ($tab==='accounts'):
  $stmt = db()->prepare('SELECT * FROM finance_accounts WHERE user_id=? ORDER BY name');
  $stmt->execute([$u['id']]);
  $rows = $stmt->fetchAll();
?>
  <div class="grid two">
    <div class="card">
      <h3>Tambah Akun</h3>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <label>Nama Akun</label>
        <input type="text" name="name" required>
        <label>Tipe</label>
        <select name="type">
          <option value="cash">Cash</option>
          <option value="bank">Bank</option>
          <option value="ewallet">E-Wallet</option>
          <option value="other">Lainnya</option>
        </select>
        <button type="submit">Simpan</button>
      </form>
    </div>
    <div class="card">
      <h3>Daftar Akun</h3>
      <table class="tbl">
        <thead><tr><th>Nama</th><th>Tipe</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr><td><?= e($r['name']) ?></td><td><?= e($r['type']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php elseif ($tab==='categories'):
  $stmt = db()->prepare('SELECT * FROM finance_categories WHERE user_id=? ORDER BY kind, name');
  $stmt->execute([$u['id']]);
  $rows = $stmt->fetchAll();
?>
  <div class="grid two">
    <div class="card">
      <h3>Tambah Kategori</h3>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <label>Nama</label>
        <input type="text" name="name" required>
        <label>Jenis</label>
        <select name="kind">
          <option value="income">Pemasukan</option>
          <option value="expense">Pengeluaran</option>
        </select>
        <button type="submit">Simpan</button>
      </form>
    </div>
    <div class="card">
      <h3>Daftar Kategori</h3>
      <table class="tbl">
        <thead><tr><th>Nama</th><th>Jenis</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr><td><?= e($r['name']) ?></td><td><?= e($r['kind']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php elseif ($tab==='transactions'):
  $acc = db()->prepare('SELECT * FROM finance_accounts WHERE user_id=? ORDER BY name');
  $acc->execute([$u['id']]);
  $accounts = $acc->fetchAll();

  $cat = db()->prepare('SELECT * FROM finance_categories WHERE user_id=? ORDER BY kind, name');
  $cat->execute([$u['id']]);
  $categories = $cat->fetchAll();

  $list = db()->prepare("
    SELECT ft.*, fa.name account, fc.name category, fc.kind
    FROM finance_transactions ft
    JOIN finance_accounts fa ON fa.id=ft.account_id
    JOIN finance_categories fc ON fc.id=ft.category_id
    WHERE ft.user_id=? ORDER BY ft.id DESC LIMIT 100
  ");
  $list->execute([$u['id']]);
  $tx = $list->fetchAll();
?>
  <div class="grid two">
    <div class="card">
      <h3>Tambah Transaksi</h3>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <label>Tanggal</label>
        <input type="date" name="trx_date" value="<?= e(date('Y-m-d')) ?>" required>
        <label>Akun</label>
        <select name="account_id" required>
          <option value="">Pilih</option>
          <?php foreach ($accounts as $a): ?>
            <option value="<?= e($a['id']) ?>"><?= e($a['name']) ?> (<?= e($a['type']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <label>Kategori</label>
        <select name="category_id" required>
          <option value="">Pilih</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?> - <?= e($c['kind']) ?></option>
          <?php endforeach; ?>
        </select>
        <label>Jumlah</label>
        <input type="number" step="0.01" name="amount" required>
        <label>Catatan</label>
        <input type="text" name="note" placeholder="Opsional">
        <button type="submit">Simpan</button>
      </form>
    </div>
    <div class="card">
      <h3>Riwayat</h3>
      <table class="tbl">
        <thead><tr><th>Tanggal</th><th>Akun</th><th>Kategori</th><th>Tipe</th><th>Jumlah</th><th>Catatan</th></tr></thead>
        <tbody>
          <?php foreach ($tx as $t): ?>
            <tr>
              <td><?= e($t['trx_date']) ?></td>
              <td><?= e($t['account']) ?></td>
              <td><?= e($t['category']) ?></td>
              <td><?= e($t['kind']) ?></td>
              <td><?= e(money_id((float)$t['amount'] * ($t['kind']==='expense'?-1:1))) ?></td>
              <td><?= e($t['note']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>