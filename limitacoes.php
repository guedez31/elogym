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
// Busca todas as siglas, quantos alunos usam cada uma e QUEM usa (id:nome separados por "|").
// O LEFT JOIN mantém as siglas que ninguém usa, com 0.
$stmt = $pdo->query("
  SELECT l.id, l.sigla, l.descricao,
         COUNT(al.alunos_id) AS usos,
         GROUP_CONCAT(CONCAT(a.id, ':', a.nome) ORDER BY a.nome SEPARATOR '|') AS alunos
  FROM limitacoes_saude l
  LEFT JOIN aluno_limitacoes al ON al.limitacoes_saude_id = l.id
  LEFT JOIN alunos a ON a.id = al.alunos_id
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
  <body class="layout-sistema">
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

    <main class="container pagina">
      <!-- TÍTULO, EXPLICAÇÃO E BOTÃO VOLTAR -->
      <div class="painel-cabecalho">
        <div>
          <h2>Siglas de Saúde</h2>
          <p class="subtitulo">Marcam as limitações dos alunos e aparecem como aviso no Montar Treino.</p>
        </div>
        <a href="professor.php" class="btn btn--secundario">← Voltar para Meus Alunos</a>
      </div>
      <br />

      <!-- AVISOS: erro de validação (vermelho) e resultados das ações (verde) -->
      <?php if ($erro): ?>
        <p class="alert alert--erro"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>
      <?php if (($_GET['ok'] ?? '') === 'adicionada'): ?>
        <p class="alert alert--sucesso">Sigla cadastrada com sucesso!</p>
      <?php endif; ?>
      <?php if (($_GET['ok'] ?? '') === 'editada'): ?>
        <p class="alert alert--sucesso">Significado atualizado com sucesso!</p>
      <?php endif; ?>
      <?php if (($_GET['ok'] ?? '') === 'excluida'): ?>
        <p class="alert alert--sucesso">Sigla excluída com sucesso!</p>
      <?php endif; ?>
      <?php if (($_GET['erro'] ?? '') === 'em_uso'): ?>
        <p class="alert alert--erro">Não dá pra excluir: a sigla está em uso por algum aluno.</p>
      <?php endif; ?>

      <!-- CARD DE CADASTRO: sigla estreita, significado largo e botão na mesma linha (desktop) -->
      <form class="card form-novo" action="limitacoes.php" method="post">
        <input type="hidden" name="acao" value="adicionar" />
        <h3 class="form-novo__titulo">
          Nova sigla
          <!-- Prévia do selo: o JavaScript mostra enquanto a professora digita -->
          <span class="selo-saude" id="previa-sigla" hidden></span>
        </h3>
        <div class="form-novo__campos form-novo__campos--sigla">
          <input type="text" id="nova-sigla" name="sigla" placeholder="Sigla (ex: PA)" maxlength="20"
                 value="<?= htmlspecialchars($sigla) ?>" required autocomplete="off" />
          <input type="text" name="descricao" placeholder="Significado (ex: Pressão alta)" maxlength="100"
                 value="<?= htmlspecialchars($descricao) ?>" required />
          <button type="submit" class="btn btn--primario">+ Cadastrar</button>
        </div>
      </form>

      <?php if (!$lista): ?>
        <!-- ESTADO VAZIO -->
        <div class="card estado-vazio" style="margin-top: 24px;">
          <h3>Nenhuma sigla cadastrada ainda</h3>
          <p>Cadastre a primeira acima (ex: PA = Pressão alta).</p>
        </div>
      <?php else: ?>
        <!-- LISTA DE SIGLAS: cada linha mostra o texto; o "Editar" troca só aquela linha pro modo edição -->
        <ul class="card lista-exercicios lista-siglas">
          <?php foreach ($lista as $l): ?>
            <li class="item-exercicio item-sigla">
              <div class="item-sigla__principal">
                <span class="selo-saude"><?= htmlspecialchars($l['sigla']) ?></span>

                <div class="item-sigla__conteudo">
                  <!-- MODO LEITURA: significado + quem usa -->
                  <div class="item-sigla__leitura">
                    <strong><?= htmlspecialchars($l['descricao']) ?></strong>
                    <?php if ($l['usos'] == 0): ?>
                      <small>Nenhum aluno com essa sigla</small>
                    <?php else: ?>
                      <!-- <details> abre/fecha sozinho, sem JavaScript -->
                      <details>
                        <summary>Usada por <?= $l['usos'] ?> <?= $l['usos'] == 1 ? 'aluno' : 'alunos' ?></summary>
                        <ul class="lista-alunos-sigla">
                          <?php foreach (explode('|', $l['alunos']) as $item): ?>
                            <?php [$aluno_id, $aluno_nome] = explode(':', $item, 2); ?>
                            <li>
                              <a href="editar-aluno.php?id=<?= (int) $aluno_id ?>"><?= htmlspecialchars($aluno_nome) ?></a>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      </details>
                    <?php endif; ?>
                  </div>

                  <!-- MODO EDIÇÃO: escondido até clicar em "Editar" -->
                  <form class="item-sigla__edicao" action="limitacoes.php" method="post">
                    <input type="hidden" name="acao" value="editar" />
                    <input type="hidden" name="id" value="<?= $l['id'] ?>" />
                    <input type="text" name="descricao" value="<?= htmlspecialchars($l['descricao']) ?>"
                           maxlength="100" required aria-label="Significado da sigla <?= htmlspecialchars($l['sigla']) ?>" />
                    <button type="submit" class="btn btn--sm btn--primario">Salvar</button>
                    <button type="button" class="btn btn--sm btn--editar" data-cancelar>Cancelar</button>
                  </form>
                </div>
              </div>

              <!-- AÇÕES: Editar sempre; Excluir só se ninguém usa (senão, botão cinza "Em uso") -->
              <div class="item-exercicio__acoes item-sigla__acoes">
                <button type="button" class="btn btn--sm btn--editar" data-editar>Editar</button>
                <?php if ($l['usos'] == 0): ?>
                  <?php $mensagem = 'Excluir a sigla ' . $l['sigla'] . ' (' . $l['descricao'] . ')?'; ?>
                  <form action="limitacoes.php" method="post"
                        onsubmit="return confirm(<?= htmlspecialchars(json_encode($mensagem, JSON_UNESCAPED_UNICODE)) ?>);">
                    <input type="hidden" name="acao" value="excluir" />
                    <input type="hidden" name="id" value="<?= $l['id'] ?>" />
                    <button type="submit" class="btn btn--sm btn--perigo">Excluir</button>
                  </form>
                <?php else: ?>
                  <span class="btn btn--sm btn--bloqueado"
                        title="Tire a sigla dos alunos pra poder excluir.">Em uso</span>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </main>

    <!-- FOOTER: crédito da EloGym -->
    <footer class="footer-sistema">
      <div class="container footer-sistema__inner">
        <p class="footer-sistema__marca">Elo<span>Gym</span></p>
        <p>© <?= date('Y') ?> EloGym. Todos os direitos reservados.</p>
        <p>Sistema de treinos desenvolvido pela EloGym para a <strong>AC Academia</strong>.</p>
      </div>
    </footer>

    <script src="js/script.js"></script>
  </body>
</html>