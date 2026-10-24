<?php
// ---------- PROTEÇÃO ----------
// Só professora logada acessa esta página; qualquer outra pessoa volta pro login.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}
require_once 'conexao.php';

// ---------- ALUNO ----------
// Lê o id do aluno vindo da URL (?id=) e busca o nome dele; se o id for inválido, volta pro painel.
$aluno_id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE id = :id");
$stmt->execute(['id' => $aluno_id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
  header('Location: professor.php');
  exit;
}

// ---------- LIMITAÇÕES DO ALUNO ----------
// Busca as limitações de saúde desse aluno (sigla e significado) pra mostrar o aviso antes de montar o treino.
$stmt = $pdo->prepare("
  SELECT l.sigla, l.descricao
  FROM aluno_limitacoes al
  INNER JOIN limitacoes_saude l ON l.id = al.limitacoes_saude_id
  WHERE al.alunos_id = :aluno
  ORDER BY l.sigla
");
$stmt->execute(['aluno' => $aluno['id']]);
$limitacoes_aluno = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- TREINOS (ABAS A, B, C...) ----------
// Carrega os treinos ativos do aluno; cada um vira uma aba.
$stmt = $pdo->prepare("
  SELECT id, letra FROM treinos
  WHERE alunos_id = :aluno AND ativo = 1
  ORDER BY letra
");
$stmt->execute(['aluno' => $aluno['id']]);
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
// Busca os exercícios do treino aberto, na ordem em que foram montados.
$exercicios = [];
if ($treino_atual) {
  $stmt = $pdo->prepare("
    SELECT te.id, e.nome, te.series, te.repeticoes, te.observacao
    FROM treino_exercicios te
    INNER JOIN exercicios e ON e.id = te.exercicios_id
    WHERE te.treinos_id = :treino
    ORDER BY te.ordem
  ");
  $stmt->execute(['treino' => $treino_atual['id']]);
  $exercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------- OPÇÕES DO FORMULÁRIO DE ADICIONAR ----------
// Busca os grupos musculares e os exercícios organizados por grupo, pros dois selects em cascata.
$grupos = $pdo->query("SELECT id, nome FROM grupo_muscular ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

$exercicios_por_grupo = [];
$lista = $pdo->query("SELECT id, nome, grupo_muscular_id FROM exercicios ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
foreach ($lista as $e) {
  $exercicios_por_grupo[$e['grupo_muscular_id']][] = ['id' => $e['id'], 'nome' => $e['nome']];
}
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Montar Treino</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <!-- HEADER: logo (volta pro painel) e nome do aluno -->
    <header class="header">
      <div class="container header__inner">
        <a href="professor.php" class="header__logo">AC <span>Academia</span></a>

        <nav class="header__menu">
          <h3><?= htmlspecialchars($aluno['nome']) ?></h3>
        </nav>
      </div>
    </header>

    <!-- BOTÃO VOLTAR E AVISO DE LIMITAÇÕES: o aviso só aparece se o aluno tem alguma sigla -->
    <div class="container">
      <br />
      <a href="professor.php" class="btn btn--secundario">← Voltar para Meus Alunos</a>
      <?php if ($limitacoes_aluno): ?>
        <p style="margin-top: 16px;">
          <strong>Atenção:</strong>
          <?php foreach ($limitacoes_aluno as $l): ?>
            <span class="selo-saude"><?= htmlspecialchars($l['sigla']) ?></span>
            <?= htmlspecialchars($l['descricao']) ?>&nbsp;&nbsp;
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
    </div>
    <br />

    <!-- ABAS DE TREINO: uma por treino do aluno (a aberta fica vermelha) e o "+" que cria o próximo -->
    <div class="abas-treino">
      <?php foreach ($treinos as $t): ?>
        <a href="montar-treino.php?id=<?= $aluno['id'] ?>&treino=<?= $t['id'] ?>"
           class="aba <?= $t['id'] == ($treino_atual['id'] ?? 0) ? 'aba--ativa' : '' ?>">
          Treino <?= htmlspecialchars($t['letra']) ?>
        </a>
      <?php endforeach; ?>
      <form action="criar-treino.php" method="post">
        <input type="hidden" name="aluno_id" value="<?= $aluno['id'] ?>" />
        <button type="submit" class="aba aba--add">+</button>
      </form>
    </div>
    <br />

    <!-- TABELA DE EXERCÍCIOS: cada linha mostra o exercício e o botão Remover (com confirmação) -->
    <div class="tabela-wrapper">
      <table>
        <tr>
          <th>Exercício</th>
          <th>Ação</th>
        </tr>
        <?php foreach ($exercicios as $ex): ?>
          <tr>
            <td>
              <h4><?= htmlspecialchars($ex['nome']) ?></h4>
              <?= htmlspecialchars($ex['series'] . 'x' . $ex['repeticoes']) ?>
              <?= $ex['observacao'] ? ' + ' . htmlspecialchars($ex['observacao']) : '' ?>
            </td>
            <td>
              <form action="remover-exercicio.php" method="post"
                    onsubmit="return confirm('Remover este exercício do treino?');">
                <input type="hidden" name="aluno_id" value="<?= $aluno['id'] ?>" />
                <input type="hidden" name="treino_id" value="<?= $treino_atual['id'] ?>" />
                <input type="hidden" name="item_id" value="<?= $ex['id'] ?>" />
                <button type="submit" class="btn btn--remover">Remover</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <!-- BOTÃO QUE ABRE/FECHA O FORMULÁRIO DE ADICIONAR EXERCÍCIO (o JavaScript faz o abre/fecha) -->
    <button type="button" class="btn btn--secundario" id="btn-toggle-form">
      + Adicionar Exercício
    </button>

    <!-- FORMULÁRIO DE ADICIONAR EXERCÍCIO: só aparece se o aluno já tem pelo menos um treino -->
    <?php if ($treino_atual): ?>
      <form class="formulario-exercicio" id="form-exercicio" action="adicionar-exercicio.php" method="post">
        <input type="hidden" name="aluno_id" value="<?= $aluno['id'] ?>" />
        <input type="hidden" name="treino_id" value="<?= $treino_atual['id'] ?>" />

        <!-- Grupo muscular e exercício: o segundo select é preenchido pelo JavaScript conforme o grupo -->
        <div class="form-linha">
          <select id="select-grupo" required>
            <option value="">Selecione um grupo muscular</option>
            <?php foreach ($grupos as $g): ?>
              <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="exercicio_id" id="select-exercicio" required>
            <option value="">Selecione um exercício</option>
          </select>
        </div>

        <!-- Séries e repetições (obrigatórios) -->
        <div class="form-linha">
          <input type="number" name="series" min="1" placeholder="Séries" required />
          <input type="text" name="repeticoes" placeholder="Repetições (ex: 8-10)" required />
        </div>

        <!-- Carga e observação (opcionais) -->
        <div class="form-linha">
          <input type="text" name="carga" placeholder="Carga (opcional)" />
          <input type="text" name="observacao" placeholder="Observação (ex: DROP, FALHA)" />
        </div>

        <button type="submit" class="btn btn--primario">+ Salvar Exercício</button>
      </form>
    <?php endif; ?>

    <!-- LISTA DE EXERCÍCIOS POR GRUPO: o PHP entrega pro JavaScript (precisa vir antes do script.js) -->
    <script>
      const exerciciosPorGrupo = <?= json_encode($exercicios_por_grupo, JSON_HEX_TAG) ?>;
    </script>
    <script src="js/script.js"></script>
  </body>
</html>