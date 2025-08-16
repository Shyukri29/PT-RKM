<?php
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/functions.php';
require_once __DIR__.'/../inc/middleware.php';
$u = current_user();

// Create/Update
if (is_post()) {
  csrf_verify();
  $id = $_POST['id'] ?? '';
  $full_name = trim($_POST['full_name'] ?? '');
  $position = trim($_POST['position'] ?? '');
  $department = $_POST['department_id'] ?: null;
  $hire_date = $_POST['hire_date'] ?: null;
  $base_salary = (float) ($_POST['base_salary'] ?? 0);

  if ($id) {
    $stmt = db()->prepare('UPDATE employees SET full_name=?, position=?, department_id=?, hire_date=?, base_salary=? WHERE id=?');
    $stmt->execute([$full_name, $position, $department, $hire_date, $base_salary, $id]);
    flash_set('ok','Karyawan diperbarui');
  } else {
    $stmt = db()->prepare('INSERT INTO employees (full_name, position, department_id, hire_date, base_salary) VALUES (?,?,?,?,?)');
    $stmt->execute([$full_name, $position, $department, $hire_date, $base_salary]);
    flash_set('ok','Karyawan ditambahkan');
  }
  redirect(BASE_URL.'/index.php?page=employees');
}

// Delete
if (isset($_GET['del'])) {
  $del = (int) $_GET['del'];
  $stmt = db()->prepare('DELETE FROM employees WHERE id=?');
  $stmt->execute([$del]);
  flash_set('ok','Karyawan dihapus');
  redirect(BASE_URL.'/index.php?page=employees');
}

$departments = db()->query('SELECT * FROM departments ORDER BY name')->fetchAll();
$employees = db()->query('SELECT e.*, d.name AS dept FROM employees e LEFT JOIN departments d ON d.id=e.department_id ORDER BY e.id DESC')->fetchAll();
$edit = null;
if (isset($_GET['edit'])) {
  $stmt = db()->prepare('SELECT * FROM employees WHERE id=?');
  $stmt->execute([(int)$_GET['edit']]);
  $edit = $stmt->fetch();
}
?>
<h1>Data Karyawan</h1>

<?php if ($msg = flash_get('ok')): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>

<div class="grid two">
  <div class="card">
    <h3><?= $edit ? 'Edit Karyawan' : 'Tambah Karyawan' ?></h3>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= e($edit['id']) ?>"><?php endif; ?>
      <label>Nama Lengkap</label>
      <input type="text" name="full_name" required value="<?= e($edit['full_name'] ?? '') ?>">
      <label>Posisi</label>
      <input type="text" name="position" value="<?= e($edit['position'] ?? '') ?>">
      <label>Departemen</label>
      <select name="department_id">
        <option value="">-</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= e($d['id']) ?>" <?= isset($edit['department_id']) && $edit['department_id']==$d['id'] ? 'selected':''; ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Tanggal Mulai</label>
      <input type="date" name="hire_date" value="<?= e($edit['hire_date'] ?? '') ?>">
      <label>Gaji Pokok</label>
      <input type="number" step="0.01" name="base_salary" value="<?= e($edit['base_salary'] ?? 0) ?>">
      <button type="submit"><?= $edit ? 'Update' : 'Simpan' ?></button>
    </form>
  </div>
  <div class="card">
    <h3>Daftar</h3>
    <table class="tbl">
      <thead><tr><th>Nama</th><th>Posisi</th><th>Dept</th><th>Gaji</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach ($employees as $row): ?>
          <tr>
            <td><?= e($row['full_name']) ?></td>
            <td><?= e($row['position']) ?></td>
            <td><?= e($row['dept'] ?? '-') ?></td>
            <td><?= e(money_id((float)$row['base_salary'])) ?></td>
            <td>
              <a href="<?= e(BASE_URL) ?>/index.php?page=employees&edit=<?= e($row['id']) ?>">Edit</a> |
              <a class="danger" href="<?= e(BASE_URL) ?>/index.php?page=employees&del=<?= e($row['id']) ?>" onclick="return confirm('Hapus karyawan?')">Hapus</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>