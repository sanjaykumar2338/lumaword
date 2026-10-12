import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { Brand } from './Brand';

export function AuthLayout({ eyebrow, title, copy, children }: { eyebrow: string; title: string; copy: string; children: ReactNode }) {
    return (
        <main className="min-h-screen bg-paper lg:grid lg:grid-cols-[1.05fr_0.95fr]">
            <section className="relative hidden overflow-hidden bg-ink p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <div className="absolute -right-24 top-24 size-80 rounded-full bg-acid/15 blur-3xl" />
                <Brand inverse />
                <div className="relative max-w-xl">
                    <p className="mb-6 text-xs font-bold uppercase tracking-[0.26em] text-acid">Hear it. Hold it. Live it.</p>
                    <blockquote className="text-balance text-4xl font-medium leading-tight tracking-[-0.05em]">
                        “Your word is a lamp for my feet, a light on my path.”
                    </blockquote>
                    <p className="mt-5 text-sm text-stone-400">Psalm 119:105</p>
                </div>
                <p className="text-sm text-stone-500">Thoughtful tools for a lived faith.</p>
            </section>
            <section className="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
                <div className="w-full max-w-md">
                    <div className="mb-10 lg:hidden"><Brand /></div>
                    <Link to="/" className="mb-8 inline-flex text-sm font-medium text-stone-500 transition hover:text-ink">← Back to home</Link>
                    <p className="text-xs font-bold uppercase tracking-[0.24em] text-moss">{eyebrow}</p>
                    <h1 className="mt-3 text-4xl font-semibold tracking-[-0.05em] text-ink">{title}</h1>
                    <p className="mt-3 text-base leading-7 text-stone-600">{copy}</p>
                    <div className="mt-8">{children}</div>
                </div>
            </section>
        </main>
    );
}
