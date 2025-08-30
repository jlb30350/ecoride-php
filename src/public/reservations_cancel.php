<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/RideModel.php";
require_once __DIR__."/../models/ReservationModel.php";
require_login();

$resId=(int)($_POST['id'] ?? 0);
if(!$resId){ http_response_code(400); exit("Requête invalide"); }

$rm=new RideModel($pdo); $resm=new ReservationModel($pdo);

$pdo->beginTransaction();
try{
  $row = $resm->cancelOwned($resId, current_user_id());
  if(!$row){ throw new Exception("Réservation introuvable"); }
  // Rendre la place
  $rm->incrementSeats($row['ride_id'], $row['seats']);
  // Supprimer la réservation
  $resm->deleteById($resId);
  $pdo->commit();
  header("Location:/dashboard.php"); exit;
} catch(Exception $e){
  $pdo->rollBack();
  http_response_code(400); exit("Annulation impossible");
}

