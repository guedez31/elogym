<?php
// Salva até quando vale a ficha de treino do aluno. Só professor logado e só via POST.
// Dois jeitos (vem no botão clicado, campo "meses"):
//   meses = 3 ou 4 → validade = hoje + 3 ou 4 meses (renovar ao montar uma ficha nova)
//   meses = 0      → usa a data escolhida no calendário (não pode ser no passado)
require_once 'includes/protecao-professor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: professor.php');
  exit;
}

$aluno_id = (int) ($_POST['aluno_id'] ?? 0);
$meses = (int) ($_POST['meses'] ?? 0);
$data = $_POST['data'] ?? '';

$stmt = $pdo->prepare("SELECT id FROM alunos WHERE id = :id");
$stmt->execute(['id' => $aluno_id]);
if (!$stmt->fetch()) {
  header('Location: professor.php');
  exit;
}

$voltar = 'montar-treino.php?id=' . $aluno_id;

if (in_array($meses, [3, 4], true)) {
  $validade = somar_meses(date('Y-m-d'), $meses);
} else {
  // Confere se é uma data real (ex: 31/02 não passa) e se não é no passado
  $d = DateTime::createFromFormat('Y-m-d', $data);
  if (!$d || $d->format('Y-m-d') !== $data || $data < date('Y-m-d')) {
    header('Location: ' . $voltar . '&erro_validade=1');
    exit;
  }
  $validade = $data;
}

$stmt = $pdo->prepare("UPDATE alunos SET treino_validade = :validade WHERE id = :id");
$stmt->execute(['validade' => $validade, 'id' => $aluno_id]);

header('Location: ' . $voltar . '&validade=1');
exit;
