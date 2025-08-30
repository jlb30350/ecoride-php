<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/RideModel.php";

$o    = trim($_GET['origin'] ?? '');
$d    = trim($_GET['destination'] ?? '');
$week = $_GET['ride_week'] ?? null;   // type="week"
$date = $_GET['ride_date'] ?? null;   // si tu gardes le champ jour

$rides = [];
$start = $end = null;

if ($o && $d) {
  $rm = new RideModel($pdo);

  if ($week && preg_match('/^(\d{4})-W(\d{2})$/', $week, $m)) {
    $year = (int)$m[1];
    $weekNum = (int)$m[2];

    $dto = new DateTime();
    $dto->setISODate($year, $weekNum); // lundi
    $start = $dto->format('Y-m-d');
    $dto->modify('+6 day');            // dimanche
    $end   = $dto->format('Y-m-d');

    $rides = $rm->search($o, $d, null, $start, $end);
  } elseif ($date) {
    $rides = $rm->search($o, $d, $date);
  } else {
    $rides = $rm->search($o, $d);      // sans filtre date
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Résultats</title>
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
<?php require_once __DIR__."/partials/nav.php"; ?>

<h2>Résultats</h2>

<?php if($start && $end): ?>
  <p>Semaine du <strong><?=htmlspecialchars($start)?></strong> au <strong><?=htmlspecialchars($end)?></strong></p>
<?php endif; ?>

<?php if(!$rides): ?>
  <p>Aucun trajet trouvé.</p>
<?php endif; ?>

<ul>
<?php foreach($rides as $r): ?>
  <li>
    <strong><?=htmlspecialchars($r['origin'])?> → <?=htmlspecialchars($r['destination'])?></strong>
    le <?=htmlspecialchars($r['ride_date'])?> — places: <?=$r['seats']?> — prix: <?=$r['price']?>€
    <?php if($r['seats'] > 0): ?>
      <form method="post" action="/reserve.php" style="display:inline">
        <input type="hidden" name="ride_id" value="<?=$r['id']?>">
        <button>Réserver</button>
      </form>
    <?php else: ?>
      <em>Complet</em>
    <?php endif; ?>
  </li>
<?php endforeach; ?>
</ul>
</body>
</html>

