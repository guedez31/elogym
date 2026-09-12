// Pega todas as imagens com classe "slide" que estão dentro de algo com classe "carrossel"
// e guarda essa lista na constante "slides" (ex: slides[0], slides[1], slides[2]...)
const slides = document.querySelectorAll(".carrossel .slide ");

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

// Pega o botão e o formulário pelo id
const botao = document.getElementById("btn-toggle-form");
const formulario = document.getElementById("form-exercicio");

// "Escuta" o evento de clique no botão
botao.addEventListener("click", function () {
  // Alterna a classe "ativo": remove se tiver, adiciona se não tiver
  formulario.classList.toggle("ativo");
});

//SELECT'S ADCIONAR EXERCICIO
const exerciciosPorGrupo = {
  peito: ["Supino Reto", "Supino Inclinado", "Crucifixo", "Crossover"],
  costas: [
    "Puxada Frontal",
    "Remada Curvada",
    "Levantamento Terra",
    "Pulldown",
  ],
  quadriceps: ["Agachamento", "Leg Press", "Cadeira Extensora"],
  posterior: ["Stiff", "Mesa Flexora", "Cadeira Flexora"],
  gluteos: ["Hip Thrust", "Glute Bridge", "Abdução de Quadril"],
  panturrilha: [
    "Elevação de Panturrilha em Pé",
    "Elevação de Panturrilha Sentado",
  ],
  abdomen: ["Prancha", "Abdominal Infra", "Abdominal Oblíquo", "Crunch"],
  ombro: [
    "Desenvolvimento",
    "Elevação Lateral",
    "Remada Alta",
    "Elevação Frontal",
  ],
  biceps: ["Rosca Direta", "Rosca Martelo", "Rosca Scott", "Rosca Concentrada"],
  triceps: ["Tríceps Testa", "Tríceps Pulley", "Paralela", "Triceps Coice"],
};

const selectGrupo = document.getElementById("select-grupo");

selectGrupo.addEventListener("change", function () {
  // isso roda toda vez que o professor escolhe um grupo diferente

  const selectExercicio = document.getElementById("select-exercicio");

  // 1. Limpa as opções antigas
  selectExercicio.innerHTML = "";

  // 2. Pega a lista de exercícios do grupo escolhido
  const grupoEscolhido = selectGrupo.value;
  const exercicios = exerciciosPorGrupo[grupoEscolhido];

  // 3. Pra cada exercício da lista, cria uma <option> nova e adiciona no select
  exercicios.forEach(function (nomeExercicio) {
    const opcao = document.createElement("option");
    opcao.value = nomeExercicio;
    opcao.textContent = nomeExercicio;
    selectExercicio.appendChild(opcao);
  });
});
