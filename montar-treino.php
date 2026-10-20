<?php
// Protege a página da professora: se não houver login na sessão ou se o
// usuário não for professor, volta pro login e interrompe o carregamento.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}

// Lê o id do aluno vindo da URL (?id=), busca o nome dele no banco e, se o id
// for inválido ou o aluno não existir, devolve a professora pro painel.
require_once 'conexao.php';

$aluno_id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE id = :id");
$stmt->execute(['id' => $aluno_id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
  header('Location: professor.php');
  exit;
}

// Carrega os treinos ativos do aluno (viram as abas A, B, C...), escolhe o
// treino aberto (o ?treino= da URL ou, se não houver, o primeiro) e busca os
// exercícios dele em ordem. O INNER JOIN aqui é o certo: todo item de
// treino_exercicios tem um exercício, então não existe "linha sem par".
$stmt = $pdo->prepare("
  SELECT id, letra FROM treinos
  WHERE alunos_id = :aluno AND ativo = 1
  ORDER BY letra
");
$stmt->execute(['aluno' => $aluno['id']]);
$treinos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$treino_pedido = (int) ($_GET['treino'] ?? 0);
$treino_atual = $treinos[0] ?? null;
foreach ($treinos as $t) {
  if ($t['id'] == $treino_pedido) {
    $treino_atual = $t;
  }
}

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

// Busca os grupos musculares e os exercícios do banco, organizados por grupo,
// pra montar os dois selects do formulário de adicionar exercício.
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
    <title>AC Academia — Treine perto de casa</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <!-- HEADER -->
    <header class="header">
      <div class="container header__inner">
        <a href="index.html" class="header__logo">AC <span>Academia</span></a>

        <nav class="header__menu">
          <h3><?= htmlspecialchars($aluno['nome']) ?></h3>
        </nav>
      </div>
    </header>
    <br />
    <div class="abas-treino">
      <?php foreach($treinos as $t): ?>
      <a href="montar-treino.php?id=<?= $aluno['id'] ?>&treino=<?= $t['id'] ?>"
        class="aba <?=  $t['id'] == ($treino_atual['id'] ?? 0) ? 'aba--ativa' : '' ?>">Treino <?=  htmlspecialchars($t['letra']) ?>
      </a>
      <?php endforeach; ?>
      <form action="criar-treino.php" method="post">
        <input type="hidden" name="aluno_id" value="<?= $aluno['id'] ?>" />
        <button type="submit" class="aba aba--add">+</button>
      </form>
    </div>
    <br />
    <div class="tabela-wrapper">
      <table>
          <tr>
            <th>Exercício</th>
            <th>Ação</th>
          </tr>
        <?php foreach ($exercicios as $ex): ?>
          <tr>
            <td>
              <h4><?=  htmlspecialchars($ex['nome']) ?></h4>
              <?= htmlspecialchars($ex['series'] . 'x' . $ex['repeticoes']) ?>
              <?=  $ex['observacao'] ? ' + ' . htmlspecialchars($ex['observacao']) : '' ?>
            </td>
            <td>
              <form action= "remover-exercicio.php" method="post"
                  onsubmit="return confirm('Remover este exercício do treino ?');">
                <input type="hidden" name="aluno_id" value="<?=  $aluno['id'] ?>" />
                <input type="hidden" name="treino_id" value="<?=  $treino_atual['id'] ?>"/>
                <input type="hidden" name="item_id" value="<?=  $ex['id'] ?>"/>
                <button type="submit" class="btn btn--remover">Remover</button> 
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <button type="button" class="btn btn--secundario" id="btn-toggle-form">
      + Adicionar Exercício
    </button>

  <?php if ($treino_atual): ?>
    <form class="formulario-exercicio" id="form-exercicio" action="adicionar-exercicio.php" method="post">
      <input type="hidden" name="aluno_id" value="<?= $aluno['id'] ?>" />
      <input type="hidden" name="treino_id" value="<?= $treino_atual['id'] ?>" />

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

      <div class="form-linha">
        <input type="number" name="series" min="1" placeholder="Séries" required />
        <input type="text" name="repeticoes" placeholder="Repetições (ex: 8-10)" required />
      </div>
      <div class="form-linha">
        <input type="text" name="carga" placeholder="Carga (opcional)" />
        <input type="text" name="observacao" placeholder="Observação (ex: DROP, FALHA)" />
      </div>

      <button type="submit" class="btn btn--primario">+ Salvar Exercício</button>
    </form>
  <?php endif; ?>
    <script>
      const exerciciosPorGrupo = <?= json_encode($exercicios_por_grupo, JSON_HEX_TAG) ?>;
    </script>
    <script src="js/script.js"></script>
  </body>
</html>
