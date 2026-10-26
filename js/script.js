// ===========================
// MENU MOBILE (hambúrguer) — existe em todas as páginas com header
// ===========================
// Ao clicar no botão de 3 risquinhos, coloca/tira a classe "header--aberto" no <header>.
// O CSS usa essa classe pra mostrar o menu e o botão Sair embaixo do logo (só abaixo de 1024px).
const header = document.querySelector(".header");
const botaoMenu = document.querySelector(".header__toggle");

if (header && botaoMenu) {
  botaoMenu.setAttribute("aria-expanded", "false");

  function fecharMenu() {
    header.classList.remove("header--aberto");
    botaoMenu.setAttribute("aria-expanded", "false");
    botaoMenu.setAttribute("aria-label", "Abrir menu");
  }

  botaoMenu.addEventListener("click", function () {
    const aberto = header.classList.toggle("header--aberto");
    botaoMenu.setAttribute("aria-expanded", aberto ? "true" : "false");
    botaoMenu.setAttribute("aria-label", aberto ? "Fechar menu" : "Abrir menu");
  });

  // Clicou num link do menu: fecha o menu (útil nos links da Home, Sobre, Contato)
  header.querySelectorAll(".header__menu a, .header__cta").forEach(function (link) {
    link.addEventListener("click", fecharMenu);
  });

  // Tecla Esc fecha o menu
  document.addEventListener("keydown", function (evento) {
    if (evento.key === "Escape") fecharMenu();
  });

  // Se a tela crescer até o tamanho de desktop com o menu aberto, volta ao normal
  window.addEventListener("resize", function () {
    if (window.innerWidth >= 1024) fecharMenu();
  });
}

// ===========================
// CARROSSEL DE FOTOS (só existe na Home)
// ===========================
// Pega todas as imagens com classe "slide" dentro de ".carrossel" e troca a visível a cada 4 segundos.
const slides = document.querySelectorAll(".carrossel .slide");

if (slides.length > 0) {
  let indiceAtual = 0;

  function trocarSlide() {
    slides[indiceAtual].classList.remove("slide--ativo");
    // "%" volta pro 0 quando chega no final da lista
    indiceAtual = (indiceAtual + 1) % slides.length;
    slides[indiceAtual].classList.add("slide--ativo");
  }

  setInterval(trocarSlide, 4000);
}

// ===========================
// TOGGLE DO FORMULÁRIO DE EXERCÍCIO (só existe no Montar Treino)
// ===========================
const botao = document.getElementById("btn-toggle-form");
const formulario = document.getElementById("form-exercicio");

if (botao && formulario) {
  botao.addEventListener("click", function () {
    formulario.classList.toggle("ativo");
  });
}

// ===========================
// SELECT EM CASCATA: GRUPO MUSCULAR → EXERCÍCIO (só existe no Montar Treino)
// ===========================
// O montar-treino.php define a variável exerciciosPorGrupo (id do grupo → lista de {id, nome})
// antes de carregar este arquivo. Ao escolher um grupo, o select de exercícios é refeito.
const selectGrupo = document.getElementById("select-grupo");

if (selectGrupo && typeof exerciciosPorGrupo !== "undefined") {
  selectGrupo.addEventListener("change", function () {
    const selectExercicio = document.getElementById("select-exercicio");
    selectExercicio.innerHTML =
      '<option value="">Selecione um exercício</option>';

    const exercicios = exerciciosPorGrupo[selectGrupo.value] || [];
    exercicios.forEach(function (ex) {
      const opcao = document.createElement("option");
      opcao.value = ex.id;
      opcao.textContent = ex.nome;
      selectExercicio.appendChild(opcao);
    });
  });
}

// ===========================
// BUSCA E FILTRO DE EXERCÍCIOS (só existe no Cadastrar Exercício)
// ===========================
// Esconde/mostra os exercícios conforme o texto digitado e o grupo escolhido nos botões.
// A busca ignora acentos e maiúsculas ("abdomen" acha "Abdômen") e também procura pelo nome do grupo.
const campoBusca = document.getElementById("busca-exercicio");
const filtroGrupos = document.getElementById("filtro-grupos");

if (campoBusca && filtroGrupos) {
  let grupoAtivo = "";

  function semAcento(texto) {
    return texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
  }

  function filtrar() {
    const termo = semAcento(campoBusca.value);
    let totalVisivel = 0;

    document.querySelectorAll(".grupo-exercicios").forEach(function (secao) {
      const nomeGrupo = secao.dataset.grupo;
      const grupoOk = grupoAtivo === "" || nomeGrupo === grupoAtivo;
      let visiveis = 0;

      secao.querySelectorAll(".item-exercicio").forEach(function (item) {
        const nome = semAcento(item.querySelector("strong").textContent);
        const mostrar = grupoOk && (nome.includes(termo) || semAcento(nomeGrupo).includes(termo));
        item.hidden = !mostrar;
        if (mostrar) visiveis++;
      });

      // Some com o título do grupo quando nenhum exercício dele aparece
      secao.hidden = visiveis === 0;
      totalVisivel += visiveis;
    });

    document.getElementById("busca-vazia").hidden = totalVisivel > 0;
  }

  campoBusca.addEventListener("input", filtrar);

  filtroGrupos.addEventListener("click", function (evento) {
    const chip = evento.target.closest(".chip");
    if (!chip) return;

    grupoAtivo = chip.dataset.grupo;
    filtroGrupos.querySelectorAll(".chip").forEach(function (c) {
      const ativo = c === chip;
      c.classList.toggle("chip--ativo", ativo);
      c.setAttribute("aria-pressed", ativo ? "true" : "false");
    });
    filtrar();
  });
}

// ===========================
// SIGLAS DE SAÚDE (só existe no limitacoes.php)
// ===========================

// --- Prévia do selo + sigla sempre em maiúscula, sem acento e sem espaço ---
const campoSigla = document.getElementById("nova-sigla");
const previaSigla = document.getElementById("previa-sigla");

if (campoSigla && previaSigla) {
  function atualizarPrevia() {
    // Mesma regra do PHP: só A-Z e 0-9
    const limpa = campoSigla.value
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toUpperCase()
      .replace(/[^A-Z0-9]/g, "");

    if (campoSigla.value !== limpa) campoSigla.value = limpa;
    previaSigla.textContent = limpa;
    previaSigla.hidden = limpa === "";
  }

  campoSigla.addEventListener("input", atualizarPrevia);
  atualizarPrevia(); // caso a página volte com erro e o campo já tenha valor
}

// --- Editar uma linha por vez ---
// "Editar" coloca a classe .item-sigla--editando na linha; o CSS esconde o texto e mostra o campo.
// "Cancelar" (ou Esc) desfaz o que foi digitado e volta pro texto.
const itensSigla = document.querySelectorAll(".item-sigla");

if (itensSigla.length > 0) {
  function fecharEdicao(item) {
    const campo = item.querySelector(".item-sigla__edicao input[name='descricao']");
    campo.value = campo.defaultValue; // volta pro valor original
    item.classList.remove("item-sigla--editando");
  }

  itensSigla.forEach(function (item) {
    item.querySelector("[data-editar]").addEventListener("click", function () {
      // Fecha qualquer outra linha que esteja em edição
      itensSigla.forEach(function (outro) {
        if (outro !== item) fecharEdicao(outro);
      });

      item.classList.add("item-sigla--editando");
      const campo = item.querySelector(".item-sigla__edicao input[name='descricao']");
      campo.focus();
      campo.select();
    });

    item.querySelector("[data-cancelar]").addEventListener("click", function () {
      fecharEdicao(item);
    });

    item.querySelector(".item-sigla__edicao").addEventListener("keydown", function (evento) {
      if (evento.key === "Escape") fecharEdicao(item);
    });
  });
}

// ===========================
// EDITAR EXERCÍCIO DO TREINO (só existe no Montar Treino)
// ===========================
// "Editar" troca só aquela linha pro formulário (séries, repetições, carga, observação).
// "Cancelar" (ou Esc) desfaz o que foi digitado: o form.reset() volta os campos pros valores originais.
const itensTreino = document.querySelectorAll(".item-treino");

if (itensTreino.length > 0) {
  function fecharEdicaoTreino(item) {
    item.querySelector(".item-treino__edicao").reset();
    item.classList.remove("item-treino--editando");
  }

  itensTreino.forEach(function (item) {
    item.querySelector("[data-editar]").addEventListener("click", function () {
      // Fecha qualquer outra linha que esteja em edição
      itensTreino.forEach(function (outro) {
        if (outro !== item) fecharEdicaoTreino(outro);
      });

      item.classList.add("item-treino--editando");
      item.querySelector(".item-treino__edicao input[name='series']").focus();
    });

    item.querySelector("[data-cancelar]").addEventListener("click", function () {
      fecharEdicaoTreino(item);
    });

    item.querySelector(".item-treino__edicao").addEventListener("keydown", function (evento) {
      if (evento.key === "Escape") fecharEdicaoTreino(item);
    });
  });
}

// ===========================
// MEU TREINO: MARCAR COMO FEITO + PROGRESSO (só existe no meu-treino.php)
// ===========================
// As marcações ficam salvas só no celular do aluno (localStorage), separadas por treino e por dia.
// No dia seguinte começa tudo desmarcado. Não mexe no banco de dados.
const listaTreino = document.getElementById("lista-treino");

if (listaTreino) {
  const agora = new Date();
  const hoje = agora.getFullYear() + "-" + (agora.getMonth() + 1) + "-" + agora.getDate();
  const chave = "feitos-" + listaTreino.dataset.treino + "-" + hoje;

  const cards = listaTreino.querySelectorAll(".card-exercicio");
  const progresso = document.getElementById("progresso");
  const textoProgresso = document.getElementById("progresso-texto");
  const barraProgresso = document.getElementById("progresso-barra");

  // localStorage pode falhar (aba anônima, bloqueio do navegador): por isso o try/catch
  function lerFeitos() {
    try {
      return JSON.parse(localStorage.getItem(chave)) || [];
    } catch (erro) {
      return [];
    }
  }

  function salvarFeitos(ids) {
    try {
      localStorage.setItem(chave, JSON.stringify(ids));
    } catch (erro) {}
  }

  // Apaga as marcações de dias anteriores, pra não acumular lixo no celular
  try {
    Object.keys(localStorage).forEach(function (k) {
      if (k.startsWith("feitos-") && !k.endsWith("-" + hoje)) localStorage.removeItem(k);
    });
  } catch (erro) {}

  function atualizarProgresso() {
    const total = cards.length;
    const feitos = listaTreino.querySelectorAll(".card-exercicio--feito").length;
    const completo = total > 0 && feitos === total;

    textoProgresso.textContent = completo ? "Treino concluído! 💪" : feitos + " de " + total + " feitos";
    barraProgresso.style.width = (total ? (feitos / total) * 100 : 0) + "%";
    progresso.classList.toggle("progresso--completo", completo);
  }

  const feitosSalvos = lerFeitos();

  cards.forEach(function (card) {
    const check = card.querySelector(".check-feito__input");

    // Restaura o que já foi marcado hoje
    if (feitosSalvos.includes(card.dataset.item)) {
      check.checked = true;
      card.classList.add("card-exercicio--feito");
    }

    check.addEventListener("change", function () {
      card.classList.toggle("card-exercicio--feito", check.checked);
      const ids = Array.from(listaTreino.querySelectorAll(".card-exercicio--feito")).map(function (c) {
        return c.dataset.item;
      });
      salvarFeitos(ids);
      atualizarProgresso();
    });
  });

  atualizarProgresso();
}

// ===========================
// MEU TREINO: IMAGEM AMPLIADA (só existe no meu-treino.php)
// ===========================
// Tocar na miniatura abre a imagem grande num <dialog>. Fecha no X, no Esc (nativo) ou tocando no fundo escuro.
const visualizador = document.getElementById("visualizador");

if (visualizador) {
  const imagemGrande = document.getElementById("visualizador-img");
  const tituloImagem = document.getElementById("visualizador-titulo");

  document.querySelectorAll("[data-ampliar]").forEach(function (botao) {
    botao.addEventListener("click", function () {
      imagemGrande.src = botao.dataset.ampliar;
      imagemGrande.alt = "Execução de " + botao.dataset.titulo;
      tituloImagem.textContent = botao.dataset.titulo;
      visualizador.showModal();
    });
  });

  visualizador.querySelector(".visualizador__fechar").addEventListener("click", function () {
    visualizador.close();
  });

  // Clique no fundo escuro (fora da caixa) fecha
  visualizador.addEventListener("click", function (evento) {
    if (evento.target === visualizador) visualizador.close();
  });
}

// ===========================
// NOME DO ARQUIVO PDF (só existe no meu-treino.php)
// ===========================
// O navegador usa o título da página como nome do PDF. Na hora de imprimir troca pra
// "Ficha de Treino - Nome do Aluno" e depois volta ao normal.
const fichaImpressao = document.querySelector(".ficha-impressao");

if (fichaImpressao) {
  const tituloOriginal = document.title;

  window.addEventListener("beforeprint", function () {
    document.title = fichaImpressao.dataset.titulo;
  });

  window.addEventListener("afterprint", function () {
    document.title = tituloOriginal;
  });
}
