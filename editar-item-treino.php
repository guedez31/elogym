<?php
// Edita um exercício que já está no treino (séries, repetições, carga e observação),
// sem precisar remover e adicionar de novo. Só professora logada e só via POST.
// Confere que o item pertence a um treino do aluno informado, pra ninguém editar treino de outro aluno.
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

// Mesmas regras do adicionar-exercicio.php (tamanhos máximos das colunas)
$aluno_id = (int) ($_POST['aluno_id'] ?? 0);
$treino_id = (int) ($_POST['treino_id'] ?? 0);
$item_id = (int) ($_POST['item_id'] ?? 0);
$series = (int) ($_POST['series'] ?? 0);
$repeticoes = mb_substr(trim($_POST['repeticoes'] ?? ''), 0, 45);
$carga = mb_substr(trim($_POST['carga'] ?? ''), 0, 45);
$observacao = mb_substr(trim($_POST['observacao'] ?? ''), 0, 245);

$voltar = 'montar-treino.php?id=' . $aluno_id . '&treino=' . $treino_id;

// O item existe e é de um treino desse aluno?
$stmt = $pdo->prepare("
  SELECT te.id
  FROM treino_exercicios te
  INNER JOIN treinos t ON t.id = te.treinos_id
  WHERE te.id = :item AND te.treinos_id = :treino AND t.alunos_id = :aluno
");
$stmt->execute(['item' => $item_id, 'treino' => $treino_id, 'aluno' => $aluno_id]);

if (!$stmt->fetch()) {
  header('Location: ' . $voltar);
  exit;
}

if ($series < 1 || $repeticoes === '') {
  header('Location: ' . $voltar . '&erro_edicao=1');
  exit;
}

$stmt = $pdo->prepare("
  UPDATE treino_exercicios
  SET series = :series, repeticoes = :repeticoes, carga = :carga, observacao = :observacao
  WHERE id = :item
");
$stmt->execute([
  'series' => $series,
  'repeticoes' => $repeticoes,
  'carga' => $carga !== '' ? $carga : null,
  'observacao' => $observacao !== '' ? $observacao : null,
  'item' => $item_id,
]);

// Marca o treino como atualizado (mesma coisa que o adicionar e o remover fazem)
$stmt = $pdo->prepare("UPDATE treinos SET data_atualizacao = NOW() WHERE id = :treino");
$stmt->execute(['treino' => $treino_id]);

header('Location: ' . $voltar . '&editado=1');
exit;