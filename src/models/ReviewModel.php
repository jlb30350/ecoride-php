<?php
class ReviewModel {
  private PDO $pdo;
  function __construct(PDO $pdo){ $this->pdo=$pdo; }

  function create($rideId,$userId,$rating,$comment){
    $st=$this->pdo->prepare("INSERT INTO reviews (ride_id,user_id,rating,comment) VALUES (?,?,?,?)");
    $st->execute([$rideId,$userId,$rating,$comment]);
    return $this->pdo->lastInsertId();
  }
  function findByRideAndUser($rideId,$userId){
    $st=$this->pdo->prepare("SELECT * FROM reviews WHERE ride_id=? AND user_id=?");
    $st->execute([$rideId,$userId]); return $st->fetch();
  }
  function findOwned($id,$userId){
    $st=$this->pdo->prepare("SELECT * FROM reviews WHERE id=? AND user_id=?");
    $st->execute([$id,$userId]); return $st->fetch();
  }
  function update($id,$userId,$rating,$comment){
    $st=$this->pdo->prepare("UPDATE reviews SET rating=?, comment=? WHERE id=? AND user_id=?");
    return $st->execute([$rating,$comment,$id,$userId]);
  }
  function deleteOwned($id,$userId){
    $st=$this->pdo->prepare("DELETE FROM reviews WHERE id=? AND user_id=?");
    return $st->execute([$id,$userId]);
  }
}

