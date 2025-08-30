<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/ReviewModel.php";
require_login();
if (current_user_role()!=='passenger'){ http_response_code(403); exit("Passager uniquement"); }

$rideId = (int)($_GET['ride_id'] ?? $_POST['ride_id'] ?? 0);
$err=null; $revM=new ReviewModel($pdo);

if ($_SERVER['REQUEST_METHOD']==='POST'){
  $rating=(int)($_POST['rating']??0);
  $comment=trim($_POST['comment']??'');
  if($rating<1||$rating>5){ $err="Note invalide (1 à 5)"; }
  else{
    // Si l'avis existe déjà, MySQL bloquera (clé unique) → on affiche un msg simple
    if($revM->findByRideAndUser($rideId, current_user_id())){ $err="Vous avez déjà laissé un avis."; }
    else { $revM->create($rideId,current_user_id(),$rating,$comment); header("Location:/dashboard.php"); exit; }
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Laisser un avis</title><link rel="stylesheet" href="/styles.css"></head>
<body>
<?php require_once __DIR__."/partials/nav.php"; ?>
<h2>Laisser un avis</h2>
<?php if($err): ?><p class="err"><?=htmlspecialchars($err)?></p><?php endif; ?>
<form method="post">
  <input type="hidden" name="ride_id" value="<?=$rideId?>">
  <label>Note (1–5)</label>
  <input type="number" name="rating" min="1" max="5" required>
  <label>Commentaire</label>
  <textarea name="comment" rows="4"></textarea>
  <button>Envoyer</button>
</form>
</body></html>

