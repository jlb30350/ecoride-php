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

$rm = new RideModel($pdo);

$err = null;
// Pour re-afficher ce que l'utilisateur a saisi en cas d'erreur
$old = [
  'origin'      => '',
  'destination' => '',
  'ride_date'   => '',
  'seats'       => '1',
  'price'       => '0'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $old['origin']      = trim($_POST['origin']      ?? '');
  $old['destination'] = trim($_POST['destination'] ?? '');
  $old['ride_date']   = trim($_POST['ride_date']   ?? '');
  $old['seats']       = trim($_POST['seats']       ?? '1');
  $old['price']       = trim($_POST['price']       ?? '0');

  // petits checks rapides côté serveur
  if ($old['origin'] === '' || $old['destination'] === '' || $old['ride_date'] === '' || (int)$old['seats'] < 1 || (float)$old['price'] <= 0) {
    $err = "Champs invalides";
  } else {
    try {
      // ⚠️ create() lèvera InvalidArgumentException si la date est passée
      $rm->create(
        current_user_id(),
        $old['origin'],
        $old['destination'],
        $old['ride_date'],
        (int)$old['seats'],
        (float)$old['price']
      );
      header("Location: /dashboard.php");
      exit;
    } catch (\InvalidArgumentException $e) {
      // ex: "La date doit être aujourd’hui ou future."
      $err = $e->getMessage();
    } catch (\Throwable $e) {
      // fallback générique
      $err = "Oups, impossible d’enregistrer le trajet. Réessaie.";
    }
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
  <div style="background:#fee;border:1px solid #fbb;padding:10px;border-radius:8px;color:#a00;margin-bottom:12px;">
    <?= htmlspecialchars($err) ?>
  </div>
<?php endif; ?>

<form method="post" class="card" style="padding:16px;max-width:640px;">
  <label>Départ
    <input name="origin" placeholder="Départ" required
           value="<?= htmlspecialchars($old['origin']) ?>">
  </label>
  <br>
  <label>Arrivée
    <input name="destination" placeholder="Arrivée" required
           value="<?= htmlspecialchars($old['destination']) ?>">
  </label>
  <br>
  <label>Date
    <!-- bloque le passé côté front; le back bloque aussi -->
    <input type="date" name="ride_date" min="<?= date('Y-m-d') ?>" required
           value="<?= htmlspecialchars($old['ride_date']) ?>">
  </label>
  <br>
  <label>Places
    <input type="number" name="seats" min="1" placeholder="Places" required
           value="<?= (int)$old['seats'] ?>">
  </label>
  <br>
  <label>Prix (€)
    <input type="number" step="0.01" min="0" name="price" placeholder="Prix (€)" required
           value="<?= htmlspecialchars($old['price']) ?>">
  </label>
  <br>
  <button class="btn btn--primary">Publier</button>
</form>
</body>
</html>
