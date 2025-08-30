<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/RideModel.php';

require_login();
if (current_user_role() !== 'driver') {
  http_response_code(403);
  exit("Accès conducteur uniquement");
}

$rm = new RideModel($pdo);

// id depuis GET ou POST
$rideId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$ride   = $rideId ? $rm->find($rideId) : null;

if (!$ride || (int)$ride['user_id'] !== (int)current_user_id()) {
  http_response_code(404);
  exit("Trajet introuvable");
}

// valeurs par défaut = valeurs existantes
$error = null;
$old = [
  'origin'      => (string)$ride['origin'],
  'destination' => (string)$ride['destination'],
  // si ride_date est DATETIME, on garde juste YYYY-MM-DD pour l’input date
  'ride_date'   => substr((string)$ride['ride_date'], 0, 10),
  'seats'       => (string)$ride['seats'],
  'price'       => (string)$ride['price'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // récupère ce que l’utilisateur a saisi
  $old['origin']      = trim($_POST['origin']      ?? $old['origin']);
  $old['destination'] = trim($_POST['destination'] ?? $old['destination']);
  $old['ride_date']   = trim($_POST['ride_date']   ?? $old['ride_date']);
  $old['seats']       = (string) (int)($_POST['seats'] ?? $old['seats']);
  $old['price']       = (string) (float)($_POST['price'] ?? $old['price']);

  if ($old['origin'] === '' || $old['destination'] === '' || $old['ride_date'] === '' || (int)$old['seats'] < 1 || (float)$old['price'] <= 0) {
    $error = "Champs invalides";
  } else {
    try {
      // 👉 update() ne validera la date que si elle a changé (cf. RideModel patché)
      $ok = $rm->update(
        $rideId,
        current_user_id(),
        $old['origin'],
        $old['destination'],
        $old['ride_date'],
        (int)$old['seats'],
        (float)$old['price']
      );
      if ($ok) {
        header("Location: /dashboard.php"); exit;
      }
      $error = "Impossible de mettre à jour ce trajet.";
    } catch (\InvalidArgumentException $e) {
      // ex : "La date doit être aujourd’hui ou future."
      $error = $e->getMessage();
    } catch (\Throwable $e) {
      $error = "Erreur inattendue, réessaie.";
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Modifier un trajet</title>
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
<?php require_once __DIR__ . "/partials/nav.php"; ?>

<h2>Modifier le trajet</h2>

<?php if ($error): ?>
  <div style="background:#fee;border:1px solid #fbb;padding:10px;border-radius:8px;color:#a00;margin-bottom:12px;">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<form method="post" class="card" style="padding:16px;max-width:640px;">
  <input type="hidden" name="id" value="<?= (int)$rideId ?>">
  <label>Départ
    <input name="origin" required value="<?= htmlspecialchars($old['origin']) ?>">
  </label>
  <br>
  <label>Arrivée
    <input name="destination" required value="<?= htmlspecialchars($old['destination']) ?>">
  </label>
  <br>
  <label>Date
    <!-- bloque le passé côté navigateur ; le back valide aussi -->
    <input type="date" name="ride_date" required
           min="<?= date('Y-m-d') ?>"
           value="<?= htmlspecialchars(substr($old['ride_date'],0,10)) ?>">
  </label>
  <br>
  <label>Places
    <input type="number" name="seats" min="1" required value="<?= (int)$old['seats'] ?>">
  </label>
  <br>
  <label>Prix (€)
    <input type="number" name="price" step="0.01" min="0" required value="<?= htmlspecialchars($old['price']) ?>">
  </label>
  <br>
  <button class="btn btn--primary">Enregistrer</button>
</form>
</body>
</html>
