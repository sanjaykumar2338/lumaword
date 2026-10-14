import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { apiRequest } from '../lib/api';
import type { ResourceResponse, User } from '../types';

type Credentials = { email: string; password: string };
type Registration = Credentials & { name: string; password_confirmation: string };

type AuthContextValue = {
    user: User | null;
    isLoading: boolean;
    login: (credentials: Credentials) => Promise<void>;
    register: (registration: Registration) => Promise<void>;
    logout: () => Promise<void>;
    setUser: (user: User) => void;
};

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<User | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        apiRequest<ResourceResponse<User | null>>('/api/v1/auth/status')
            .then((response) => setUser(response.data))
            .catch(() => setUser(null))
            .finally(() => setIsLoading(false));
    }, []);

    const value = useMemo<AuthContextValue>(() => ({
        user,
        isLoading,
        login: async (credentials) => {
            const response = await apiRequest<ResourceResponse<User>>('/api/v1/auth/login', {
                method: 'POST',
                body: JSON.stringify(credentials),
            });
            setUser(response.data);
        },
        register: async (registration) => {
            const response = await apiRequest<ResourceResponse<User>>('/api/v1/auth/register', {
                method: 'POST',
                body: JSON.stringify(registration),
            });
            setUser(response.data);
        },
        logout: async () => {
            await apiRequest<void>('/api/v1/auth/logout', { method: 'POST' });
            setUser(null);
        },
        setUser,
    }), [isLoading, user]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth must be used within AuthProvider.');
    }

    return context;
}
