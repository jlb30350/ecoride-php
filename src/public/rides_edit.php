<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/RideModel.php";
require_login();
if(current_user_role()!=='driver'){ http_response_code(403); exit("Accès conducteur uniquement"); }

$rm = new RideModel($pdo);
$rideId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$ride = $rideId ? $rm->find($rideId) : null;
if(!$ride || $ride['user_id'] != current_user_id()){ http_response_code(404); exit("Trajet introuvable"); }

if($_SERVER['REQUEST_METHOD']==='POST'){
  $o=trim($_POST['origin']); $d=trim($_POST['destination']);
  $date=$_POST['ride_date']; $seats=(int)$_POST['seats']; $price=(float)$_POST['price'];
  if(!$o||!$d||!$date||$seats<1||$price<=0){ $err="Champs invalides"; }
  else {
    $rm->update($rideId, current_user_id(), $o,$d,$date,$seats,$price);
    header("Location:/dashboard.php"); exit;
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Modifier trajet</title><link rel="stylesheet" href="/styles.css"></head>
<body><h2>Modifier le trajet</h2>
<?php if(!empty($err)) echo "<p class='err'>".htmlspecialchars($err)."</p>"; ?>
<form method="post">
  <input type="hidden" name="id" value="<?=$ride['id']?>">
  <input name="origin" value="<?=htmlspecialchars($ride['origin'])?>" required>
  <input name="destination" value="<?=htmlspecialchars($ride['destination'])?>" required>
  <input type="date" name="ride_date" value="<?=htmlspecialchars($ride['ride_date'])?>" required>
  <input type="number" name="seats" min="1" value="<?=htmlspecialchars($ride['seats'])?>" required>
  <input type="number" step="0.01" name="price" value="<?=htmlspecialchars($ride['price'])?>" required>
  <button>Enregistrer</button>
</form>
</body></html>

