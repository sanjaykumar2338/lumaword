import { Link } from 'react-router-dom';

export function Brand({ inverse = false }: { inverse?: boolean }) {
    return (
        <Link to="/" className="group inline-flex items-center gap-3" aria-label="LumaWord home">
            <span className={`grid size-10 place-items-center overflow-hidden rounded-xl ${inverse ? 'bg-acid' : 'bg-transparent'}`}>
                <img src="/logo-mark.png" alt="" className="size-9 object-contain" />
            </span>
            <span className={`text-lg font-semibold tracking-[-0.04em] ${inverse ? 'text-white' : 'text-ink'}`}>
                LUMA<span className={inverse ? 'text-acid' : 'text-moss'}>WORD</span>
            </span>
        </Link>
    );
}
