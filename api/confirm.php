<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require 'db.php';

$raw_input = file_get_contents("php://input");
error_log("Raw POST input: " . $raw_input);
$data = json_decode($raw_input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
  echo json_encode(["erro" => "JSON parse fail: " . json_last_error_msg()]);
  exit;
}

$phone = trim($data['phone'] ?? '');
if (empty($phone)) {
  echo json_encode(["erro" => "Phone empty"]);
  exit;
}
$attending = $data['attending'];
$names = $data['accompanying']; // array de nomes

try {
    $conn->beginTransaction();

    // atualizar presença
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

    error_log("UPDATE phone: " . $phone . " affected: " . $stmt->rowCount());

    if ($stmt->rowCount() == 0) {
        echo json_encode(["debug" => "No row updated for phone: '" . $phone . "' length:" . strlen($phone)]);
        exit;
    }

    $guest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$guest) {
        throw new Exception("Convidado não encontrado");
    }

    $guest_id = $guest['id'];

    // remover acompanhantes antigos
    $conn->prepare("DELETE FROM guests_accompanying WHERE guest_id = :id")
         ->execute([':id' => $guest_id]);

    // inserir novos acompanhantes
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

    echo json_encode(["status" => "sucesso"]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(["erro" => $e->getMessage()]);
}