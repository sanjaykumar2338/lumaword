import { StrictMode } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import '../css/app.css';
import { AuthProvider, useAuth } from './context/AuthContext';
import { DashboardPage } from './pages/DashboardPage';
import { BibleStudiesPage } from './pages/BibleStudiesPage';
import { BibleStudyDetailPage } from './pages/BibleStudyDetailPage';
import { DevotionalsPage } from './pages/DevotionalsPage';
import { ForgotPasswordPage } from './pages/ForgotPasswordPage';
import { LandingPage } from './pages/LandingPage';
import { LoginPage } from './pages/LoginPage';
import { NotFoundPage } from './pages/NotFoundPage';
import { RegisterPage } from './pages/RegisterPage';
import { ReadingPlansPage } from './pages/ReadingPlansPage';
import { ResetPasswordPage } from './pages/ResetPasswordPage';
import { SettingsPage } from './pages/SettingsPage';
import { NewSermonPage } from './pages/NewSermonPage';
import { SermonDetailPage } from './pages/SermonDetailPage';
import { SermonLibraryPage } from './pages/SermonLibraryPage';

declare global {
    interface Window {
        lumaWordRoot?: Root;
    }
}

function LoadingScreen() {
    return <div className="grid min-h-screen place-items-center bg-paper text-sm font-medium text-stone-500">Loading Luma…</div>;
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
            <Route path="/devotionals" element={<ProtectedRoute><DevotionalsPage /></ProtectedRoute>} />
            <Route path="/reading-plans" element={<ProtectedRoute><ReadingPlansPage /></ProtectedRoute>} />
            <Route path="/bible-studies/:studyId" element={<ProtectedRoute><BibleStudyDetailPage /></ProtectedRoute>} />
            <Route path="/bible-studies" element={<ProtectedRoute><BibleStudiesPage /></ProtectedRoute>} />
            <Route path="/settings" element={<ProtectedRoute><SettingsPage /></ProtectedRoute>} />
            <Route path="*" element={<NotFoundPage />} />
        </Routes>
    );
}

const root = window.lumaWordRoot ??= createRoot(document.getElementById('app')!);

root.render(
    <StrictMode>
        <BrowserRouter>
            <AuthProvider><App /></AuthProvider>
        </BrowserRouter>
    </StrictMode>,
);
