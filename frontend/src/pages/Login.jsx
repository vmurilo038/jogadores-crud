
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import './Login.css';

function Login() {
    const { login, registrar } = useAuth();
    const navigate = useNavigate();

    const [modo, setModo] = useState('login');
    const [nome, setNome] = useState('');
    const [email, setEmail] = useState('');
    const [senha, setSenha] = useState('');
    const [mostrarSenha, setMostrarSenha] = useState(false);
    const [mensagem, setMensagem] = useState('');
    const [erro, setErro] = useState('');
    const [carregando, setCarregando] = useState(false);
    const [bloqueioSegundos, setBloqueioSegundos] = useState(0);

    useEffect(() => {
        if (bloqueioSegundos <= 0) {
            return;
        }

        const intervalo = setInterval(() => {
            setBloqueioSegundos((tempoAtual) =>
                Math.max(tempoAtual - 1, 0)
            );
        }, 1000);

        return () => clearInterval(intervalo);
    }, [bloqueioSegundos]);

    function formatarTempo(segundos) {
        const minutos = Math.floor(segundos / 60);
        const segundosRestantes = segundos % 60;

        return `${String(minutos).padStart(2, '0')}:${String(segundosRestantes).padStart(2, '0')}`;
    }

    async function handleSubmit(event) {
        event.preventDefault();

        if (modo === 'login' && bloqueioSegundos > 0) {
            return;
        }

        setErro('');
        setMensagem('');
        setCarregando(true);

        try {
            if (modo === 'cadastro') {
                await registrar({ nome, email, senha });

                setMensagem('Cadastro realizado! Agora entre com sua conta.');
                setModo('login');
                setNome('');
                setSenha('');
            } else {
                await login(email, senha);
                navigate('/inicio');
            }
        } catch (error) {
            if (error.status === 429) {
                setErro('');

                const tempo = Math.max(
                    1,
                    Number(error.retryAfter) || 60
                );

                setBloqueioSegundos(tempo);
            } else {
                setErro(
                    error.message || 'Não foi possível realizar a operação.'
                );
            }
        } finally {
            setCarregando(false);
        }
    }

    function trocarModo(novoModo) {
        setModo(novoModo);
        setErro('');
        setMensagem('');
    }

    return (
        <main className="login-page">
            <div className="login-background-glow"></div>

            <section className="login-card">
                <div className="login-logo">
                    <div className="logo-icon">J</div>
                    <span>JOGADORES</span>
                </div>

                <div className="login-heading">
                    <p className="login-tag">SISTEMA DE GERENCIAMENTO</p>

                    <h1>
                        {modo === 'login'
                            ? 'Bem-vindo de volta.'
                            : 'Crie sua conta.'}
                    </h1>

                    <p>
                        {modo === 'login'
                            ? 'Entre para acessar o gerenciamento de jogadores.'
                            : 'Preencha seus dados para começar.'}
                    </p>
                </div>

                <div className="login-tabs">
                    <button
                        type="button"
                        className={modo === 'login' ? 'active' : ''}
                        onClick={() => trocarModo('login')}
                    >
                        Entrar
                    </button>

                    <button
                        type="button"
                        className={modo === 'cadastro' ? 'active' : ''}
                        onClick={() => trocarModo('cadastro')}
                    >
                        Criar conta
                    </button>
                </div>

                {mensagem && (
                    <div className="login-message success">
                        {mensagem}
                    </div>
                )}

                {erro && (
                    <div className="login-message error">
                        {erro}
                    </div>
                )}

                {modo === 'login' && bloqueioSegundos > 0 && (
                    <div className="login-message error login-lock-message">
                        <strong>Acesso temporariamente bloqueado</strong>

                        <p>
                            Muitas tentativas de login. Aguarde para tentar novamente.
                        </p>

                        <div className="login-countdown">
                            {formatarTempo(bloqueioSegundos)}
                        </div>

                        <small>
                            Você poderá tentar novamente quando o contador chegar a zero.
                        </small>
                    </div>
                )}

                <form onSubmit={handleSubmit}>
                    {modo === 'cadastro' && (
                        <div className="login-field login-field-animation">
                            <label>Nome completo</label>

                            <input
                                type="text"
                                placeholder="Digite seu nome"
                                value={nome}
                                onChange={(e) => setNome(e.target.value)}
                                required
                            />
                        </div>
                    )}

                    <div className="login-field">
                        <label>E-mail</label>

                        <input
                            type="email"
                            placeholder="seuemail@exemplo.com"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            required
                        />
                    </div>

                    <div className="login-field">
                        <label>Senha</label>

                        <div className="password-wrapper">
                            <input
                                type={mostrarSenha ? 'text' : 'password'}
                                placeholder="Digite sua senha"
                                value={senha}
                                onChange={(e) => setSenha(e.target.value)}
                                minLength={6}
                                required
                            />

                            <button
                                type="button"
                                onClick={() => setMostrarSenha(!mostrarSenha)}
                            >
                                {mostrarSenha ? 'Ocultar' : 'Mostrar'}
                            </button>
                        </div>
                    </div>

                    <button
                        className="login-submit"
                        type="submit"
                        disabled={
                            carregando ||
                            (modo === 'login' && bloqueioSegundos > 0)
                        }
                    >
                        {carregando
                            ? 'Aguarde...'
                            : modo === 'login' && bloqueioSegundos > 0
                                ? `Aguarde ${formatarTempo(bloqueioSegundos)}`
                                : modo === 'login'
                                    ? 'Entrar na plataforma'
                                    : 'Criar minha conta'}
                    </button>
                </form>

                <p className="login-footer">
                    Jogadores · Gerenciamento de atletas
                </p>
            </section>
        </main>
    );
}

export default Login;
