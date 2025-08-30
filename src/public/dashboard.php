<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../lib/auth.php";
require_once __DIR__ . "/../models/RideModel.php";
require_once __DIR__ . "/../models/ReservationModel.php";
require_once __DIR__ . "/../models/ReviewModel.php";

require_login();

$rm   = new RideModel($pdo);
$resm = new ReservationModel($pdo);
$revM = new ReviewModel($pdo);

// helper: format "YYYY-mm-dd HH:ii[:ss]" -> "dd/mm/YYYY HH:ii" en Europe/Paris
function fmt_dt(?string $s): string {
  if (!$s) return '';
  $tz = new DateTimeZone('Europe/Paris');
  $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string)$s, $tz)
     ?: DateTimeImmutable::createFromFormat('Y-m-d H:i',    substr((string)$s,0,16), $tz);
  return $dt ? $dt->format('d/m/Y H:i') : htmlspecialchars((string)$s);
}

$userId = current_user_id();
$role   = current_user_role();

$myRides = ($role === 'driver') ? $rm->listByUser($userId) : [];
$myRes   = $resm->listByUser($userId);

$driverReviews = ($role === 'driver') ? $revM->listForDriver($userId) : [];
$driverAvg     = ($role === 'driver') ? $revM->averageForDriver($userId) : null;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
<?php require_once __DIR__ . "/partials/nav.php"; ?>

<h2>Dashboard (<?= htmlspecialchars($role) ?>)</h2>

<?php if ($role === 'driver'): ?>
  <h3>Mes trajets publiés</h3>
  <ul>
    <?php foreach ($myRides as $r): ?>
      <li>
        <?= htmlspecialchars($r['origin']) ?> → <?= htmlspecialchars($r['destination']) ?>
        le <?= fmt_dt($r['ride_date'] ?? '') ?>
        (places <?= (int)$r['seats'] ?>, prix <?= htmlspecialchars($r['price']) ?>€)
        <a href="/rides_edit.php?id=<?= (int)$r['id'] ?>">Modifier</a>
        <form method="post" action="/rides_delete.php" style="display:inline" onsubmit="return confirm('Supprimer ce trajet ?');">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button>Supprimer</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>

  <h3>Avis reçus <?= $driverAvg !== null ? "(moyenne ⭐ " . number_format((float)$driverAvg, 2) . ")" : "" ?></h3>
  <ul>
    <?php foreach ($driverReviews as $rv): ?>
      <li>
        ⭐ <?= (int)$rv['rating'] ?> — <?= htmlspecialchars($rv['comment'] ?? '') ?>
        (trajet <?= htmlspecialchars($rv['origin']) ?> → <?= htmlspecialchars($rv['destination']) ?>
         le <?= fmt_dt($rv['ride_date'] ?? '') ?>)
      </li>
    <?php endforeach; ?>
    <?php if (!$driverReviews): ?><li>Aucun avis reçu pour l’instant.</li><?php endif; ?>
  </ul>
<?php endif; ?>

<h3>Mes réservations</h3>
<ul>
  <?php foreach ($myRes as $r): ?>
    <li>
      <?= htmlspecialchars($r['origin']) ?> → <?= htmlspecialchars($r['destination']) ?>
      le <?= fmt_dt($r['ride_date'] ?? '') ?>
      <?php $existing = $revM->findByRideAndUser($r['ride_id'], $userId); ?>
      <?php if ($existing): ?>
        — Votre avis : ⭐ <?= (int)$existing['rating'] ?>
        <a href="/reviews_edit.php?id=<?= (int)$existing['id'] ?>">Modifier</a>
        <form method="post" action="/reviews_delete.php" style="display:inline" onsubmit="return confirm('Supprimer cet avis ?');">
          <input type="hidden" name="id" value="<?= (int)$existing['id'] ?>">
          <button>Supprimer</button>
        </form>
      <?php else: ?>
        <a href="/reviews_new.php?ride_id=<?= (int)$r['ride_id'] ?>">Laisser un avis</a>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>
</body>
</html>
