<?php
// ---------- PROTEÇÃO ----------
// Só professora logada acessa esta página; qualquer outra pessoa volta pro login.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}
require_once 'conexao.php';

// ---------- VARIÁVEIS DO FORMULÁRIO ----------
// Guardam o que foi digitado, pra não perder os campos quando houver erro de validação.
$erro = "";
$sigla = "";
$descricao = "";

// ---------- PROCESSAMENTO DOS FORMULÁRIOS ----------
// A página tem 3 formulários (adicionar, editar, excluir); o campo escondido "acao" diz qual foi enviado.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao = $_POST['acao'] ?? '';

  // Adicionar: valida a sigla (só letras/números, sem espaço) e o significado, impede sigla repetida e grava.
  if ($acao === 'adicionar') {
    $sigla = mb_strtoupper(trim($_POST['sigla'] ?? ''));
    $descricao = trim($_POST['descricao'] ?? '');

    if (!preg_match('/^[A-Z0-9]{1,20}$/', $sigla)) {
      $erro = "A sigla deve ter de 1 a 20 letras ou números, sem espaços ou acentos.";
    } elseif ($descricao === '' || mb_strlen($descricao) > 100) {
      $erro = "Informe o significado da sigla (até 100 caracteres).";
    } else {
      $stmt = $pdo->prepare("SELECT id FROM limitacoes_saude WHERE sigla = :sigla");
      $stmt->execute(['sigla' => $sigla]);

      if ($stmt->fetch()) {
        $erro = "Essa sigla já existe.";
      } else {
        $stmt = $pdo->prepare("INSERT INTO limitacoes_saude (sigla, descricao) VALUES (:sigla, :descricao)");
        $stmt->execute(['sigla' => $sigla, 'descricao' => $descricao]);
        header('Location: limitacoes.php?ok=adicionada');
        exit;
      }
    }

  // Editar: só o significado pode mudar (a sigla fica fixa, porque os alunos estão ligados a ela).
  } elseif ($acao === 'editar') {
    $id = (int) ($_POST['id'] ?? 0);
    $nova_descricao = trim($_POST['descricao'] ?? '');

    if ($nova_descricao === '' || mb_strlen($nova_descricao) > 100) {
      $erro = "Informe o significado da sigla (até 100 caracteres).";
    } else {
      $stmt = $pdo->prepare("UPDATE limitacoes_saude SET descricao = :descricao WHERE id = :id");
      $stmt->execute(['descricao' => $nova_descricao, 'id' => $id]);
      header('Location: limitacoes.php?ok=editada');
      exit;
    }

  // Excluir: só apaga se nenhum aluno usa a sigla, pra não deixar aluno com limitação "perdida".
  } elseif ($acao === 'excluir') {
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM aluno_limitacoes WHERE limitacoes_saude_id = :id");
    $stmt->execute(['id' => $id]);

    if ($stmt->fetchColumn() > 0) {
      header('Location: limitacoes.php?erro=em_uso');
      exit;
    }

    $stmt = $pdo->prepare("DELETE FROM limitacoes_saude WHERE id = :id");
    $stmt->execute(['id' => $id]);
    header('Location: limitacoes.php?ok=excluida');
    exit;
  }
}

// ---------- LISTA DE SIGLAS ----------
// Busca todas as siglas e conta quantos alunos usam cada uma (LEFT JOIN mantém as que ninguém usa, com 0).
$stmt = $pdo->query("
  SELECT l.id, l.sigla, l.descricao, COUNT(al.alunos_id) AS usos
  FROM limitacoes_saude l
  LEFT JOIN aluno_limitacoes al ON al.limitacoes_saude_id = l.id
  GROUP BY l.id, l.sigla, l.descricao
  ORDER BY l.sigla
");
$lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AC Academia — Siglas de Saúde</title>
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <!-- HEADER: logo (volta pro painel), nome da professora e botão Sair -->
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
      <!-- TÍTULO E BOTÃO VOLTAR -->
      <div class="painel-cabecalho">
        <h2>Siglas de Saúde</h2>
        <a href="professor.php" class="btn btn--secundario">← Voltar para Meus Alunos</a>
      </div>
      <br />

      <!-- AVISOS: erro de validação (vermelho) e resultados das ações (verde) -->
      <?php if ($erro): ?>
        <p style="color: #e53228; margin-bottom: 12px;"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <?php if (($_GET['ok'] ?? '') === 'adicionada'): ?>
        <p style="color: #1e7a3c; margin-bottom: 12px;">Sigla cadastrada com sucesso!</p>
      <?php endif; ?>
      <?php if (($_GET['ok'] ?? '') === 'editada'): ?>
        <p style="color: #1e7a3c; margin-bottom: 12px;">Significado atualizado com sucesso!</p>
      <?php endif; ?>
      <?php if (($_GET['ok'] ?? '') === 'excluida'): ?>
        <p style="color: #1e7a3c; margin-bottom: 12px;">Sigla excluída com sucesso!</p>
      <?php endif; ?>
      <?php if (($_GET['erro'] ?? '') === 'em_uso'): ?>
        <p style="color: #e53228; margin-bottom: 12px;">Não dá pra excluir: a sigla está em uso por algum aluno.</p>
      <?php endif; ?>

      <!-- FORMULÁRIO PARA CADASTRAR UMA SIGLA NOVA -->
      <form action="limitacoes.php" method="post">
        <input type="hidden" name="acao" value="adicionar" />
        <div class="form-linha">
          <input type="text" name="sigla" placeholder="Sigla (ex: PA)" maxlength="20"
                 value="<?= htmlspecialchars($sigla) ?>" required />
          <input type="text" name="descricao" placeholder="Significado (ex: Pressão alta)" maxlength="100"
                 value="<?= htmlspecialchars($descricao) ?>" required />
        </div>
        <button type="submit" class="btn btn--primario">+ Cadastrar Sigla</button>
      </form>
      <br />

      <!-- TABELA DE SIGLAS: significado editável na própria linha e botão Excluir -->
      <div class="tabela-wrapper">
        <table>
          <tr>
            <th>Sigla</th>
            <th>Significado</th>
            <th>Ação</th>
          </tr>
          <?php foreach ($lista as $l): ?>
            <tr>
              <td><strong><?= htmlspecialchars($l['sigla']) ?></strong></td>

              <!-- Edição do significado: campo preenchido + botão Salvar -->
              <td>
                <form class="linha-edicao" action="limitacoes.php" method="post">
                  <input type="hidden" name="acao" value="editar" />
                  <input type="hidden" name="id" value="<?= $l['id'] ?>" />
                  <input type="text" name="descricao" value="<?= htmlspecialchars($l['descricao']) ?>"
                         maxlength="100" required />
                  <button type="submit" class="btn btn--editar">Salvar</button>
                </form>
              </td>

              <!-- Exclusão: só aparece o botão se ninguém usa a sigla; senão mostra quantos usam -->
              <td>
                <?php if ($l['usos'] == 0): ?>
                  <form action="limitacoes.php" method="post"
                        onsubmit="return confirm('Excluir esta sigla?');">
                    <input type="hidden" name="acao" value="excluir" />
                    <input type="hidden" name="id" value="<?= $l['id'] ?>" />
                    <button type="submit" class="btn btn--remover">Excluir</button>
                  </form>
                <?php else: ?>
                  <span>Em uso (<?= $l['usos'] ?>)</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <br />
    </div>
  </body>
</html>