<?php
error_reporting(E_ALL); ini_set("display_errors",1);
$h=getenv("DB_HOST"); $d=getenv("DB_NAME"); $u=getenv("DB_USER"); $p=getenv("DB_PASS");
echo "ENV => HOST=$h DB=$d USER=$u\n";
try {
  $pdo=new PDO("mysql:host=$h;dbname=$d;charset=utf8mb4",$u,$p,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  foreach($pdo->query("SHOW TABLES") as $r){ echo implode(" ",$r),"\n"; }
} catch(Throwable $e){ echo "ERR: ",$e->getMessage(); }
