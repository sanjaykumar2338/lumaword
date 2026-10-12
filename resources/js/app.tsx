import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import { DashboardPage } from './pages/DashboardPage';
import { ForgotPasswordPage } from './pages/ForgotPasswordPage';
import { LandingPage } from './pages/LandingPage';
import { LoginPage } from './pages/LoginPage';
import { NotFoundPage } from './pages/NotFoundPage';
import { RegisterPage } from './pages/RegisterPage';
import { ResetPasswordPage } from './pages/ResetPasswordPage';
import { SettingsPage } from './pages/SettingsPage';
import { NewSermonPage } from './pages/NewSermonPage';
import { SermonDetailPage } from './pages/SermonDetailPage';
import { SermonLibraryPage } from './pages/SermonLibraryPage';

function LoadingScreen() {
    return <div className="grid min-h-screen place-items-center bg-paper text-sm font-medium text-stone-500">Loading LumaWord…</div>;
}

function ProtectedRoute({ children }: { children: React.ReactNode }) {
    const { user, isLoading } = useAuth();

    if (isLoading) return <LoadingScreen />;

    return user ? children : <Navigate to="/login" replace />;
}

function GuestRoute({ children }: { children: React.ReactNode }) {
    const { user, isLoading } = useAuth();

    if (isLoading) return <LoadingScreen />;

    return user ? <Navigate to="/dashboard" replace /> : children;
}

function App() {
    return (
        <Routes>
            <Route path="/" element={<LandingPage />} />
            <Route path="/login" element={<GuestRoute><LoginPage /></GuestRoute>} />
            <Route path="/register" element={<GuestRoute><RegisterPage /></GuestRoute>} />
            <Route path="/forgot-password" element={<GuestRoute><ForgotPasswordPage /></GuestRoute>} />
            <Route path="/reset-password/:token" element={<GuestRoute><ResetPasswordPage /></GuestRoute>} />
            <Route path="/dashboard" element={<ProtectedRoute><DashboardPage /></ProtectedRoute>} />
            <Route path="/sermons/new" element={<ProtectedRoute><NewSermonPage /></ProtectedRoute>} />
            <Route path="/sermons/:sermonId" element={<ProtectedRoute><SermonDetailPage /></ProtectedRoute>} />
            <Route path="/sermons" element={<ProtectedRoute><SermonLibraryPage /></ProtectedRoute>} />
            <Route path="/settings" element={<ProtectedRoute><SettingsPage /></ProtectedRoute>} />
            <Route path="*" element={<NotFoundPage />} />
        </Routes>
    );
}

createRoot(document.getElementById('app')!).render(
    <StrictMode>
        <BrowserRouter>
            <AuthProvider><App /></AuthProvider>
        </BrowserRouter>
    </StrictMode>,
);
