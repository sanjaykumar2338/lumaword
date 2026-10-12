export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly errors: Record<string, string[]> = {},
    ) {
        super(message);
    }
}

const getCookie = (name: string): string | undefined => {
    const cookie = document.cookie
        .split('; ')
        .find((item) => item.startsWith(`${name}=`));

    return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : undefined;
};

const prepareCsrf = async (): Promise<void> => {
    await fetch('/sanctum/csrf-cookie', {
        credentials: 'include',
        headers: { Accept: 'application/json' },
    });
};

export async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
    const method = (options.method ?? 'GET').toUpperCase();

    if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
        await prepareCsrf();
    }

    const headers = new Headers(options.headers);
    headers.set('Accept', 'application/json');

    if (options.body) {
        headers.set('Content-Type', 'application/json');
    }

    const xsrfToken = getCookie('XSRF-TOKEN');
    if (xsrfToken) {
        headers.set('X-XSRF-TOKEN', xsrfToken);
    }

    const response = await fetch(path, {
        ...options,
        credentials: 'include',
        headers,
    });

    if (response.status === 204) {
        return undefined as T;
    }

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new ApiError(
            payload.message ?? 'Something went wrong. Please try again.',
            response.status,
            payload.errors ?? {},
        );
    }

    return payload as T;
}
