import { Link } from 'react-router-dom';

export function Brand({ inverse = false }: { inverse?: boolean }) {
    return (
        <Link to="/" className="group inline-flex items-center gap-3" aria-label="Luma home">
            <span className={`grid size-11 place-items-center rounded-xl ${inverse ? 'bg-paper' : 'bg-transparent'}`}>
                <img src="/logo-mark.png" alt="" className="size-10 object-contain" />
            </span>
            <span className={`text-lg font-bold tracking-[0.18em] ${inverse ? 'text-white' : 'text-ink'}`}>
                LUMA
            </span>
        </Link>
    );
}
