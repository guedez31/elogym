<?php
// Cadastro de exercícios pela professora: confere nome e grupo (que precisa existir),
// impede repetir o mesmo nome no mesmo grupo, grava e recarrega com aviso. Também busca
// os grupos pro select e a lista de exercícios com quantas vezes cada um está em uso.
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

$stmt = $pdo->query("
  SELECT e.id, e.nome, g.nome AS grupo, COUNT(te.id) AS usos
  FROM exercicios e
  INNER JOIN grupo_muscular g ON g.id = e.grupo_muscular_id
  LEFT JOIN treino_exercicios te ON te.exercicios_id = e.id
  GROUP BY e.id, e.nome, g.nome
  ORDER BY g.nome, e.nome
");
$lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Cadastrar Exercício</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
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

    <div class="container">
      <br />
      <div class="painel-cabecalho">
        <h2>Cadastrar Exercício</h2>
        <a href="professor.php" class="btn btn--secundario">← Voltar para Meus Alunos</a>
      </div>
      <br />

      <?php if ($erro): ?>
        <p style="color: #e53228; margin-bottom: 12px;"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <?php if (isset($_GET['cadastrado'])): ?>
        <p style="color: #1e7a3c; margin-bottom: 12px;">Exercício cadastrado com sucesso!</p>
      <?php endif; ?>
      <?php if (isset($_GET['editado'])): ?>
        <p style="color: #1e7a3c; margin-bottom: 12px;">Exercício atualizado com sucesso!</p>
      <?php endif; ?>
      <?php if (isset($_GET['excluido'])): ?>
        <p style="color: #1e7a3c; margin-bottom: 12px;">Exercício excluído com sucesso!</p>
      <?php endif; ?>
      <?php if (isset($_GET['erro'])): ?>
        <p style="color: #e53228; margin-bottom: 12px;">Não dá pra excluir: o exercício está em uso em algum treino.</p>
      <?php endif; ?>

      <form action="cadastrar-exerc.php" method="post">
        <div class="form-linha">
          <input type="text" name="nome" placeholder="Nome do Exercício"
                 value="<?= htmlspecialchars($nome) ?>" required />
          <select name="grupo_id" required>
            <option value="">Selecione um grupo muscular</option>
            <?php foreach ($grupos as $g): ?>
              <option value="<?= $g['id'] ?>" <?= $g['id'] == $grupo_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn--primario">+ Cadastrar Exercício</button>
      </form>
      <br />

      <div class="tabela-wrapper">
        <table>
          <tr>
            <th>Grupo</th>
            <th>Exercício</th>
            <th>Ação</th>
          </tr>
          <?php foreach ($lista as $ex): ?>
            <tr>
              <td><?= htmlspecialchars($ex['grupo']) ?></td>
              <td><?= htmlspecialchars($ex['nome']) ?></td>
              <td>
                <div class="painel-acoes" style="justify-content: center;">
                  <a href="editar-exercicio.php?id=<?= $ex['id'] ?>" class="btn btn--editar">Editar</a>
                  <?php if ($ex['usos'] == 0): ?>
                    <form action="excluir-exercicio.php" method="post"
                          onsubmit="return confirm('Excluir este exercício?');">
                      <input type="hidden" name="id" value="<?= $ex['id'] ?>" />
                      <button type="submit" class="btn btn--remover">Excluir</button>
                    </form>
                  <?php else: ?>
                    <span>Em uso (<?= $ex['usos'] ?>)</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <br />
    </div>
  </body>
</html>