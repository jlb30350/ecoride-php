<?php
session_start();
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Ecoride</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnKkK-Bq/F-9lW5uT2n-w5S2v5Vz9b-p5Uq-p5" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="alert alert-success" role="alert">
                    Vous avez été déconnecté avec succès.
                </div>
                <p>Merci de votre visite !</p>
                <a href="index.php" class="btn btn-primary mt-3">Retour à l'accueil</a>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>
