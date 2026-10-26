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
  <title>AC Academia — Meus Alunos</title>
  <link rel="stylesheet" href="css/style.css" />
</head>

<body class="layout-sistema">
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

  <main class="container pagina">
    <!-- AVISO DE SUCESSO: aparece quando acabou de cadastrar um aluno -->
    <?php if (isset($_GET['cadastrado'])): ?>
      <p class="alert alert--sucesso">Aluno cadastrado com sucesso!</p>
    <?php endif; ?>

    <!-- CABEÇALHO DO PAINEL: título e botões de cadastro -->
    <div class="card painel-cabecalho">
      <h2>Meus Alunos</h2>
      <div class="painel-acoes">
        <a href="cadastrar-aluno.php" class="btn btn--primario">+ Cadastrar Aluno</a>
        <a href="cadastrar-exerc.php" class="btn btn--exercicio">+ Cadastrar Exercício</a>
        <a href="limitacoes.php" class="btn btn--exercicio">Siglas de Saúde</a>
      </div>
    </div>
    <br />

    <?php if (!$alunos): ?>
      <!-- ESTADO VAZIO: nenhum aluno cadastrado ainda -->
      <div class="card estado-vazio">
        <h3>Nenhum aluno cadastrado ainda</h3>
        <p>Clique em "+ Cadastrar Aluno" pra começar.</p>
      </div>
    <?php else: ?>
      <!-- TABELA DE ALUNOS: vira um card por aluno no celular (classe tabela-cards + data-label) -->
      <div class="tabela-wrapper">
        <table class="tabela-cards">
          <tr>
            <th>Nome</th>
            <th>Idade</th>
            <th>Saúde</th>
            <th>Treino (Status)</th>
            <th>Ação</th>
          </tr>
          <?php foreach ($alunos as $aluno): ?>
            <tr>
              <td class="td-titulo"><?= htmlspecialchars($aluno['nome']) ?></td>

              <!-- Idade: mostra "—" se o aluno não tem data de nascimento cadastrada -->
              <td data-label="Idade"><?= $aluno['idade'] !== null ? (int) $aluno['idade'] : '—' ?></td>

              <!-- Siglas: ao passar o mouse aparece o significado; "—" se o aluno não tem limitação -->
              <td data-label="Saúde">
                <?php if ($aluno['siglas']): ?>
                  <span title="<?= htmlspecialchars($aluno['detalhe']) ?>"><?= htmlspecialchars($aluno['siglas']) ?></span>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>

              <!-- Status em badge: verde se já tem treino, vermelho se ainda não tem -->
              <td data-label="Treino">
                <?php if ($aluno['total_treinos'] > 0): ?>
                  <span class="badge badge--ok">Atualizado</span>
                <?php else: ?>
                  <span class="badge badge--pendente">Sem treino</span>
                <?php endif; ?>
              </td>

              <!-- Ações: treino (Editar/Montar Treino) e dados do aluno -->
              <td class="td-acoes">
                <div class="painel-acoes">
                  <?php if ($aluno['total_treinos'] > 0): ?>
                    <a href="montar-treino.php?id=<?= $aluno['id'] ?>" class="btn btn--editar">Editar Treino</a>
                  <?php else: ?>
                    <a href="montar-treino.php?id=<?= $aluno['id'] ?>" class="btn btn--montar">Montar Treino</a>
                  <?php endif; ?>
                  <a href="editar-aluno.php?id=<?= $aluno['id'] ?>" class="btn btn--editar">Dados</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>

    <!-- LEGENDA: significado de todas as siglas, útil no celular onde não existe "passar o mouse" -->
    <?php if ($legenda): ?>
      <p style="margin-top: 12px; font-size: 0.85rem;">
        <strong>Legenda:</strong>
        <?= htmlspecialchars(implode(' · ', array_map(fn($l) => $l['sigla'] . ' = ' . $l['descricao'], $legenda))) ?>
      </p>
    <?php endif; ?>
  </main>
    <!-- FOOTER: crédito da EloGym, que fornece o sistema pra AC Academia -->
  <footer class="footer-sistema">
    <div class="container footer-sistema__inner">
      <p class="footer-sistema__marca">Elo<span>Gym</span></p>
      <p>© <?= date('Y') ?> EloGym. Todos os direitos reservados.</p>
      <p>Sistema de treinos desenvolvido pela EloGym para a <strong>AC Academia</strong>.</p>
    </div>
  </footer>

  <script src="js/script.js"></script>
</body>

</html>
