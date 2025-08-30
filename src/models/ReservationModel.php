<?php
class ReservationModel {
  private PDO $pdo;
  function __construct(PDO $pdo){ $this->pdo=$pdo; }

  // ⚠️ Ne PAS gérer de transaction ici, elle est gérée par reserve.php
  function create($rideId,$userId,$seats=1){
    $st=$this->pdo->prepare("INSERT INTO reservations (ride_id,user_id,seats) VALUES (?,?,?)");
    $st->execute([$rideId,$userId,$seats]);
    return $this->pdo->lastInsertId();
  }

  function listByUser($userId){
    $sql="SELECT rs.*, r.origin, r.destination, r.ride_date
          FROM reservations rs
          JOIN rides r ON r.id=rs.ride_id
          WHERE rs.user_id=?
          ORDER BY rs.created_at DESC";
    $st=$this->pdo->prepare($sql); $st->execute([$userId]); return $st->fetchAll();
  }

  function cancelOwned($reservationId, $userId){
    $st = $this->pdo->prepare(
      "SELECT rs.*, r.user_id as driver_id
       FROM reservations rs
       JOIN rides r ON r.id=rs.ride_id
       WHERE rs.id=? AND rs.user_id=?"
    );
    $st->execute([$reservationId,$userId]);
    return $st->fetch();
  }

  function deleteById($reservationId){
    $st = $this->pdo->prepare("DELETE FROM reservations WHERE id=?");
    return $st->execute([$reservationId]);
  }
}

