<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/RideModel.php";
require_once __DIR__."/../models/ReservationModel.php";
require_once __DIR__."/../models/ReviewModel.php";

require_login(); // vérifie la session tout de suite

$rm   = new RideModel($pdo);
$resm = new ReservationModel($pdo);
$revM = new ReviewModel($pdo);

// Données utilisateur
$myRides = current_user_role()==='driver' ? $rm->listByUser(current_user_id()) : [];
$myRes   = $resm->listByUser(current_user_id());

// Pour l'encart "Avis reçus" côté conducteur
$driverReviews = current_user_role()==='driver' ? $revM->listForDriver(current_user_id()) : [];
$driverAvg     = current_user_role()==='driver' ? $revM->averageForDriver(current_user_id()) : null;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
<?php require_once __DIR__."/partials/nav.php"; ?>

<h2>Dashboard (<?=htmlspecialchars(current_user_role())?>)</h2>

<?php if (current_user_role()==='driver'): ?>
  <h3>Mes trajets publiés</h3>
  <ul>
    <?php foreach ($myRides as $r): ?>
      <li>
        <?=htmlspecialchars($r['origin'])?> → <?=htmlspecialchars($r['destination'])?> le <?=htmlspecialchars($r['ride_date'])?>
        (places <?= (int)$r['seats'] ?>, prix <?= htmlspecialchars($r['price']) ?>€)
        <a href="/rides_edit.php?id=<?=$r['id']?>">Modifier</a>
        <form method="post" action="/rides_delete.php" style="display:inline" onsubmit="return confirm('Supprimer ce trajet ?');">
          <input type="hidden" name="id" value="<?=$r['id']?>">
          <button>Supprimer</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>

  <h3>Avis reçus <?= $driverAvg !== null ? "(moyenne ⭐ ".number_format($driverAvg,2).")" : "" ?></h3>
  <ul>
    <?php foreach ($driverReviews as $rv): ?>
      <li>
        ⭐ <?= (int)$rv['rating'] ?> — <?= htmlspecialchars($rv['comment'] ?? '') ?>
        (trajet <?=htmlspecialchars($rv['origin'])?> → <?=htmlspecialchars($rv['destination'])?> le <?=htmlspecialchars($rv['ride_date'])?>)
      </li>
    <?php endforeach; ?>
    <?php if (!$driverReviews): ?><li>Aucun avis reçu pour l’instant.</li><?php endif; ?>
  </ul>
<?php endif; ?>

<h3>Mes réservations</h3>
<ul>
  <?php foreach ($myRes as $r): ?>
    <li>
      <?=htmlspecialchars($r['origin'])?> → <?=htmlspecialchars($r['destination'])?> 
      le <?=htmlspecialchars($r['ride_date'])?>

      <?php $existing = $revM->findByRideAndUser($r['ride_id'], current_user_id()); ?>
      <?php if ($existing): ?>
        — Votre avis : ⭐ <?= (int)$existing['rating'] ?>
        <a href="/reviews_edit.php?id=<?=$existing['id']?>">Modifier</a>
        <form method="post" action="/reviews_delete.php" style="display:inline" onsubmit="return confirm('Supprimer cet avis ?');">
          <input type="hidden" name="id" value="<?=$existing['id']?>">
          <button>Supprimer</button>
        </form>
      <?php else: ?>
        <a href="/reviews_new.php?ride_id=<?=$r['ride_id']?>">Laisser un avis</a>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>
</body>
</html>

