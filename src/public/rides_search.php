<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../models/RideModel.php';

$rm = new RideModel($pdo);

// --- Params (pré-remplissage du formulaire) ---
$origin = isset($_GET['origin']) ? trim($_GET['origin']) : '';
$dest   = isset($_GET['destination']) ? trim($_GET['destination']) : '';
$week   = isset($_GET['week']) ? trim($_GET['week']) : ''; // format type="week" => YYYY-Www

// Au moins un critère ?
$hasQuery = ($origin !== '' || $dest !== '' || $week !== '');

// Résultats par défaut
$rides = [];
if ($hasQuery) {
  // La méthode search() gère semaine ISO, date ou rien (patchée plus tôt)
  $rides = $rm->search($origin, $dest, $week) ?? [];
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>EcoRide — Chercher</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/styles.css" />
</head>
<body>
  <?php require_once __DIR__ . '/partials/nav.php'; ?>

  <section class="container card search">
    <h1>Rechercher un trajet</h1>

    <form action="/rides_search.php" method="get" class="search__form" style="margin-bottom: 1rem;">
      <div class="field">
        <label for="origin">Départ</label>
        <input id="origin" name="origin" type="text" placeholder="Ex : Paris"
               value="<?= htmlspecialchars($origin) ?>">
      </div>

      <div class="field">
        <label for="destination">Arrivée</label>
        <input id="destination" name="destination" type="text" placeholder="Ex : Lyon"
               value="<?= htmlspecialchars($dest) ?>">
      </div>

      <div class="field">
        <label for="week">Semaine</label>
        <input id="week" name="week" type="week" value="<?= htmlspecialchars($week) ?>">
      </div>

      <button class="btn btn--primary" type="submit">Rechercher</button>
    </form>
  </section>

  <section class="container">
    <h2>Résultats</h2>

    <?php if (!$hasQuery): ?>
      <p>Saisis au moins un critère puis lance la recherche.</p>

    <?php elseif (empty($rides)): ?>
      <p>Aucun trajet trouvé.</p>

    <?php else: ?>
      <ul>
        <?php foreach ($rides as $r): ?>
          <li>
            <?= htmlspecialchars($r['origin']) ?> → <?= htmlspecialchars($r['destination']) ?>
            le <?= htmlspecialchars($r['ride_date'] ?? $r['date'] ?? '') ?>
            — places <?= (int)($r['seats'] ?? 0) ?>
            — <?= htmlspecialchars($r['price'] ?? '') ?> €
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</body>
</html>
