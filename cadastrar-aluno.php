<?php
// ---------- PROTEÇÃO ----------
// Só professora logada acessa esta página; qualquer outra pessoa volta pro login.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
  header('Location: login.php');
  exit;
}
require_once 'conexao.php';

// ---------- LIMITAÇÕES DISPONÍVEIS ----------
// Busca as siglas cadastradas (viram as caixinhas) e guarda os ids válidos pra conferir o que vier do formulário.
$limitacoes = $pdo->query("SELECT id, sigla, descricao FROM limitacoes_saude ORDER BY descricao")->fetchAll(PDO::FETCH_ASSOC);
$ids_validos = array_map('intval', array_column($limitacoes, 'id'));

// ---------- VARIÁVEIS DO FORMULÁRIO ----------
// Guardam o que foi digitado, pra não perder os campos quando houver erro de validação.
$erro = "";
$nome = "";
$cpf = "";
$nascimento = "";
$marcadas = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // ---------- LEITURA DOS CAMPOS ----------
  // Pega o que veio do formulário: CPF só com números e as limitações marcadas como lista de ids.
  $nome = trim($_POST['nome'] ?? '');
  $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
  $senha = $_POST['senha_temporaria'] ?? '';
  $nascimento = $_POST['data_nascimento'] ?? '';
  $marcadas = is_array($_POST['limitacoes'] ?? null) ? array_values(array_unique(array_map('intval', $_POST['limitacoes']))) : [];

  // ---------- VALIDAÇÃO DA DATA DE NASCIMENTO ----------
  // Campo opcional: se preenchido, precisa ser uma data real, não futura e de no máximo 120 anos atrás.
  $nascimento_ok = true;
  if ($nascimento !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $nascimento);
    $hoje = new DateTime('today');
    $limite = (new DateTime('today'))->modify('-120 years');
    $nascimento_ok = $d && $d->format('Y-m-d') === $nascimento && $d <= $hoje && $d >= $limite;
  }

  // ---------- VALIDAÇÃO GERAL ----------
  // Confere os campos na ordem; o primeiro problema encontrado vira a mensagem de erro.
  if ($nome === '' || mb_strlen($nome) > 150) {
    $erro = "Informe o nome do aluno (até 150 caracteres).";
  } elseif (strlen($cpf) !== 11) {
    $erro = "O CPF precisa ter 11 números.";
  } elseif (strlen($senha) < 6) {
    $erro = "A senha temporária precisa ter pelo menos 6 caracteres.";
  } elseif (!$nascimento_ok) {
    $erro = "Data de nascimento inválida.";
  } elseif (array_diff($marcadas, $ids_validos)) {
    $erro = "Selecione apenas limitações de saúde válidas.";
  } else {
    // ---------- CPF REPETIDO ----------
    // O CPF não pode existir em alunos nem em professores, porque o login procura nas duas tabelas.
    $stmt = $pdo->prepare("
      SELECT 1 FROM alunos WHERE cpf = :cpf1
      UNION
      SELECT 1 FROM professores WHERE cpf = :cpf2
    ");
    $stmt->execute(['cpf1' => $cpf, 'cpf2' => $cpf]);

    if ($stmt->fetch()) {
      $erro = "Já existe um cadastro com esse CPF.";
    } else {
      // ---------- GRAVAÇÃO ----------
      // Transação: grava o aluno e as limitações dele juntos, ou não grava nada se algo falhar.
      $pdo->beginTransaction();

      // Grava o aluno com senha em hash e primeiro_acesso = 1 (ele troca a senha no primeiro login).
      $stmt = $pdo->prepare("
        INSERT INTO alunos (nome, cpf, senha, primeiro_acesso, dt_cadastro, data_nascimento)
        VALUES (:nome, :cpf, :senha, 1, NOW(), :nascimento)
      ");
      $stmt->execute([
        'nome' => $nome,
        'cpf' => $cpf,
        'senha' => password_hash($senha, PASSWORD_DEFAULT),
        'nascimento' => $nascimento !== '' ? $nascimento : null
      ]);
      $novo_id = $pdo->lastInsertId();

      // Liga o aluno novo a cada limitação marcada (uma linha na tabela de ligação por limitação).
      $stmt = $pdo->prepare("INSERT INTO aluno_limitacoes (alunos_id, limitacoes_saude_id) VALUES (:aluno, :limitacao)");
      foreach ($marcadas as $limitacao_id) {
        $stmt->execute(['aluno' => $novo_id, 'limitacao' => $limitacao_id]);
      }

      $pdo->commit();
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
      <div class="card card-formulario">
        <!-- TÍTULO E BOTÃO VOLTAR -->
        <div class="painel-cabecalho">
          <h2>Cadastrar Aluno</h2>
          <a href="professor.php" class="btn btn--secundario">← Voltar</a>
        </div>

        <!-- AVISO DE ERRO DE VALIDAÇÃO -->
        <?php if ($erro): ?>
          <p style="color: #e53228; margin-bottom: 12px;"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <!-- FORMULÁRIO DE CADASTRO -->
        <form class="form-cadastro" action="cadastrar-aluno.php" method="post">
          <!-- Dados principais do aluno -->
          <label for="nome">Nome completo</label>
          <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>" required />

          <label for="cpf">CPF</label>
          <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($cpf) ?>" placeholder="Só números" required />

          <!-- Data de nascimento (opcional): o campo de data não deixa escolher dia futuro -->
          <label for="data_nascimento">Data de nascimento (opcional)</label>
          <input type="date" id="data_nascimento" name="data_nascimento"
                 value="<?= htmlspecialchars($nascimento) ?>" max="<?= date('Y-m-d') ?>" />

          <!-- Limitações de saúde (opcional): uma caixinha por sigla cadastrada, dá pra marcar várias -->
          <label>Limitações de saúde (opcional)</label>
          <?php foreach ($limitacoes as $l): ?>
            <label class="caixa-saude">
              <input type="checkbox" name="limitacoes[]" value="<?= $l['id'] ?>"
                     <?= in_array((int) $l['id'], $marcadas, true) ? 'checked' : '' ?> />
              <?= htmlspecialchars($l['sigla'] . ' — ' . $l['descricao']) ?>
            </label>
          <?php endforeach; ?>
          <small>Marque todas que valem. Falta alguma? Cadastre em "Siglas de Saúde" no painel.</small>

          <!-- Senha temporária: aparece em texto pra professora poder passar ao aluno -->
          <label for="senha_temporaria">Senha temporária</label>
          <input type="text" id="senha_temporaria" name="senha_temporaria" required />
          <small>Passe essa senha ao aluno. Ele vai trocar no primeiro acesso.</small>

          <button type="submit" class="btn btn--primario">Cadastrar Aluno</button>
        </form>
      </div>
    </div>
  </body>
</html>