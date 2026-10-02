# app

The Mandato Aberto v2 application: one Laravel 13 monolith with Inertia v3, Vue 3 and server-side rendering on PostgreSQL 18 (`.specs/STATE.md` AD-013). It imports the ETL's JSON contract (`etl/schema/*.json`) and renders each deputy and each roll call with the design package (`design/`). Plan and checks in `.specs/features/app-skeleton/`.

Development runs in [Laravel Sail](https://laravel.com/docs/sail): PHP 8.5 and PostgreSQL 18 in Docker, no PHP or database on the host. Sail mounts `../design`, `../etl`, `../data`, `../site` and `../.github` beside the app, so the paths below are the same inside and outside the container.

## First run

```bash
cp .env.example .env               # then set APP_URL, APP_PORT, FORWARD_DB_PORT and VITE_PORT if 80, 5432 or 5173 are taken
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd)/..:/var/www" -w /var/www/app \
  laravelsail/php84-composer:latest composer install --ignore-platform-reqs   # Sail's composer image; the app itself runs on 8.5
./vendor/bin/sail up -d
alias sail=./vendor/bin/sail      # the commands below assume it
sail artisan key:generate
sail artisan migrate
sail npm --prefix /var/www/design ci && sail npm --prefix /var/www/design run build   # the design tokens
sail npm ci && sail npm run build  # client bundle in public/build, SSR bundle in bootstrap/ssr
```

## Import the contract

```bash
sail artisan mandato:import                              # reads ../data/out, written by `mandato-etl build`
sail artisan mandato:import ../site/tests/fixtures/out   # the small fixture the tests use
sail artisan mandato:import ../data/out --dry-run        # validate and count, write nothing
```

Every file is validated against `../etl/schema` before any write. The import is a snapshot of each house and legislature: what the contract no longer has is removed, members and propositions stay. It exits `0` on success, `1` when it refuses the contract or fails (nothing is written), `2` when the directory does not exist.

## Run the SSR server

```bash
sail artisan inertia:start-ssr                                    # in the foreground
sail exec -d -u sail laravel.test php artisan inertia:start-ssr   # or detached
```

Pages answer at `http://localhost:${APP_PORT}/deputados/{id}/` and `/votacoes/{id}/`. Without the SSR server they still answer, with every share tag in the head and the body rendered in the browser.

## Tests and gates

```bash
sail artisan test                  # Pest; needs the bundles built and the SSR server running
sail artisan test --filter="profile shows the deputy and the indicators"   # one check's proof
sail bin pint --test               # code style
sail bin phpstan analyse           # Larastan, level 6
```

CI runs the same gates in job `app` of `.github/workflows/ci.yml`.
