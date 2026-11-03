#!/usr/bin/env bash
set -Eeuo pipefail
export DOCKER_CONFIG="${DOCKER_CONFIG:-$HOME/.docker-deploy}"

NEW_TAG="${1:-}"
ENV_FILE=".env.production"
COMPOSE_FILE="compose.wsl.yaml"
STATE_FILE=".deployed-image-tag"
HEALTH_URL="http://localhost:8003/api/health"

if [[ -z "$NEW_TAG" ]]; then
    echo "Usage: ./deploy.sh IMAGE_TAG"
    exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
    echo "ERROR: Missing $ENV_FILE"
    exit 1
fi

OLD_TAG=""
if [[ -f "$STATE_FILE" ]]; then
    OLD_TAG="$(cat "$STATE_FILE")"
fi

export IMAGE_TAG="$NEW_TAG"

compose() {
    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" "$@"
}

rollback() {
    echo "ERROR: Deployment failed."

    if [[ -z "$OLD_TAG" ]]; then
        echo "No previous version recorded."
        exit 1
    fi

    echo "Rolling back to $OLD_TAG..."
    export IMAGE_TAG="$OLD_TAG"

    compose up -d --no-build

    for i in {1..15}; do
        if curl -fsS --max-time 3 "$HEALTH_URL" >/dev/null; then
            echo "Rollback successful: $OLD_TAG"
            exit 1
        fi
        sleep 2
    done

    echo "ERROR: Rollback health check failed."
    exit 1
}

echo "Deploying $NEW_TAG"
echo "Previous version: ${OLD_TAG:-none}"

compose config --quiet

compose pull app nginx queue-worker

if ! compose up -d --no-build; then
    rollback
fi

echo "Waiting for API..."

HEALTHY=false

for i in {1..15}; do
    if curl -fsS --max-time 3 "$HEALTH_URL" >/dev/null; then
        HEALTHY=true
        break
    fi
    sleep 2
done

if [[ "${2:-}" == "--fail-health" ]]; then
    echo "TEST: Simulating failed health check..."
    HEALTHY=false
fi

if [[ "$HEALTHY" != true ]]; then
    rollback
fi

echo "Running migrations..."

if ! compose exec -T app php artisan migrate --force; then
    rollback
fi

printf '%s\n' "$NEW_TAG" > "$STATE_FILE"

echo "Deployment successful: $NEW_TAG"
