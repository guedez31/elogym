<?php
// ---------- PROTEÇÃO ----------
// Só aluno logado acessa esta página; qualquer outra pessoa volta pro login.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
  header('Location: login.php');
  exit;
}
require_once 'conexao.php';

// ---------- TREINOS DO ALUNO (ABAS) ----------
// O aluno vem da sessão (não da URL), então ele nunca consegue ver o treino de outra pessoa.
$aluno_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT primeiro_acesso FROM alunos WHERE id = :id");
$stmt->execute(['id' => $aluno_id]);
if ($stmt->fetchColumn() == 1) {
  header('Location: trocasenha.php');
  exit;
}

$stmt = $pdo->prepare("
  SELECT id, letra FROM treinos
  WHERE alunos_id = :aluno AND ativo = 1
  ORDER BY letra
");
$stmt->execute(['aluno' => $aluno_id]);
$treinos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- TREINO ABERTO ----------
// Escolhe a aba aberta: a do ?treino= da URL (só se for deste aluno) ou, se não houver, a primeira.
$treino_pedido = (int) ($_GET['treino'] ?? 0);
$treino_atual = $treinos[0] ?? null;
foreach ($treinos as $t) {
  if ($t['id'] == $treino_pedido) {
    $treino_atual = $t;
  }
}

// ---------- EXERCÍCIOS DO TREINO ----------
// Busca os exercícios do treino aberto, na ordem em que a professora montou.
$exercicios = [];
if ($treino_atual) {
  $stmt = $pdo->prepare("
    SELECT e.nome, te.series, te.repeticoes, te.carga, te.observacao
    FROM treino_exercicios te
    INNER JOIN exercicios e ON e.id = te.exercicios_id
    WHERE te.treinos_id = :treino
    ORDER BY te.ordem
  ");
  $stmt->execute(['treino' => $treino_atual['id']]);
  $exercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Meu Treino</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <!-- HEADER: logo (volta pra tela do aluno), nome do aluno e botão Sair -->
    <header class="header">
      <div class="container header__inner">
        <a href="meu-treino.php" class="header__logo">AC <span>Academia</span></a>

        <nav class="header__menu">
          <p>Bem-vindo, Aluno(a) <?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
        </nav>

        <a href="logout.php" class="btn btn--primario header__cta">Sair</a>

        <button class="header__toggle" aria-label="Abrir menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </header>
    <br />

    <!-- ABAS DE TREINO: uma por treino do aluno (a aberta fica vermelha) -->
    <div class="abas-treino">
      <?php foreach ($treinos as $t): ?>
        <a href="meu-treino.php?treino=<?= $t['id'] ?>"
           class="aba <?= $t['id'] == ($treino_atual['id'] ?? 0) ? 'aba--ativa' : '' ?>">
          Treino <?= htmlspecialchars($t['letra']) ?>
        </a>
      <?php endforeach; ?>
    </div>
    <br />

    <!-- CONTEÚDO: sem treino mostra a mensagem; com treino mostra a tabela e o botão de PDF -->
    <?php if (!$treino_atual): ?>
      <p>Você ainda não tem treino cadastrado. Fale com o seu professor.</p>
    <?php else: ?>
      <!-- Tabela de exercícios: nome com a carga (se houver) e séries x repetições com a observação -->
      <div class="tabela-wrapper">
        <table class="tabela-treino">
          <tr>
            <th>Exercício</th>
            <th>Séries x Repetições</th>
          </tr>
          <?php foreach ($exercicios as $ex): ?>
            <tr>
              <td>
                <h4><?= htmlspecialchars($ex['nome']) ?></h4>
                <?= $ex['carga'] ? 'Carga: ' . htmlspecialchars($ex['carga']) : '' ?>
              </td>
              <td>
                <?= htmlspecialchars($ex['series'] . 'x' . $ex['repeticoes']) ?>
                <?= $ex['observacao'] ? ' + ' . htmlspecialchars($ex['observacao']) : '' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <br />

      <!-- Botão de PDF: ainda sem função, entra numa próxima etapa -->
      <button type="button" class="btn btn--primario">Baixar Treino em PDF</button>
    <?php endif; ?>
  </body>
</html>