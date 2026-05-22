<?php
require_once 'config/config.php';
$stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = 'ride_op'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($user);
?>
