<?php
ini_set('display_errors', 0);
error_reporting(0);

try {

    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: 5432;
    $db   = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');

    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$db;sslmode=require",
        $user,
        $pass
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    echo json_encode([
        "erro" => "Erro conexão banco",
        "debug" => $e->getMessage()
    ]);
    exit;
}