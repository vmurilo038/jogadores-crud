
import { Navigate, Route, Routes } from 'react-router-dom';
import { useAuth } from './context/AuthContext';
import Login from './pages/Login';
import Jogadores from './pages/Jogadores';

function RotaProtegida({ children }) {
    const { usuario, carregando } = useAuth();

    if (carregando) {
        return <p>Carregando...</p>;
    }

    if (!usuario) {
        return <Navigate to="/login" replace />;
    }

    return children;
}

function App() {
    const { usuario, carregando } = useAuth();

    if (carregando) {
        return <p>Carregando...</p>;
    }

    return (
        <Routes>
            <Route
                path="/"
                element={
                    <Navigate to={usuario ? '/inicio' : '/login'} replace />
                }
            />

            <Route path="/login" element={<Login />} />

            <Route
                path="/inicio"
                element={
                    <RotaProtegida>
                        <Jogadores />
                    </RotaProtegida>
                }
            />

            <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
    );
}

export default App;
