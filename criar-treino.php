<?php
// Cria o próximo treino (A, B, C...) do aluno e volta pra tela de montar treino
// com a aba nova aberta. Só aceita professora logada e só via POST (formulário).
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: professor.php');
  exit;
}

$aluno_id = (int) ($_POST['aluno_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id FROM alunos WHERE id = :id");
$stmt->execute(['id' => $aluno_id]);
if (!$stmt->fetch()) {
  header('Location: professor.php');
  exit;
}

// Pega a maior letra que o aluno já tem (A, B...) e avança uma. Sem treino, começa no A.
$stmt = $pdo->prepare("SELECT MAX(letra) FROM treinos WHERE alunos_id = :aluno");
$stmt->execute(['aluno' => $aluno_id]);
$ultima = $stmt->fetchColumn();
$proxima = $ultima ? chr(ord($ultima) + 1) : 'A';

// Limite de 6 treinos (A até F) pra não criar letras estranhas.
if ($proxima > 'F') {
  header('Location: montar-treino.php?id=' . $aluno_id);
  exit;
}

$stmt = $pdo->prepare("
  INSERT INTO treinos (letra, data_criacao, data_atualizacao, alunos_id, professores_id)
  VALUES (:letra, NOW(), NOW(), :aluno, :professor)
");
$stmt->execute([
  'letra' => $proxima,
  'aluno' => $aluno_id,
  'professor' => $_SESSION['usuario_id']
]);

$novo_treino = $pdo->lastInsertId();
header('Location: montar-treino.php?id=' . $aluno_id . '&treino=' . $novo_treino);
exit;