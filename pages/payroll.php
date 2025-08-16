<?php
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/functions.php';
require_once __DIR__.'/../inc/middleware.php';

// Create payroll
if (is_post()) {
  csrf_verify();
  $employee_id = (int) ($_POST['employee_id'] ?? 0);
  $period_start = $_POST['period_start'] ?? '';
  $period_end = $_POST['period_end'] ?? '';
  $base_salary = (float) ($_POST['base_salary'] ?? 0);
  $allowances = (float) ($_POST['allowances'] ?? 0);
  $deductions = (float) ($_POST['deductions'] ?? 0);
  $stmt = db()->prepare('INSERT INTO payrolls (employee_id, period_start, period_end, base_salary, allowances, deductions) VALUES (?,?,?,?,?,?)');
  $stmt->execute([$employee_id, $period_start, $period_end, $base_salary, $allowances, $deductions]);
  flash_set('ok','Slip gaji dibuat');
  redirect(BASE_URL.'/index.php?page=payroll');
}

// List
$employees = db()->query('SELECT id, full_name FROM employees ORDER BY full_name')->fetchAll();
$payrolls = db()->query('SELECT p.*, e.full_name FROM payrolls p JOIN employees e ON e.id=p.employee_id ORDER BY p.id DESC LIMIT 50')->fetchAll();
?>
<h1>Payroll</h1>
<?php if ($msg = flash_get('ok')): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>

<div class="grid two">
  <div class="card">
    <h3>Buat Slip Gaji</h3>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <label>Karyawan</label>
      <select name="employee_id" required>
        <option value="">Pilih</option>
        <?php foreach ($employees as $emp): ?>
          <option value="<?= e($emp['id']) ?>"><?= e($emp['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Periode Mulai</label>
      <input type="date" name="period_start" required>
      <label>Periode Akhir</label>
      <input type="date" name="period_end" required>
      <label>Gaji Pokok</label>
      <input type="number" step="0.01" name="base_salary" required>
      <label>Tunjangan</label>
      <input type="number" step="0.01" name="allowances" value="0">
      <label>Potongan</label>
      <input type="number" step="0.01" name="deductions" value="0">
      <button type="submit">Simpan</button>
    </form>
  </div>
  <div class="card">
    <h3>Daftar Slip</h3>
    <table class="tbl">
      <thead><tr><th>Karyawan</th><th>Periode</th><th>Gaji Bersih</th></tr></thead>
      <tbody>
        <?php foreach ($payrolls as $p): ?>
          <tr>
            <td><?= e($p['full_name']) ?></td>
            <td><?= e($p['period_start']) ?> s/d <?= e($p['period_end']) ?></td>
            <td><?= e(money_id((float)$p['net_pay'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>