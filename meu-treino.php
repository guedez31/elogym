<?php
// Protege a página: se não houver login na sessão ou se o usuário não for
// aluno, volta pro login e interrompe o carregamento (o exit evita que o
// resto da página seja processado).
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
  header('Location: login.php');
  exit;
}
// Carrega os treinos ativos do aluno logado (viram as abas), escolhe o treino
// aberto (o ?treino= da URL, só se for dele, ou o primeiro) e busca os exercícios
// em ordem. O aluno vem da sessão, então nunca vê o treino de outra pessoa.
require_once 'conexao.php';

$aluno_id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
  SELECT id, letra FROM treinos
  WHERE alunos_id = :aluno AND ativo = 1
  ORDER BY letra
");
$stmt->execute(['aluno' => $aluno_id]);
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
    <title>AC Academia — Treine perto de casa</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <!-- HEADER -->
    <header class="header">
      <div class="container header__inner">
        <a href="index.html" class="header__logo">AC <span>Academia</span></a>

        <nav class="header__menu">
          <p>Bem-vindo, Aluno(a) <?php echo $_SESSION['usuario_nome']; ?></p>
        </nav>

        <a href="logout.php" class="btn btn--primario header__cta">Sair</a>

        <button class="header__toggle" aria-label="Abrir menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </header>

    <br />
    <div >
      <div class="abas-treino">
  <?php foreach ($treinos as $t): ?>
    <a href="meu-treino.php?treino=<?= $t['id'] ?>"
       class="aba <?= $t['id'] == ($treino_atual['id'] ?? 0) ? 'aba--ativa' : '' ?>">
      Treino <?= htmlspecialchars($t['letra']) ?>
    </a>
  <?php endforeach; ?>
</div>
<br />

<?php if (!$treino_atual): ?>
  <p>Você ainda não tem treino cadastrado. Fale com o seu professor.</p>
<?php endif; ?>

    <?php if ($treino_atual): ?>
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
    <?php endif; ?>

          <br />
          <button type="button" class="btn btn--primario">
            Baixar Treino em PDF
          </button>
    </div>
  </body>
</html>
