<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require 'db.php';

$phone = $_GET['phone'] ?? '';

$sql = "SELECT * FROM guests WHERE phone = :phone";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':phone', $phone);
$stmt->execute();

$guest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$guest) {
    echo json_encode(["erro" => "Convidado não encontrado"]);
    exit;
}

// buscar acompanhantes
$sql2 = "SELECT * FROM guests_accompanying WHERE guest_id = :id";
$stmt2 = $conn->prepare($sql2);
$stmt2->bindParam(':id', $guest['id']);
$stmt2->execute();

$acompanhantes = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "guest" => $guest,
    "acompanhantes_max" => $guest['max_guests'],
    "acompanhantes" => $acompanhantes
]);
