<?php
// Protege a página: se não houver login na sessão ou se o usuário não for
// aluno, volta pro login e interrompe o carregamento (o exit evita que o
// resto da página seja processado).
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
  header('Location: login.php');
  exit;
}

// Troca de senha do aluno em primeiro acesso: confere se as duas senhas batem e
// têm tamanho mínimo, grava o hash novo, zera o primeiro_acesso e manda pro treino.
require_once 'conexao.php';

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nova = $_POST['nova_senha'] ?? '';
  $confirmar = $_POST['confirmar_senha'] ?? '';

  if (strlen($nova) < 6) {
    $erro = "A senha precisa ter pelo menos 6 caracteres.";
  } elseif ($nova !== $confirmar) {
    $erro = "As senhas não são iguais.";
  } else {
    $stmt = $pdo->prepare("UPDATE alunos SET senha = :senha, primeiro_acesso = 0 WHERE id = :id");
    $stmt->execute([
      'senha' => password_hash($nova, PASSWORD_DEFAULT),
      'id' => $_SESSION['usuario_id']
    ]);
    header('Location: meu-treino.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Troca de Senha</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <div class="login-pagina">
      <div class="login-card">
        <h2>AC Academia</h2>
        <p>Primeira vez acessando o sistema? Crie uma nova senha para continuar.</p>
        <br />

        <!-- AVISO DE ERRO (um só, com o texto escapado) -->
        <?php if ($erro): ?>
          <p class="alert alert--erro"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <form action="trocasenha.php" method="post">
          <input type="password" id="nova_senha" name="nova_senha"
                 required minlength="6" placeholder="Nova senha (mínimo 6 caracteres)" />
          <br /><br />
          <input type="password" id="confirmar_senha" name="confirmar_senha"
                 required minlength="6" placeholder="Confirmar nova senha" />
          <br /><br />
          <button type="submit" class="btn btn--primario">Confirmar Nova Senha</button>
        </form>
      </div>
    </div>
  </body>
</html>
