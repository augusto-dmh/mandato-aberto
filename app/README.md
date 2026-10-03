# app

The Mandato Aberto v2 application: one Laravel 13 monolith with Inertia v3, Vue 3 and server-side rendering on PostgreSQL 18 (`.specs/STATE.md` AD-013). It imports the ETL's JSON contract v3 (`etl/schema/v3/*.json`) and renders each deputy, senator and roll call, and the methodology, with the design package (`design/`). Plans and checks in `.specs/features/app-skeleton/` and `.specs/features/app-contract-v3/`.

Development runs in [Laravel Sail](https://laravel.com/docs/sail): PHP 8.5 and PostgreSQL 18 in Docker, no PHP or database on the host. Sail mounts `../design`, `../etl`, `../data`, `../site` and `../.github` beside the app, so the paths below are the same inside and outside the container.

## First run

```bash
cp .env.example .env               # then set APP_URL, APP_PORT, FORWARD_DB_PORT and VITE_PORT if 80, 5432 or 5173 are taken,
                                   # and COMPOSE_PROJECT_NAME when another checkout runs Sail too
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
sail artisan mandato:import                              # reads ../data/v3/camara then ../data/v3/senado (`mandato-etl build --contract 3`)
sail artisan mandato:import ../data/v3/senado            # one house
sail artisan mandato:import tests/fixtures/v3            # the small fixtures the tests use
sail artisan mandato:import ../data/v3 --dry-run         # validate and count, write nothing
```

Every file is validated against `../etl/schema/v3` and every reference between files resolved before any write. Each house is imported in its own transaction: a snapshot of each legislature its `meta.json` lists (what the contract no longer has is removed; members and propositions stay), with its classification rules and full texts replaced as sets. It exits `0` when every house imported, `1` when any house was refused or failed (that house writes nothing; a house already imported stays), `2` when the directory does not exist.

## Run the SSR server

```bash
sail artisan inertia:start-ssr                                    # in the foreground
sail exec -d -u sail laravel.test php artisan inertia:start-ssr   # or detached
```

Pages answer at `http://localhost:${APP_PORT}/` (home), `/busca/` (search), `/legislaturas/{n}/` (overview), `/deputados/{id}/`, `/senadores/{id}/` (each with `legislatura/{n}/`), `/votacoes/{id}/`, `/senado/votacoes/{id}/` and `/metodologia/`. Without the SSR server they still answer, with every share tag in the head and the body rendered in the browser. With it, the home, search and overview send no client script at all.

## Tests and gates

```bash
sail artisan test                  # Pest; needs the bundles built and the SSR server running
sail artisan test --filter="indicators show the merit base then all votes"   # one check's proof
sail npm --prefix /var/www/design test   # the design package's own tests (vote encoding)
sail bin pint --test               # code style
sail bin phpstan analyse           # Larastan, level 6
```

CI runs the same gates in job `app` of `.github/workflows/ci.yml`.

## Page weight

The home, search and overview have a first-load budget (`.specs/features/app-home/checks.md`, C42 to C44). The HTML and CSS halves run in Pest; the whole first load is measured in Chromium over the budget dataset, outside CI:

```bash
sail artisan migrate:fresh --force && sail artisan db:seed --class=BudgetSeeder --force   # replaces the development data
npm ci --prefix ../design && npx --prefix ../design playwright install chromium
node tests/budget/first-load.mjs http://localhost:${APP_PORT}                             # exits 1 over budget
```
