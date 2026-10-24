<?php
// ---------- PROTEÇÃO ----------
// Só professora logada acessa o painel; qualquer outra pessoa volta pro login.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}
require_once 'conexao.php';

// ---------- LISTA DE ALUNOS ----------
// Uma linha por aluno, com: idade (calculada da data de nascimento), siglas de saúde juntas numa linha,
// o significado delas (pro texto ao passar o mouse) e o total de treinos (define "Atualizado" ou "Sem Treino").
// O DISTINCT evita contar em dobro: o aluno passa por duas ligações (limitações e treinos) ao mesmo tempo.
$stmt = $pdo->query("
  SELECT a.id, a.nome,
         TIMESTAMPDIFF(YEAR, a.data_nascimento, CURDATE()) AS idade,
         GROUP_CONCAT(DISTINCT l.sigla ORDER BY l.sigla SEPARATOR ', ') AS siglas,
         GROUP_CONCAT(DISTINCT CONCAT(l.sigla, ' = ', l.descricao)
                      ORDER BY CONCAT(l.sigla, ' = ', l.descricao) SEPARATOR ' | ') AS detalhe,
         COUNT(DISTINCT t.id) AS total_treinos
  FROM alunos a
  LEFT JOIN aluno_limitacoes al ON al.alunos_id = a.id
  LEFT JOIN limitacoes_saude l ON l.id = al.limitacoes_saude_id
  LEFT JOIN treinos t ON t.alunos_id = a.id
  GROUP BY a.id, a.nome, a.data_nascimento
  ORDER BY a.nome
");
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- LEGENDA DAS SIGLAS ----------
// Busca todas as siglas e significados pra mostrar a legenda embaixo da tabela.
$legenda = $pdo->query("SELECT sigla, descricao FROM limitacoes_saude ORDER BY sigla")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Treine perto de casa</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <!-- HEADER: logo (volta pro painel), nome da professora e botão Sair -->
    <header class="header">
      <div class="container header__inner">
        <a href="professor.php" class="header__logo">AC <span>Academia</span></a>

        <nav class="header__menu">
          <p>Bem-vindo, Professor(a) <?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
        </nav>

        <a href="logout.php" class="btn btn--primario header__cta">Sair</a>

        <button class="header__toggle" aria-label="Abrir menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </header>
    <br />

    <!-- AVISO DE SUCESSO: aparece quando acabou de cadastrar um aluno -->
    <?php if (isset($_GET['cadastrado'])): ?>
      <p style="color: #1e7a3c; margin: 0 20px 12px;">Aluno cadastrado com sucesso!</p>
    <?php endif; ?>

    <!-- CABEÇALHO DO PAINEL: título e botões de cadastro -->
    <div class="cadastrar-exercicio card">
      <div class="cadastrar-exercicio card-body painel-cabecalho">
        <h2 class="card-title">Meus Alunos</h2>
        <div class="painel-acoes">
          <a href="cadastrar-aluno.php" class="btn btn--primario">+ Cadastrar Aluno</a>
          <a href="cadastrar-exerc.php" class="btn btn--exercicio">+ Cadastrar Exercício</a>
          <a href="limitacoes.php" class="btn btn--exercicio">Siglas de Saúde</a>
        </div>
      </div>
    </div>
    <br />

    <!-- TABELA DE ALUNOS: nome, idade, siglas de saúde, status do treino e botão de ação -->
    <div class="tabela-wrapper">
      <table>
        <tr>
          <th>Nome</th>
          <th>Idade</th>
          <th>Saúde</th>
          <th>Treino (Status)</th>
          <th>Ação</th>
        </tr>
        <?php foreach ($alunos as $aluno): ?>
          <tr>
            <td><?= htmlspecialchars($aluno['nome']) ?></td>

            <!-- Idade: mostra "—" se o aluno não tem data de nascimento cadastrada -->
            <td><?= $aluno['idade'] !== null ? (int) $aluno['idade'] : '—' ?></td>

            <!-- Siglas: ao passar o mouse aparece o significado; "—" se o aluno não tem limitação -->
            <td>
              <?php if ($aluno['siglas']): ?>
                <span title="<?= htmlspecialchars($aluno['detalhe']) ?>"><?= htmlspecialchars($aluno['siglas']) ?></span>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>

            <!-- Status e ação: com treino vira "Editar"; sem treino vira "Montar Treino" -->
            <?php if ($aluno['total_treinos'] > 0): ?>
              <td>Atualizado</td>
              <td><a href="montar-treino.php?id=<?= $aluno['id'] ?>" class="btn btn--editar">Editar</a></td>
            <?php else: ?>
              <td>Sem Treino</td>
              <td><a href="montar-treino.php?id=<?= $aluno['id'] ?>" class="btn btn--montar">Montar Treino</a></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <!-- LEGENDA: significado de todas as siglas, útil no celular onde não existe "passar o mouse" -->
    <p style="margin: 12px 20px; font-size: 0.85rem;">
      <strong>Legenda:</strong>
      <?= htmlspecialchars(implode(' · ', array_map(fn($l) => $l['sigla'] . ' = ' . $l['descricao'], $legenda))) ?>
    </p>
    <script src="js/script.js"></script>
  </body>
</html>