console.log('script.js');

let cfg = {};
let AllItens = [];
let list = ['16700562', '16700563', '16700564', '16700565', '16700566', '16700568', '16700569', '16700570', '16700571', '16701083'];

function setCookie(name, value, days = 365) {
    // Se days for uma string como "+7 days", extrai o número
    let daysValue = days;
    if (typeof days === 'string' && days.includes('days')) {
        const match = days.match(/(\d+)/);
        daysValue = match ? parseInt(match[1]) : 7; // Padrão para 7 dias
    } else if (typeof days === 'number') {
        daysValue = days;
    } else {
        daysValue = 7; // Padrão seguro: 7 dias
    }
    
    const expires = new Date();
    expires.setTime(expires.getTime() + (daysValue * 24 * 60 * 60 * 1000));
    document.cookie = name + "=" + value + ";expires=" + expires.toUTCString() + ";path=/";
}

function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}
function safeDecodeURIComponent(str) {
    try {
        return decodeURIComponent(str || '');
    } catch (e) {
        // Se houver erro na decodificação, retorna a string original
        return str || '';
    }
}

function updateVersionField() {
    const versaoCampo = document.getElementById('VersaoCampo');
    
    if (versaoCampo) {
        const selectedOption = versaoCampo.options[versaoCampo.selectedIndex];
        if (selectedOption) {
            const descricao = selectedOption.getAttribute('data-descricao');
        }
    }
}
function loadVersoes() {
    $.post('lb/server.php', { tipo: 1 }, function(response) {
        if (response && response.success && response.versoes) {
            const selectVersao = document.getElementById('VersaoCampo');
            if (!selectVersao) {
                console.error('Elemento #VersaoCampo não encontrado no DOM');
                return;
            }
            
            // Limpa as opções existentes
            selectVersao.innerHTML = '';
            
            // Adiciona as versões disponíveis com período
            response.versoes.forEach(function(versaoData) {
                const option = document.createElement('option');
                option.value = versaoData.versao;
                
                // Formata o período a partir da versão (últimos 6 dígitos: AAMMMM)
                const versaoStr = versaoData.versao.toString();
                const ano = versaoStr.substring(0, 4);
                const mes = versaoStr.substring(4, 6);
                
                option.textContent = `Versão: ${versaoData.versao} | 1º Período: ${mes}/${ano}`;
                option.setAttribute('data-descricao', versaoData.descricao || '');
                option.setAttribute('data-inicio', versaoData.inicio || '');
                option.setAttribute('data-editavel', versaoData.editavel || 'false');
                selectVersao.appendChild(option);
            });
            
            // Seleciona a versão atual se houver cookie
            if (cfg.versao) {
                selectVersao.value = cfg.versao;
                
                // Atualiza cfg.editavel baseado na versão selecionada
                const selectedOption = selectVersao.options[selectVersao.selectedIndex];
                if (selectedOption) {
                    const editavelValue = selectedOption.getAttribute('data-editavel') || 'false';
                    cfg.editavel = editavelValue === 'true' || editavelValue === '1';
                    console.log('Versão selecionada:', cfg.versao);
                    console.log('cfg.editavel atualizado para:', cfg.editavel);
                }
            }
        } else {
            console.error('Erro ao carregar versões:', response);
        }
    }, "json").fail(function(xhr, status, error) {
        console.error('Erro na requisição de versões:', status, error);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    if(getCookie('cookie_timeout')) {
        cookie_timeout = getCookie('cookie_timeout');
    } else {
        cookie_timeout = "+7 days";
        setCookie('cookie_timeout', cookie_timeout);
    }

    if(getCookie('colunasVisiveis')) {
        const colunasVisiveisValue = getCookie('colunasVisiveis');
        colVisiveis.value = colunasVisiveisValue;
        cfg.colunasVisiveis = parseInt(colunasVisiveisValue) || 12;
        // Renova o cookie com novo tempo de expiração
        setCookie('colunasVisiveis', colunasVisiveisValue, cookie_timeout);
    }
    if(getCookie('versao')) {
        cfg.versao = parseInt(getCookie('versao')) || 202507;
        inicio = parseInt(getCookie('inicio')) || 0;
        cfg.descricao = safeDecodeURIComponent(getCookie('descricao'));
        cfg.editavel = getCookie('editavel') === 'true' || getCookie('editavel') === '1';
        
        // Renova os cookies de versão com novo tempo de expiração
        setCookie('versao', cfg.versao, cookie_timeout);
        setCookie('inicio', inicio, cookie_timeout);
        setCookie('descricao', getCookie('descricao'), cookie_timeout);
        setCookie('editavel', cfg.editavel ? 'true' : 'false', cookie_timeout);
        
        $.post('lb/server.php', { tipo: 1, periodo: inicio }, function(dt, s) {
            periodos = dt;
			// console.log(periodos);
            
            // Update version field after periodos are loaded
            setTimeout(() => {
                updateVersionField();
            }, 100);
        }, "json").fail(function(xhr, status, error) {
            console.error('Erro ao carregar períodos:', status, error);
            console.error('Resposta do servidor:', xhr.responseText);
            
            // Mesmo com erro, tentar atualizar o campo com dados padrão
            setTimeout(() => {
                updateVersionField();
            }, 100);
        });
    } else {
        // Busca a versão mais atual do servidor baseada no timestamp
        $.post('lb/server.php', { tipo: '1.1' }, function(response) {
            if (response && response.success) {
                cfg.versao = parseInt(response.versao) || 202507;
                cfg.descricao = response.descricao || '';
                cfg.editavel = response.editavel === 'true' || response.editavel === '1' || response.editavel === 1;
                const inicioValue = parseInt(response.inicio) || 0;
                
                // Salva os cookies com a versão mais atual
                setCookie('versao', cfg.versao.toString(), cookie_timeout);
                setCookie('inicio', inicioValue.toString(), cookie_timeout);
                setCookie('descricao', cfg.descricao, cookie_timeout);
                setCookie('editavel', cfg.editavel ? 'true' : 'false', cookie_timeout);
                
                // Atualiza a variável inicio (se existir)
                if (typeof inicio !== 'undefined') {
                    inicio = inicioValue;
                }
                
                console.log('Versão mais atual carregada:', cfg.versao);
                
                // Carrega os períodos com o início da versão mais atual
                $.post('lb/server.php', { tipo: 1, periodo: inicioValue }, function(dt, s) {
                    if (typeof periodos !== 'undefined') {
                        periodos = dt;
                    }
                    
                    // Atualiza o campo de versão após carregar períodos
                    setTimeout(() => {
                        updateVersionField();
                    }, 100);
                }, "json").fail(function(xhr, status, error) {
                    console.error('Erro ao carregar períodos:', status, error);
                    
                    // Mesmo com erro, tentar atualizar o campo
                    setTimeout(() => {
                        updateVersionField();
                    }, 100);
                });
            } else {
                // Fallback: se não conseguir buscar, usa valor padrão
                console.warn('Não foi possível buscar versão mais atual, usando padrão');
                cfg.versao = 202507;
                cfg.editavel = false;
                setCookie('versao', cfg.versao, cookie_timeout);
                setCookie('editavel', 'false', cookie_timeout);
                
                setTimeout(() => {
                    updateVersionField();
                }, 100);
            }
        }, "json").fail(function(xhr, status, error) {
            console.error('Erro ao buscar versão mais atual:', status, error);
            
            // Fallback em caso de erro
            cfg.versao = 202507;
            cfg.editavel = false;
            setCookie('versao', cfg.versao, cookie_timeout);
            setCookie('editavel', 'false', cookie_timeout);
            
            setTimeout(() => {
                updateVersionField();
            }, 100);
        });
    }

    loadVersoes();
    
    // Event Listener para mudança de versão
    const versaoCampo = document.getElementById('VersaoCampo');
    if (versaoCampo) {
        versaoCampo.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption) {
                // Obtém os dados da opção selecionada
                const novaVersao = parseInt(selectedOption.value) || 202507;
                const descricao = selectedOption.getAttribute('data-descricao') || '';
                const inicioValue = parseInt(selectedOption.getAttribute('data-inicio')) || 0;
                const editavelValue = selectedOption.getAttribute('data-editavel') || 'false';
                const editavel = editavelValue === 'true' || editavelValue === '1';
                
                // Atualiza a variável cfg
                cfg.versao = novaVersao;
                cfg.descricao = descricao;
                cfg.editavel = editavel;
                
                // Atualiza a variável inicio (se existir)
                if (typeof inicio !== 'undefined') {
                    inicio = inicioValue;
                }
                
                // Salva os cookies com as informações da versão
                setCookie('versao', cfg.versao.toString(), cookie_timeout);
                setCookie('inicio', inicioValue.toString(), cookie_timeout);
                setCookie('descricao', descricao, cookie_timeout);
                setCookie('editavel', editavel ? 'true' : 'false', cookie_timeout);
                
                console.log('Versão alterada para:', cfg.versao);
                console.log('Descrição:', cfg.descricao);
                console.log('Início:', inicioValue);
                console.log('Editável:', cfg.editavel);
                
                // Recarrega os períodos com o novo início
                $.post('lb/server.php', { tipo: 1, periodo: inicioValue }, function(dt, s) {
                    if (typeof periodos !== 'undefined') {
                        periodos = dt;
                    }
                    // console.log('Períodos recarregados:', periodos);
                }, "json").fail(function(xhr, status, error) {
                    console.error('Erro ao recarregar períodos:', status, error);
                });
                
                // Atualiza o campo de versão
                updateVersionField();
            }
        });
    }


    $.post('lb/server.php', { tipo: 3 }, function(response) {
        console.log('Resposta recebida:', response);
        if (response && response.success && response.itens) {
            AllItens = response.itens;
            console.log('Itens carregados com sucesso');
            console.log('Total de itens:', AllItens.length);
            console.log('AllItens armazenado:', AllItens);
            console.log('Primeiro item:', AllItens[0]);

            // Popula filtros com base nos valores de AllItens
            try {
                const byKey = (arr, key) => arr
                    .map(it => it && it[key])
                    .filter(v => v !== undefined && v !== null && v !== '')
                    .reduce((set, v) => (set.add(v), set), new Set());

                const toOptions = (set, { numeric = false } = {}) => {
                    const vals = Array.from(set);
                    if (numeric) {
                        vals.sort((a, b) => Number(a) - Number(b));
                    } else {
                        vals.sort((a, b) => String(a).localeCompare(String(b)));
                    }
                    return vals;
                };

                const fillSelect = (selId, values, labelAll = 'Todos') => {
                    const sel = document.getElementById(selId);
                    if (!sel) return;
                    sel.innerHTML = '';
                    const optAll = document.createElement('option');
                    optAll.value = '';
                    optAll.textContent = labelAll;
                    sel.appendChild(optAll);
                    values.forEach(v => {
                        const opt = document.createElement('option');
                        opt.value = v;
                        opt.textContent = v;
                        sel.appendChild(opt);
                    });
                };

                // Extrai conjuntos únicos
                const tipos = byKey(AllItens, 'tipo');
                const leadtimes = byKey(AllItens, 'leadtime');
                const coberturas = byKey(AllItens, 'cobertura');
                const familias = byKey(AllItens, 'familia');
                const fornecedores = byKey(AllItens, 'fornecedor');

                // Converte para arrays ordenados e injeta nos selects
                fillSelect('filtroTipoItem', toOptions(tipos), 'Todos os Tipos');
                fillSelect('filtroLeadtime', toOptions(leadtimes, { numeric: true }), 'Todos os Leadtimes');
                fillSelect('filtrocobertura', toOptions(coberturas, { numeric: true }), 'Todas as Coberturas');
                fillSelect('filtrofamilia', toOptions(familias), 'Todas as Famílias');
                fillSelect('filtrofornecedor', toOptions(fornecedores), 'Todos os Fornecedores');
            } catch (e) {
                console.error('Erro ao popular filtros:', e);
            }
        } else {
            console.warn('Resposta não tem o formato esperado:', response);
            AllItens = [];
        }
    }, "json").fail(function(xhr, status, error) {
        console.error('Erro ao carregar itens:', status, error);
        console.error('Resposta do servidor:', xhr.responseText);
        AllItens = [];
    });
    $.post('lb/calcSimples.php', { versao: cfg.versao, inicio: inicio, lista: list }, 
        function(response) {
            console.log('response');
           return response;
        },"json");

    // Limpar filtros
    const btnLimpa = document.getElementById('btnLimpaFiltro');
    if (btnLimpa) {
        btnLimpa.addEventListener('click', function() {
            const ids = ['filtroTipoItem','filtroLeadtime','filtrocobertura','filtrofamilia','filtrofornecedor'];
            ids.forEach(id => {
                const sel = document.getElementById(id);
                if (sel) {
                    sel.value = '';
                    sel.dispatchEvent(new Event('change'));
                }
            });
            updateFilterIconState();
        });
    }

    // Atualiza o fill do ícone conforme estado dos filtros
    function filtersAreActive() {
        const ids = ['filtroTipoItem','filtroLeadtime','filtrocobertura','filtrofamilia','filtrofornecedor'];
        for (const id of ids) {
            const sel = document.getElementById(id);
            if (sel && sel.value !== '') return true;
        }
        return false;
    }

    function updateFilterIconState() {
        const btn = document.getElementById('btnLimpaFiltro');
        if (!btn) return;
        const svg = btn.querySelector('svg');
        if (!svg) return;
        const active = filtersAreActive();
        // Quando filtros ativos: ícone preenchido, senão: contorno (fill none)
        svg.setAttribute('fill', active ? 'currentColor' : 'none');
        // Garante stroke visível quando fill none
        svg.setAttribute('stroke', 'currentColor');
    }

    // Observa mudanças nos selects para atualizar o ícone
    ['filtroTipoItem','filtroLeadtime','filtrocobertura','filtrofamilia','filtrofornecedor']
        .forEach(id => {
            const sel = document.getElementById(id);
            if (sel) sel.addEventListener('change', updateFilterIconState);
        });

    // Atualiza estado inicial
    updateFilterIconState();
});