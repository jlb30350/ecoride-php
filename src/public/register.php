<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/UserModel.php";



if($_SERVER['REQUEST_METHOD']==='POST'){
  $email=trim($_POST['email']??'');
  $password=$_POST['password']??'';
  $role=$_POST['role']??'passenger';
  if(!$email || !$password){ $err="Champs requis"; }
  else {
    $um=new UserModel($pdo);
    if($um->findByEmail($email)){ $err="Email déjà utilisé"; }
    else {
      $id=$um->create($email,$password,$role);
      $_SESSION['user']=['id'=>$id,'email'=>$email,'role'=>$role];
      header("Location: /dashboard.php"); exit;
    }
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Inscription</title><link rel="stylesheet" href="/styles.css"></head>
<body><?php require_once __DIR__."/partials/nav.php"; ?><h2>Créer un compte</h2>
<?php if(!empty($err)) echo "<p class='err'>".htmlspecialchars($err)."</p>"; ?>
<form method="post">
  <input type="email" name="email" placeholder="Email" required>
  <input type="password" name="password" placeholder="Mot de passe" required>
  <select name="role">
    <option value="passenger">Passager</option>
    <option value="driver">Conducteur</option>
  </select>
  <button>S'inscrire</button>
</form>
</body></html>
