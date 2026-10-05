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
  if(!$usuario) {
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
    <!-- HEADER -->
    <header class="header">
      <div class="container header__inner">
        <a href="index.html" class="header__logo">AC <span>Academia</span></a>
      </div>
    </header>

  
    <div class="login-pagina">
      <div class="login-card">
        <h2 class="card-title">AC Academia</h2>
        <?php if ($erro): ?>
  <p style="color: #e53228; margin-bottom: 12px;"><?= $erro ?></p>
<?php endif; ?>
        <form action="login.php" method="post">
          <div class="mb-3">
            <label for="cpf" class="form-label"></label>
            <input
              type="text"
              class="form-control"
              id="cpf"
              name="cpf"
              required
              placeholder="CPF"
            />
          </div>
          <br />
          <div class="mb-3">
            <label for="senha" class="form-label"></label>
            <input
              type="password"
              class="form-control"
              id="senha"
              name="senha"
              required
              placeholder="Senha"
            />
          </div>
          <br />
          <button type="submit" class="btn btn--primario">Entrar</button>
          
        </form>
        <br />
        <a href="#" class="link-secundario">Esqueci minha senha</a>
      </div>
    </div>

    <script src="js/script.js"></script>
  </body>
  
</html>
