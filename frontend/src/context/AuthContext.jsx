
import { createContext, useContext, useEffect, useState } from 'react';
import { apiRequest } from '../services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [usuario, setUsuario] = useState(null);
    const [carregando, setCarregando] = useState(true);

    useEffect(() => {
        async function verificarSessao() {
            const token = localStorage.getItem('jogadores_token');

            if (!token) {
                setCarregando(false);
                return;
            }

            try {
                const resposta = await apiRequest('/api/auth/me');

                if (!resposta || !resposta.usuario) {
                    throw new Error('Sessão inválida.');
                }

                setUsuario(resposta.usuario);
            } catch (erro) {
                localStorage.removeItem('jogadores_token');
                setUsuario(null);
            } finally {
                setCarregando(false);
            }
        }

        verificarSessao();
    }, []);

    async function registrar(dados) {
        return await apiRequest('/api/auth/registrar', {
            method: 'POST',
            body: JSON.stringify(dados)
        });
    }

    async function login(email, senha) {
        // Não permite reaproveitar uma sessão anterior durante uma nova tentativa.
        localStorage.removeItem('jogadores_token');
        setUsuario(null);

        const resposta = await apiRequest('/api/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, senha })
        });

        // Só autentica se a API devolver um token e um usuário válidos.
        if (
            !resposta ||
            !resposta.token ||
            !resposta.usuario
        ) {
            throw new Error('Resposta de autenticação inválida.');
        }

        localStorage.setItem('jogadores_token', resposta.token);
        setUsuario(resposta.usuario);

        return resposta;
    }

    async function logout() {
        try {
            await apiRequest('/api/auth/logout', {
                method: 'POST'
            });
        } finally {
            localStorage.removeItem('jogadores_token');
            setUsuario(null);
        }
    }

    return (
        <AuthContext.Provider
            value={{
                usuario,
                carregando,
                registrar,
                login,
                logout
            }}
        >
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    return useContext(AuthContext);
}
