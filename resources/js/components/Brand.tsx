import { Link } from 'react-router-dom';

export function Brand({ inverse = false }: { inverse?: boolean }) {
    return (
        <Link to="/" className="group inline-flex items-center gap-3" aria-label="LumaWord home">
            <span className={`grid size-9 place-items-center rounded-full border text-xs font-bold tracking-tight ${inverse ? 'border-white/20 bg-acid text-ink' : 'border-ink bg-ink text-acid'}`}>
                L
            </span>
            <span className={`text-lg font-semibold tracking-[-0.04em] ${inverse ? 'text-white' : 'text-ink'}`}>
                LUMA<span className={inverse ? 'text-acid' : 'text-moss'}>WORD</span>
            </span>
        </Link>
    );
}
