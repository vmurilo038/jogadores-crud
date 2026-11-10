
import { useEffect, useState } from 'react';
import Header from '../components/Header';
import Menu from '../components/Menu';
import { apiRequest, API_URL } from '../services/api';
import './Jogadores.css';

function Jogadores() {
    const [jogadores, setJogadores] = useState([]);
    const [carregando, setCarregando] = useState(true);
    const [modalAberto, setModalAberto] = useState(false);
    const [jogadorEditando, setJogadorEditando] = useState(null);
    const [jogadorExcluindo, setJogadorExcluindo] = useState(null);
    const [salvando, setSalvando] = useState(false);
    const [mensagem, setMensagem] = useState('');
    const [erro, setErro] = useState('');

    const [imagemPreview, setImagemPreview] = useState('');
    const [zoomImagem, setZoomImagem] = useState(1);
    const [posicaoImagemX, setPosicaoImagemX] = useState(0);
    const [posicaoImagemY, setPosicaoImagemY] = useState(0);

    const [formulario, setFormulario] = useState({
        nome: '',
        idade: '',
        posicao: '',
        numero_camisa: '',
        imagem: null
    });

    async function carregarJogadores(mostrarCarregamento = true) {
        if (mostrarCarregamento) {
            setCarregando(true);
        }

        setErro('');

        try {
            const dados = await apiRequest('/api/jogadores');

            if (!Array.isArray(dados.jogadores)) {
                throw new Error('A API não retornou uma lista válida de jogadores.');
            }

            setJogadores(dados.jogadores);

        } catch (error) {
            console.error('ERRO AO CARREGAR JOGADORES:', error);
            setErro(error.message);
        } finally {
            if (mostrarCarregamento) {
                setCarregando(false);
            }
        }
    }

    useEffect(() => {
        carregarJogadores();
    }, []);

    function limparAjusteImagem() {
        if (imagemPreview) {
            URL.revokeObjectURL(imagemPreview);
        }

        setImagemPreview('');
        setZoomImagem(1);
        setPosicaoImagemX(0);
        setPosicaoImagemY(0);
    }

    function abrirCadastro() {
        limparAjusteImagem();
        setJogadorEditando(null);

        setFormulario({
            nome: '',
            idade: '',
            posicao: '',
            numero_camisa: '',
            imagem: null
        });

        setModalAberto(true);
    }

    function abrirEdicao(jogador) {
        limparAjusteImagem();
        setJogadorEditando(jogador);

        setFormulario({
            nome: jogador.nome || '',
            idade: jogador.idade || '',
            posicao: jogador.posicao || '',
            numero_camisa: jogador.numero_camisa || '',
            imagem: null
        });

        setModalAberto(true);
    }

    function fecharModal() {
        if (salvando) return;

        setModalAberto(false);
        setJogadorEditando(null);
        limparAjusteImagem();
    }

    function alterarCampo(event) {
        const { name, value, files } = event.target;

        if (name === 'imagem') {
            const arquivo = files && files[0];

            if (!arquivo) return;

            if (imagemPreview) {
                URL.revokeObjectURL(imagemPreview);
            }

            setImagemPreview(URL.createObjectURL(arquivo));
            setZoomImagem(1);
            setPosicaoImagemX(0);
            setPosicaoImagemY(0);

            setFormulario((anterior) => ({
                ...anterior,
                imagem: arquivo
            }));

            event.target.value = '';
            return;
        }

        setFormulario((anterior) => ({
            ...anterior,
            [name]: value
        }));
    }

    function gerarImagemRecortada(arquivo) {
        return new Promise((resolve, reject) => {
            const imagem = new Image();
            const url = URL.createObjectURL(arquivo);

            imagem.onload = () => {
                const larguraOriginal = imagem.naturalWidth;
                const alturaOriginal = imagem.naturalHeight;
                const proporcao = 16 / 9;

                let larguraCorte = Math.min(
                    larguraOriginal,
                    alturaOriginal * proporcao
                );

                let alturaCorte = larguraCorte / proporcao;

                larguraCorte /= zoomImagem;
                alturaCorte /= zoomImagem;

                const sobraX = larguraOriginal - larguraCorte;
                const sobraY = alturaOriginal - alturaCorte;

                const inicioX =
                    (sobraX / 2) +
                    (posicaoImagemX / 100) * (sobraX / 2);

                const inicioY =
                    (sobraY / 2) +
                    (posicaoImagemY / 100) * (sobraY / 2);

                const canvas = document.createElement('canvas');

                canvas.width = 1200;
                canvas.height = 675;

                const contexto = canvas.getContext('2d');

                contexto.drawImage(
                    imagem,
                    Math.max(0, inicioX),
                    Math.max(0, inicioY),
                    larguraCorte,
                    alturaCorte,
                    0,
                    0,
                    canvas.width,
                    canvas.height
                );

                canvas.toBlob((blob) => {
                    URL.revokeObjectURL(url);

                    if (!blob) {
                        reject(new Error('Não foi possível ajustar a imagem.'));
                        return;
                    }

                    const nome =
                        arquivo.name.replace(/\.[^.]+$/, '') || 'jogador';

                    const imagemFinal = new File(
                        [blob],
                        `${nome}.jpg`,
                        { type: 'image/jpeg' }
                    );

                    resolve(imagemFinal);
                }, 'image/jpeg', 0.92);
            };

            imagem.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('Não foi possível carregar a imagem selecionada.'));
            };

            imagem.src = url;
        });
    }

    async function salvarJogador(event) {
        event.preventDefault();

        setSalvando(true);
        setMensagem('');
        setErro('');

        const dados = new FormData();

        dados.append('nome', formulario.nome);
        dados.append('idade', formulario.idade);
        dados.append('posicao', formulario.posicao);
        dados.append('numero_camisa', formulario.numero_camisa);

        try {
            if (formulario.imagem) {
                const imagemFinal = await gerarImagemRecortada(
                    formulario.imagem
                );

                dados.append('imagem', imagemFinal);
            }

            if (jogadorEditando) {
                await apiRequest(
                    `/api/jogadores/atualizar/${jogadorEditando.id}`,
                    {
                        method: 'POST',
                        body: dados
                    }
                );

                setMensagem('Jogador atualizado com sucesso!');

            } else {
                await apiRequest('/api/jogadores/criar', {
                    method: 'POST',
                    body: dados
                });

                setMensagem('Jogador cadastrado com sucesso!');
            }

            await carregarJogadores(false);

            setModalAberto(false);
            setJogadorEditando(null);
            limparAjusteImagem();

            setFormulario({
                nome: '',
                idade: '',
                posicao: '',
                numero_camisa: '',
                imagem: null
            });

        } catch (error) {
            console.error('ERRO AO SALVAR JOGADOR:', error);
            setErro(error.message);
        } finally {
            setSalvando(false);
        }
    }

    async function excluirJogador() {
        if (!jogadorExcluindo) return;

        try {
            await apiRequest(`/api/jogadores/${jogadorExcluindo.id}`, {
                method: 'DELETE'
            });

            setJogadores((anteriores) =>
                anteriores.filter(
                    (jogador) =>
                        String(jogador.id) !== String(jogadorExcluindo.id)
                )
            );

            setMensagem('Jogador excluído com sucesso!');

        } catch (error) {
            setErro(error.message);
        } finally {
            setJogadorExcluindo(null);
        }
    }

    function enderecoImagem(caminho) {
        if (!caminho) return null;

        return `${API_URL}/${caminho}`;
    }

    return (
        <div className="app-layout">
            <Header />

            <div className="app-content">
                <Menu />

                <main className="main-content players-dashboard">
                    <div className="players-heading">
                        <div>
                            <h1>Jogadores</h1>
                            <p>
                                Gerencie os jogadores cadastrados no sistema.
                            </p>
                        </div>

                        <button
                            className="players-primary-button"
                            onClick={abrirCadastro}
                        >
                            + Novo jogador
                        </button>
                    </div>

                    {mensagem && (
                        <div className="players-notice success">
                            {mensagem}
                            <button onClick={() => setMensagem('')}>×</button>
                        </div>
                    )}

                    {erro && (
                        <div className="players-notice error">
                            {erro}
                            <button onClick={() => setErro('')}>×</button>
                        </div>
                    )}

                    <section className="players-panel">
                        <div className="players-panel-header">
                            <h2>Todos os jogadores</h2>
                            <span>{jogadores.length} jogadores</span>
                        </div>

                        {carregando ? (
                            <div className="players-empty">
                                <div className="players-loading"></div>
                                <p>Carregando jogadores...</p>
                            </div>
                        ) : jogadores.length === 0 ? (
                            <div className="players-empty">
                                <div className="players-empty-icon">⚽</div>
                                <h3>Nenhum jogador cadastrado</h3>
                                <p>
                                    Cadastre seu primeiro jogador para começar.
                                </p>
                            </div>
                        ) : (
                            <div className="players-grid">
                                {jogadores.map((jogador, index) => (
                                    <article
                                        className="player-card"
                                        key={jogador.id}
                                        style={{
                                            '--card-index': index
                                        }}
                                    >
                                        {jogador.imagem ? (
                                            <img
                                                className="player-photo"
                                                src={enderecoImagem(jogador.imagem)}
                                                alt={jogador.nome}
                                                onError={(event) => {
                                                    console.error(
                                                        'ERRO AO CARREGAR FOTO:',
                                                        event.target.src
                                                    );
                                                }}
                                            />
                                        ) : (
                                            <div className="player-photo-placeholder">
                                                {jogador.nome.charAt(0).toUpperCase()}
                                            </div>
                                        )}

                                        <div className="player-card-info">
                                            <h3>{jogador.nome}</h3>
                                            <p>{jogador.posicao}</p>

                                            <div className="player-details">
                                                <span>{jogador.idade} anos</span>
                                                <span>
                                                    Camisa {jogador.numero_camisa || '—'}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="player-card-actions">
                                            <button
                                                className="player-edit-button"
                                                onClick={() => abrirEdicao(jogador)}
                                            >
                                                Editar
                                            </button>

                                            <button
                                                className="player-delete-button"
                                                onClick={() => setJogadorExcluindo(jogador)}
                                            >
                                                Excluir
                                            </button>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                    </section>
                </main>
            </div>

            {modalAberto && (
                <div
                    className="players-modal-overlay"
                    onClick={fecharModal}
                >
                    <div
                        className="players-modal"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <div className="players-modal-header">
                            <div>
                                <h2>
                                    {jogadorEditando
                                        ? 'Editar jogador'
                                        : 'Novo jogador'}
                                </h2>

                                <p>
                                    Preencha as informações do jogador.
                                </p>
                            </div>

                            <button
                                className="players-modal-close"
                                onClick={fecharModal}
                                disabled={salvando}
                            >
                                ×
                            </button>
                        </div>

                        <form onSubmit={salvarJogador}>
                            <label>
                                Nome completo

                                <input
                                    type="text"
                                    name="nome"
                                    value={formulario.nome}
                                    onChange={alterarCampo}
                                    placeholder="Ex.: João Silva"
                                    required
                                />
                            </label>

                            <div className="players-form-row">
                                <label>
                                    Idade

                                    <input
                                        type="number"
                                        name="idade"
                                        min="1"
                                        max="100"
                                        value={formulario.idade}
                                        onChange={alterarCampo}
                                        placeholder="Ex.: 22"
                                        required
                                    />
                                </label>

                                <label>
                                    Número da camisa

                                    <input
                                        type="number"
                                        name="numero_camisa"
                                        min="1"
                                        max="99"
                                        value={formulario.numero_camisa}
                                        onChange={alterarCampo}
                                        placeholder="Ex.: 10"
                                    />
                                </label>
                            </div>

                            <label>
                                Posição

                                <select
                                    name="posicao"
                                    value={formulario.posicao}
                                    onChange={alterarCampo}
                                    required
                                >
                                    <option value="">Selecione uma posição</option>
                                    <option value="Goleiro">Goleiro</option>
                                    <option value="Zagueiro">Zagueiro</option>
                                    <option value="Lateral">Lateral</option>
                                    <option value="Volante">Volante</option>
                                    <option value="Meio-campista">Meio-campista</option>
                                    <option value="Ponta">Ponta</option>
                                    <option value="Atacante">Atacante</option>
                                </select>
                            </label>

                            <div className="players-upload-section">
                                <label className="players-upload-label">
                                    Foto do jogador
                                </label>

                                <label className="players-file-picker">
                                    <span className="players-file-picker-icon">
                                        ↑
                                    </span>

                                    <span className="players-file-picker-text">
                                        <strong>
                                            {formulario.imagem
                                                ? 'Trocar imagem'
                                                : 'Escolher imagem'}
                                        </strong>

                                        <small>JPG, PNG ou WEBP</small>
                                    </span>

                                    <input
                                        type="file"
                                        name="imagem"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={alterarCampo}
                                    />
                                </label>

                                {imagemPreview && (
                                    <div className="players-image-editor">
                                        <div className="players-image-editor-heading">
                                            <div>
                                                <strong>Ajuste sua foto</strong>
                                                <small>
                                                    Escolha o melhor enquadramento.
                                                </small>
                                            </div>

                                            <button
                                                type="button"
                                                className="players-image-reset"
                                                onClick={() => {
                                                    setZoomImagem(1);
                                                    setPosicaoImagemX(0);
                                                    setPosicaoImagemY(0);
                                                }}
                                            >
                                                Restaurar
                                            </button>
                                        </div>

                                        <div
                                            className="players-crop-preview"
                                            style={{
                                                '--crop-x': `${(posicaoImagemX + 100) / 2}%`,
                                                '--crop-y': `${(posicaoImagemY + 100) / 2}%`,
                                                '--crop-zoom': zoomImagem
                                            }}
                                        >
                                            <img
                                                src={imagemPreview}
                                                alt="Prévia do recorte"
                                            />

                                            <span className="players-crop-guide"></span>
                                        </div>

                                        <div className="players-crop-control">
                                            <div>
                                                <label htmlFor="zoom-imagem">
                                                    Zoom
                                                </label>
                                                <span>
                                                    {zoomImagem.toFixed(1)}x
                                                </span>
                                            </div>

                                            <input
                                                id="zoom-imagem"
                                                type="range"
                                                min="1"
                                                max="3"
                                                step="0.1"
                                                value={zoomImagem}
                                                onChange={(event) =>
                                                    setZoomImagem(Number(event.target.value))
                                                }
                                            />
                                        </div>

                                        <div className="players-crop-control">
                                            <div>
                                                <label htmlFor="posicao-imagem-x">
                                                    Mover para os lados
                                                </label>
                                            </div>

                                            <input
                                                id="posicao-imagem-x"
                                                type="range"
                                                min="-100"
                                                max="100"
                                                value={posicaoImagemX}
                                                onChange={(event) =>
                                                    setPosicaoImagemX(Number(event.target.value))
                                                }
                                            />
                                        </div>

                                        <div className="players-crop-control">
                                            <div>
                                                <label htmlFor="posicao-imagem-y">
                                                    Mover para cima/baixo
                                                </label>
                                            </div>

                                            <input
                                                id="posicao-imagem-y"
                                                type="range"
                                                min="-100"
                                                max="100"
                                                value={posicaoImagemY}
                                                onChange={(event) =>
                                                    setPosicaoImagemY(Number(event.target.value))
                                                }
                                            />
                                        </div>

                                        <small className="players-crop-hint">
                                            A prévia mostra como a foto ficará no cartão.
                                        </small>
                                    </div>
                                )}

                                {!imagemPreview && (
                                    <small className="players-upload-hint">
                                        Escolha uma foto para ajustar o enquadramento.
                                    </small>
                                )}
                            </div>

                            <div className="players-modal-actions">
                                <button
                                    type="button"
                                    className="players-cancel-button"
                                    onClick={fecharModal}
                                    disabled={salvando}
                                >
                                    Cancelar
                                </button>

                                <button
                                    type="submit"
                                    className="players-primary-button"
                                    disabled={salvando}
                                >
                                    {salvando
                                        ? 'Salvando...'
                                        : jogadorEditando
                                            ? 'Salvar alterações'
                                            : 'Cadastrar jogador'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {jogadorExcluindo && (
                <div
                    className="players-modal-overlay"
                    onClick={() => setJogadorExcluindo(null)}
                >
                    <div
                        className="players-confirm-modal"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <h2>Excluir jogador?</h2>

                        <p>
                            Tem certeza de que deseja excluir{' '}
                            <strong>{jogadorExcluindo.nome}</strong>?
                            Essa ação não poderá ser desfeita.
                        </p>

                        <div className="players-modal-actions">
                            <button
                                className="players-cancel-button"
                                onClick={() => setJogadorExcluindo(null)}
                            >
                                Cancelar
                            </button>

                            <button
                                className="players-delete-button"
                                onClick={excluirJogador}
                            >
                                Sim, excluir
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

export default Jogadores;
