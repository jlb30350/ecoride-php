<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/RideModel.php";

require_login();
if (current_user_role() !== 'driver') {
  echo "<p>Accès conducteur uniquement</p>";
  echo '<p><a href="/dashboard.php">← Retour au Dashboard</a></p>';
  exit;
}


$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $o     = trim($_POST['origin']);
  $d     = trim($_POST['destination']);
  $date  = $_POST['ride_date'];
  $seats = (int)$_POST['seats'];
  $price = (float)$_POST['price'];

  if (!$o || !$d || !$date || $seats < 1 || $price <= 0) {
    $err = "Champs invalides";
  } else {
    $rm = new RideModel($pdo);
    $rm->create(current_user_id(), $o, $d, $date, $seats, $price);
    header("Location:/dashboard.php");
    exit;
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Publier un trajet</title>
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
<?php require_once __DIR__."/partials/nav.php"; ?>

<h2>Publier un trajet</h2>

<?php if ($err): ?>
  <p class="err"><?= htmlspecialchars($err) ?></p>
<?php endif; ?>

<form method="post">
  <input name="origin" placeholder="Départ" required>
  <input name="destination" placeholder="Arrivée" required>
  <input type="date" name="ride_date" required>
  <input type="number" name="seats" min="1" placeholder="Places" required>
  <input type="number" step="0.01" name="price" placeholder="Prix (€)" required>
  <button>Publier</button>
</form>
</body>
</html>

