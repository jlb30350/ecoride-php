<?php
class RideModel {
  private PDO $pdo;
  function __construct(PDO $pdo){ $this->pdo = $pdo; }

  function find($id){
    $st = $this->pdo->prepare("SELECT * FROM rides WHERE id=?");
    $st->execute([$id]);
    return $st->fetch();
  }

  function listByUser($userId){
    $st = $this->pdo->prepare("SELECT * FROM rides WHERE user_id=? ORDER BY ride_date DESC");
    $st->execute([$userId]);
    return $st->fetchAll();
  }

  function create($userId,$o,$d,$date,$seats,$price){
    $st = $this->pdo->prepare("INSERT INTO rides (user_id,origin,destination,ride_date,seats,price) VALUES (?,?,?,?,?,?)");
    $st->execute([$userId,$o,$d,$date,$seats,$price]);
    return $this->pdo->lastInsertId();
  }

  function update($rideId,$userId,$o,$d,$date,$seats,$price){
    $st=$this->pdo->prepare("UPDATE rides SET origin=?, destination=?, ride_date=?, seats=?, price=? WHERE id=? AND user_id=?");
    return $st->execute([$o,$d,$date,$seats,$price,$rideId,$userId]);
  }

  function deleteOwned($rideId,$userId){
    $st=$this->pdo->prepare("DELETE FROM rides WHERE id=? AND user_id=?");
    return $st->execute([$rideId,$userId]);
  }

  function incrementSeats($rideId,$count){
    $st=$this->pdo->prepare("UPDATE rides SET seats=seats+? WHERE id=?");
    return $st->execute([$count,$rideId]);
  }

  // recherche jour ou semaine
  function search($o,$d,$date=null,$dateFrom=null,$dateTo=null){
    $sql = "SELECT r.*, u.email AS driver_email
            FROM rides r
            JOIN users u ON u.id = r.user_id
            WHERE r.origin LIKE ? AND r.destination LIKE ?";
    $params = ["%$o%","%$d%"];

    if ($date) {
      $sql .= " AND r.ride_date = ?";
      $params[] = $date;
    } elseif ($dateFrom && $dateTo) {
      $sql .= " AND r.ride_date BETWEEN ? AND ?";
      $params[] = $dateFrom;
      $params[] = $dateTo;
    }

    $sql .= " ORDER BY r.ride_date ASC";
    $st = $this->pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
  }
}

