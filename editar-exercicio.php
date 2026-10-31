<?php
// Edição de exercício pela professora: carrega o exercício pelo ?id=, confere nome e
// grupo, impede repetir o mesmo nome no mesmo grupo e atualiza o registro.
require_once 'includes/protecao-professor.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, nome, grupo_muscular_id FROM exercicios WHERE id = :id");
$stmt->execute(['id' => $id]);
$exercicio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exercicio) {
  header('Location: cadastrar-exerc.php');
  exit;
}

$grupos = $pdo->query("SELECT id, nome FROM grupo_muscular ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

$erro = "";
$nome = $exercicio['nome'];
$grupo_id = (int) $exercicio['grupo_muscular_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome'] ?? '');
  $grupo_id = (int) ($_POST['grupo_id'] ?? 0);

  $stmt = $pdo->prepare("SELECT id FROM grupo_muscular WHERE id = :id");
  $stmt->execute(['id' => $grupo_id]);
  $grupo_ok = $stmt->fetch();

  if ($nome === '' || mb_strlen($nome) > 150) {
    $erro = "Informe o nome do exercício (até 150 caracteres).";
  } elseif (!$grupo_ok) {
    $erro = "Selecione um grupo muscular.";
  } else {
    $stmt = $pdo->prepare("
      SELECT id FROM exercicios
      WHERE nome = :nome AND grupo_muscular_id = :grupo AND id <> :id
    ");
    $stmt->execute(['nome' => $nome, 'grupo' => $grupo_id, 'id' => $id]);

    if ($stmt->fetch()) {
      $erro = "Esse exercício já existe nesse grupo.";
    } else {
      $stmt = $pdo->prepare("UPDATE exercicios SET nome = :nome, grupo_muscular_id = :grupo WHERE id = :id");
      $stmt->execute(['nome' => $nome, 'grupo' => $grupo_id, 'id' => $id]);
      header('Location: cadastrar-exerc.php?editado=1');
      exit;
    }
  }
}

// ---------- TELA ----------
require_once 'includes/layout.php';
topo_professor('Editar Exercício', 'exercicios');
?>
      <div class="card card-formulario">
        <div class="painel-cabecalho">
          <h2>Editar Exercício</h2>
          <a href="cadastrar-exerc.php" class="btn btn--secundario">← Voltar</a>
        </div>

        <?php if ($erro): ?>
          <p class="alert alert--erro"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <form class="form-cadastro" action="editar-exercicio.php" method="post">
          <input type="hidden" name="id" value="<?= $exercicio['id'] ?>" />

          <label for="nome">Nome do exercício</label>
          <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>" required />

          <label for="grupo_id">Grupo muscular</label>
          <select id="grupo_id" name="grupo_id" required>
            <?php foreach ($grupos as $g): ?>
              <option value="<?= $g['id'] ?>" <?= $g['id'] == $grupo_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['nome']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <button type="submit" class="btn btn--primario">Salvar alterações</button>
        </form>
      </div>
<?php rodape_professor(); ?>
