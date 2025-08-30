<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/RideModel.php";
require_login();
if(current_user_role()!=='driver'){ http_response_code(403); exit("Accès conducteur uniquement"); }

$rideId=(int)($_POST['id'] ?? 0);
if(!$rideId){ http_response_code(400); exit("Requête invalide"); }

$rm=new RideModel($pdo);
$ride=$rm->find($rideId);
if(!$ride || $ride['user_id']!=current_user_id()){ http_response_code(404); exit("Trajet introuvable"); }

$rm->deleteOwned($rideId, current_user_id());
header("Location:/dashboard.php"); exit;

