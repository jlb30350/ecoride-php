<?php
class RideModel {
  private PDO $pdo;
  function __construct(PDO $pdo){ $this->pdo = $pdo; }

  function find($id){
    $st = $this->pdo->prepare("SELECT * FROM rides WHERE id=?");
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC);
  }

  function listByUser($userId){
    $st = $this->pdo->prepare("SELECT * FROM rides WHERE user_id=? ORDER BY ride_date DESC");
    $st->execute([$userId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  function create($userId,$o,$d,$date,$seats,$price){
    $st = $this->pdo->prepare("INSERT INTO rides (user_id,origin,destination,ride_date,seats,price) VALUES (?,?,?,?,?,?)");
    $st->execute([$userId,$o,$d,$date,$seats,$price]);
    return (int)$this->pdo->lastInsertId();
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

  // 🔎 Recherche par ville + (optionnel) jour ou semaine ISO
  // $date peut être :
  //   - "YYYY-Www" (ex: "2025-W36") => semaine ISO (lundi..dimanche)
  //   - "YYYY-MM-DD" (ex: "2025-09-01") => jour précis
  //   - sinon ignoré (ou utiliser $dateFrom/$dateTo pour une plage)
  function search($o, $d, $date = null, $dateFrom = null, $dateTo = null){
    $o = trim((string)$o);
    $d = trim((string)$d);
    $date = trim((string)($date ?? ''));
    $dateFrom = $dateFrom ? trim((string)$dateFrom) : null;
    $dateTo   = $dateTo   ? trim((string)$dateTo)   : null;

    $sql = "SELECT r.*, u.email AS driver_email
            FROM rides r
            JOIN users u ON u.id = r.user_id
            WHERE r.origin LIKE ? AND r.destination LIKE ?";
    $params = ["%{$o}%", "%{$d}%"];

    // Cas 1: semaine ISO "YYYY-Www"
    if ($date !== '' && preg_match('/^(\d{4})-W(\d{2})$/', $date, $m)) {
      $year = (int)$m[1];
      $week = (int)$m[2];

      $dt = new DateTime();
      $dt->setISODate($year, $week);              // lundi de la semaine
      $monday = $dt->format('Y-m-d');
      $sunday = (clone $dt)->modify('+6 days')->format('Y-m-d');

      // Compatible DATE ou DATETIME
      $sql .= " AND DATE(r.ride_date) BETWEEN ? AND ?";
      $params[] = $monday;
      $params[] = $sunday;

    // Cas 2: date précise "YYYY-MM-DD"
    } elseif ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      $sql .= " AND DATE(r.ride_date) = ?";
      $params[] = $date;

    // Cas 3: plage explicite
    } elseif ($dateFrom && $dateTo) {
      $sql .= " AND DATE(r.ride_date) BETWEEN ? AND ?";
      $params[] = $dateFrom;
      $params[] = $dateTo;
    }

    $sql .= " ORDER BY r.ride_date ASC";
    $st = $this->pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }
}
