
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

    const dados = await resposta.json().catch(() => ({}));

    if (!resposta.ok) {
        const erro = new Error(
            dados.mensagem || 'Erro ao comunicar com o servidor.'
        );

        erro.status = resposta.status;

        erro.retryAfter = Number(
            dados.tente_novamente_em ||
            resposta.headers.get('Retry-After') ||
            0
        );

        throw erro;
    }

    return dados;
}

export { API_URL };
