<?php
class UserModel {
  private PDO $pdo;
  function __construct(PDO $pdo){ $this->pdo = $pdo; }
  function findByEmail($email){
    $st = $this->pdo->prepare("SELECT * FROM users WHERE email=?");
    $st->execute([$email]);
    return $st->fetch();
  }
  function create($email, $password, $role){
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $this->pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?,?,?)");
    $st->execute([$email, $hash, $role]);
    return $this->pdo->lastInsertId();
  }
}
