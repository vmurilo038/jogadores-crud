import { NavLink } from 'react-router-dom';

function Menu() {
    return (
        <aside className="sidebar">
            <span className="menu-label">MENU PRINCIPAL</span>

            <NavLink to="/inicio" className="menu-link">
                <span>⚽</span>
                Jogadores
            </NavLink>

            <NavLink to="/logs" className="menu-link">
                <span>📋</span>
                Logs
            </NavLink>
        </aside>
    );
}

export default Menu;
