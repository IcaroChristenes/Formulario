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

    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            "erro" => "JSON inválido",
            "debug" => json_last_error_msg()
        ]);
        exit;
    }

    $phone = trim($data['phone'] ?? '');
    $attending = filter_var($data['attending'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $names = $data['accompanying'] ?? [];

    if (empty($phone)) {
        echo json_encode(["erro" => "Telefone vazio"]);
        exit;
    }

    $conn->beginTransaction();

    $sql = "UPDATE guests 
            SET attending = :attending,
                response_timestamp = NOW()
            WHERE phone = :phone
            RETURNING id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':attending' => $attending,
        ':phone' => $phone
    ]);

    if ($stmt->rowCount() == 0) {
        echo json_encode([
            "erro" => "Nenhum convidado atualizado",
            "debug" => $phone
        ]);
        exit;
    }

    $guest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$guest) {
        throw new Exception("Convidado não encontrado");
    }

    $guest_id = $guest['id'];

    $conn->prepare("DELETE FROM guests_accompanying WHERE guest_id = :id")
         ->execute([':id' => $guest_id]);

    $sqlInsert = "INSERT INTO guests_accompanying (guest_id, name) VALUES (:guest_id, :name)";
    $stmtInsert = $conn->prepare($sqlInsert);

    foreach ($names as $name) {
        if (!empty($name)) {
            $stmtInsert->execute([
                ':guest_id' => $guest_id,
                ':name' => $name
            ]);
        }
    }

    $conn->commit();

    echo json_encode([
        "status" => "sucesso"
    ]);

} catch (Exception $e) {

    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }

    echo json_encode([
        "erro" => "Erro interno",
        "debug" => $e->getMessage()
    ]);
}