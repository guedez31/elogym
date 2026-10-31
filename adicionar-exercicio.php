<?php
// Grava um exercício no treino aberto: só professora logada e só via POST. Confere
// que o treino é mesmo do aluno e que o exercício existe, calcula a próxima posição
// (ordem) e volta pra tela com a mesma aba aberta.
require_once 'includes/protecao-professor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: professor.php');
  exit;
}

$aluno_id = (int) ($_POST['aluno_id'] ?? 0);
$treino_id = (int) ($_POST['treino_id'] ?? 0);
$exercicio_id = (int) ($_POST['exercicio_id'] ?? 0);
$series = (int) ($_POST['series'] ?? 0);
$repeticoes = mb_substr(trim($_POST['repeticoes'] ?? ''), 0, 45);
$carga = mb_substr(trim($_POST['carga'] ?? ''), 0, 45);
$observacao = mb_substr(trim($_POST['observacao'] ?? ''), 0, 245);

$voltar = 'montar-treino.php?id=' . $aluno_id . '&treino=' . $treino_id;

$stmt = $pdo->prepare("SELECT id FROM treinos WHERE id = :treino AND alunos_id = :aluno");
$stmt->execute(['treino' => $treino_id, 'aluno' => $aluno_id]);
$treino_ok = $stmt->fetch();

$stmt = $pdo->prepare("SELECT id FROM exercicios WHERE id = :id");
$stmt->execute(['id' => $exercicio_id]);
$exercicio_ok = $stmt->fetch();

if (!$treino_ok || !$exercicio_ok || $series < 1 || $repeticoes === '') {
  header('Location: ' . $voltar);
  exit;
}

$stmt = $pdo->prepare("SELECT COALESCE(MAX(ordem), 0) + 1 FROM treino_exercicios WHERE treinos_id = :treino");
$stmt->execute(['treino' => $treino_id]);
$ordem = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
  INSERT INTO treino_exercicios (treinos_id, exercicios_id, series, repeticoes, carga, observacao, ordem)
  VALUES (:treino, :exercicio, :series, :repeticoes, :carga, :observacao, :ordem)
");
$stmt->execute([
  'treino' => $treino_id,
  'exercicio' => $exercicio_id,
  'series' => $series,
  'repeticoes' => $repeticoes,
  'carga' => $carga !== '' ? $carga : null,
  'observacao' => $observacao !== '' ? $observacao : null,
  'ordem' => $ordem,
]);

$stmt = $pdo->prepare("UPDATE treinos SET data_atualizacao = NOW() WHERE id = :treino");
$stmt->execute(['treino' => $treino_id]);

header('Location: ' . $voltar);
exit;