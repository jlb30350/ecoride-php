<?php // src/public/partials/nav.php
require_once __DIR__."/../../lib/auth.php"; // pour current_user_id()

?>
<header class="topbar">
  <a class="brand" href="/">EcoRide</a>
  <nav class="menu">
    <a href="/">Accueil</a>
    <a href="/rides_search.php?origin=&destination=">Chercher</a>
    <?php if (current_user_id()): ?>
      <a href="/dashboard.php">Dashboard</a>
      <a href="/rides_new.php">Publier un trajet</a>
      <a href="/logout.php">Déconnexion</a>
    <?php else: ?>
      <a href="/login.php">Connexion</a>
      <a href="/register.php">Inscription</a>
    <?php endif; ?>
  </nav>
</header>

