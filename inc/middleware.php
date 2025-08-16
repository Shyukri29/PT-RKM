<?php
require_once __DIR__.'/db.php';

function current_user(): ?array { return $_SESSION['user'] ?? null; }

function require_login(): void {
  if (!current_user()) redirect(BASE_URL.'/index.php?page=login');
}

function require_admin(): void {
  $u = current_user();
  if (!$u || $u['role'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
  }
}