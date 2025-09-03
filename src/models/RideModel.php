<?php
class RideModel {
  private PDO $pdo;
  function __construct(PDO $pdo){ $this->pdo = $pdo; }

  /** 🔒 Normalise SANS décaler l’heure (Europe/Paris) + refuse le passé */
  private function normalizeFutureDate(string $raw): string {
    $raw = trim($raw);
    $tz  = new \DateTimeZone('Europe/Paris');
    $now = new \DateTimeImmutable('now', $tz);
    $today = new \DateTimeImmutable('today', $tz);

    // 1) datetime-local: "YYYY-MM-DDTHH:MM[:SS]" ou "YYYY-MM-DD HH:MM[:SS]"
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?$/', $raw, $m)) {
      $yyyy=$m[1]; $mm=$m[2]; $dd=$m[3]; $HH=$m[4]; $ii=$m[5]; $ss=$m[6] ?? '00';

      // vérif futur (en Paris) mais on renvoie la chaîne telle quelle (normalisée)
      $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "$yyyy-$mm-$dd $HH:$ii:$ss", $tz);
      if ($dt === false) throw new \InvalidArgumentException("Format de date invalide: $raw");
      if ($dt < $now)    throw new \InvalidArgumentException("La date/heure doit être dans le futur.");

      return "$yyyy-$mm-$dd $HH:$ii:$ss"; // 👉 pas de conversion, donc pas de +1h
    }

    // 2) date seule: "YYYY-MM-DD"
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
      $yyyy=$m[1]; $mm=$m[2]; $dd=$m[3];
      $dt = \DateTimeImmutable::createFromFormat('Y-m-d', "$yyyy-$mm-$dd", $tz);
      if ($dt === false) throw new \InvalidArgumentException("Format de date invalide: $raw");
      if ($dt < $today)  throw new \InvalidArgumentException("La date doit être aujourd’hui ou future.");

      return "$yyyy-$mm-$dd 00:00:00";
    }

    throw new \InvalidArgumentException("Format de date invalide: $raw");
  }

  /** Récupère un trajet par id */
  function find($id){
    $st = $this->pdo->prepare("SELECT * FROM rides WHERE id=?");
    $st->execute([$id]);
    return $st->fetch(\PDO::FETCH_ASSOC);
  }

  /** 🗓️ Trajets à venir pour l’utilisateur (dashboard) */
  function listByUser($userId){
    $today = (new \DateTime('today', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
    $st = $this->pdo->prepare(
      "SELECT * FROM rides
       WHERE user_id=? AND DATE(ride_date) >= ?
       ORDER BY ride_date ASC"
    );
    $st->execute([$userId, $today]);
    return $st->fetchAll(\PDO::FETCH_ASSOC);
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

  /** Edition — prend en compte le changement d’heure (pas que le jour) */
  function update($rideId,$userId,$o,$d,$date,$seats,$price){
    $st = $this->pdo->prepare("SELECT user_id, ride_date FROM rides WHERE id=?");
    $st->execute([$rideId]);
    $row = $st->fetch(\PDO::FETCH_ASSOC);
    if (!$row || (int)$row['user_id'] !== (int)$userId) {
      return false;
    }

    // Canonique actuel et demandé à la minute
    $current = substr((string)$row['ride_date'], 0, 16);               // "YYYY-mm-dd HH:ii"
    $posted  = preg_replace('/T/', ' ', substr((string)$date, 0, 16)); // "YYYY-mm-dd HH:ii"

    if ($posted === $current) {
      $newDate = $row['ride_date']; // rien n’a changé
    } else {
      $newDate = $this->normalizeFutureDate((string)$date); // valide + renvoie exact
    }

    $st = $this->pdo->prepare(
      "UPDATE rides
       SET origin=?, destination=?, ride_date=?, seats=?, price=?
       WHERE id=? AND user_id=?"
    );
    return $st->execute([$o,$d,$newDate,$seats,$price,$rideId,$userId]);
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

  /** 🔎 Recherche (jour ou semaine ISO optionnels) */
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
      // semaine ISO -> lundi..dimanche (TZ Paris pour cohérence)
      $dt = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
      $dt->setISODate((int)$m[1], (int)$m[2]);
      $monday = $dt->format('Y-m-d');
      $sunday = (clone $dt)->modify('+6 days')->format('Y-m-d');
      $sql .= " AND DATE(r.ride_date) BETWEEN ? AND ?";
      $params[] = $monday; $params[] = $sunday;

    } elseif ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      $sql .= " AND DATE(r.ride_date) = ?";
      $params[] = $date;

    } elseif ($dateFrom && $dateTo) {
      $sql .= " AND DATE(r.ride_date) BETWEEN ? AND ?";
      $params[] = $dateFrom; $params[] = $dateTo;
    }

    $sql .= " ORDER BY r.ride_date ASC";
    $st = $this->pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(\PDO::FETCH_ASSOC);
  }
}
