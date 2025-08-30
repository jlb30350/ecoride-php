<?php
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../lib/auth.php";
require_once __DIR__."/../models/ReviewModel.php";
require_login();
if (current_user_role()!=='passenger'){ http_response_code(403); exit("Passager uniquement"); }

$id = (int)($_POST['id'] ?? 0);
$revM = new ReviewModel($pdo);
$rev = $revM->findOwned($id, current_user_id());
if ($rev) { $revM->deleteOwned($id, current_user_id()); }
header("Location:/dashboard.php"); exit;

