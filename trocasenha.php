<?php
// Troca de senha no PRIMEIRO ACESSO — vale pra aluno e pra professor novo.
// Protege a página: sem login na sessão volta pro login (o exit evita que o resto rode).
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_tipo'] ?? '', ['aluno', 'professor'], true)) {
  header('Location: login.php');
  exit;
}

require_once 'conexao.php';
require_once 'includes/layout.php';

// A tabela vem de uma lista fixa (nunca do que o usuário digitou), então é seguro colocar no SQL.
$tabela = $_SESSION['usuario_tipo'] === 'aluno' ? 'alunos' : 'professores';
$destino = $_SESSION['usuario_tipo'] === 'aluno' ? 'meu-treino.php' : 'professor.php';

$erro = "";

// Confere se as duas senhas batem e têm tamanho mínimo, grava o hash novo,
// zera o primeiro_acesso e manda pra tela inicial de cada tipo de usuário.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nova = $_POST['nova_senha'] ?? '';
  $confirmar = $_POST['confirmar_senha'] ?? '';

  if (strlen($nova) < 6) {
    $erro = "A senha precisa ter pelo menos 6 caracteres.";
  } elseif ($nova !== $confirmar) {
    $erro = "As senhas não são iguais.";
  } else {
    $stmt = $pdo->prepare("UPDATE {$tabela} SET senha = :senha, primeiro_acesso = 0 WHERE id = :id");
    $stmt->execute([
      'senha' => password_hash($nova, PASSWORD_DEFAULT),
      'id' => $_SESSION['usuario_id']
    ]);
    header('Location: ' . $destino);
    exit;
  }
}
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <?php head_sistema('Troca de Senha'); ?>
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
                 required minlength="6" placeholder="Nova senha (mínimo 6 caracteres)" autocomplete="new-password" />
          <br /><br />
          <input type="password" id="confirmar_senha" name="confirmar_senha"
                 required minlength="6" placeholder="Confirmar nova senha" autocomplete="new-password" />
          <br /><br />
          <button type="submit" class="btn btn--primario">Confirmar Nova Senha</button>
        </form>
      </div>
    </div>
  </body>
</html>
