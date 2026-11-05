import { useAuth } from '../context/AuthContext';
import './Header.css';

function Header() {
    const { usuario, logout } = useAuth();

    return (
        <header className="main-header">
            <div className="header-brand">
                <div className="header-logo">J</div>
                <span>JOGADORES</span>
            </div>

            <div className="header-user">
                {usuario?.imagem ? (
                    <img
                        src={usuario.imagem}
                        alt="Foto do usuário"
                        className="user-avatar"
                    />
                ) : (
                    <div className="user-avatar-placeholder">
                        {usuario?.nome?.charAt(0)?.toUpperCase() || 'U'}
                    </div>
                )}

                <div className="user-info">
                    <strong>{usuario?.nome}</strong>
                    <span>Administrador</span>
                </div>

                <button
                    className="logout-button"
                    onClick={logout}
                    title="Sair da conta"
                >
                    <span>Sair</span>
                    <span className="logout-icon">↗</span>
                </button>
            </div>
        </header>
    );
}

export default Header;