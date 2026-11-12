
import { useCallback, useEffect, useState } from 'react';
import Header from '../components/Header';
import Menu from '../components/Menu';
import { apiRequest } from '../services/api';
import './Logs.css';

function Logs() {
    const [logs, setLogs] = useState([]);
    const [carregando, setCarregando] = useState(true);
    const [erro, setErro] = useState('');
    const [pagina, setPagina] = useState(1);
    const [paginacao, setPaginacao] = useState({
        pagina_atual: 1,
        total_paginas: 1,
        total_registros: 0
    });

    const [filtros, setFiltros] = useState({
        nivel: 'todos',
        pesquisa: '',
        data_inicio: '',
        data_fim: ''
    });

    const [filtrosAplicados, setFiltrosAplicados] = useState(filtros);
    const [logSelecionado, setLogSelecionado] = useState(null);

    const carregarLogs = useCallback(async () => {
        setCarregando(true);
        setErro('');

        try {
            const parametros = new URLSearchParams({
                pagina: String(pagina),
                nivel: filtrosAplicados.nivel,
                pesquisa: filtrosAplicados.pesquisa,
                data_inicio: filtrosAplicados.data_inicio,
                data_fim: filtrosAplicados.data_fim
            });

            const resposta = await apiRequest(`/api/logs?${parametros}`);

            setLogs(resposta.logs || []);
            setPaginacao(resposta.paginacao || {
                pagina_atual: 1,
                total_paginas: 1,
                total_registros: 0
            });
        } catch (erro) {
            setErro(erro.message || 'Não foi possível carregar os logs.');
        } finally {
            setCarregando(false);
        }
    }, [pagina, filtrosAplicados]);

    useEffect(() => {
        carregarLogs();
    }, [carregarLogs]);

    function alterarFiltro(evento) {
        const { name, value } = evento.target;

        setFiltros((anterior) => ({
            ...anterior,
            [name]: value
        }));
    }

    function aplicarFiltros(evento) {
        evento.preventDefault();
        setPagina(1);
        setFiltrosAplicados({ ...filtros });
    }

    function limparFiltros() {
        const filtrosLimpos = {
            nivel: 'todos',
            pesquisa: '',
            data_inicio: '',
            data_fim: ''
        };

        setFiltros(filtrosLimpos);
        setFiltrosAplicados(filtrosLimpos);
        setPagina(1);
    }

    function formatarData(data) {
        if (!data) return '-';

        // Considera que o backend salvou a data em UTC.
        const dataUTC = new Date(data.replace(' ', 'T') + 'Z');

        if (Number.isNaN(dataUTC.getTime())) {
            return data;
        }

        // Converte UTC para o horário de Fortaleza.
        return dataUTC.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hourCycle: 'h23',
            timeZone: 'America/Fortaleza'
        });
    }

    function formatarContexto(contexto) {
        if (!contexto) return {};

        if (typeof contexto === 'object') return contexto;

        try {
            return JSON.parse(contexto);
        } catch {
            return { informacao: contexto };
        }
    }

    function classeNivel(nivel) {
        return `log-level log-level-${String(nivel || 'info').toLowerCase()}`;
    }

    return (
        <div className="app-layout">
            <Header />

            <div className="app-content">
                <Menu />

                <main className="main-content logs-dashboard">
                    <div className="logs-heading">
                        <div>
                            <span className="logs-overline">MONITORAMENTO DO SISTEMA</span>
                            <h1>Logs do sistema</h1>
                            <p>Acompanhe as atividades e os acontecimentos do Jogadores.</p>
                        </div>

                        <button
                            className="logs-refresh-button"
                            onClick={carregarLogs}
                            disabled={carregando}
                        >
                            ↻ Atualizar
                        </button>
                    </div>

                    <section className="logs-summary">
                        <div className="logs-summary-card">
                            <span>Total de registros</span>
                            <strong>{paginacao.total_registros || 0}</strong>
                        </div>

                        <div className="logs-summary-card">
                            <span>Página atual</span>
                            <strong>{paginacao.pagina_atual || 1}</strong>
                        </div>

                        <div className="logs-summary-card">
                            <span>Total de páginas</span>
                            <strong>{paginacao.total_paginas || 1}</strong>
                        </div>
                    </section>

                    <section className="logs-panel">
                        <div className="logs-panel-heading">
                            <div>
                                <h2>Registros</h2>
                                <p>Pesquise e filtre os acontecimentos do sistema.</p>
                            </div>
                        </div>

                        <form className="logs-filters" onSubmit={aplicarFiltros}>
                            <div className="logs-filter-field logs-search-field">
                                <label htmlFor="pesquisa">Pesquisar</label>
                                <input
                                    id="pesquisa"
                                    type="text"
                                    name="pesquisa"
                                    value={filtros.pesquisa}
                                    onChange={alterarFiltro}
                                    placeholder="Ação, usuário ou ID..."
                                />
                            </div>

                            <div className="logs-filter-field">
                                <label htmlFor="nivel">Nível</label>
                                <select
                                    id="nivel"
                                    name="nivel"
                                    value={filtros.nivel}
                                    onChange={alterarFiltro}
                                >
                                    <option value="todos">Todos os níveis</option>
                                    <option value="info">Info</option>
                                    <option value="notice">Notice</option>
                                    <option value="warning">Warning</option>
                                    <option value="error">Error</option>
                                    <option value="critical">Critical</option>
                                    <option value="alert">Alert</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="debug">Debug</option>
                                </select>
                            </div>

                            <div className="logs-filter-field">
                                <label htmlFor="data_inicio">Data inicial</label>
                                <input
                                    id="data_inicio"
                                    type="date"
                                    name="data_inicio"
                                    value={filtros.data_inicio}
                                    onChange={alterarFiltro}
                                />
                            </div>

                            <div className="logs-filter-field">
                                <label htmlFor="data_fim">Data final</label>
                                <input
                                    id="data_fim"
                                    type="date"
                                    name="data_fim"
                                    value={filtros.data_fim}
                                    onChange={alterarFiltro}
                                />
                            </div>

                            <div className="logs-filter-actions">
                                <button type="submit" className="logs-apply-button">
                                    Filtrar
                                </button>

                                <button
                                    type="button"
                                    className="logs-clear-button"
                                    onClick={limparFiltros}
                                >
                                    Limpar
                                </button>
                            </div>
                        </form>

                        {erro && (
                            <div className="logs-error">
                                <span>!</span>
                                {erro}
                            </div>
                        )}

                        <div className="logs-table-wrapper">
                            <table className="logs-table">
                                <thead>
                                    <tr>
                                        <th>Nível</th>
                                        <th>Ação</th>
                                        <th>Usuário</th>
                                        <th>Método</th>
                                        <th>Data</th>
                                        <th>Detalhes</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {carregando ? (
                                        <tr>
                                            <td colSpan="6" className="logs-empty">
                                                Carregando registros...
                                            </td>
                                        </tr>
                                    ) : logs.length === 0 ? (
                                        <tr>
                                            <td colSpan="6" className="logs-empty">
                                                Nenhum registro encontrado.
                                            </td>
                                        </tr>
                                    ) : (
                                        logs.map((log) => (
                                            <tr key={log.id}>
                                                <td>
                                                    <span className={classeNivel(log.nivel)}>
                                                        {log.nivel}
                                                    </span>
                                                </td>

                                                <td className="logs-action-cell">
                                                    {log.acao}
                                                </td>

                                                <td>
                                                    {log.usuario_nome || (
                                                        log.usuario_id
                                                            ? `Usuário #${log.usuario_id}`
                                                            : 'Sistema'
                                                    )}
                                                </td>

                                                <td>
                                                    <span className="logs-method">
                                                        {log.metodo || '-'}
                                                    </span>
                                                </td>

                                                <td>{formatarData(log.criado_em)}</td>

                                                <td>
                                                    <button
                                                        className="logs-details-button"
                                                        onClick={() => setLogSelecionado(log)}
                                                    >
                                                        Ver detalhes
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="logs-pagination">
                            <span>
                                {paginacao.total_registros || 0} registro(s) encontrado(s)
                            </span>

                            <div>
                                <button
                                    onClick={() => setPagina((atual) => Math.max(1, atual - 1))}
                                    disabled={pagina <= 1 || carregando}
                                >
                                    Anterior
                                </button>

                                <strong>
                                    {paginacao.pagina_atual || 1} / {paginacao.total_paginas || 1}
                                </strong>

                                <button
                                    onClick={() => setPagina((atual) =>
                                        Math.min(paginacao.total_paginas || 1, atual + 1)
                                    )}
                                    disabled={
                                        pagina >= (paginacao.total_paginas || 1) || carregando
                                    }
                                >
                                    Próxima
                                </button>
                            </div>
                        </div>
                    </section>
                </main>
            </div>

            {logSelecionado && (
                <div
                    className="logs-modal-overlay"
                    onClick={() => setLogSelecionado(null)}
                >
                    <div
                        className="logs-modal"
                        onClick={(evento) => evento.stopPropagation()}
                    >
                        <div className="logs-modal-heading">
                            <div>
                                <span className="logs-overline">REGISTRO #{logSelecionado.id}</span>
                                <h2>Detalhes do log</h2>
                            </div>

                            <button
                                onClick={() => setLogSelecionado(null)}
                                className="logs-modal-close"
                            >
                                ×
                            </button>
                        </div>

                        <div className="logs-modal-content">
                            <div className="logs-detail-item">
                                <span>Nível</span>
                                <strong className={classeNivel(logSelecionado.nivel)}>
                                    {logSelecionado.nivel}
                                </strong>
                            </div>

                            <div className="logs-detail-item">
                                <span>Ação</span>
                                <strong>{logSelecionado.acao}</strong>
                            </div>

                            <div className="logs-detail-item">
                                <span>Usuário</span>
                                <strong>
                                    {logSelecionado.usuario_nome ||
                                        (logSelecionado.usuario_id
                                            ? `Usuário #${logSelecionado.usuario_id}`
                                            : 'Sistema')}
                                </strong>
                            </div>

                            <div className="logs-detail-item">
                                <span>Data</span>
                                <strong>{formatarData(logSelecionado.criado_em)}</strong>
                            </div>

                            <div className="logs-detail-item">
                                <span>Endereço IP</span>
                                <strong>{logSelecionado.ip || '-'}</strong>
                            </div>

                            <div className="logs-detail-item">
                                <span>Método</span>
                                <strong>{logSelecionado.metodo || '-'}</strong>
                            </div>

                            <div className="logs-detail-item">
                                <span>Rota</span>
                                <strong>{logSelecionado.rota || '-'}</strong>
                            </div>

                            <div className="logs-detail-item">
                                <span>Navegador</span>
                                <strong>{logSelecionado.user_agent || '-'}</strong>
                            </div>

                            <div className="logs-context">
                                <span>Contexto</span>
                                <pre>
                                    {JSON.stringify(
                                        formatarContexto(logSelecionado.contexto),
                                        null,
                                        2
                                    )}
                                </pre>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

export default Logs;
