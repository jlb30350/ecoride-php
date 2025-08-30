<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/UserModel.php";



if($_SERVER['REQUEST_METHOD']==='POST'){
  $email=trim($_POST['email']??''); $password=$_POST['password']??'';
  $um=new UserModel($pdo);
  $u=$um->findByEmail($email);
  if(!$u || !password_verify($password,$u['password_hash'])){ $err="Identifiants invalides"; }
  else { $_SESSION['user']=['id'=>$u['id'],'email'=>$u['email'],'role'=>$u['role']]; header("Location:/dashboard.php"); exit; }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Connexion</title><link rel="stylesheet" href="/styles.css"></head>
<body><?php require_once __DIR__."/partials/nav.php"; ?><h2>Connexion</h2>
<?php if(!empty($err)) echo "<p class='err'>".htmlspecialchars($err)."</p>"; ?>
<form method="post">
  <input type="email" name="email" placeholder="Email" required>
  <input type="password" name="password" placeholder="Mot de passe" required>
  <button>Se connecter</button>
</form>
</body></html>
