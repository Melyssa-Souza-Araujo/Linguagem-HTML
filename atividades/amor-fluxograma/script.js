// O Mapa do Fluxograma:
// Cada chave é um ID de estado, e o valor é um objeto com a pergunta e as transições 'sim' e 'nao'.
const flowchart = {
    // ESTADO INICIAL
    'start': {
        question: 'Me beija?',
        sim: 'beijo_goxtosu', // Caminho 'Sim'
        nao: 'vc_me_odeia'    // Caminho 'Não'
    },

    // CAMINHO 'NÃO'
    'vc_me_odeia': {
        question: 'Vc me odeia?',
        sim: 'quer_beijar_alguem',
        nao: 'me_beija_entao'
    },
    'quer_beijar_alguem': {
        question: 'Você quer beijar alguém?',
        sim: 'me_beija_entao',
        nao: 'vc_tem_boca'
    },
    'vc_tem_boca': {
        question: 'Você tem boca?',
        sim: 'beijo_goxtosu',
        nao: 'mentira' // O meme leva a 'Me beija'
    },
    'mentira': {
        question: 'MENTIRA',
        sim: 'vc_tem_boca', // Não é um botão, mas força a próxima pergunta
        nao: 'vc_tem_boca'  // Não é um botão, mas força a próxima pergunta
    },

    // CAMINHO 'SIM' E OUTROS
    'me_beija_entao': {
        question: 'ME BEIJA então',
        sim: 'beijo_goxtosu',
        nao: 'vc_me_odeia'
    },
    'me_beija': {
        question: 'Me beija',
        sim: 'beijo_goxtosu',
        nao: 'me_beija_final'
    },
    'me_beija_final': {
        question: 'Me beija',
        sim: 'beijo_goxtosu',
        nao: 'beijo_goxtosu' // O meme tem várias setas apontando para cá
    },

    // ESTADO FINAL DE SUCESSO
    'beijo_goxtosu': {
        answer: 'BEIJO GOXTOSU',
        type: 'success'
    },

    // ESTADO FINAL IMPLÍCITO DE FALHA (não ocorre no meme)
    // 'falha': {
    //     answer: 'Tente novamente quando tiver boca.',
    //     type: 'failure'
    // }
};


// Variável para rastrear o estado atual
let currentState = 'start';

// Elementos do DOM
const questionText = document.getElementById('question-text');
const buttonsContainer = document.getElementById('buttons-container');
const gameContainer = document.getElementById('game-container');


/**
 * Função principal para atualizar a interface com o novo estado.
 * @param {string} nextStateId O ID do próximo estado no objeto flowchart.
 */
function updateGame(nextStateId) {
    // Atualiza o estado atual
    currentState = nextStateId;
    const state = flowchart[currentState];

    // Se o estado atual tiver uma 'answer', é o final do jogo
    if (state.answer) {
        displayFinalAnswer(state);
        return;
    }

    // Atualiza o texto da pergunta
    questionText.textContent = state.question;

    // Remove todos os listeners existentes antes de recriar os botões
    const simButton = document.getElementById('btn-sim');
    const naoButton = document.getElementById('btn-nao');

    // Se a pergunta for "MENTIRA", só precisa passar para o próximo estado sem botões
    if (state.question === 'MENTIRA') {
        buttonsContainer.style.display = 'none';
        // Simula um clique "automático" para a próxima pergunta após um pequeno atraso
        setTimeout(() => {
            updateGame(state.sim); // MENTIRA sempre leva para 'vc_tem_boca'
        }, 1500); // Exibe MENTIRA por 1.5s
        return;
    } else {
        buttonsContainer.style.display = 'flex';
    }


    // Verifica se os botões existem. Se sim, apenas atualiza seus listeners.
    if (simButton && naoButton) {
        // Clonar para remover os listeners antigos de forma eficaz
        const newSimButton = simButton.cloneNode(true);
        simButton.parentNode.replaceChild(newSimButton, simButton);

        const newNaoButton = naoButton.cloneNode(true);
        naoButton.parentNode.replaceChild(newNaoButton, naoButton);

        // Adiciona novos listeners com os IDs de transição corretos
        newSimButton.onclick = () => updateGame(state.sim);
        newNaoButton.onclick = () => updateGame(state.nao);
    }
}

/**
 * Exibe a tela final (BEIJO GOXTOSU).
 * @param {object} state O objeto de estado final.
 */
function displayFinalAnswer(state) {
    // Limpa a área do jogo
    gameContainer.innerHTML = '';

    // Cria a div da mensagem final
    const messageDiv = document.createElement('div');
    messageDiv.textContent = state.answer;
    messageDiv.className = state.type === 'success' ? 'success-message' : 'failure-message';
    gameContainer.appendChild(messageDiv);

    // Cria e adiciona o botão de recomeçar
    const restartButton = document.createElement('button');
    restartButton.textContent = 'Recomeçar';
    restartButton.className = 'btn-restart';
    restartButton.onclick = restartGame;
    gameContainer.appendChild(restartButton);
}

/**
 * Reseta o jogo para o estado inicial.
 */
function restartGame() {
    // Recria a estrutura inicial do HTML
    gameContainer.innerHTML = `
        <h1 id="question-text" class="question">Me beija?</h1>
        <div id="buttons-container" class="buttons-container">
            <button id="btn-nao" class="btn btn-nao">Não</button>
            <button id="btn-sim" class="btn btn-sim">Sim</button>
        </div>
    `;

    // Reseta o estado
    currentState = 'start';

    // Chama a função principal para configurar o estado inicial (e listeners)
    setupInitialListeners();
}

/**
 * Configura os listeners iniciais para o primeiro estado.
 */
function setupInitialListeners() {
    const initialState = flowchart['start'];
    const simButton = document.getElementById('btn-sim');
    const naoButton = document.getElementById('btn-nao');
    
    // Define os listeners para o estado inicial
    simButton.onclick = () => updateGame(initialState.sim);
    naoButton.onclick = () => updateGame(initialState.nao);
}


// Inicializa o jogo ao carregar a página
document.addEventListener('DOMContentLoaded', setupInitialListeners);
