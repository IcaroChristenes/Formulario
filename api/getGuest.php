<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 0);
error_reporting(0);

ob_clean();

try {

    require 'db.php';

    $phone = $_GET['phone'] ?? '';

    if (!$phone) {
        echo json_encode(["erro" => "Telefone não informado"]);
        exit;
    }

    $sql = "SELECT * FROM guests WHERE phone = :phone";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();

    $guest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$guest) {
        echo json_encode(["erro" => "Convidado não encontrado"]);
        exit;
    }

    // acompanhantes
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

} catch (Exception $e) {

    echo json_encode([
        "erro" => "Erro interno",
        "debug" => $e->getMessage()
    ]);
}