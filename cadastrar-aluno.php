<?php
// Cadastro de aluno pela professora: confere nome, CPF (11 números, que não pode
// existir em alunos nem em professores) e senha temporária (mín. 6), grava o hash
// com primeiro_acesso = 1 e volta pro painel com aviso de sucesso.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}
require_once 'conexao.php';

$erro = "";
$nome = "";
$cpf = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome'] ?? '');
  $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
  $senha = $_POST['senha_temporaria'] ?? '';

  if ($nome === '' || mb_strlen($nome) > 150) {
    $erro = "Informe o nome do aluno (até 150 caracteres).";
  } elseif (strlen($cpf) !== 11) {
    $erro = "O CPF precisa ter 11 números.";
  } elseif (strlen($senha) < 6) {
    $erro = "A senha temporária precisa ter pelo menos 6 caracteres.";
  } else {
    $stmt = $pdo->prepare("
      SELECT 1 FROM alunos WHERE cpf = :cpf1
      UNION
      SELECT 1 FROM professores WHERE cpf = :cpf2
    ");
    $stmt->execute(['cpf1' => $cpf, 'cpf2' => $cpf]);

    if ($stmt->fetch()) {
      $erro = "Já existe um cadastro com esse CPF.";
    } else {
      $stmt = $pdo->prepare("
        INSERT INTO alunos (nome, cpf, senha, primeiro_acesso, dt_cadastro)
        VALUES (:nome, :cpf, :senha, 1, NOW())
      ");
      $stmt->execute([
        'nome' => $nome,
        'cpf' => $cpf,
        'senha' => password_hash($senha, PASSWORD_DEFAULT)
      ]);
      header('Location: professor.php?cadastrado=1');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Cadastrar Aluno</title>
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
      <a href="professor.php" class="link-secundario">← Voltar para Meus Alunos</a>

      <div class="card card-formulario">
        <h2>Cadastrar Aluno</h2>

        <?php if ($erro): ?>
          <p style="color: #e53228; margin-bottom: 12px;"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <form class="form-cadastro" action="cadastrar-aluno.php" method="post">
          <label for="nome">Nome completo</label>
          <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>" required />

          <label for="cpf">CPF</label>
          <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($cpf) ?>" placeholder="Só números" required />

          <label for="senha_temporaria">Senha temporária</label>
          <input type="text" id="senha_temporaria" name="senha_temporaria" required />
          <small>Passe essa senha ao aluno. Ele vai trocar no primeiro acesso.</small>

          <button type="submit" class="btn btn--primario">Cadastrar Aluno</button>
        </form>
      </div>
    </div>
  </body>
</html>