<?php
// Muda a situação de um aluno. Só professor logado e só via POST.
//   desativar → sai da lista principal e da frequência e não consegue mais entrar,
//               mas as fichas e o histórico continuam guardados
//   reativar  → volta tudo como estava (mesmo login, mesma senha, mesmas fichas)
//   excluir   → SÓ ADMINISTRADOR: apaga o aluno de vez (ex: cadastro errado ou
//               pedido do aluno pela LGPD). Não dá pra desfazer.
require_once 'includes/protecao-professor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: professor.php');
  exit;
}

$aluno_id = (int) ($_POST['aluno_id'] ?? 0);
$acao = $_POST['acao'] ?? '';

$stmt = $pdo->prepare("SELECT id, ativo FROM alunos WHERE id = :id");
$stmt->execute(['id' => $aluno_id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
  header('Location: professor.php');
  exit;
}

// ---------- DESATIVAR ----------
if ($acao === 'desativar') {
  $stmt = $pdo->prepare("UPDATE alunos SET ativo = 0, desativado_em = :hoje WHERE id = :id");
  $stmt->execute(['hoje' => date('Y-m-d'), 'id' => $aluno_id]);
  header('Location: professor.php?desativado=1');
  exit;
}

// ---------- REATIVAR ----------
// Volta pro Montar Treino: se o aluno ficou fora muito tempo, a ficha deve estar vencida
// e a professora já vê ali o bloco de validade pra renovar.
if ($acao === 'reativar') {
  $stmt = $pdo->prepare("UPDATE alunos SET ativo = 1, desativado_em = NULL WHERE id = :id");
  $stmt->execute(['id' => $aluno_id]);
  header('Location: montar-treino.php?id=' . $aluno_id . '&reativado=1');
  exit;
}

// ---------- EXCLUIR (só administrador) ----------
// O banco não deixa apagar aluno que tem fichas, então apaga na ordem:
// exercícios das fichas → fichas → aluno. As siglas de saúde e os check-ins
// somem sozinhos (ON DELETE CASCADE). Tudo numa transação: ou apaga tudo, ou nada.
if ($acao === 'excluir') {
  exigir_admin();

  $pdo->beginTransaction();

  $stmt = $pdo->prepare("
    DELETE te FROM treino_exercicios te
    INNER JOIN treinos t ON t.id = te.treinos_id
    WHERE t.alunos_id = :aluno
  ");
  $stmt->execute(['aluno' => $aluno_id]);

  $stmt = $pdo->prepare("DELETE FROM treinos WHERE alunos_id = :aluno");
  $stmt->execute(['aluno' => $aluno_id]);

  $stmt = $pdo->prepare("DELETE FROM alunos WHERE id = :aluno");
  $stmt->execute(['aluno' => $aluno_id]);

  $pdo->commit();
  header('Location: professor.php?excluido=1');
  exit;
}

header('Location: editar-aluno.php?id=' . $aluno_id);
exit;
