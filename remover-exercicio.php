<?php
// Remove um exercício do treino: só professora logada e só via POST. O DELETE só acontece se a linha
// de treino_exercicios pertencer a um treino do aluno informado (o JOIN impede apagar item de outro
// aluno mexendo nos campos escondidos).
require_once 'includes/protecao-professor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: professor.php');
  exit;
}

$aluno_id = (int) ($_POST['aluno_id'] ?? 0);
$treino_id = (int) ($_POST['treino_id'] ?? 0);
$item_id = (int) ($_POST['item_id'] ?? 0);

$stmt = $pdo->prepare("
  DELETE te FROM treino_exercicios te
  INNER JOIN treinos t ON t.id = te.treinos_id
  WHERE te.id = :item AND te.treinos_id = :treino AND t.alunos_id = :aluno
");
$stmt->execute(['item' => $item_id, 'treino' => $treino_id, 'aluno' => $aluno_id]);

if ($stmt->rowCount() > 0) {
  $stmt = $pdo->prepare("UPDATE treinos SET data_atualizacao = NOW() WHERE id = :treino");
  $stmt->execute(['treino' => $treino_id]);
}

// Volta pra mesma aba do treino (antes faltava o "=" em "&treino=", e a tela voltava sempre pro Treino A)
header('Location: montar-treino.php?id=' . $aluno_id . '&treino=' . $treino_id);
exit;
