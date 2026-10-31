<?php
// Exclui um exercício: só professora logada e só via POST. O DELETE só acontece se o
// exercício não estiver em nenhum treino, pra não quebrar a ficha de nenhum aluno.
require_once 'includes/protecao-professor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: cadastrar-exerc.php');
  exit;
}

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare("
  DELETE FROM exercicios
  WHERE id = :id
    AND NOT EXISTS (SELECT 1 FROM treino_exercicios WHERE exercicios_id = :id2)
");
$stmt->execute(['id' => $id, 'id2' => $id]);

if ($stmt->rowCount() > 0) {
  header('Location: cadastrar-exerc.php?excluido=1');
} else {
  header('Location: cadastrar-exerc.php?erro=em_uso');
}
exit;