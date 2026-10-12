import { useState, type ReactNode } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { Brand } from './Brand';

const navItems = [
    { to: '/dashboard', label: 'Home', icon: '⌂' },
    { to: '/settings', label: 'Account', icon: '○' },
];

export function AppShell({ children }: { children: ReactNode }) {
    const [menuOpen, setMenuOpen] = useState(false);
    const { user, logout } = useAuth();
    const navigate = useNavigate();

    const handleLogout = async () => {
        await logout();
        navigate('/');
    };

    return (
        <div className="min-h-screen bg-[#efede4]">
            <aside className="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col bg-ink px-5 py-6 text-white lg:flex">
                <Brand inverse />
                <nav className="mt-14 grid gap-2" aria-label="Dashboard navigation">
                    {navItems.map((item) => (
                        <NavLink
                            key={item.to}
                            to={item.to}
                            className={({ isActive }) => `flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition ${isActive ? 'bg-acid text-ink' : 'text-stone-400 hover:bg-white/5 hover:text-white'}`}
                        >
                            <span aria-hidden="true">{item.icon}</span>{item.label}
                        </NavLink>
                    ))}
                </nav>
                <div className="mt-auto border-t border-white/10 pt-5">
                    <p className="truncate text-sm font-medium">{user?.name}</p>
                    <p className="truncate text-xs text-stone-500">{user?.email}</p>
                    <button onClick={handleLogout} className="mt-4 text-sm text-stone-400 transition hover:text-acid">Sign out</button>
                </div>
            </aside>

            <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-black/10 bg-paper/95 px-5 backdrop-blur lg:hidden">
                <Brand />
                <button
                    type="button"
                    onClick={() => setMenuOpen((open) => !open)}
                    aria-expanded={menuOpen}
                    aria-label="Toggle navigation"
                    className="grid size-10 place-items-center rounded-full border border-stone-300 text-xl"
                >
                    {menuOpen ? '×' : '≡'}
                </button>
            </header>

            {menuOpen && (
                <nav className="fixed inset-x-4 top-20 z-50 grid gap-2 rounded-2xl border border-black/10 bg-ink p-4 text-white shadow-2xl lg:hidden">
                    {navItems.map((item) => (
                        <NavLink key={item.to} to={item.to} onClick={() => setMenuOpen(false)} className="rounded-xl px-4 py-3 text-sm hover:bg-white/10">
                            {item.label}
                        </NavLink>
                    ))}
                    <button onClick={handleLogout} className="rounded-xl px-4 py-3 text-left text-sm text-stone-400 hover:bg-white/10">Sign out</button>
                </nav>
            )}

            <main className="min-h-screen lg:pl-64">
                <div className="mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-10 lg:px-10">{children}</div>
            </main>
        </div>
    );
}
