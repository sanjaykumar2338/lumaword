import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { AppShell } from '../components/AppShell';
import { BannerManager } from '../components/BannerManager';
import { apiRequest } from '../lib/api';
import { chartPercent, formatAdminFeature, formatMicrosAsUsd } from '../lib/admin';
import type { AdminDashboard, AdminSeriesPoint, AdminUser, AdminUserDetail, PaginatedResponse, ResourceResponse } from '../types';

const metricLabels: Record<string, string> = {
    total_users: 'Registered users',
    registrations_today: 'Registered today',
    registrations_7_days: 'Registered in 7 days',
    registrations_30_days: 'Registered in 30 days',
    free_users: 'Free users',
    premium_users: 'Premium users',
    active_subscriptions: 'Active subscriptions',
    total_sermons: 'Sermons submitted',
    total_bible_studies: 'Bible studies',
    total_devotionals: 'Devotionals',
    total_ai_requests: 'AI requests',
    estimated_ai_cost_micros: 'Estimated AI cost',
};

export function AdminDashboardPage() {
    const [dashboard, setDashboard] = useState<AdminDashboard | null>(null);
    const [users, setUsers] = useState<PaginatedResponse<AdminUser> | null>(null);
    const [selectedUser, setSelectedUser] = useState<AdminUserDetail | null>(null);
    const [search, setSearch] = useState('');
    const [appliedSearch, setAppliedSearch] = useState('');
    const [page, setPage] = useState(1);
    const [range, setRange] = useState<7 | 30>(30);
    const [dashboardError, setDashboardError] = useState('');
    const [usersError, setUsersError] = useState('');
    const [usersLoading, setUsersLoading] = useState(true);

    useEffect(() => {
        apiRequest<ResourceResponse<AdminDashboard>>('/api/v1/admin/dashboard')
            .then((response) => setDashboard(response.data))
            .catch(() => setDashboardError('Admin analytics could not be loaded. Refresh the page to try again.'));
    }, []);

    const loadUsers = useCallback(() => {
        setUsersLoading(true);
        setUsersError('');
        const query = new URLSearchParams({ page: String(page), per_page: '20' });
        if (appliedSearch) query.set('search', appliedSearch);

        apiRequest<PaginatedResponse<AdminUser>>(`/api/v1/admin/users?${query}`)
            .then(setUsers)
            .catch(() => setUsersError('User records could not be loaded.'))
            .finally(() => setUsersLoading(false));
    }, [appliedSearch, page]);

    useEffect(() => loadUsers(), [loadUsers]);

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        setPage(1);
        setAppliedSearch(search.trim());
        setSelectedUser(null);
    };

    const inspectUser = async (userId: number) => {
        setUsersError('');
        try {
            const response = await apiRequest<ResourceResponse<AdminUserDetail>>(`/api/v1/admin/users/${userId}`);
            setSelectedUser(response.data);
        } catch {
            setUsersError('That user summary could not be loaded.');
        }
    };

    return (
        <AppShell>
            <header className="flex flex-col gap-4 border-b border-black/10 pb-8 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Administrator</p>
                    <h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-5xl">Luma operations</h1>
                    <p className="mt-3 max-w-2xl text-sm leading-6 text-stone-600">Monitor membership, content, and AI usage without opening private user content.</p>
                </div>
                {dashboard && <p className="text-xs text-stone-500">Updated {formatDateTime(dashboard.generated_at)}</p>}
            </header>

            {dashboardError && <ErrorState message={dashboardError} />}
            {!dashboard && !dashboardError && <LoadingState label="Loading admin analytics…" />}

            {dashboard && <>
                <section className="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Dashboard metrics">
                    {Object.entries(dashboard.metrics).map(([key, value]) => (
                        <article key={key} className="rounded-2xl border border-black/10 bg-paper p-5">
                            <p className="text-xs font-semibold text-stone-500">{metricLabels[key] ?? formatAdminFeature(key)}</p>
                            <strong className="mt-3 block text-2xl tracking-[-0.04em] text-ink">
                                {key.includes('cost_micros') ? formatMicrosAsUsd(value) : value.toLocaleString()}
                            </strong>
                        </article>
                    ))}
                </section>

                <section className="mt-6 rounded-[1.75rem] border border-black/10 bg-paper p-6 sm:p-8" aria-labelledby="growth-heading">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Growth and activity</p><h2 id="growth-heading" className="mt-2 text-2xl font-semibold">Recent platform rhythm</h2></div>
                        <div className="flex rounded-xl bg-stone-200 p-1" aria-label="Chart period">
                            {[7, 30].map((days) => <button key={days} type="button" onClick={() => setRange(days as 7 | 30)} className={`rounded-lg px-4 py-2 text-xs font-bold ${range === days ? 'bg-ink text-white' : 'text-stone-600'}`}>{days} days</button>)}
                        </div>
                    </div>
                    <div className="mt-7 grid gap-8 xl:grid-cols-2">
                        <BarChart title="Registrations" points={range === 7 ? dashboard.registrations.last_7_days : dashboard.registrations.last_30_days} />
                        <BarChart title="Recorded daily activity" points={range === 7 ? dashboard.activity.last_7_days : dashboard.activity.last_30_days} />
                    </div>
                </section>

                <SubscriptionPanel dashboard={dashboard} />
                <AiUsagePanel dashboard={dashboard} />
            </>}

            <section className="mt-6 rounded-[1.75rem] border border-black/10 bg-paper p-6 sm:p-8" aria-labelledby="users-heading">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">User management</p><h2 id="users-heading" className="mt-2 text-2xl font-semibold">Account and usage summaries</h2></div>
                    <form onSubmit={submitSearch} className="flex w-full max-w-xl gap-2">
                        <label className="sr-only" htmlFor="admin-user-search">Search by name or email</label>
                        <input id="admin-user-search" type="search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search name or email" className="h-11 min-w-0 grow rounded-xl border border-stone-300 bg-white px-4 text-sm" />
                        <button className="h-11 rounded-xl bg-ink px-5 text-sm font-semibold text-white hover:bg-moss">Search</button>
                    </form>
                </div>
                {usersError && <p role="alert" className="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{usersError}</p>}
                {usersLoading ? <LoadingState label="Loading users…" compact /> : users && <>
                    <div className="mt-6 overflow-x-auto rounded-2xl border border-black/10">
                        <table className="min-w-[980px] w-full border-collapse text-left text-sm">
                            <thead className="bg-stone-100 text-xs uppercase tracking-wide text-stone-500"><tr><th className="px-4 py-3">User</th><th className="px-4 py-3">Joined</th><th className="px-4 py-3">Membership</th><th className="px-4 py-3">AI usage</th><th className="px-4 py-3">Content</th><th className="px-4 py-3">Last activity</th><th className="px-4 py-3"><span className="sr-only">Actions</span></th></tr></thead>
                            <tbody className="divide-y divide-black/10 bg-white">
                                {users.data.map((user) => <tr key={user.id}>
                                    <td className="px-4 py-4"><strong className="block">{user.name}</strong><span className="text-xs text-stone-500">{user.email}</span>{user.is_admin && <span className="ml-2 rounded-full bg-acid px-2 py-0.5 text-[0.65rem] font-bold">Admin</span>}</td>
                                    <td className="px-4 py-4 text-stone-600">{formatDate(user.created_at)}</td>
                                    <td className="px-4 py-4"><StatusBadge status={user.membership} /><span className="mt-1 block text-xs text-stone-500">{user.subscription_status.replaceAll('_', ' ')}</span></td>
                                    <td className="px-4 py-4"><strong>{user.usage.ai_requests_this_month}</strong> this month<span className="block text-xs text-stone-500">{user.usage.ai_requests} total · {formatMicrosAsUsd(user.usage.estimated_ai_cost_micros)}</span></td>
                                    <td className="px-4 py-4 text-stone-600">{user.usage.sermons} sermons · {user.usage.bible_studies} studies · {user.usage.devotionals} devotionals</td>
                                    <td className="px-4 py-4 text-stone-600">{user.last_activity_at ? formatDateTime(user.last_activity_at) : 'Not recorded'}</td>
                                    <td className="px-4 py-4"><button type="button" onClick={() => inspectUser(user.id)} className="rounded-lg border border-stone-300 px-3 py-2 text-xs font-semibold hover:bg-stone-100">Inspect</button></td>
                                </tr>)}
                            </tbody>
                        </table>
                    </div>
                    {users.data.length === 0 && <p className="mt-6 rounded-xl border border-dashed border-stone-300 p-6 text-center text-sm text-stone-500">No users match this search.</p>}
                    <div className="mt-5 flex items-center justify-between gap-4 text-sm"><span className="text-stone-500">{users.meta.total.toLocaleString()} users</span><div className="flex gap-2"><button type="button" disabled={users.meta.current_page <= 1} onClick={() => setPage((value) => value - 1)} className="rounded-lg border border-stone-300 px-3 py-2 font-semibold disabled:opacity-40">Previous</button><span className="px-2 py-2">{users.meta.current_page} / {users.meta.last_page}</span><button type="button" disabled={users.meta.current_page >= users.meta.last_page} onClick={() => setPage((value) => value + 1)} className="rounded-lg border border-stone-300 px-3 py-2 font-semibold disabled:opacity-40">Next</button></div></div>
                </>}
                {selectedUser && <UserDetail user={selectedUser} onClose={() => setSelectedUser(null)} />}
            </section>

            <BannerManager />
        </AppShell>
    );
}

function BarChart({ title, points }: { title: string; points: AdminSeriesPoint[] }) {
    const maximum = Math.max(0, ...points.map((point) => point.count));

    return <div><div className="flex items-center justify-between"><h3 className="font-semibold">{title}</h3><span className="text-xs text-stone-500">{points.reduce((sum, point) => sum + point.count, 0)} total</span></div><div className="mt-4 flex h-40 items-end gap-1" role="img" aria-label={`${title} over ${points.length} days`}>{points.map((point) => <div key={point.date} className="group relative flex h-full min-w-0 flex-1 items-end"><div className="w-full rounded-t bg-moss transition group-hover:bg-acid" style={{ height: `${chartPercent(point.count, maximum)}%` }} /><span className="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden -translate-x-1/2 whitespace-nowrap rounded bg-ink px-2 py-1 text-[0.65rem] text-white group-hover:block">{formatDate(point.date)}: {point.count}</span></div>)}</div><div className="mt-2 flex justify-between text-[0.65rem] text-stone-500"><span>{formatDate(points[0]?.date)}</span><span>{formatDate(points.at(-1)?.date)}</span></div></div>;
}

function SubscriptionPanel({ dashboard }: { dashboard: AdminDashboard }) {
    return <section className="mt-6 rounded-[1.75rem] bg-ink p-6 text-white sm:p-8" aria-labelledby="subscriptions-heading"><p className="text-xs font-bold uppercase tracking-[0.22em] text-acid">Subscriptions</p><h2 id="subscriptions-heading" className="mt-2 text-2xl font-semibold">Cashier status monitor</h2><div className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">{Object.entries(dashboard.subscriptions.counts).map(([status, count]) => <div key={status} className="rounded-2xl border border-white/10 bg-white/5 p-4"><span className="text-xs capitalize text-stone-400">{status.replaceAll('_', ' ')}</span><strong className="mt-2 block text-2xl">{count}</strong></div>)}</div><div className="mt-6 overflow-x-auto rounded-2xl border border-white/10"><table className="min-w-[760px] w-full text-left text-sm"><thead className="bg-white/5 text-xs uppercase text-stone-400"><tr><th className="px-4 py-3">User</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Stripe subscription</th><th className="px-4 py-3">Trial / end date</th></tr></thead><tbody className="divide-y divide-white/10">{dashboard.subscriptions.recent.map((subscription) => <tr key={subscription.id}><td className="px-4 py-3"><strong className="block">{subscription.user.name}</strong><span className="text-xs text-stone-400">{subscription.user.email}</span></td><td className="px-4 py-3 capitalize">{subscription.status.replaceAll('_', ' ')}</td><td className="px-4 py-3 font-mono text-xs text-stone-300">{subscription.stripe_id}</td><td className="px-4 py-3 text-stone-300">{subscription.trial_ends_at ? `Trial ${formatDate(subscription.trial_ends_at)}` : subscription.ends_at ? `Ends ${formatDate(subscription.ends_at)}` : 'Renewal date not stored locally'}</td></tr>)}</tbody></table></div>{dashboard.subscriptions.recent.length === 0 && <p className="mt-5 text-sm text-stone-400">No Stripe subscription records are available yet.</p>}</section>;
}

function AiUsagePanel({ dashboard }: { dashboard: AdminDashboard }) {
    const usage = dashboard.ai_usage;

    return <section className="mt-6 rounded-[1.75rem] border border-black/10 bg-paper p-6 sm:p-8" aria-labelledby="ai-heading"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">AI usage</p><h2 id="ai-heading" className="mt-2 text-2xl font-semibold">Requests, quotas, and estimated cost</h2><p className="mt-2 text-xs text-stone-500">Cost values are estimates calculated from recorded token usage and configured rates. They are not OpenAI invoice totals.</p><div className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">{[['Requests today', usage.requests_today], ['Requests this month', usage.requests_this_month], ['Failed / released this month', usage.failed_requests_this_month], ['Estimated this month', formatMicrosAsUsd(usage.estimated_cost_micros_this_month)]].map(([label, value]) => <div key={label} className="rounded-2xl bg-white p-4"><span className="text-xs text-stone-500">{label}</span><strong className="mt-2 block text-xl">{value}</strong></div>)}</div><div className="mt-7 grid gap-6 xl:grid-cols-2"><div><h3 className="font-semibold">Usage by feature this month</h3><div className="mt-3 grid gap-3">{Object.entries(usage.by_feature).map(([feature, totals]) => <div key={feature} className="rounded-xl border border-black/10 bg-white p-4"><div className="flex justify-between gap-4"><strong className="text-sm">{formatAdminFeature(feature)}</strong><span className="text-sm">{totals.requests} requests</span></div><p className="mt-1 text-xs text-stone-500">{totals.failed_requests} failed / released · {formatMicrosAsUsd(totals.estimated_cost_micros)} estimated</p></div>)}{Object.keys(usage.by_feature).length === 0 && <p className="text-sm text-stone-500">No AI requests have been recorded this month.</p>}</div></div><div><h3 className="font-semibold">Quota utilization</h3><div className="mt-3 grid gap-3">{Object.entries(usage.quota_utilization).map(([feature, quota]) => <div key={feature} className="rounded-xl border border-black/10 bg-white p-4"><div className="flex justify-between gap-4 text-sm"><strong>{formatAdminFeature(feature)}</strong><span>{quota.used} / {quota.available}</span></div><div className="mt-3 h-2 overflow-hidden rounded-full bg-stone-200"><div className="h-full rounded-full bg-moss" style={{ width: `${Math.min(100, quota.percent)}%` }} /></div><p className="mt-2 text-xs text-stone-500">{quota.percent}% of combined monthly allowance used</p></div>)}</div></div></div><div className="mt-8"><h3 className="font-semibold">Six-month usage trend</h3><div className="mt-3 overflow-x-auto rounded-xl border border-black/10 bg-white"><table className="min-w-[560px] w-full text-left text-sm"><thead className="bg-stone-100 text-xs uppercase text-stone-500"><tr><th className="px-4 py-3">Month</th><th className="px-4 py-3">Requests</th><th className="px-4 py-3">Failed / released</th><th className="px-4 py-3">Estimated cost</th></tr></thead><tbody className="divide-y divide-black/10">{usage.monthly_trends.map((month) => <tr key={month.month}><td className="px-4 py-3 font-semibold">{new Date(`${month.month}-01T00:00:00`).toLocaleDateString(undefined, { month: 'long', year: 'numeric' })}</td><td className="px-4 py-3">{month.requests}</td><td className="px-4 py-3">{month.failed_requests}</td><td className="px-4 py-3">{formatMicrosAsUsd(month.estimated_cost_micros)}</td></tr>)}</tbody></table></div></div></section>;
}

function UserDetail({ user, onClose }: { user: AdminUserDetail; onClose: () => void }) {
    return <aside className="mt-6 rounded-2xl border border-moss/20 bg-white p-5" aria-labelledby="user-detail-heading"><div className="flex items-start justify-between gap-4"><div><p className="text-xs font-bold uppercase tracking-wider text-moss">User detail</p><h3 id="user-detail-heading" className="mt-1 text-xl font-semibold">{user.name}</h3><p className="text-sm text-stone-500">{user.email}</p></div><button type="button" onClick={onClose} className="rounded-lg border border-stone-300 px-3 py-2 text-xs font-semibold">Close</button></div><div className="mt-5 grid gap-4 md:grid-cols-3"><div><span className="text-xs text-stone-500">Membership</span><p className="mt-1 font-semibold capitalize">{user.membership} · {user.subscription_status.replaceAll('_', ' ')}</p></div><div><span className="text-xs text-stone-500">Joined</span><p className="mt-1 font-semibold">{formatDateTime(user.created_at)}</p></div><div><span className="text-xs text-stone-500">Last reliable activity</span><p className="mt-1 font-semibold">{user.last_activity_at ? formatDateTime(user.last_activity_at) : 'Not recorded'}</p></div></div><div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">{Object.entries(user.usage_by_feature).map(([feature, usage]) => <div key={feature} className="rounded-xl bg-stone-100 p-4"><strong className="text-sm">{formatAdminFeature(feature)}</strong><p className="mt-2 text-xs text-stone-600">{usage.requests} requests · {usage.failed_requests} failed</p><p className="mt-1 text-xs text-stone-500">{formatMicrosAsUsd(usage.estimated_cost_micros)} estimated</p></div>)}</div>{user.subscriptions.length > 0 && <div className="mt-5"><h4 className="text-sm font-semibold">Subscription history</h4><div className="mt-2 grid gap-2">{user.subscriptions.map((subscription) => <div key={subscription.stripe_id} className="flex flex-col justify-between gap-2 rounded-xl border border-black/10 p-3 text-xs sm:flex-row"><span className="capitalize">{subscription.status.replaceAll('_', ' ')}</span><code>{subscription.stripe_id}</code><span>{subscription.ends_at ? `Ends ${formatDate(subscription.ends_at)}` : 'No local end date'}</span></div>)}</div></div>}<p className="mt-5 text-xs leading-5 text-stone-500">This view intentionally excludes passwords, payment methods, API credentials, transcripts, sermon notes, and devotional or Bible-study content.</p></aside>;
}

function StatusBadge({ status }: { status: string }) {
    return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold capitalize ${status === 'premium' || status === 'active' ? 'bg-acid text-ink' : 'bg-stone-200 text-stone-700'}`}>{status.replaceAll('_', ' ')}</span>;
}

function LoadingState({ label, compact = false }: { label: string; compact?: boolean }) {
    return <div className={`${compact ? 'mt-6 min-h-28' : 'mt-8 min-h-48'} grid place-items-center rounded-2xl border border-dashed border-black/15 text-sm text-stone-500`}>{label}</div>;
}

function ErrorState({ message }: { message: string }) {
    return <div role="alert" className="mt-8 rounded-2xl border border-red-300 bg-red-50 p-5 text-sm text-red-800">{message}</div>;
}

function formatDate(value?: string | null): string {
    return value ? new Date(value).toLocaleDateString() : '—';
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString();
}
