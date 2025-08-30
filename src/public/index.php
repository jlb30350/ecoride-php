<?php require_once __DIR__."/../config/db.php"; ?>
<?php require_once __DIR__."/../lib/auth.php"; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>EcoRide</title>
  <link rel="stylesheet" href="/styles.css">
</head>
<body>
<?php require_once __DIR__."/partials/nav.php"; ?>
<h1>EcoRide</h1>
<p>Chercher un trajet</p>
<form method="get" action="/rides_search.php">
  <input name="origin" placeholder="Départ">
  <input name="destination" placeholder="Arrivée">
  <input type="week" name="ride_week" placeholder="Semaine (YYYY-Www)">
<!-- tu peux garder l'ancien champ jour si tu veux, mais un seul suffit -->

  <button>Rechercher</button>
</form>
</body>
</html>

