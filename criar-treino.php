<?php
// Cria o próximo treino (A, B, C...) do aluno e volta pra tela de montar treino
// com a aba nova aberta. Só aceita professora logada e só via POST (formulário).
require_once 'includes/protecao-professor.php';

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

// Pega a primeira letra livre de A a F (se a professora apagou o B, o próximo criado volta a ser o B).
$stmt = $pdo->prepare("SELECT letra FROM treinos WHERE alunos_id = :aluno");
$stmt->execute(['aluno' => $aluno_id]);
$usadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
$livres = array_diff(range('A', 'F'), $usadas);
$proxima = reset($livres); // false se todas de A a F já existem

// Limite de 6 treinos (A até F) pra não criar letras estranhas.
if ($proxima === false) {
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

// Primeira ficha do aluno: a validade começa com 3 meses a partir de hoje
// (a professora muda na tela de montar treino se for outro prazo).
$stmt = $pdo->prepare("
  UPDATE alunos SET treino_validade = DATE_ADD(:hoje, INTERVAL 3 MONTH)
  WHERE id = :aluno AND treino_validade IS NULL
");
$stmt->execute(['hoje' => date('Y-m-d'), 'aluno' => $aluno_id]);
header('Location: montar-treino.php?id=' . $aluno_id . '&treino=' . $novo_treino);
exit;