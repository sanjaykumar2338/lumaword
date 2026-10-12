import { useState, type FormEvent } from 'react';
import { AppShell } from '../components/AppShell';
import { FormField } from '../components/FormField';
import { useAuth } from '../context/AuthContext';
import { ApiError, apiRequest } from '../lib/api';
import type { MessageResponse, ResourceResponse, User } from '../types';

export function SettingsPage() {
    const { user, setUser } = useAuth();
    const [profile, setProfile] = useState({ name: user?.name ?? '', email: user?.email ?? '' });
    const [passwords, setPasswords] = useState({ current_password: '', password: '', password_confirmation: '' });
    const [profileErrors, setProfileErrors] = useState<Record<string, string[]>>({});
    const [passwordErrors, setPasswordErrors] = useState<Record<string, string[]>>({});
    const [notice, setNotice] = useState('');

    const updateProfile = async (event: FormEvent) => {
        event.preventDefault(); setProfileErrors({}); setNotice('');
        try {
            const response = await apiRequest<ResourceResponse<User>>('/api/v1/user/profile', { method: 'PATCH', body: JSON.stringify(profile) });
            setUser(response.data); setNotice(response.message ?? 'Profile updated.');
        } catch (error) {
            setProfileErrors(error instanceof ApiError ? error.errors : { email: ['Unable to update your profile.'] });
        }
    };

    const updatePassword = async (event: FormEvent) => {
        event.preventDefault(); setPasswordErrors({}); setNotice('');
        try {
            const response = await apiRequest<MessageResponse>('/api/v1/user/password', { method: 'PUT', body: JSON.stringify(passwords) });
            setPasswords({ current_password: '', password: '', password_confirmation: '' }); setNotice(response.message);
        } catch (error) {
            setPasswordErrors(error instanceof ApiError ? error.errors : { current_password: ['Unable to update your password.'] });
        }
    };

    return (
        <AppShell>
            <header className="border-b border-black/10 pb-8"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Account</p><h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Settings</h1><p className="mt-3 text-sm text-stone-600">Manage the details used to secure and identify your account.</p></header>
            {notice && <div role="status" className="mt-6 rounded-xl border border-lime-700/20 bg-lime-100 px-4 py-3 text-sm font-medium text-moss">{notice}</div>}
            <div className="mt-8 grid gap-6 xl:grid-cols-2">
                <section className="rounded-2xl border border-black/10 bg-paper p-6 sm:p-8"><h2 className="text-xl font-semibold">Profile</h2><p className="mt-2 text-sm text-stone-600">Your name and sign-in email.</p><form onSubmit={updateProfile} className="mt-7 grid gap-5"><FormField id="settings-name" label="Name" value={profile.name} onChange={(event) => setProfile((value) => ({ ...value, name: event.target.value }))} error={profileErrors.name?.[0]} required /><FormField id="settings-email" label="Email address" type="email" value={profile.email} onChange={(event) => setProfile((value) => ({ ...value, email: event.target.value }))} error={profileErrors.email?.[0]} required /><button className="h-11 justify-self-start rounded-xl bg-ink px-5 text-sm font-semibold text-white hover:bg-moss">Save profile</button></form></section>
                <section className="rounded-2xl border border-black/10 bg-paper p-6 sm:p-8"><h2 className="text-xl font-semibold">Password</h2><p className="mt-2 text-sm text-stone-600">Confirm your current password before choosing a new one.</p><form onSubmit={updatePassword} className="mt-7 grid gap-5"><FormField id="current-password" label="Current password" type="password" autoComplete="current-password" value={passwords.current_password} onChange={(event) => setPasswords((value) => ({ ...value, current_password: event.target.value }))} error={passwordErrors.current_password?.[0]} required /><FormField id="new-password" label="New password" type="password" autoComplete="new-password" value={passwords.password} onChange={(event) => setPasswords((value) => ({ ...value, password: event.target.value }))} error={passwordErrors.password?.[0]} required /><FormField id="confirm-password" label="Confirm new password" type="password" autoComplete="new-password" value={passwords.password_confirmation} onChange={(event) => setPasswords((value) => ({ ...value, password_confirmation: event.target.value }))} required /><button className="h-11 justify-self-start rounded-xl bg-ink px-5 text-sm font-semibold text-white hover:bg-moss">Update password</button></form></section>
            </div>
        </AppShell>
    );
}
