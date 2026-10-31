<?php
// ---------- PROTEÇÃO ----------
// Só ADMINISTRADOR acessa (ex: a Daniela). Professor comum que abrir esse endereço volta pro painel.
require_once 'includes/protecao-professor.php';
exigir_admin();

$meu_id = (int) $_SESSION['usuario_id'];

// ---------- VARIÁVEIS DO FORMULÁRIO DE CADASTRO ----------
// Guardam o que foi digitado, pra não perder os campos quando houver erro de validação.
$erro = "";
$nome = "";
$cpf = "";
$email = "";
$admin = false;

// ---------- PROCESSAMENTO DOS FORMULÁRIOS ----------
// O campo escondido "acao" diz qual formulário foi enviado:
//   cadastrar → novo professor | ativo → ativar/desativar | admin → dar/tirar admin | senha → redefinir senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao = $_POST['acao'] ?? '';
  $id = (int) ($_POST['id'] ?? 0);

  if ($acao === 'cadastrar') {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha_temporaria'] ?? '';
    $admin = isset($_POST['admin']);

    if ($nome === '' || mb_strlen($nome) > 250) {
      $erro = "Informe o nome do professor (até 250 caracteres).";
    } elseif (!cpf_valido($cpf)) {
      $erro = "CPF inválido. Confira os números digitados.";
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $erro = "E-mail inválido.";
    } elseif (strlen($senha) < 6) {
      $erro = "A senha temporária precisa ter pelo menos 6 caracteres.";
    } else {
      // O CPF não pode existir em alunos nem em professores, porque o login procura nas duas tabelas.
      $stmt = $pdo->prepare("SELECT 1 FROM alunos WHERE cpf = :cpf1 UNION SELECT 1 FROM professores WHERE cpf = :cpf2");
      $stmt->execute(['cpf1' => $cpf, 'cpf2' => $cpf]);

      if ($stmt->fetch()) {
        $erro = "Já existe um cadastro com esse CPF.";
      } else {
        // primeiro_acesso = 1: o professor novo troca a senha temporária no primeiro login
        $stmt = $pdo->prepare("
          INSERT INTO professores (nome, cpf, email, senha, admin, ativo, primeiro_acesso)
          VALUES (:nome, :cpf, :email, :senha, :admin, 1, 1)
        ");
        $stmt->execute([
          'nome' => $nome,
          'cpf' => $cpf,
          'email' => $email !== '' ? $email : null,
          'senha' => password_hash($senha, PASSWORD_DEFAULT),
          'admin' => $admin ? 1 : 0,
        ]);
        header('Location: professores.php?ok=cadastrado');
        exit;
      }
    }

  // As ações abaixo nunca valem pra própria conta: assim o admin não se tranca pra fora
  // e sempre sobra pelo menos um administrador ativo.
  } elseif (in_array($acao, ['ativo', 'admin'], true) && $id !== $meu_id) {
    $coluna = $acao; // vem da lista fixa acima, então é seguro no SQL
    $stmt = $pdo->prepare("UPDATE professores SET {$coluna} = 1 - {$coluna} WHERE id = :id");
    $stmt->execute(['id' => $id]);
    header('Location: professores.php?ok=' . $acao);
    exit;

  } elseif ($acao === 'senha' && $id !== $meu_id) {
    $nova = $_POST['senha_temporaria'] ?? '';
    if (strlen($nova) < 6) {
      header('Location: professores.php?erro=senha');
      exit;
    }
    $stmt = $pdo->prepare("UPDATE professores SET senha = :senha, primeiro_acesso = 1 WHERE id = :id");
    $stmt->execute(['senha' => password_hash($nova, PASSWORD_DEFAULT), 'id' => $id]);
    header('Location: professores.php?ok=senha');
    exit;
  }
}

// ---------- LISTA DE PROFESSORES ----------
// Ativos primeiro, depois por nome. Mostra quantos alunos cada um tem com treino montado.
$professores = $pdo->query("
  SELECT p.id, p.nome, p.cpf, p.email, p.admin, p.ativo, p.primeiro_acesso,
         (SELECT COUNT(DISTINCT t.alunos_id) FROM treinos t WHERE t.professores_id = p.id) AS total_alunos
  FROM professores p
  ORDER BY p.ativo DESC, p.nome
")->fetchAll(PDO::FETCH_ASSOC);

$mensagens = [
  'cadastrado' => 'Professor cadastrado! Passe o CPF e a senha temporária pra ele entrar.',
  'ativo'      => 'Acesso do professor atualizado.',
  'admin'      => 'Permissão de administrador atualizada.',
  'senha'      => 'Senha redefinida! O professor vai trocar no próximo acesso.',
];

require_once 'includes/layout.php';
topo_professor('Professores', 'professores');
?>
      <div class="painel-cabecalho">
        <div>
          <h2>Professores</h2>
          <p class="subtitulo">Só administradores veem esta tela.</p>
        </div>
      </div>

      <!-- AVISOS -->
      <?php if (isset($mensagens[$_GET['ok'] ?? ''])): ?>
        <p class="alert alert--sucesso"><?= $mensagens[$_GET['ok']] ?></p>
      <?php endif; ?>
      <?php if (($_GET['erro'] ?? '') === 'senha'): ?>
        <p class="alert alert--erro">A senha temporária precisa ter pelo menos 6 caracteres.</p>
      <?php endif; ?>

      <!-- LISTA: um card por professor no celular -->
      <div class="tabela-wrapper">
        <table class="tabela-cards">
          <tr>
            <th>Nome</th>
            <th>CPF</th>
            <th>Perfil</th>
            <th>Situação</th>
            <th>Ação</th>
          </tr>
          <?php foreach ($professores as $p): ?>
            <tr class="<?= $p['ativo'] ? '' : 'linha--inativa' ?>">
              <td class="td-titulo">
                <?= htmlspecialchars($p['nome']) ?>
                <?php if ($p['id'] == $meu_id): ?><small class="td-detalhe">(você)</small><?php endif; ?>
                <?php if ($p['email']): ?><span class="td-detalhe"><?= htmlspecialchars($p['email']) ?></span><?php endif; ?>
              </td>
              <td data-label="CPF"><?= htmlspecialchars(formatar_cpf($p['cpf'])) ?></td>
              <td data-label="Perfil"><?= $p['admin'] ? '<span class="badge badge--admin">Admin</span>' : 'Professor(a)' ?></td>
              <td data-label="Situação">
                <?php if (!$p['ativo']): ?>
                  <span class="badge badge--pendente">Desativado</span>
                <?php elseif ($p['primeiro_acesso']): ?>
                  <span class="badge badge--andamento" title="Ainda não entrou pra trocar a senha temporária">Aguardando 1º acesso</span>
                <?php else: ?>
                  <span class="badge badge--ok">Ativo</span>
                <?php endif; ?>
              </td>
              <td class="td-acoes">
                <?php if ($p['id'] == $meu_id): ?>
                  —
                <?php else: ?>
                  <div class="painel-acoes">
                    <form action="professores.php" method="post"
                          onsubmit="return confirm(<?= htmlspecialchars(json_encode(($p['ativo'] ? 'Desativar o acesso de ' : 'Reativar o acesso de ') . $p['nome'] . '?', JSON_UNESCAPED_UNICODE)) ?>);">
                      <input type="hidden" name="acao" value="ativo" />
                      <input type="hidden" name="id" value="<?= $p['id'] ?>" />
                      <button type="submit" class="btn btn--sm <?= $p['ativo'] ? 'btn--perigo' : 'btn--editar' ?>">
                        <?= $p['ativo'] ? 'Desativar' : 'Reativar' ?>
                      </button>
                    </form>
                    <form action="professores.php" method="post">
                      <input type="hidden" name="acao" value="admin" />
                      <input type="hidden" name="id" value="<?= $p['id'] ?>" />
                      <button type="submit" class="btn btn--sm btn--editar"><?= $p['admin'] ? 'Tirar admin' : 'Tornar admin' ?></button>
                    </form>
                    <!-- Redefinir senha: abre um campo pequeno embaixo -->
                    <details class="redefinir-senha">
                      <summary class="btn btn--sm btn--editar">Senha</summary>
                      <form action="professores.php" method="post">
                        <input type="hidden" name="acao" value="senha" />
                        <input type="hidden" name="id" value="<?= $p['id'] ?>" />
                        <input type="text" name="senha_temporaria" minlength="6" required placeholder="Nova senha temporária" />
                        <button type="submit" class="btn btn--sm btn--primario">Salvar</button>
                      </form>
                    </details>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <p class="subtitulo" style="margin-top: 8px;">
        Professor desativado não consegue mais entrar, mas os treinos que ele montou continuam com os alunos.
      </p>

      <!-- CADASTRO DE PROFESSOR -->
      <div class="card card-formulario" id="novo">
        <h2>Cadastrar professor</h2>
        <br />

        <?php if ($erro): ?>
          <p class="alert alert--erro"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <form class="form-cadastro" action="professores.php#novo" method="post">
          <input type="hidden" name="acao" value="cadastrar" />

          <label for="nome">Nome completo</label>
          <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>" maxlength="250" required />

          <label for="cpf">CPF</label>
          <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars(formatar_cpf($cpf)) ?>"
                 placeholder="000.000.000-00" inputmode="numeric" maxlength="14" data-cpf required />
          <small>É com o CPF que o professor entra no sistema.</small>

          <label for="email">E-mail (opcional)</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" />

          <label for="senha_temporaria">Senha temporária</label>
          <input type="text" id="senha_temporaria" name="senha_temporaria" minlength="6" required />
          <small>Passe essa senha ao professor. Ele vai trocar no primeiro acesso.</small>

          <label class="caixa-saude">
            <input type="checkbox" name="admin" <?= $admin ? 'checked' : '' ?> />
            Administrador (pode cadastrar e desativar professores)
          </label>

          <button type="submit" class="btn btn--primario">Cadastrar professor</button>
        </form>
      </div>
<?php rodape_professor(); ?>
