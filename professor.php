<?php
// Protege a página da professora: se não houver login na sessão ou se o
// usuário não for professor, volta pro login e interrompe o carregamento.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}

//Busque todos os alunos no banco, com o status do treino de cada um:
//"Atualizado" se o aluno já tem pelo menos um treino, "Sem Treino" se o aluno ainda não tem treino.
require_once 'conexao.php';

$stmt = $pdo->query("
  SELECT a.id, a.nome,
    COUNT(t.id) AS total_treinos
  FROM alunos a
  LEFT JOIN treinos t ON t.alunos_id = a.id
  GROUP BY a.id, a.nome
  ORDER By a.nome
  ");
  $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
          <p>Bem-vindo, Professor(a) <?php echo $_SESSION['usuario_nome']; ?></p>
        </nav>

        <a href="logout.php" class="btn btn--primario header__cta">Sair</a>

        <button class="header__toggle" aria-label="Abrir menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </header>
    <br />
    <div class="cadastrar-exercicio card">
      <div class="cadastrar-exercicio card-body painel-cabecalho">
        <h2 class="card-title">Meus Alunos</h2>
        <a href="cadastrar-exerc.php" class="btn btn--exercicio"
          >+ Cadastrar Exercício</a
        >
      </div>
    </div>
    <br />

    <div class="tabela-wrapper">
      <table>
  <tr>
    <th>Nome</th>
    <th>Treino (Status)</th>
    <th>Ação</th>
  </tr>
  <?php foreach ($alunos as $aluno): ?>
    <tr>
      <td><?= htmlspecialchars($aluno['nome']) ?></td>
      <?php if ($aluno['total_treinos'] > 0): ?>
        <td>Atualizado</td>
        <td><a href="editar-aluno.php?id=<?= $aluno['id'] ?>" class="btn btn--editar">Editar</a></td>
      <?php else: ?>
        <td>Sem Treino</td>
        <td><a href="montar-treino.php?id=<?= $aluno['id'] ?>" class="btn btn--montar">Montar Treino</a></td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
</table>
    </div>
    <script src="js/script.js"></script>
  </body>
</html>
