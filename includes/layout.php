<?php
// =====================================================================
// LAYOUT DAS TELAS DO SISTEMA
// Antes, cada página repetia o mesmo <head>, header e footer. Agora fica tudo aqui:
//
//   head_sistema('Título')            → <meta>, CSS e o script do modo escuro (todas as telas logadas)
//   botao_tema()                      → botão sol/lua que liga e desliga o modo escuro
//   topo_professor('Título', 'alunos') → abre a página do professor: header + menu lateral
//   rodape_professor()                 → fecha a página: footer EloGym + script.js
//
// O 2º parâmetro do topo_professor diz qual item do menu fica destacado.
// =====================================================================

function head_sistema(string $titulo): void { ?>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AC Academia — <?= htmlspecialchars($titulo) ?></title>
  <link rel="stylesheet" href="css/style.css" />
  <!-- MODO ESCURO: roda antes da página aparecer, pra não piscar branco.
       Usa a escolha salva; se a pessoa nunca escolheu, segue o tema do celular/computador. -->
  <script>
    (function () {
      var tema = null;
      try { tema = localStorage.getItem("tema"); } catch (e) {}
      if (!tema && window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) tema = "escuro";
      if (tema === "escuro") document.documentElement.setAttribute("data-tema", "escuro");
    })();
  </script>
<?php }

function botao_tema(): void { ?>
  <button type="button" class="tema-toggle" data-tema-toggle aria-label="Alternar modo escuro" title="Modo escuro">
    <!-- Lua (aparece no modo claro) -->
    <svg class="tema-toggle__lua" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
    <!-- Sol (aparece no modo escuro) -->
    <svg class="tema-toggle__sol" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 1.5v2.5M12 20v2.5M4.6 4.6l1.8 1.8M17.6 17.6l1.8 1.8M1.5 12H4M20 12h2.5M4.6 19.4l1.8-1.8M17.6 6.4l1.8-1.8"/></svg>
  </button>
<?php }

// Ícones do menu lateral (SVG simples, sem precisar de biblioteca)
function icone_menu(string $nome): string {
  $caminhos = [
    'alunos'      => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.2c2.1.7 3.5 2.8 3.5 5.8"/>',
    'frequencia'  => '<rect x="3" y="4.5" width="18" height="16.5" rx="2"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/><path d="M8 15l2.5 2.5L16 12"/>',
    'cadastrar'   => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M19 8v6M16 11h6"/>',
    'exercicios'  => '<path d="M6.5 6.5v11M17.5 6.5v11M3.5 9v6M20.5 9v6M6.5 12h11"/>',
    'siglas'      => '<path d="M12 21s-7.5-4.6-7.5-10.5A4.5 4.5 0 0 1 12 7.2a4.5 4.5 0 0 1 7.5 3.3C19.5 16.4 12 21 12 21z"/>',
    'professores' => '<path d="M12 3l8 3.5v5c0 4.6-3.3 8.3-8 9.5-4.7-1.2-8-4.9-8-9.5v-5z"/><path d="M8.5 12l2.5 2.5 4.5-4.5"/>',
    'sair'        => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l-5-5 5-5M5 12h11"/>',
  ];
  return '<svg class="sidebar__icone" viewBox="0 0 24 24" aria-hidden="true">' . ($caminhos[$nome] ?? '') . '</svg>';
}

function topo_professor(string $titulo, string $ativo = ''): void {
  // Itens do menu: [chave, link, texto]. "Professores" só aparece pro administrador.
  $itens = [
    ['alunos',     'professor.php',       'Meus Alunos'],
    ['frequencia', 'frequencia.php',      'Frequência'],
    ['cadastrar',  'cadastrar-aluno.php', 'Cadastrar Aluno'],
    ['exercicios', 'cadastrar-exerc.php', 'Exercícios'],
    ['siglas',     'limitacoes.php',      'Siglas de Saúde'],
  ];
  if (!empty($_SESSION['usuario_admin'])) {
    $itens[] = ['professores', 'professores.php', 'Professores'];
  }
  ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php head_sistema($titulo); ?>
</head>

<body class="layout-sistema layout-painel">
  <!-- HEADER: logo, botão do modo escuro e hambúrguer (abre o menu lateral no celular) -->
  <header class="header header--painel">
    <div class="header__inner header__inner--painel">
      <a href="professor.php" class="header__logo">AC <span>Academia</span></a>

      <div class="header__ferramentas">
        <?php botao_tema(); ?>
        <button class="header__toggle" aria-label="Abrir menu" aria-controls="sidebar">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

  <div class="painel">
    <!-- MENU LATERAL: fixo à esquerda no computador; no celular abre por cima ao tocar no hambúrguer -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar__usuario">
        <span class="sidebar__avatar" aria-hidden="true"><?= htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['usuario_nome'], 0, 1))) ?></span>
        <div>
          <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong>
          <small><?= !empty($_SESSION['usuario_admin']) ? 'Administrador(a)' : 'Professor(a)' ?></small>
        </div>
      </div>

      <nav class="sidebar__nav" aria-label="Menu do professor">
        <?php foreach ($itens as [$chave, $link, $texto]): ?>
          <a href="<?= $link ?>" class="sidebar__link <?= $chave === $ativo ? 'sidebar__link--ativo' : '' ?>"
             <?= $chave === $ativo ? 'aria-current="page"' : '' ?>>
            <?= icone_menu($chave) ?> <?= $texto ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <a href="logout.php" class="sidebar__link sidebar__sair"><?= icone_menu('sair') ?> Sair</a>
    </aside>

    <!-- Fundo escuro atrás do menu no celular (tocar nele fecha o menu) -->
    <div class="sidebar__fundo" data-fechar-menu></div>

    <main class="pagina painel__conteudo">
<?php }

// $antes_do_script: HTML extra antes do script.js (ex: variáveis que o JS precisa)
function rodape_professor(string $antes_do_script = ''): void { ?>
    </main>
  </div>

  <!-- FOOTER: crédito da EloGym, que fornece o sistema pra AC Academia -->
  <footer class="footer-sistema">
    <div class="container footer-sistema__inner">
      <p class="footer-sistema__marca">Elo<span>Gym</span></p>
      <p>© <?= date('Y') ?> EloGym. Todos os direitos reservados.</p>
      <p>Sistema de treinos desenvolvido pela EloGym para a <strong>AC Academia</strong>.</p>
    </div>
  </footer>

  <?= $antes_do_script ?>
  <script src="js/script.js"></script>
</body>
</html>
<?php }
