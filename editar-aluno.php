<?php
// ---------- PROTEÇÃO ----------
// Só professora logada acessa esta página; qualquer outra pessoa volta pro login.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}
require_once 'conexao.php';

// ---------- ALUNO ----------
// Lê o id do aluno (?id= na URL ou campo escondido do formulário) e busca os dados dele; se não existir, volta pro painel.
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, nome, cpf, data_nascimento FROM alunos WHERE id = :id");
$stmt->execute(['id' => $id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
  header('Location: professor.php');
  exit;
}

// ---------- LIMITAÇÕES DISPONÍVEIS ----------
// Busca as siglas cadastradas (viram as caixinhas) e guarda os ids válidos pra conferir o que vier do formulário.
$limitacoes = $pdo->query("SELECT id, sigla, descricao FROM limitacoes_saude ORDER BY descricao")->fetchAll(PDO::FETCH_ASSOC);
$ids_validos = array_map('intval', array_column($limitacoes, 'id'));

// ---------- VALORES INICIAIS DO FORMULÁRIO ----------
// Começam com o que está salvo no banco; se houver erro de validação, são trocados pelo que foi digitado.
$erro_dados = "";
$erro_senha = "";
$nome = $aluno['nome'];
$nascimento = $aluno['data_nascimento'] ?? '';

$stmt = $pdo->prepare("SELECT limitacoes_saude_id FROM aluno_limitacoes WHERE alunos_id = :aluno");
$stmt->execute(['aluno' => $id]);
$marcadas = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

// ---------- PROCESSAMENTO DOS FORMULÁRIOS ----------
// A página tem 2 formulários; o campo escondido "acao" diz qual foi enviado.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao = $_POST['acao'] ?? '';

  // Salvar dados: valida nome, nascimento e limitações; grava tudo junto numa transação.
  if ($acao === 'dados') {
    $nome = trim($_POST['nome'] ?? '');
    $nascimento = $_POST['data_nascimento'] ?? '';
    $marcadas = is_array($_POST['limitacoes'] ?? null) ? array_values(array_unique(array_map('intval', $_POST['limitacoes']))) : [];

    // Nascimento é opcional: se preenchido, precisa ser data real, não futura e de no máximo 120 anos atrás.
    $nascimento_ok = true;
    if ($nascimento !== '') {
      $d = DateTime::createFromFormat('Y-m-d', $nascimento);
      $hoje = new DateTime('today');
      $limite = (new DateTime('today'))->modify('-120 years');
      $nascimento_ok = $d && $d->format('Y-m-d') === $nascimento && $d <= $hoje && $d >= $limite;
    }

    if ($nome === '' || mb_strlen($nome) > 150) {
      $erro_dados = "Informe o nome do aluno (até 150 caracteres).";
    } elseif (!$nascimento_ok) {
      $erro_dados = "Data de nascimento inválida.";
    } elseif (array_diff($marcadas, $ids_validos)) {
      $erro_dados = "Selecione apenas limitações de saúde válidas.";
    } else {
      // Atualiza o aluno, apaga as limitações antigas e grava as marcadas agora (ou não grava nada se algo falhar).
      $pdo->beginTransaction();

      $stmt = $pdo->prepare("UPDATE alunos SET nome = :nome, data_nascimento = :nascimento WHERE id = :id");
      $stmt->execute([
        'nome' => $nome,
        'nascimento' => $nascimento !== '' ? $nascimento : null,
        'id' => $id
      ]);

      $stmt = $pdo->prepare("DELETE FROM aluno_limitacoes WHERE alunos_id = :aluno");
      $stmt->execute(['aluno' => $id]);

      $stmt = $pdo->prepare("INSERT INTO aluno_limitacoes (alunos_id, limitacoes_saude_id) VALUES (:aluno, :limitacao)");
      foreach ($marcadas as $limitacao_id) {
        $stmt->execute(['aluno' => $id, 'limitacao' => $limitacao_id]);
      }

      $pdo->commit();
      header('Location: editar-aluno.php?id=' . $id . '&ok=dados');
      exit;
    }

  // Redefinir senha: grava uma senha temporária nova e força a troca no próximo login (primeiro_acesso = 1).
  } elseif ($acao === 'senha') {
    $nova = $_POST['senha_temporaria'] ?? '';

    if (strlen($nova) < 6) {
      $erro_senha = "A senha temporária precisa ter pelo menos 6 caracteres.";
    } else {
      $stmt = $pdo->prepare("UPDATE alunos SET senha = :senha, primeiro_acesso = 1 WHERE id = :id");
      $stmt->execute([
        'senha' => password_hash($nova, PASSWORD_DEFAULT),
        'id' => $id
      ]);
      header('Location: editar-aluno.php?id=' . $id . '&ok=senha');
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
    <title>AC Academia — Editar Aluno</title>
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
      <!-- FORMULÁRIO 1: DADOS DO ALUNO -->
      <div class="card card-formulario">
        <div class="painel-cabecalho">
          <h2>Editar Aluno</h2>
          <a href="professor.php" class="btn btn--secundario">← Voltar</a>
        </div>

        <!-- Avisos: sucesso (verde) e erro de validação (vermelho) -->
        <?php if (($_GET['ok'] ?? '') === 'dados'): ?>
          <p style="color: #1e7a3c; margin-bottom: 12px;">Dados atualizados com sucesso!</p>
        <?php endif; ?>
        <?php if ($erro_dados): ?>
          <p style="color: #e53228; margin-bottom: 12px;"><?= htmlspecialchars($erro_dados) ?></p>
        <?php endif; ?>

        <form class="form-cadastro" action="editar-aluno.php" method="post">
          <input type="hidden" name="acao" value="dados" />
          <input type="hidden" name="id" value="<?= $aluno['id'] ?>" />

          <label for="nome">Nome completo</label>
          <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>" required />

          <!-- CPF só para consulta: é com ele que o aluno entra no sistema, então não muda aqui -->
          <label for="cpf">CPF</label>
          <input type="text" id="cpf" value="<?= htmlspecialchars($aluno['cpf']) ?>" disabled />

          <label for="data_nascimento">Data de nascimento (opcional)</label>
          <input type="date" id="data_nascimento" name="data_nascimento"
                 value="<?= htmlspecialchars($nascimento) ?>" max="<?= date('Y-m-d') ?>" />

          <!-- Limitações de saúde: as já salvas vêm marcadas -->
          <label>Limitações de saúde (opcional)</label>
          <?php foreach ($limitacoes as $l): ?>
            <label class="caixa-saude">
              <input type="checkbox" name="limitacoes[]" value="<?= $l['id'] ?>"
                     <?= in_array((int) $l['id'], $marcadas, true) ? 'checked' : '' ?> />
              <?= htmlspecialchars($l['sigla'] . ' — ' . $l['descricao']) ?>
            </label>
          <?php endforeach; ?>

          <button type="submit" class="btn btn--primario">Salvar alterações</button>
        </form>
      </div>

      <!-- FORMULÁRIO 2: REDEFINIR SENHA TEMPORÁRIA -->
      <div class="card card-formulario">
        <h2>Redefinir senha</h2>
        <br />

        <?php if (($_GET['ok'] ?? '') === 'senha'): ?>
          <p style="color: #1e7a3c; margin-bottom: 12px;">Senha redefinida! Passe a nova senha ao aluno.</p>
        <?php endif; ?>
        <?php if ($erro_senha): ?>
          <p style="color: #e53228; margin-bottom: 12px;"><?= htmlspecialchars($erro_senha) ?></p>
        <?php endif; ?>

        <form class="form-cadastro" action="editar-aluno.php" method="post"
              onsubmit="return confirm('Redefinir a senha deste aluno?');">
          <input type="hidden" name="acao" value="senha" />
          <input type="hidden" name="id" value="<?= $aluno['id'] ?>" />

          <label for="senha_temporaria">Nova senha temporária</label>
          <input type="text" id="senha_temporaria" name="senha_temporaria" required />
          <small>O aluno vai trocar essa senha no próximo acesso.</small>

          <button type="submit" class="btn btn--primario">Redefinir senha</button>
        </form>
      </div>
    </div>
  </body>
</html>