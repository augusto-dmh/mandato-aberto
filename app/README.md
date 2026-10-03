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
sail npm ci && sail npm run build  # client bundle in public/build, SSR bundle in bootstrap/ssr, card renderer in bootstrap/cards
```

## Import the contract

```bash
sail artisan mandato:import                              # reads ../data/v3/camara then ../data/v3/senado (`mandato-etl build --contract 3`)
sail artisan mandato:import ../data/v3/senado            # one house
sail artisan mandato:import tests/fixtures/v3            # the small fixtures the tests use
sail artisan mandato:import ../data/v3 --dry-run         # validate and count, write nothing
```

Every file is validated against `../etl/schema/v3` and every reference between files resolved before any write. Each house is imported in its own transaction: a snapshot of each legislature its `meta.json` lists (what the contract no longer has is removed; members and propositions stay), with its classification rules and full texts replaced as sets. It exits `0` when every house imported, `1` when any house was refused or failed (that house writes nothing; a house already imported stays), `2` when the directory does not exist.

## Official photos and share cards

```bash
sail artisan mandato:photos                       # caches each member's official photo (new, or checked more than 7 days ago)
sail artisan mandato:photos --house=senado --member=5012 --stale-after=0
```

Photos are fetched only from `www.camara.leg.br`, `www.senado.leg.br` and `legis.senado.leg.br`, kept byte for byte on the `media` disk (`storage/app/media/`) and served at `/fotos/{sha256}.jpg`. A member listed in `config/mandato.php` `photo_suppressed` as `camara:<id>` or `senado:<id>` shows initials, and their photo files answer 410.

Member and roll-call pages link their card at `.../card/{code}/{1200x630,1080x1350,1080x1920}.png`. The first request of a card stores its snapshot and renders it with `node bootstrap/cards/render.mjs` (the design package's `MemberCard` or `RollCallCard` in headless Chromium, installed in the Sail image by `docker/8.5/Dockerfile`); later requests serve the stored PNG. A served request finds the browser through `PLAYWRIGHT_BROWSERS_PATH` in `.env` (`/opt/ms-playwright` in the Sail image, as `.env.example` sets it): `artisan serve` and PHP-FPM do not pass the image's own variable to the PHP answering the request. Each page links its three images and its code, and carries the `1200x630` card as `og:image`. A code opens `/verificar/{code}/`, which shows the values the card showed and whether the current data still match; `/verificar/` takes a typed code. Plan and checks in `.specs/features/share-cards/`.

```bash
sail artisan mandato:cards:prune             # deletes card PNGs older than 30 days whose code is not its subject's latest
sail artisan mandato:cards:prune --days=7    # snapshots stay, so a pruned card renders again when asked
```

## Run the SSR server

```bash
sail artisan inertia:start-ssr                                    # in the foreground
sail exec -d -u sail laravel.test php artisan inertia:start-ssr   # or detached
```

Pages answer at `http://localhost:${APP_PORT}/deputados/{id}/`, `/senadores/{id}/` (each with `legislatura/{n}/`), `/votacoes/{id}/`, `/senado/votacoes/{id}/`, `/metodologia/` and `/verificar/`. Without the SSR server they still answer, with every share tag in the head and the body rendered in the browser.

## Tests and gates

```bash
sail artisan test                  # Pest; needs the bundles built and the SSR server running
sail artisan test --filter="indicators show the merit base then all votes"   # one check's proof
sail npm --prefix /var/www/design test   # the design package's own tests (vote encoding)
sail bin pint --test               # code style
sail bin phpstan analyse           # Larastan, level 6
```

CI runs the same gates in job `app` of `.github/workflows/ci.yml`.
