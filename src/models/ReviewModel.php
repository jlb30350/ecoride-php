<?php
class ReviewModel
{
    private PDO $pdo;
    private ?string $driverCol = null;

    function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ---------- CRUD de base (tes méthodes existantes) ----------
    function create($rideId, $userId, $rating, $comment)
    {
        $st = $this->pdo->prepare("INSERT INTO reviews (ride_id,user_id,rating,comment) VALUES (?,?,?,?)");
        $st->execute([$rideId, $userId, $rating, $comment]);
        return (int)$this->pdo->lastInsertId();
    }

    function findByRideAndUser($rideId, $userId)
    {
        $st = $this->pdo->prepare("SELECT * FROM reviews WHERE ride_id=? AND user_id=?");
        $st->execute([$rideId, $userId]);
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    function findOwned($id, $userId)
    {
        $st = $this->pdo->prepare("SELECT * FROM reviews WHERE id=? AND user_id=?");
        $st->execute([$id, $userId]);
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    function update($id, $userId, $rating, $comment)
    {
        $st = $this->pdo->prepare("UPDATE reviews SET rating=?, comment=? WHERE id=? AND user_id=?");
        return $st->execute([$rating, $comment, $id, $userId]);
    }

    function deleteOwned($id, $userId)
    {
        $st = $this->pdo->prepare("DELETE FROM reviews WHERE id=? AND user_id=?");
        return $st->execute([$id, $userId]);
    }

    // ---------- Helper: détecte le nom de la colonne "conducteur" dans rides ----------
    private function driverColumn(): string
    {
        if ($this->driverCol) return $this->driverCol;

        $candidates = ['driver_id', 'user_id', 'owner_id'];
        $placeholders = implode(',', array_fill(0, count($candidates), '?'));

        $st = $this->pdo->prepare("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'rides'
              AND COLUMN_NAME IN ($placeholders)
            LIMIT 1
        ");
        $st->execute($candidates);
        $col = $st->fetchColumn();

        if (!$col) {
            $col = 'user_id';
        } // fallback raisonnable
        return $this->driverCol = $col;
    }

    // ---------- NOUVELLES MÉTHODES demandées par ton dashboard ----------
    // Liste des avis reçus pour les trajets du conducteur
    function listForDriver(int $driverId): array
    {
        $col = $this->driverColumn();
        $sql = "
            SELECT
                r.id, r.rating, r.comment, r.ride_id,
                ri.origin, ri.destination, ri.ride_date
            FROM reviews r
            JOIN rides  ri ON ri.id = r.ride_id
            WHERE ri.$col = ?
            ORDER BY r.id DESC
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([$driverId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Moyenne des notes reçues par le conducteur
    function averageForDriver(int $driverId)
    {
        $col = $this->driverColumn();
        $sql = "
            SELECT AVG(r.rating)
            FROM reviews r
            JOIN rides  ri ON ri.id = r.ride_id
            WHERE ri.$col = ?
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([$driverId]);
        $avg = $st->fetchColumn();
        return $avg !== null ? (float)$avg : null;
    }
}
