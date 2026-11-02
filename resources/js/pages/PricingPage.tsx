import { AppShell } from '../components/AppShell';
import { BillingOverview } from '../components/BillingOverview';

export function PricingPage() {
    return <AppShell>
        <header className="max-w-3xl"><p className="text-xs font-bold uppercase tracking-[0.22em] text-moss">Membership</p><h1 className="mt-2 text-4xl font-semibold tracking-[-0.055em] sm:text-6xl">Choose the space you need to study well.</h1><p className="mt-4 text-stone-600">Free keeps Scripture, reading plans, saved work, and modest AI allowances available. Premium raises—not removes—AI limits.</p></header>
        <div className="mt-10 grid gap-6 lg:grid-cols-2">
            <section className="rounded-[1.75rem] border border-black/10 bg-paper p-7"><p className="text-sm font-bold uppercase tracking-widest text-moss">Free</p><p className="mt-4 text-4xl font-semibold">$0</p><ul className="mt-6 grid gap-3 text-sm text-stone-700"><li>✓ Bible reading and reading plans</li><li>✓ Saved sermons and studies</li><li>✓ Monthly AI allowances</li><li>✓ First-party promotional banners may appear</li></ul></section>
            <section className="rounded-[1.75rem] border border-moss bg-ink p-7 text-white"><p className="text-sm font-bold uppercase tracking-widest text-acid">Premium</p><p className="mt-4 text-4xl font-semibold">$9.99 <span className="text-base font-normal text-stone-400">monthly</span></p><p className="mt-2 text-sm text-stone-400">or proposed $79.99 annually</p><ul className="mt-6 grid gap-3 text-sm text-stone-200"><li>✓ Higher monthly AI allowances</li><li>✓ Ad-free experience</li><li>✓ Billing and invoices through Stripe</li><li>✓ Access continues through a paid cancellation period</li></ul></section>
        </div>
        <section className="mt-6 rounded-[1.75rem] border border-black/10 bg-paper p-7"><BillingOverview /></section>
    </AppShell>;
}
