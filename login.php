<?php
session_start();
require_once "conexao.php";

$erro = "";

// Só processa se o formulário foi enviado (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
  $senha_digitada = trim($_POST['senha'] ?? '');

  // 1. Procura o CPF na tabela de ALUNOS
  $stmt = $pdo->prepare("SELECT * FROM alunos WHERE cpf = :cpf");
  $stmt->execute(['cpf' => $cpf]);
  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
  $tipo = 'aluno';

  // 2. Se não achou em alunos, procura em PROFESSORES
  if (!$usuario) {
    $stmt = $pdo->prepare("SELECT * FROM professores WHERE cpf = :cpf");
    $stmt->execute(['cpf' => $cpf]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    $tipo = 'professor';
  }

  // 3. Verifica se achou o usuário E se a senha bate com o hash salvo
  if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
    // Guarda os dados na sessão — vale pra todas as páginas depois
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_tipo'] = $tipo;

    // Redireciona conforme o tipo
    if ($tipo === 'aluno') {
      if ($usuario['primeiro_acesso'] == 1) {
        header('Location: trocasenha.php');
      } else {
        header('Location: meu-treino.php');
      }
    } else {
      header('Location: professor.php');
    }
    exit;
  } else {
    $erro = "CPF ou senha incorretos.";
  }
}
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Login</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>

  <body>
    <!-- HEADER: só o logo, que volta pra Home -->
    <header class="header">
      <div class="container header__inner">
        <a href="index.html" class="header__logo">AC <span>Academia</span></a>
      </div>
    </header>

    <div class="login-pagina">
      <div class="login-card">
        <h2>AC Academia</h2>
        <p>Entre com seu CPF e senha.</p>
        <br />

        <!-- AVISO DE ERRO -->
        <?php if ($erro): ?>
          <p class="alert alert--erro"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <!-- Os <label> ficam invisíveis na tela (.sr-only), mas leitores de tela continuam lendo -->
        <form action="login.php" method="post">
          <label for="cpf" class="sr-only">CPF</label>
          <input type="text" id="cpf" name="cpf" required placeholder="CPF"
                 inputmode="numeric" autocomplete="username" />
          <br /><br />

          <label for="senha" class="sr-only">Senha</label>
          <input type="password" id="senha" name="senha" required placeholder="Senha"
                 autocomplete="current-password" />
          <br /><br />

          <button type="submit" class="btn btn--primario">Entrar</button>
        </form>

        <a href="#" class="link-secundario">Esqueci minha senha</a>
      </div>
    </div>

    <script src="js/script.js"></script>
  </body>
</html>