<?php
class RideModel {
  private PDO $pdo;
  function __construct(PDO $pdo){ $this->pdo = $pdo; }


 /** 🔒 Normalise la date et refuse le passé (≥ aujourd’hui, TZ Paris) */
private function normalizeFutureDate(string $raw): string {
  $raw = trim($raw);
  // supporte les formats avec espace OU 'T' (input type="datetime-local")
$formats = ['Y-m-d\TH:i:s','Y-m-d\TH:i','Y-m-d H:i:s','Y-m-d H:i','Y-m-d'];
$tz = new DateTimeZone('Europe/Paris');
foreach ($formats as $f) {
  $tmp = DateTimeImmutable::createFromFormat($f, $raw, $tz);
  if ($tmp !== false) { $chosenFmt = $f; $dt = $tmp; break; }
}
$today = new DateTimeImmutable('today', $tz);
$now   = new DateTimeImmutable('now',   $tz);
// ... (comparaisons) ...


  if ($chosenFmt === 'Y-m-d') {
    if ($dt < $today) throw new \InvalidArgumentException("La date doit être aujourd’hui ou future.");
    return $dt->format('Y-m-d');
  }

  if ($dt < $now) throw new \InvalidArgumentException("La date/heure doit être dans le futur.");

  // sortie normalisée en DATETIME
  return ($chosenFmt === 'Y-m-d\TH:i' || $chosenFmt === 'Y-m-d H:i')
    ? $dt->format('Y-m-d H:i:00')
    : $dt->format('Y-m-d H:i:s');
}



  /** Récupère un trajet par id */
  function find($id){
    $st = $this->pdo->prepare("SELECT * FROM rides WHERE id=?");
    $st->execute([$id]);
    return $st->fetch(\PDO::FETCH_ASSOC);
  }

  /** 🗓️ Trajets à venir pour l’utilisateur (dashboard) */
function listByUser($userId){
  $today = (new DateTime('today', new DateTimeZone('Europe/Paris')))->format('Y-m-d');
  $st = $this->pdo->prepare(
    "SELECT * FROM rides
     WHERE user_id=? AND DATE(ride_date) >= ?
     ORDER BY ride_date ASC"
  );
  $st->execute([$userId, $today]);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}


  /** (optionnel) Tous les trajets (inclut passés) */
  function listByUserAll($userId){
    $st = $this->pdo->prepare(
      "SELECT * FROM rides WHERE user_id=? ORDER BY ride_date DESC"
    );
    $st->execute([$userId]);
    return $st->fetchAll(\PDO::FETCH_ASSOC);
  }

  /** Création — refuse date passée */
  function create($userId,$o,$d,$date,$seats,$price){
    $date = $this->normalizeFutureDate((string)$date);
    $st = $this->pdo->prepare(
      "INSERT INTO rides (user_id,origin,destination,ride_date,seats,price)
       VALUES (?,?,?,?,?,?)"
    );
    $st->execute([$userId,$o,$d,$date,$seats,$price]);
    return (int)$this->pdo->lastInsertId();
  }

  /** Edition — refuse date passée */
function update($rideId,$userId,$o,$d,$date,$seats,$price){
  // récupère la ligne existante
  $st = $this->pdo->prepare("SELECT user_id, ride_date FROM rides WHERE id=?");
  $st->execute([$rideId]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row || (int)$row['user_id'] !== (int)$userId) {
    return false; // pas à toi ou inexistant
  }

  $origDate = substr((string)$row['ride_date'], 0, 10);
  $newDate  = substr((string)$date,        0, 10);

  if ($newDate !== $origDate) {
    // on ne valide que si la date a CHANGÉ → doit être future
    $date = $this->normalizeFutureDate((string)$date);
  } else {
    // on garde exactement la valeur DB pour éviter les écarts de format
    $date = $row['ride_date'];
  }

  $st = $this->pdo->prepare(
    "UPDATE rides
     SET origin=?, destination=?, ride_date=?, seats=?, price=?
     WHERE id=? AND user_id=?"
  );
  return $st->execute([$o,$d,$date,$seats,$price,$rideId,$userId]);
}


  /** Suppression (propriétaire) */
  function deleteOwned($rideId,$userId){
    $st = $this->pdo->prepare("DELETE FROM rides WHERE id=? AND user_id=?");
    return $st->execute([$rideId,$userId]);
  }

  /** Incrémente/décrémente les places */
  function incrementSeats($rideId,$count){
    $st = $this->pdo->prepare("UPDATE rides SET seats=seats+? WHERE id=?");
    return $st->execute([$count,$rideId]);
  }

  // 🔎 Recherche par villes + (optionnel) jour/semaine ISO
  // $date:
  //  - "YYYY-Www" (ex: "2025-W36") => semaine ISO (lundi..dimanche)
  //  - "YYYY-MM-DD" => jour précis
  //  - sinon ignoré (ou utiliser $dateFrom/$dateTo)
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

    if ($date !== '' && preg_match('/^(\d{4})-W(\d{2})$/', $date, $m)) {
      // Semaine ISO -> lundi..dimanche
      $dt = new \DateTime();
      $dt->setISODate((int)$m[1], (int)$m[2]);
      $monday = $dt->format('Y-m-d');
      $sunday = (clone $dt)->modify('+6 days')->format('Y-m-d');
      $sql .= " AND DATE(r.ride_date) BETWEEN ? AND ?";
      $params[] = $monday; $params[] = $sunday;

    } elseif ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      // Jour précis
      $sql .= " AND DATE(r.ride_date) = ?";
      $params[] = $date;

    } elseif ($dateFrom && $dateTo) {
      // Plage explicite
      $sql .= " AND DATE(r.ride_date) BETWEEN ? AND ?";
      $params[] = $dateFrom; $params[] = $dateTo;
    }

    $sql .= " ORDER BY r.ride_date ASC";
    $st = $this->pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(\PDO::FETCH_ASSOC);
  }
}
