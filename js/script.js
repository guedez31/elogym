// ===========================
// CARROSSEL DE FOTOS (só existe na Home)
// ===========================
// Pega todas as imagens com classe "slide" que estão dentro de algo com classe "carrossel"
// e guarda essa lista na constante "slides" (ex: slides[0], slides[1], slides[2]...)
const slides = document.querySelectorAll(".carrossel .slide ");

// Só roda o carrossel se encontrou pelo menos uma foto na página atual
if (slides.length > 0) {
  // Guarda qual foto está ativa agora, começando pela primeira (índice 0)
  let indiceAtual = 0;

  // Função que faz a troca de uma foto pra outra
  function trocarSlide() {
    // Remove a classe "slide--ativo" da foto que está atualmente visível
    slides[indiceAtual].classList.remove("slide--ativo");

    // Calcula o próximo índice: soma 1, e usa "%" (resto da divisão)
    // pra voltar ao 0 automaticamente quando chegar no final da lista
    indiceAtual = (indiceAtual + 1) % slides.length;

    // Adiciona a classe "slide--ativo" na nova foto, tornando ela visível
    slides[indiceAtual].classList.add("slide--ativo");
  }

  // Chama a função trocarSlide() repetidamente, a cada 4000ms (4 segundos)
  setInterval(trocarSlide, 4000);
}

// ===========================
// TOGGLE DO FORMULÁRIO DE EXERCÍCIO (só existe no Montar Treino)
// ===========================

// Pega o botão e o formulário pelo id
const botao = document.getElementById("btn-toggle-form");
const formulario = document.getElementById("form-exercicio");

// Só adiciona o evento se os dois elementos existirem na página atual
if (botao && formulario) {
  // "Escuta" o evento de clique no botão
  botao.addEventListener("click", function () {
    // Alterna a classe "ativo": remove se tiver, adiciona se não tiver
    formulario.classList.toggle("ativo");
  });
}

// ===========================
// SELECT EM CASCATA: GRUPO MUSCULAR → EXERCÍCIO (só existe no Montar Treino)
// ===========================
// Os exercícios por grupo vêm do banco: o montar-treino.php define a variável
// exerciciosPorGrupo (id do grupo → lista de {id, nome}) antes de carregar este
// arquivo. Ao escolher um grupo, o select de exercícios é refeito com a lista
// dele; se voltar pra "Selecione um grupo", fica só a opção vazia.
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
