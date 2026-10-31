<?php
// CHECK-IN: o aluno marca que concluiu o treino de hoje (ou desfaz, se tocou sem querer).
// Só aluno logado e só via POST. Fica salvo no banco, então o professor vê na tela de Frequência.
// Um check-in por dia (a tabela tem chave única aluno + data).
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
  header('Location: login.php');
  exit;
}
require_once 'conexao.php';
require_once 'includes/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: meu-treino.php');
  exit;
}

$aluno_id = $_SESSION['usuario_id'];
$treino_id = (int) ($_POST['treino_id'] ?? 0);
$hoje = date('Y-m-d');

// DESFAZER: apaga só o check-in de hoje (os dias anteriores ficam pro histórico)
if (($_POST['acao'] ?? '') === 'desfazer') {
  $stmt = $pdo->prepare("DELETE FROM checkins WHERE alunos_id = :aluno AND data = :hoje");
  $stmt->execute(['aluno' => $aluno_id, 'hoje' => $hoje]);
  header('Location: meu-treino.php?treino=' . $treino_id);
  exit;
}

// Confere que o treino é desse aluno (ninguém marca treino de outra pessoa mexendo no formulário)
$stmt = $pdo->prepare("SELECT letra FROM treinos WHERE id = :treino AND alunos_id = :aluno AND ativo = 1");
$stmt->execute(['treino' => $treino_id, 'aluno' => $aluno_id]);
$letra = $stmt->fetchColumn();

if ($letra === false) {
  header('Location: meu-treino.php');
  exit;
}

// INSERT IGNORE: se já tem check-in hoje, não faz nada (em vez de dar erro)
$stmt = $pdo->prepare("
  INSERT IGNORE INTO checkins (alunos_id, treinos_id, letra, data, criado_em)
  VALUES (:aluno, :treino, :letra, :hoje, :agora)
");
$stmt->execute([
  'aluno' => $aluno_id,
  'treino' => $treino_id,
  'letra' => $letra,
  'hoje' => $hoje,
  'agora' => date('Y-m-d H:i:s'),
]);

header('Location: meu-treino.php?treino=' . $treino_id . '&concluido=1');
exit;
