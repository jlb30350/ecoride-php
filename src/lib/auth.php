<?php
session_start();

function require_login() {
  if (!isset($_SESSION['user'])) {
    header('Location: /login.php'); exit;
  }
}

function current_user_id() {
  return $_SESSION['user']['id'] ?? null;
}

function current_user_role() {
  return $_SESSION['user']['role'] ?? null;
}

