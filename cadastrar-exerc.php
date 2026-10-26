<?php
// Cadastro de exercícios pela professora: confere nome e grupo (que precisa existir),
// impede repetir o mesmo nome no mesmo grupo, grava e recarrega com aviso. Também busca
// os grupos pro select e a lista de exercícios, separada por grupo, com quantos treinos usam cada um.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}
require_once 'conexao.php';

$erro = "";
$nome = "";
$grupo_id = 0;

$grupos = $pdo->query("SELECT id, nome FROM grupo_muscular ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome'] ?? '');
  $grupo_id = (int) ($_POST['grupo_id'] ?? 0);

  $stmt = $pdo->prepare("SELECT id FROM grupo_muscular WHERE id = :id");
  $stmt->execute(['id' => $grupo_id]);
  $grupo_ok = $stmt->fetch();

  if ($nome === '' || mb_strlen($nome) > 150) {
    $erro = "Informe o nome do exercício (até 150 caracteres).";
  } elseif (!$grupo_ok) {
    $erro = "Selecione um grupo muscular.";
  } else {
    $stmt = $pdo->prepare("SELECT id FROM exercicios WHERE nome = :nome AND grupo_muscular_id = :grupo");
    $stmt->execute(['nome' => $nome, 'grupo' => $grupo_id]);

    if ($stmt->fetch()) {
      $erro = "Esse exercício já existe nesse grupo.";
    } else {
      $stmt = $pdo->prepare("INSERT INTO exercicios (nome, grupo_muscular_id) VALUES (:nome, :grupo)");
      $stmt->execute(['nome' => $nome, 'grupo' => $grupo_id]);
      header('Location: cadastrar-exerc.php?cadastrado=1');
      exit;
    }
  }
}

// Lista de exercícios: "usos" agora conta em quantos TREINOS diferentes o exercício aparece
// (antes contava linhas, então o mesmo exercício duas vezes no mesmo treino contava 2).
$stmt = $pdo->query("
  SELECT e.id, e.nome, g.nome AS grupo, COUNT(DISTINCT te.treinos_id) AS usos
  FROM exercicios e
  INNER JOIN grupo_muscular g ON g.id = e.grupo_muscular_id
  LEFT JOIN treino_exercicios te ON te.exercicios_id = e.id
  GROUP BY e.id, e.nome, g.nome
  ORDER BY g.nome, e.nome
");
$lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separa os exercícios por grupo: ['Abdômen' => [...], 'Bíceps' => [...]]
$por_grupo = [];
foreach ($lista as $ex) {
  $por_grupo[$ex['grupo']][] = $ex;
}
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Exercícios</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body class="layout-sistema">
    <!-- HEADER -->
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
      <!-- TÍTULO E BOTÃO VOLTAR -->
      <div class="painel-cabecalho">
        <h2>Exercícios</h2>
        <a href="professor.php" class="btn btn--secundario">← Voltar para Meus Alunos</a>
      </div>
      <br />

      <!-- AVISOS -->
      <?php if ($erro): ?>
        <p class="alert alert--erro"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <?php if (isset($_GET['cadastrado'])): ?>
        <p class="alert alert--sucesso">Exercício cadastrado com sucesso!</p>
      <?php endif; ?>
      <?php if (isset($_GET['editado'])): ?>
        <p class="alert alert--sucesso">Exercício atualizado com sucesso!</p>
      <?php endif; ?>
      <?php if (isset($_GET['excluido'])): ?>
        <p class="alert alert--sucesso">Exercício excluído com sucesso!</p>
      <?php endif; ?>
      <?php if (isset($_GET['erro'])): ?>
        <p class="alert alert--erro">Não dá pra excluir: o exercício está em uso em algum treino.</p>
      <?php endif; ?>

      <!-- CARD DE CADASTRO: nome, grupo e botão numa linha só no desktop -->
      <form class="card form-novo" action="cadastrar-exerc.php" method="post">
        <h3>Novo exercício</h3>
        <div class="form-novo__campos">
          <input type="text" name="nome" placeholder="Nome do exercício (ex: Supino Reto)"
                 value="<?= htmlspecialchars($nome) ?>" maxlength="150" required />
          <select name="grupo_id" required>
            <option value="">Grupo muscular</option>
            <?php foreach ($grupos as $g): ?>
              <option value="<?= $g['id'] ?>" <?= $g['id'] == $grupo_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn--primario">+ Cadastrar</button>
        </div>
      </form>

      <?php if (!$lista): ?>
        <!-- ESTADO VAZIO -->
        <div class="card estado-vazio" style="margin-top: 24px;">
          <h3>Nenhum exercício cadastrado ainda</h3>
          <p>Use o formulário acima pra cadastrar o primeiro.</p>
        </div>
      <?php else: ?>
        <!-- BUSCA E FILTRO POR GRUPO (o JavaScript esconde/mostra os itens, sem recarregar a página) -->
        <div class="filtro-exercicios">
          <input type="search" id="busca-exercicio" class="campo-busca"
                 placeholder="Buscar exercício..." aria-label="Buscar exercício" />
          <div class="filtro-grupos" id="filtro-grupos">
            <button type="button" class="chip chip--ativo" data-grupo="" aria-pressed="true">
              Todos (<?= count($lista) ?>)
            </button>
            <?php foreach ($por_grupo as $grupo => $itens): ?>
              <button type="button" class="chip" data-grupo="<?= htmlspecialchars($grupo) ?>" aria-pressed="false">
                <?= htmlspecialchars($grupo) ?> (<?= count($itens) ?>)
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- LISTA SEPARADA POR GRUPO MUSCULAR -->
        <?php foreach ($por_grupo as $grupo => $itens): ?>
          <section class="grupo-exercicios" data-grupo="<?= htmlspecialchars($grupo) ?>">
            <h3 class="grupo-exercicios__titulo">
              <?= htmlspecialchars($grupo) ?> <span>(<?= count($itens) ?>)</span>
            </h3>
            <ul class="card lista-exercicios">
              <?php foreach ($itens as $ex): ?>
                <li class="item-exercicio">
                  <div class="item-exercicio__info">
                    <strong><?= htmlspecialchars($ex['nome']) ?></strong>
                    <small>
                      <?php if ($ex['usos'] == 0): ?>
                        Ainda não usado em treinos
                      <?php else: ?>
                        Usado em <?= $ex['usos'] ?> <?= $ex['usos'] == 1 ? 'treino' : 'treinos' ?>
                      <?php endif; ?>
                    </small>
                  </div>
                  <div class="item-exercicio__acoes">
                    <a href="editar-exercicio.php?id=<?= $ex['id'] ?>" class="btn btn--sm btn--editar">Editar</a>
                    <?php if ($ex['usos'] == 0): ?>
                      <form action="excluir-exercicio.php" method="post"
                            onsubmit="return confirm('Excluir este exercício?');">
                        <input type="hidden" name="id" value="<?= $ex['id'] ?>" />
                        <button type="submit" class="btn btn--sm btn--perigo">Excluir</button>
                      </form>
                    <?php else: ?>
                      <span class="btn btn--sm btn--bloqueado"
                            title="Remova dos treinos pra poder excluir.">Em uso</span>
                    <?php endif; ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endforeach; ?>

        <!-- Aparece quando a busca não encontra nada -->
        <div id="busca-vazia" class="card estado-vazio" style="margin-top: 24px;" hidden>
          <h3>Nenhum exercício encontrado</h3>
          <p>Tente outro nome ou escolha "Todos".</p>
        </div>
      <?php endif; ?>
    </main>

    <!-- FOOTER: crédito da EloGym -->
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