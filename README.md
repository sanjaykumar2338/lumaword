# LumaWord

LumaWord is an AI-powered Christian discipleship, sermon study, devotional, and apologetics platform. This repository currently contains the Phase 1 web foundation: the public landing experience, authentication, a protected dashboard, account settings, a versioned API, and delivery tooling.

Sermon ingestion and AI processing are intentionally **not active**. The dashboard labels them as Phase 2 functionality.

## Stack

- Laravel 13 / PHP 8.3+
- React 19 + TypeScript
- Tailwind CSS 4 and Vite
- MySQL for local and production data
- Laravel Sanctum for first-party SPA authentication and future token-capable API routes
- PHPUnit 12

Redis, OpenAI, Stripe, and Filament are deferred until their roadmap phases require them.

## Requirements

- PHP 8.3–8.5 with `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `session`, `tokenizer`, `xml`, and `zip`
- Composer 2
- Node.js 24 LTS and npm
- MySQL 8+ or a compatible current MariaDB release

> The PHP 7.4 bundled with older XAMPP releases cannot run Laravel 13. Update XAMPP or point your terminal and web server to PHP 8.3+.

## Local setup

```bash
git clone https://github.com/sanjaykumar2338/lumaword.git
cd lumaword
composer install
cp .env.example .env
php artisan key:generate
npm ci
```

Create an empty MySQL database named `lumaword`, then adjust these values in `.env` if needed:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lumaword
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations and start the development processes:

```bash
php artisan migrate
composer run dev
```

Open `http://localhost:8000`. Laravel serves the React shell while Vite provides frontend assets during development.

### Password reset email in local development

The default `MAIL_MAILER=log` setting writes password-reset messages to `storage/logs/laravel.log`. Configure a real mail provider through environment variables before staging or production use.

## Verification

```bash
php artisan test --compact
npm run typecheck
npm run build
vendor/bin/pint --dirty --format agent
composer audit
npm audit
```

Tests use an in-memory SQLite database so CI does not need a database service. Application runtime defaults to MySQL through `.env.example`.

## API

Phase 1 endpoints are versioned under `/api/v1`. Browser authentication uses Sanctum's secure, cookie-based SPA flow. Protected routes also use the `auth:sanctum` guard so later mobile token authentication can share the same user-facing endpoints.

No public token-issuance endpoint exists in Phase 1; the native mobile authentication contract remains an open decision.

See [Architecture](docs/ARCHITECTURE.md), [Phase status](docs/PHASE_STATUS.md), and [Open decisions](docs/OPEN_DECISIONS.md).

## Security

- Never commit `.env` files, credentials, API keys, user content, `vendor`, `node_modules`, or built assets.
- Keep `APP_DEBUG=false` in production.
- Use HTTPS and secure cookies in production.
- Configure the production mail provider and `APP_URL` before password-reset testing.
- Uploaded/private sermon content is not implemented in Phase 1; future storage must default to private access.

## License

Proprietary project for LumaWord. No reuse license has been granted.
