<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/ReviewModel.php";
require_login();
if (current_user_role()!=='passenger'){ http_response_code(403); exit("Passager uniquement"); }

$revM = new ReviewModel($pdo);
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$review = $revM->findOwned($id, current_user_id());
if(!$review){ http_response_code(404); exit("Avis introuvable"); }

$err=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $rating=(int)($_POST['rating']??0);
  $comment=trim($_POST['comment']??'');
  if($rating<1||$rating>5){ $err="Note invalide (1 à 5)"; }
  else { $revM->update($id, current_user_id(), $rating, $comment); header("Location:/dashboard.php"); exit; }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Modifier avis</title>
<link rel="stylesheet" href="/styles.css">
</head><body>
<?php require_once __DIR__."/partials/nav.php"; ?>
<h2>Modifier l’avis</h2>
<?php if($err): ?><p class="err"><?=htmlspecialchars($err)?></p><?php endif; ?>
<form method="post">
  <input type="hidden" name="id" value="<?=$review['id']?>">
  <label>Note (1–5)</label>
  <input type="number" name="rating" min="1" max="5" value="<?= (int)$review['rating']?>" required>
  <label>Commentaire</label>
  <textarea name="comment" rows="4"><?= htmlspecialchars($review['comment'] ?? '') ?></textarea>
  <button>Enregistrer</button>
</form>
</body></html>

