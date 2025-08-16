<?php
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function csrf_verify(): void {
  if (!is_post() || empty($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(400);
    exit('Invalid CSRF token');
  }
}

function redirect(string $path): void {
  header('Location: '.$path);
  exit;
}

function flash_set(string $k, string $v): void { $_SESSION['flash'][$k] = $v; }
function flash_get(string $k): ?string {
  if (isset($_SESSION['flash'][$k])) { $v = $_SESSION['flash'][$k]; unset($_SESSION['flash'][$k]); return $v; }
  return null;
}

function money_id(float $n): string { return 'Rp '.number_format($n, 2, ',', '.'); }