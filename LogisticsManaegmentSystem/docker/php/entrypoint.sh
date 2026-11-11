#!/bin/sh
#
# Container entrypoint for the API, queue worker and scheduler.
#
# Three services share one image and one bind mounted source tree, so everything
# that mutates that tree - composer install, the .env file, the cached config -
# is gated here rather than left to each service's command. Two rules make that
# safe:
#
#   * only the `api` service installs dependencies and migrates, and the others
#     wait for its result instead of racing it;
#   * the cache step runs before migrations, because a config.php left behind by
#     a previous `prod` run would otherwise point artisan at the wrong database.
#
set -e

cd "${APP_PATH:-/var/www/html}"

log() {
    printf '==> %s\n' "$1"
}

fail() {
    printf '==> ERROR: %s\n' "$1" >&2
    exit 1
}

# ------------------------------------------------------------------ vendor --
# A fresh clone has no vendor/, because it is gitignored. Installing on first
# boot is what makes `docker compose up` work on a clean checkout.
if [ ! -f vendor/autoload.php ]; then
    if [ "${BOOTSTRAP_INSTALL:-true}" = "true" ]; then
        log "vendor/ is missing, running composer install"
        composer install --no-interaction --prefer-dist --no-progress
    else
        # Another service owns the install. Wait for it rather than running a
        # second composer against the same directory.
        log "waiting for vendor/ to be installed by the api service"
        waited=0
        while [ ! -f vendor/autoload.php ]; do
            waited=$((waited + 1))
            [ "$waited" -gt 150 ] && fail "vendor/ never appeared - start the api service first"
            sleep 2
        done
        log "vendor/ is ready"
    fi
fi

# --------------------------------------------------------------------- .env --
if [ ! -f .env ]; then
    log "backend/.env not found, copying .env.example"
    cp .env.example .env
fi

# key:generate writes back to .env, so the key survives a container rebuild
# instead of invalidating every session and token on the next `up`.
if ! grep -qE '^APP_KEY=.+' .env; then
    log "APP_KEY is empty, generating one (persisted into backend/.env)"
    php artisan key:generate --force --no-interaction
fi

# ------------------------------------------------------------------ caches --
if [ "${APP_ENV:-local}" = "production" ]; then
    log "caching configuration for production"
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction
    php artisan event:cache --no-interaction
    php artisan view:cache --no-interaction
else
    # The bind mount is edited live, so a cached config, route table or compiled
    # view is guaranteed to be stale. Clearing is also what stops a
    # `docker compose -f docker-compose.prod.yml up` run from leaving
    # bootstrap/cache/config.php behind for the next local `artisan` call.
    php artisan optimize:clear --no-interaction >/dev/null 2>&1 || true
fi

# -------------------------------------------------------------- migrations --
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    log "applying migrations"
    php artisan migrate --force --no-interaction
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    log "seeding the database"
    php artisan db:seed --force --no-interaction
fi

log "starting: $*"
exec "$@"
