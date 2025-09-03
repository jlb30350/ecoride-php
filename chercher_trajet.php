<?php
error_reporting(E_ALL); // Affiche toutes les erreurs PHP
ini_set('display_errors', 1); // Force l'affichage des erreurs à l'écran

// On démarre la session pour que la barre de navigation fonctionne
session_start();

// On inclut le fichier de connexion à la base de données
require 'db_connect.php';

// On prépare des variables pour stocker ce que l'utilisateur va taper
$villeDepart = '';
$villeArrivee = '';
$dateTrajet = '';
$trajetsTrouves = [];

// On vérifie si le formulaire de recherche a été envoyé (méthode GET)
if (isset($_GET['depart']) || isset($_GET['arrivee']) || isset($_GET['date'])) {
    
    // On récupère les informations que l'utilisateur a tapées et on enlève les espaces en trop
    $villeDepart = trim(htmlspecialchars($_GET['depart'] ?? ''));
    $villeArrivee = trim(htmlspecialchars($_GET['arrivee'] ?? ''));
    $dateTrajet = trim(htmlspecialchars($_GET['date'] ?? ''));

    // On prépare la base de notre requête SQL
    $sql = "SELECT t.id, t.ville_depart, t.ville_arrivee, t.date_trajet, t.heure_depart, t.places_restantes, t.prix, u.prenom, u.nom_famille
            FROM trajets t
            JOIN utilisateurs u ON t.conducteur_id = u.id
            WHERE 1=1"; 

    $params = []; // Tableau pour stocker les paramètres
    $types = '';  // Chaîne pour stocker les types de paramètres

    // On ajoute les conditions de recherche S'IL Y A UNE VALEUR
    if (!empty($villeDepart)) {
        $sql .= " AND t.ville_depart LIKE ?";
        $params[] = '%' . $villeDepart . '%';
        $types .= 's';
    }
    if (!empty($villeArrivee)) {
        $sql .= " AND t.ville_arrivee LIKE ?";
        $params[] = '%' . $villeArrivee . '%';
        $types .= 's';
    }
    if (!empty($dateTrajet)) {
        $sql .= " AND t.date_trajet = ?"; 
        $params[] = $dateTrajet;
        $types .= 's';
    }

    // On ajoute le tri par date et heure
    $sql .= " ORDER BY t.date_trajet ASC, t.heure_depart ASC";

    // On prépare la requête SQL de manière sécurisée
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $trajetsTrouves = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechercher un trajet sur ECORIDE</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container mt-5">
        <h2 class="text-center mb-4">Rechercher un covoiturage</h2>
        <!-- Formulaire de recherche -->
        <form action="chercher_trajet.php" method="GET" class="card p-4">
            <div class="row">
                <div class="col">
                    <label for="depart" class="form-label">Départ :</label>
                    <input type="text" id="depart" name="depart" placeholder="Ville de départ" class="form-control" value="<?php echo htmlspecialchars($villeDepart); ?>">
                </div>
                <div class="col">
                    <label for="arrivee" class="form-label">Arrivée :</label>
                    <input type="text" id="arrivee" name="arrivee" placeholder="Ville d'arrivée" class="form-control" value="<?php echo htmlspecialchars($villeArrivee); ?>">
                </div>
                <div class="col">
                    <label for="date" class="form-label">Date :</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($dateTrajet); ?>">
                </div>
                <div class="col-auto d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </div>
        </form>

        <!-- Affichage du message de succès si présent dans l'URL -->
        <?php if (isset($_GET['success'])): ?>
            <p class="mt-3 text-center text-success"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

        <section class="resultats-trajets mt-5">
            <h3>Résultats de la recherche</h3>
            <?php if (!empty($trajetsTrouves)) { ?>
                <?php foreach ($trajetsTrouves as $trajet) { ?>
                    <div class="card p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p>De: <strong><?php echo htmlspecialchars($trajet['ville_depart']); ?></strong> à: <strong><?php echo htmlspecialchars($trajet['ville_arrivee']); ?></strong></p>
                                <p>Date: <?php echo htmlspecialchars($trajet['date_trajet']); ?> à <?php echo htmlspecialchars($trajet['heure_depart']); ?></p>
                                <p>Places disponibles: <?php echo htmlspecialchars($trajet['places_restantes']); ?></p>
                                <p>Prix: <?php echo htmlspecialchars($trajet['prix']); ?> €</p>
                                <p>Conducteur: <?php echo htmlspecialchars($trajet['prenom'] . ' ' . $trajet['nom_famille']); ?></p>
                            </div>
                            <?php if (isset($_SESSION['user_id']) && $trajet['places_restantes'] > 0): ?>
                                <form action="traitement_reservation.php" method="POST">
                                    <input type="hidden" name="trajet_id" value="<?php echo htmlspecialchars($trajet['id']); ?>">
                                    <label for="places_reservees">Places :</label>
                                    <input type="number" id="places_reservees" name="places_reservees" min="1" max="<?php echo htmlspecialchars($trajet['places_restantes']); ?>" value="1">
                                    <button type="submit">Réserver</button>
                                </form>
                            <?php else: ?>
                                <p>Impossible de réserver</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p>Aucun trajet trouvé pour cette recherche.</p>
            <?php } ?>
        </section>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
