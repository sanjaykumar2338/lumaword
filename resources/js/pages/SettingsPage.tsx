import { useEffect, useState, type FormEvent } from 'react';
import { AppShell } from '../components/AppShell';
import { FormField } from '../components/FormField';
import { useAuth } from '../context/AuthContext';
import { ApiError, apiRequest } from '../lib/api';
import type { DevotionalReminderPreference, MessageResponse, ResourceResponse, User } from '../types';

const commonTimezones = ['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Africa/Johannesburg', 'Asia/Kolkata', 'Asia/Singapore', 'Asia/Tokyo', 'Australia/Sydney', 'Pacific/Auckland'];

export function SettingsPage() {
    const { user, setUser } = useAuth();
    const [profile, setProfile] = useState({ name: user?.name ?? '', email: user?.email ?? '' });
    const [passwords, setPasswords] = useState({ current_password: '', password: '', password_confirmation: '' });
    const [profileErrors, setProfileErrors] = useState<Record<string, string[]>>({});
    const [passwordErrors, setPasswordErrors] = useState<Record<string, string[]>>({});
    const [reminderErrors, setReminderErrors] = useState<Record<string, string[]>>({});
    const [notice, setNotice] = useState('');
    const [reminder, setReminder] = useState<DevotionalReminderPreference | null>(null);
    const [reminderLoading, setReminderLoading] = useState(true);
    const [reminderWorking, setReminderWorking] = useState(false);
    const browserPushAvailable = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    useEffect(() => {
        apiRequest<ResourceResponse<DevotionalReminderPreference>>('/api/v1/devotional-reminder-preferences')
            .then((response) => setReminder(response.data))
            .catch(() => setReminderErrors({ enabled: ['Reminder settings could not be loaded. Please refresh and try again.'] }))
            .finally(() => setReminderLoading(false));
    }, []);

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

    const saveReminder = async (event?: FormEvent, overrides: Partial<DevotionalReminderPreference> = {}) => {
        event?.preventDefault();
        if (!reminder) return;

        setReminderWorking(true); setReminderErrors({}); setNotice('');
        const values = { ...reminder, ...overrides };

        try {
            const response = await apiRequest<ResourceResponse<DevotionalReminderPreference>>('/api/v1/devotional-reminder-preferences', {
                method: 'PUT',
                body: JSON.stringify({ enabled: values.enabled, reminder_time: values.reminder_time, timezone: values.timezone }),
            });
            setReminder(response.data); setNotice(response.message ?? 'Reminder preferences updated.');
        } catch (error) {
            setReminderErrors(error instanceof ApiError ? error.errors : { enabled: ['Unable to update reminder preferences.'] });
        } finally {
            setReminderWorking(false);
        }
    };

    const enableBrowserPush = async () => {
        if (!reminder || !browserPushAvailable || !reminder.vapid_public_key) return;

        setReminderWorking(true); setReminderErrors({}); setNotice('');

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') throw new Error('Notification permission was not granted.');

            const registration = await navigator.serviceWorker.register('/service-worker.js');
            const existing = await registration.pushManager.getSubscription();
            const subscription = existing ?? await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: base64UrlToUint8Array(reminder.vapid_public_key) });
            const serialized = subscription.toJSON();

            await apiRequest('/api/v1/push-subscriptions', { method: 'POST', body: JSON.stringify({ endpoint: subscription.endpoint, keys: serialized.keys, content_encoding: 'aes128gcm' }) });
            await saveReminder(undefined, { enabled: true, subscription_count: 1 });
            setNotice('Browser notifications are enabled.');
        } catch (error) {
            setReminderErrors({ enabled: [error instanceof Error ? error.message : 'Browser notifications could not be enabled.'] });
            setReminderWorking(false);
        }
    };

    const disableBrowserPush = async () => {
        if (!reminder) return;

        setReminderWorking(true); setReminderErrors({}); setNotice('');

        try {
            const registration = browserPushAvailable ? await navigator.serviceWorker.getRegistration('/service-worker.js') : undefined;
            const subscription = await registration?.pushManager.getSubscription();

            if (subscription) {
                await apiRequest('/api/v1/push-subscriptions', { method: 'DELETE', body: JSON.stringify({ endpoint: subscription.endpoint }) });
                await subscription.unsubscribe();
            }

            await saveReminder(undefined, { enabled: false, subscription_count: 0 });
            setNotice('Devotional reminders and this browser’s notifications are disabled.');
        } catch {
            setReminderErrors({ enabled: ['Browser notifications could not be disabled. Please try again.'] });
            setReminderWorking(false);
        }
    };

    return (
        <AppShell>
            <header className="border-b border-black/10 pb-8"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Account</p><h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Settings</h1><p className="mt-3 text-sm text-stone-600">Manage your account and devotional rhythm.</p></header>
            {notice && <div role="status" className="mt-6 rounded-xl border border-lime-700/20 bg-lime-100 px-4 py-3 text-sm font-medium text-moss">{notice}</div>}
            <div className="mt-8 grid gap-6 xl:grid-cols-2">
                <section className="rounded-2xl border border-black/10 bg-paper p-6 sm:p-8"><h2 className="text-xl font-semibold">Profile</h2><p className="mt-2 text-sm text-stone-600">Your name and sign-in email.</p><form onSubmit={updateProfile} className="mt-7 grid gap-5"><FormField id="settings-name" label="Name" value={profile.name} onChange={(event) => setProfile((value) => ({ ...value, name: event.target.value }))} error={profileErrors.name?.[0]} required /><FormField id="settings-email" label="Email address" type="email" value={profile.email} onChange={(event) => setProfile((value) => ({ ...value, email: event.target.value }))} error={profileErrors.email?.[0]} required /><button className="h-11 justify-self-start rounded-xl bg-ink px-5 text-sm font-semibold text-white hover:bg-moss">Save profile</button></form></section>
                <section className="rounded-2xl border border-black/10 bg-paper p-6 sm:p-8"><h2 className="text-xl font-semibold">Password</h2><p className="mt-2 text-sm text-stone-600">Confirm your current password before choosing a new one.</p><form onSubmit={updatePassword} className="mt-7 grid gap-5"><FormField id="current-password" label="Current password" type="password" autoComplete="current-password" value={passwords.current_password} onChange={(event) => setPasswords((value) => ({ ...value, current_password: event.target.value }))} error={passwordErrors.current_password?.[0]} required /><FormField id="new-password" label="New password" type="password" autoComplete="new-password" value={passwords.password} onChange={(event) => setPasswords((value) => ({ ...value, password: event.target.value }))} error={passwordErrors.password?.[0]} required /><FormField id="confirm-password" label="Confirm new password" type="password" autoComplete="new-password" value={passwords.password_confirmation} onChange={(event) => setPasswords((value) => ({ ...value, password_confirmation: event.target.value }))} required /><button className="h-11 justify-self-start rounded-xl bg-ink px-5 text-sm font-semibold text-white hover:bg-moss">Update password</button></form></section>
            </div>

            <section className="mt-6 rounded-[1.75rem] border border-black/10 bg-paper p-6 sm:p-8" aria-labelledby="reminders-title">
                <p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Devotional rhythm</p><h2 id="reminders-title" className="mt-2 text-2xl font-semibold">Daily reminders</h2><p className="mt-2 max-w-2xl text-sm leading-6 text-stone-600">Receive one respectful reminder at your local time if you have not completed a devotional that day. Browser permission is requested only when you choose to enable it.</p>
                {reminderErrors.enabled?.[0] && <p role="alert" className="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{reminderErrors.enabled[0]}</p>}
                {reminderLoading ? <p className="mt-6 text-sm text-stone-500">Loading reminder settings…</p> : reminder && <form onSubmit={(event) => saveReminder(event)} className="mt-6 grid gap-5 sm:grid-cols-2">
                    <label className="grid gap-2 text-sm font-semibold">Reminder time<input type="time" required value={reminder.reminder_time} onChange={(event) => setReminder({ ...reminder, reminder_time: event.target.value })} className="h-11 rounded-xl border border-stone-300 bg-white px-3 font-normal outline-none focus:border-moss" />{reminderErrors.reminder_time?.[0] && <span className="text-xs text-red-700">{reminderErrors.reminder_time[0]}</span>}</label>
                    <label className="grid gap-2 text-sm font-semibold">Timezone<input list="luma-timezones" required value={reminder.timezone} onChange={(event) => setReminder({ ...reminder, timezone: event.target.value })} className="h-11 rounded-xl border border-stone-300 bg-white px-3 font-normal outline-none focus:border-moss" /><datalist id="luma-timezones">{commonTimezones.map((timezone) => <option key={timezone} value={timezone} />)}</datalist>{reminderErrors.timezone?.[0] && <span className="text-xs text-red-700">{reminderErrors.timezone[0]}</span>}</label>
                    <label className="flex items-center gap-3 text-sm font-semibold sm:col-span-2"><input type="checkbox" checked={reminder.enabled} onChange={(event) => setReminder({ ...reminder, enabled: event.target.checked })} className="size-5 accent-moss" />Send daily devotional reminders</label>
                    <div className="flex flex-wrap gap-3 sm:col-span-2"><button disabled={reminderWorking} className="h-11 rounded-xl bg-ink px-5 text-sm font-semibold text-white hover:bg-moss disabled:opacity-60">Save reminder settings</button>{reminder.subscription_count > 0 ? <button type="button" disabled={reminderWorking} onClick={disableBrowserPush} className="h-11 rounded-xl border border-red-300 px-5 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-60">Disable browser notifications</button> : <button type="button" disabled={reminderWorking || !browserPushAvailable || !reminder.push_supported} onClick={enableBrowserPush} className="h-11 rounded-xl bg-acid px-5 text-sm font-bold text-ink hover:bg-lime-300 disabled:cursor-not-allowed disabled:opacity-50">Enable browser notifications</button>}</div>
                    {!browserPushAvailable && <p className="text-xs text-stone-500 sm:col-span-2">This browser does not expose the Web Push APIs Luma needs.</p>}
                    {browserPushAvailable && !reminder.push_supported && <p className="text-xs text-amber-800 sm:col-span-2">Web Push keys must be configured on the server before notifications can be enabled.</p>}
                    <p className="text-xs leading-5 text-stone-500 sm:col-span-2">Web Push works in supported desktop and mobile browsers over HTTPS. This does not enable native iOS app notifications.</p>
                </form>}
            </section>
        </AppShell>
    );
}

function base64UrlToUint8Array(value: string): Uint8Array<ArrayBuffer> {
    const padding = '='.repeat((4 - value.length % 4) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const decoded = window.atob(base64);
    const bytes = new Uint8Array(new ArrayBuffer(decoded.length));

    for (let index = 0; index < decoded.length; index++) bytes[index] = decoded.charCodeAt(index);

    return bytes;
}
