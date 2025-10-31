document.addEventListener('DOMContentLoaded', () => {
            const formCadastro = document.getElementById('formCadastro');
            const tabelaBody = document.querySelector('#tabelaAlunos tbody');
            const tabelaHeaders = document.querySelectorAll('#tabelaAlunos th.sortable');
            const btnLimpar = document.getElementById('btnLimpar');
            const filtroNotaSelect = document.getElementById('filtroNota');

            const inputNota = document.getElementById('nota');
            const notaError = document.getElementById('notaError'); 

            let alunos = [];
            let classificacaoAtiva = { chave: 'nome', direcao: 'asc' };

            // --- Funções Auxiliares ---

            const limparErroNota = () => {
                notaError.textContent = '';
                notaError.style.display = 'none';
                inputNota.classList.remove('input-error');
            };

            const mostrarErroNota = (mensagem) => {
                notaError.textContent = mensagem;
                notaError.style.display = 'block';
                inputNota.classList.add('input-error');
            };

            const carregarAlunos = () => {
                const dadosJSON = localStorage.getItem('alunosData');
                if (dadosJSON) {
                    // Usa map para garantir que a nota seja tratada como float
                    alunos = JSON.parse(dadosJSON).map(aluno => ({
                        ...aluno,
                        nota: parseFloat(aluno.nota) // Garante que a nota é um número
                    }));
                } else {
                    alunos = [];
                }
                classificarAlunos('nome', 'asc', false); 
                document.querySelector('th[data-sort-key="nome"]').setAttribute('aria-sort', 'ascending');
            };

            const salvarAlunos = () => {
                localStorage.setItem('alunosData', JSON.stringify(alunos));
            };

            const getClassificacaoCor = (nota) => {
                if (nota < 5) {
                    return 'nota-inferior';
                } else if (nota > 9) {
                    return 'nota-superior';
                }
                return '';
            };

            const filtrarAlunos = (listaAlunos, filtro) => {
                switch (filtro) {
                    case 'menor5':
                        return listaAlunos.filter(aluno => aluno.nota < 5);
                    case 'maior9':
                        return listaAlunos.filter(aluno => aluno.nota > 9);
                    case 'entre5e9':
                        return listaAlunos.filter(aluno => aluno.nota >= 5 && aluno.nota <= 9);
                    case 'todos':
                    default:
                        return listaAlunos;
                }
            };

            // Funções para os Novos Requisitos
            
            // ** REQUISITO 2: Excluir Aluno **
            window.excluirAluno = (index) => {
                if (confirm(`Tem certeza que deseja EXCLUIR o aluno(a) ${alunos[index].nome}?`)) {
                    alunos.splice(index, 1); // Remove o aluno do array
                    salvarAlunos(); // Salva a alteração
                    exibirAlunosNaTabela(alunos); // Reexibe a tabela
                }
            };

            // ** REQUISITO 1: Alterar Nota do Aluno **
            window.alterarNota = (index) => {
                const aluno = alunos[index];
                const novaNotaStr = prompt(`Digite a nova nota (0 a 10) para ${aluno.nome}:`, aluno.nota.toFixed(1));

                // Se o usuário cancelar ou não digitar nada
                if (novaNotaStr === null || novaNotaStr.trim() === "") {
                    return; // Cancela a operação
                }

                const novaNota = parseFloat(novaNotaStr);

                // Validação da nota
                if (isNaN(novaNota) || novaNota < 0 || novaNota > 10) {
                    alert(`A nota inserida (${novaNotaStr}) é inválida. Digite um valor entre 0 e 10.`);
                    return;
                }
                
                // Atualiza a nota do aluno no array
                alunos[index].nota = novaNota;
                salvarAlunos(); // Salva a alteração
                // Reexibe a lista para aplicar a cor e a ordenação correta
                classificarAlunos(classificacaoAtiva.chave, classificacaoAtiva.direcao);
                alert(`Nota de ${aluno.nome} alterada para ${novaNota.toFixed(1)} com sucesso!`);
            };

            // Função que exibe os alunos (adiciona botões de ação)
            const exibirAlunosNaTabela = (listaAlunos) => {
                const filtroAtual = filtroNotaSelect.value;
                const alunosFiltrados = filtrarAlunos(listaAlunos, filtroAtual);

                tabelaBody.innerHTML = '';
                // Usa o índice do aluno no array ORIGINAL para as ações de Excluir/Alterar
                alunosFiltrados.forEach((alunoFiltrado) => {
                    // Encontra o índice original do aluno filtrado no array 'alunos'
                    const indexOriginal = alunos.findIndex(aluno => 
                        aluno.nome === alunoFiltrado.nome && 
                        aluno.endereco === alunoFiltrado.endereco && 
                        aluno.nota === alunoFiltrado.nota
                    );

                    const row = tabelaBody.insertRow();
                    row.insertCell().textContent = alunoFiltrado.nome;
                    row.insertCell().textContent = alunoFiltrado.endereco;
                    
                    let cellNota = row.insertCell();
                    cellNota.textContent = alunoFiltrado.nota.toFixed(1);
                    cellNota.classList.add(getClassificacaoCor(alunoFiltrado.nota));
                    
                    // Célula de Ações
                    let cellAcoes = row.insertCell();
                    cellAcoes.innerHTML = `
                        <button class="btn-tabela btn-alterar" onclick="alterarNota(${indexOriginal})">✏️ Alterar Nota</button>
                        <button class="btn-tabela btn-excluir" onclick="excluirAluno(${indexOriginal})">🗑️ Excluir</button>
                    `;
                });
            };

            // Função para classificar e salvar o estado de classificação
            const classificarAlunos = (chave, direcao, updateActiveState = true) => {
                alunos.sort((a, b) => {
                    let valorA = a[chave];
                    let valorB = b[chave];

                    if (chave === 'nota') {
                        valorA = parseFloat(valorA);
                        valorB = parseFloat(valorB);
                    } else {
                        valorA = String(valorA).toLowerCase();
                        valorB = String(valorB).toLowerCase();
                    }

                    if (valorA < valorB) {
                        return direcao === 'asc' ? -1 : 1;
                    }
                    if (valorA > valorB) {
                        return direcao === 'asc' ? 1 : -1;
                    }
                    return 0;
                });

                if (updateActiveState) {
                    classificacaoAtiva = { chave, direcao };
                }
                
                exibirAlunosNaTabela(alunos);
            };

            // --- Event Listeners ---

            formCadastro.addEventListener('submit', (e) => {
                e.preventDefault();
                
                limparErroNota(); 

                const nome = document.getElementById('nome').value.trim();
                const endereco = document.getElementById('endereco').value.trim();
                const nota = parseFloat(inputNota.value);

                if (isNaN(nota) || nota < 0 || nota > 10) {
                    const mensagem = `Erro: A nota deve ser entre 0.0 e 10.0. Você inseriu ${inputNota.value}.`;
                    mostrarErroNota(mensagem);
                    return; 
                }
                
                if (!nome || !endereco) {
                    alert('Por favor, preencha todos os campos (Nome e Endereço).');
                    return;
                }

                const novoAluno = {
                    nome: nome,
                    endereco: endereco,
                    nota: nota
                };
                
                alunos.push(novoAluno);
                salvarAlunos(); 

                classificarAlunos(classificacaoAtiva.chave, classificacaoAtiva.direcao);

                formCadastro.reset();
                document.getElementById('nome').focus();
            });

            // Lógica de Classificação da Tabela
            tabelaHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    const chave = header.getAttribute('data-sort-key');
                    let direcaoAtual = header.getAttribute('aria-sort');
                    let novaDirecao = '';

                    if (chave === 'nota') {
                        if (direcaoAtual === 'descending') {
                            novaDirecao = 'ascending';
                        } else if (direcaoAtual === 'ascending') {
                            novaDirecao = null;
                        } else {
                            novaDirecao = 'descending';
                        }
                    } else { // Para "nome"
                        if (direcaoAtual === 'ascending') {
                            novaDirecao = 'descending';
                        } else if (direcaoAtual === 'descending') {
                            novaDirecao = null;
                        } else {
                            novaDirecao = 'ascending';
                        }
                    }

                    tabelaHeaders.forEach(th => th.removeAttribute('aria-sort'));

                    if (novaDirecao) {
                        header.setAttribute('aria-sort', novaDirecao);
                        const direcaoParaFuncao = novaDirecao === 'ascending' ? 'asc' : 'desc';
                        classificarAlunos(chave, direcaoParaFuncao);
                    } else {
                        // Volta para a ordem inicial (Nome ASC)
                        document.querySelector('th[data-sort-key="nome"]').setAttribute('aria-sort', 'ascending');
                        classificarAlunos('nome', 'asc');
                    }
                });
            });

            // Evento para o Filtro de Notas
            filtroNotaSelect.addEventListener('change', () => {
                exibirAlunosNaTabela(alunos); 
            });

            // Botão Limpar Dados
            btnLimpar.addEventListener('click', () => {
                if(confirm('Tem certeza que deseja limpar TODOS os dados de alunos? Esta ação é irreversível!')) {
                    localStorage.removeItem('alunosData');
                    alunos = [];
                    // Reinicializa a visualização para o estado padrão
                    filtroNotaSelect.value = 'todos'; 
                    tabelaHeaders.forEach(th => th.removeAttribute('aria-sort'));
                    document.querySelector('th[data-sort-key="nome"]').setAttribute('aria-sort', 'ascending');
                    classificacaoAtiva = { chave: 'nome', direcao: 'asc' };
                    exibirAlunosNaTabela(alunos);

                    alert('Dados de alunos limpos com sucesso!');
                }
            });

            // Inicialização: carrega, classifica por nome (asc) e exibe
            carregarAlunos();
            exibirAlunosNaTabela(alunos);
        });