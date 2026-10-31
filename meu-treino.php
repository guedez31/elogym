<?php
// ---------- PROTEÇÃO ----------
// Só aluno logado acessa esta página; qualquer outra pessoa volta pro login.
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
  header('Location: login.php');
  exit;
}
require_once 'conexao.php';
require_once 'includes/funcoes.php';
require_once 'includes/layout.php';

// ---------- PRIMEIRO ACESSO ----------
// O aluno vem da sessão (não da URL), então ele nunca consegue ver o treino de outra pessoa.
// Se ainda não trocou a senha temporária, manda pra troca antes de mostrar o treino.
$aluno_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT primeiro_acesso, treino_validade FROM alunos WHERE id = :id");
$stmt->execute(['id' => $aluno_id]);
$dados_aluno = $stmt->fetch(PDO::FETCH_ASSOC);
if ($dados_aluno['primeiro_acesso'] == 1) {
  header('Location: trocasenha.php');
  exit;
}

// ---------- FUNÇÕES DE FORMATAÇÃO ----------
// "Peito, Tríceps" → "Peito e Tríceps"; "Costas, Bíceps, Ombro" → "Bíceps, Costas e Ombro"
function juntar_grupos(?string $grupos): string {
  if (!$grupos) return '';
  $lista = explode(', ', $grupos);
  if (count($lista) === 1) return $lista[0];
  $ultimo = array_pop($lista);
  return implode(', ', $lista) . ' e ' . $ultimo;
}

// "8-10" → "8–10 reps"; "12" → "12 reps"; texto livre (ex: "até a falha") fica como está
function formatar_reps(string $reps): string {
  if (preg_match('/^\d+(\s*[-–]\s*\d+)?$/u', $reps)) {
    return str_replace('-', '–', preg_replace('/\s+/u', '', $reps)) . ' reps';
  }
  return $reps;
}

// ---------- TREINOS DO ALUNO (ABAS) ----------
// Cada treino vem com os grupos musculares dos exercícios dele (ex: "Peito, Tríceps"),
// calculados pelo banco — a professora não precisa digitar nada.
$stmt = $pdo->prepare("
  SELECT t.id, t.letra, t.data_atualizacao,
         GROUP_CONCAT(DISTINCT g.nome ORDER BY g.nome SEPARATOR ', ') AS grupos
  FROM treinos t
  LEFT JOIN treino_exercicios te ON te.treinos_id = t.id
  LEFT JOIN exercicios e ON e.id = te.exercicios_id
  LEFT JOIN grupo_muscular g ON g.id = e.grupo_muscular_id
  WHERE t.alunos_id = :aluno AND t.ativo = 1
  GROUP BY t.id, t.letra, t.data_atualizacao
  ORDER BY t.letra
");
$stmt->execute(['aluno' => $aluno_id]);
$treinos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- TREINO ABERTO ----------
// Escolhe a aba aberta: a do ?treino= da URL (só se for deste aluno) ou, se não houver, a primeira.
$treino_pedido = (int) ($_GET['treino'] ?? 0);
$treino_atual = $treinos[0] ?? null;
foreach ($treinos as $t) {
  if ($t['id'] == $treino_pedido) {
    $treino_atual = $t;
  }
}

// ---------- EXERCÍCIOS DO TREINO ----------
// Busca os exercícios do treino aberto, na ordem em que a professora montou.
// O te.id identifica cada exercício pra lembrar quais o aluno já marcou como feito.
$exercicios = [];
if ($treino_atual) {
  $stmt = $pdo->prepare("
    SELECT te.id, e.nome, e.midia, te.series, te.repeticoes, te.carga, te.observacao
    FROM treino_exercicios te
    INNER JOIN exercicios e ON e.id = te.exercicios_id
    WHERE te.treinos_id = :treino
    ORDER BY te.ordem
  ");
  $stmt->execute(['treino' => $treino_atual['id']]);
  $exercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------- CHECK-IN ----------
// Check-in de hoje (se já concluiu, mostra o aviso em vez do botão) e quantos treinos fez nos últimos 7 dias.
$hoje = date('Y-m-d');
$stmt = $pdo->prepare("SELECT letra, criado_em FROM checkins WHERE alunos_id = :aluno AND data = :hoje");
$stmt->execute(['aluno' => $aluno_id, 'hoje' => $hoje]);
$checkin_hoje = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM checkins WHERE alunos_id = :aluno AND data > :inicio");
$stmt->execute(['aluno' => $aluno_id, 'inicio' => date('Y-m-d', strtotime('-7 days'))]);
$treinos_semana = (int) $stmt->fetchColumn();

// ---------- VALIDADE DA FICHA ----------
// Se a validade passou, o aluno vê um aviso pra pedir ficha nova ao professor.
$ficha_vencida = $dados_aluno['treino_validade'] && dias_ate($dados_aluno['treino_validade']) < 0;

// ---------- FICHA COMPLETA (PDF) ----------
// Busca os exercícios de TODOS os treinos do aluno de uma vez, separados por treino:
// [id_do_treino => [exercício, exercício...]]. Só é usado na impressão.
$todos_exercicios = [];
if ($treinos) {
  $stmt = $pdo->prepare("
    SELECT te.treinos_id, e.nome, te.series, te.repeticoes, te.carga, te.observacao
    FROM treino_exercicios te
    INNER JOIN exercicios e ON e.id = te.exercicios_id
    INNER JOIN treinos t ON t.id = te.treinos_id
    WHERE t.alunos_id = :aluno AND t.ativo = 1
    ORDER BY te.treinos_id, te.ordem
  ");
  $stmt->execute(['aluno' => $aluno_id]);
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
    $todos_exercicios[$linha['treinos_id']][] = $linha;
  }
}

?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <?php head_sistema('Meu Treino'); ?>
  </head>
  <body class="layout-sistema">
    <!-- HEADER: logo (volta pra tela do aluno), nome do aluno e botão Sair -->
    <header class="header">
      <div class="container header__inner">
        <a href="meu-treino.php" class="header__logo">AC <span>Academia</span></a>

        <nav class="header__menu">
          <p>Bem-vindo, Aluno(a) <?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
        </nav>

        <?php botao_tema(); ?>

        <a href="logout.php" class="btn btn--primario header__cta">Sair</a>

        <button class="header__toggle" aria-label="Abrir menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </header>

    <main class="container pagina">
      <?php if (!$treino_atual): ?>
        <!-- ESTADO VAZIO: aluno ainda sem treino -->
        <div class="card estado-vazio">
          <h3>Seu treino ainda não está pronto</h3>
          <p>Fale com o seu professor pra ele montar a sua ficha.</p>
        </div>
      <?php else: ?>
        <!-- AVISO DE FICHA VENCIDA: a validade que o professor definiu já passou -->
        <?php if ($ficha_vencida): ?>
          <p class="alert alert--aviso">
            Sua ficha venceu em <?= date('d/m/Y', strtotime($dados_aluno['treino_validade'])) ?>.
            Fale com o seu professor pra montar um treino novo.
          </p>
        <?php endif; ?>

        <!-- ABAS: letra do treino + grupos musculares (a aberta fica vermelha) -->
        <div class="abas-treino">
          <?php foreach ($treinos as $t): ?>
            <a href="meu-treino.php?treino=<?= $t['id'] ?>"
               class="aba aba--treino <?= $t['id'] == $treino_atual['id'] ? 'aba--ativa' : '' ?>">
              <strong>Treino <?= htmlspecialchars($t['letra']) ?></strong>
              <?php if ($t['grupos']): ?>
                <small><?= htmlspecialchars(juntar_grupos($t['grupos'])) ?></small>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- TOPO DO TREINO: título, resumo e botão de PDF -->
        <?php $qtd = count($exercicios); ?>
        <div class="treino-topo">
          <div>
            <!-- Só aparece no PDF, pra folha impressa dizer de quem é o treino -->
            <h2>Treino <?= htmlspecialchars($treino_atual['letra']) ?></h2>
            <p class="subtitulo">
              <?= $qtd ?> <?= $qtd == 1 ? 'exercício' : 'exercícios' ?>
              <?php if ($treino_atual['grupos']): ?>
                · <?= htmlspecialchars(juntar_grupos($treino_atual['grupos'])) ?>
              <?php endif; ?>
              <?php if ($treino_atual['data_atualizacao']): ?>
                · atualizado em <?= date('d/m', strtotime($treino_atual['data_atualizacao'])) ?>
              <?php endif; ?>
              <?php if ($dados_aluno['treino_validade'] && !$ficha_vencida): ?>
                · ficha válida até <?= date('d/m/Y', strtotime($dados_aluno['treino_validade'])) ?>
              <?php endif; ?>
            </p>
          </div>
          <!-- PDF com TODOS os treinos (aparece se pelo menos um treino tiver exercício) -->
          <?php if ($todos_exercicios): ?>
            <button type="button" class="btn btn--sm btn--secundario" onclick="window.print()">Baixar ficha completa (PDF)</button>
          <?php endif; ?>
        </div>

        <?php if (!$exercicios): ?>
          <div class="card estado-vazio">
            <h3>Nenhum exercício neste treino ainda</h3>
            <p>O seu professor ainda está montando o Treino <?= htmlspecialchars($treino_atual['letra']) ?>.</p>
          </div>
        <?php else: ?>
          <!-- PROGRESSO: o JavaScript atualiza conforme o aluno marca os exercícios -->
          <div class="progresso" id="progresso">
            <p class="progresso__texto" id="progresso-texto">0 de <?= $qtd ?> feitos</p>
            <div class="progresso__barra"><div class="progresso__preenchido" id="progresso-barra"></div></div>
          </div>

          <!-- LISTA DE EXERCÍCIOS: um card por exercício, na ordem da professora -->
          <ol class="lista-treino" id="lista-treino" data-treino="<?= $treino_atual['id'] ?>">
            <?php foreach ($exercicios as $i => $ex): ?>
              <li class="card-exercicio" data-item="<?= $ex['id'] ?>">
                <span class="card-exercicio__ordem"><?= $i + 1 ?></span>

                <!-- Imagem: tocar abre ampliada -->
                <?php if ($ex['midia']): ?>
                  <button type="button" class="card-exercicio__midia"
                          data-ampliar="<?= htmlspecialchars($ex['midia']) ?>"
                          data-titulo="<?= htmlspecialchars($ex['nome']) ?>"
                          aria-label="Ampliar demonstração de <?= htmlspecialchars($ex['nome']) ?>">
                    <img src="<?= htmlspecialchars($ex['midia']) ?>" alt="" loading="lazy" />
                  </button>
                <?php else: ?>
                  <span class="card-exercicio__midia card-exercicio__midia--vazia" aria-hidden="true">🏋️</span>
                <?php endif; ?>

                <!-- Nome, séries/reps em pílulas, técnica em etiqueta e carga -->
                <div class="card-exercicio__info">
                  <h3 class="card-exercicio__nome"><?= htmlspecialchars($ex['nome']) ?></h3>
                  <div class="card-exercicio__pilulas">
                    <span class="pilula"><?= (int) $ex['series'] ?> <?= $ex['series'] == 1 ? 'série' : 'séries' ?></span>
                    <span class="pilula"><?= htmlspecialchars(formatar_reps($ex['repeticoes'])) ?></span>
                    <?php if ($ex['observacao']): ?>
                      <span class="etiqueta-tecnica"><?= htmlspecialchars($ex['observacao']) ?></span>
                    <?php endif; ?>
                  </div>
                  <?php if ($ex['carga']): ?>
                    <p class="card-exercicio__carga">Carga: <?= htmlspecialchars($ex['carga']) ?></p>
                  <?php endif; ?>
                </div>

                <!-- Marcar como feito (fica salvo só neste celular e zera no dia seguinte) -->
                <label class="check-feito">
                  <input type="checkbox" class="check-feito__input"
                         aria-label="Marcar <?= htmlspecialchars($ex['nome']) ?> como feito" />
                  <span class="check-feito__caixa"></span>
                </label>
              </li>
            <?php endforeach; ?>
          </ol>

          <!-- CHECK-IN: concluir o treino de hoje. Vai pro banco, e o professor vê na Frequência. -->
          <?php if ($checkin_hoje): ?>
            <div class="checkin checkin--feito">
              <div>
                <strong>✓ Treino de hoje concluído!</strong>
                <span>
                  Treino <?= htmlspecialchars($checkin_hoje['letra'] ?? '') ?> às <?= date('H:i', strtotime($checkin_hoje['criado_em'])) ?>
                  · <?= $treinos_semana ?> <?= $treinos_semana == 1 ? 'treino' : 'treinos' ?> nos últimos 7 dias
                </span>
              </div>
              <form action="concluir-treino.php" method="post" onsubmit="return confirm('Desfazer o check-in de hoje?');">
                <input type="hidden" name="acao" value="desfazer" />
                <input type="hidden" name="treino_id" value="<?= $treino_atual['id'] ?>" />
                <button type="submit" class="btn btn--sm btn--editar">Desfazer</button>
              </form>
            </div>
          <?php else: ?>
            <form class="checkin" id="checkin" action="concluir-treino.php" method="post">
              <input type="hidden" name="treino_id" value="<?= $treino_atual['id'] ?>" />
              <div>
                <strong>Terminou o treino?</strong>
                <span>Marque o check-in pro seu professor acompanhar sua frequência.</span>
              </div>
              <button type="submit" class="btn btn--primario">Concluir Treino <?= htmlspecialchars($treino_atual['letra']) ?></button>
            </form>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>
            <!-- FICHA COMPLETA PRA IMPRESSÃO: escondida na tela, só aparece no PDF.
           Uma tabela por treino (A, B, C...), com todos os exercícios. -->
      <?php if ($todos_exercicios): ?>
        <section class="ficha-impressao" data-titulo="Ficha de Treino - <?= htmlspecialchars($_SESSION['usuario_nome']) ?>">
          <div class="ficha-impressao__topo">
            <h1>Ficha de Treino</h1>
            <p>
              Aluno(a): <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong>
              · AC Academia · gerada em <?= date('d/m/Y') ?>
            </p>
          </div>

          <?php foreach ($treinos as $t): ?>
            <?php $lista_treino = $todos_exercicios[$t['id']] ?? []; ?>
            <div class="ficha-treino">
              <h2>
                Treino <?= htmlspecialchars($t['letra']) ?>
                <?php if ($t['grupos']): ?>
                  <small>· <?= htmlspecialchars(juntar_grupos($t['grupos'])) ?></small>
                <?php endif; ?>
              </h2>

              <?php if (!$lista_treino): ?>
                <p>Sem exercícios ainda.</p>
              <?php else: ?>
                <table class="ficha-tabela">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Exercício</th>
                      <th>Séries</th>
                      <th>Repetições</th>
                      <th>Carga</th>
                      <th>Observação</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($lista_treino as $i => $ex): ?>
                      <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($ex['nome']) ?></td>
                        <td><?= (int) $ex['series'] ?></td>
                        <td><?= htmlspecialchars($ex['repeticoes']) ?></td>
                        <td><?= $ex['carga'] ? htmlspecialchars($ex['carga']) : '—' ?></td>
                        <td><?= $ex['observacao'] ? htmlspecialchars($ex['observacao']) : '—' ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <p class="ficha-impressao__rodape">Sistema de treinos EloGym para a AC Academia</p>
        </section>
      <?php endif; ?>

    </main>

    <!-- VISUALIZADOR: imagem ampliada (abre ao tocar na miniatura; fecha no X, no Esc ou tocando fora) -->
    <dialog class="visualizador" id="visualizador">
      <button type="button" class="visualizador__fechar" aria-label="Fechar">×</button>
      <img id="visualizador-img" src="" alt="" />
      <p id="visualizador-titulo"></p>
    </dialog>

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