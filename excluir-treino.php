<?php
// Apaga um treino inteiro (a aba A, B, C...) e todos os exercícios dele.
// Só professora logada e só via POST. Confere que o treino é mesmo do aluno informado,
// pra ninguém apagar treino de outro aluno mexendo nos campos escondidos.
require_once 'includes/protecao-professor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: professor.php');
  exit;
}

$aluno_id = (int) ($_POST['aluno_id'] ?? 0);
$treino_id = (int) ($_POST['treino_id'] ?? 0);

// Busca a letra do treino (e confirma que ele pertence a esse aluno)
$stmt = $pdo->prepare("SELECT letra FROM treinos WHERE id = :treino AND alunos_id = :aluno");
$stmt->execute(['treino' => $treino_id, 'aluno' => $aluno_id]);
$letra = $stmt->fetchColumn();

if ($letra === false) {
  header('Location: montar-treino.php?id=' . $aluno_id);
  exit;
}

// Transação: apaga os exercícios do treino e depois o treino; se algo falhar, não apaga nada.
$pdo->beginTransaction();
try {
  $stmt = $pdo->prepare("DELETE FROM treino_exercicios WHERE treinos_id = :treino");
  $stmt->execute(['treino' => $treino_id]);

  $stmt = $pdo->prepare("DELETE FROM treinos WHERE id = :treino AND alunos_id = :aluno");
  $stmt->execute(['treino' => $treino_id, 'aluno' => $aluno_id]);

  $pdo->commit();
} catch (Throwable $e) {
  $pdo->rollBack();
  throw $e;
}

// Volta pra tela do aluno (abre a primeira aba que sobrou) com o aviso de sucesso
header('Location: montar-treino.php?id=' . $aluno_id . '&apagado=' . urlencode($letra));
exit;