<?php

$host = "localhost";
$porta = "3306";
$usuario = "root";
$senha = "31032004";
$banco = "mydb";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}