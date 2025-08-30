<?php
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../lib/auth.php";
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>EcoRide — Accueil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/styles.css" />
</head>
<body>
  <?php require_once __DIR__ . "/partials/nav.php"; ?>

  <header class="container hero">
    <div class="hero__content">
      <h1>EcoRide</h1>
      <p class="subtitle">Cherche un trajet, réserve, roule.</p>
      <a href="/rides_search.php" class="btn btn--primary">Chercher un trajet</a>
    </div>
    <div class="hero__illu">🚗🌱</div>
  </header>
</body>
</html>
