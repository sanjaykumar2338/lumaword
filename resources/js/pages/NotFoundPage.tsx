import { Link } from 'react-router-dom';
import { Brand } from '../components/Brand';

export function NotFoundPage() {
    return <main className="grid min-h-screen place-items-center bg-ink px-5 text-white"><div className="text-center"><div className="flex justify-center"><Brand inverse /></div><p className="mt-14 text-xs font-bold uppercase tracking-[0.25em] text-acid">404</p><h1 className="mt-4 text-5xl font-semibold tracking-[-0.06em]">This path is unlit.</h1><p className="mt-4 text-stone-400">Let’s get you back to familiar ground.</p><Link to="/" className="mt-8 inline-flex rounded-full bg-acid px-6 py-3 font-semibold text-ink">Return home</Link></div></main>;
}
