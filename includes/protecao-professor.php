<?php
// =====================================================================
// PROTEÇÃO DAS TELAS DO PROFESSOR
// Uso: require_once 'includes/protecao-professor.php'; no topo de cada página do professor.
//
// Confere, a cada página aberta:
//   1. se tem alguém logado como professor (senão volta pro login);
//   2. se o professor continua ATIVO no banco (se o admin desativar, ele cai na hora);
//   3. se ainda não trocou a senha temporária (primeiro acesso → tela de troca).
// Também atualiza $_SESSION['usuario_admin'], que libera o menu "Professores".
// =====================================================================
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/funcoes.php';

$stmt = $pdo->prepare("SELECT ativo, admin, primeiro_acesso FROM professores WHERE id = :id");
$stmt->execute(['id' => $_SESSION['usuario_id']]);
$professor_logado = $stmt->fetch(PDO::FETCH_ASSOC);

// Professor apagado ou desativado: encerra a sessão
if (!$professor_logado || !$professor_logado['ativo']) {
  session_destroy();
  header('Location: login.php?desativado=1');
  exit;
}

$_SESSION['usuario_admin'] = (bool) $professor_logado['admin'];

if ($professor_logado['primeiro_acesso']) {
  header('Location: trocasenha.php');
  exit;
}

// Use no topo das páginas que só o administrador pode abrir (ex: professores.php)
function exigir_admin(): void {
  if (empty($_SESSION['usuario_admin'])) {
    header('Location: professor.php');
    exit;
  }
}
