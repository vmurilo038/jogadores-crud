const API_URL = import.meta.env.VITE_API_URL;

export async function apiRequest(endpoint, options = {}) {
    const token = localStorage.getItem('jogadores_token');

    const headers = {
        ...options.headers
    };

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    if (options.body && !(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }

    const resposta = await fetch(`${API_URL}${endpoint}`, {
        ...options,
        headers
    });

    const dados = await resposta.json();

    if (!resposta.ok) {
        throw new Error(dados.mensagem || 'Erro ao comunicar com o servidor.');
    }

    return dados;
}

export { API_URL };
