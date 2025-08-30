<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/RideModel.php";
require_once __DIR__."/../models/ReservationModel.php";
require_login();
if($_SERVER['REQUEST_METHOD']!=='POST'){ header("Location:/"); exit; }

$rideId=(int)($_POST['ride_id']??0);
$rm=new RideModel($pdo); $ride=$rm->find($rideId);
if(!$ride){ http_response_code(404); exit("Trajet introuvable"); }
if($ride['seats']<1){ exit("Plus de places"); }

try{
  $pdo->beginTransaction();
  $ok=$rm->decrementSeats($rideId,1);
  if(!$ok){ throw new Exception("Capacité insuffisante"); }
  $res=new ReservationModel($pdo); $res->create($rideId,current_user_id(),1);
  $pdo->commit();
  header("Location:/dashboard.php?ok=1"); exit;
} catch(Exception $e){
  $pdo->rollBack();
  http_response_code(400); exit("Réservation impossible");
}
