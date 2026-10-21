
@echo off
setlocal EnableExtensions EnableDelayedExpansion

if "%~1"=="" (
    echo Usage: deploy.bat IMAGE_TAG
    exit /b 1
)

set "NEW_TAG=%~1"
set "ENV_FILE=.env.production"
set "COMPOSE_FILE=compose.deploy.yaml"
set "HEALTH_URL=http://localhost:8002/api/health"
set "STATE_FILE=.deployed-image-tag"
set "SIMULATE_FAILURE=%~2"

if not exist "%ENV_FILE%" (
    echo ERROR: Missing %ENV_FILE%
    exit /b 1
)

set "OLD_TAG="
if exist "%STATE_FILE%" set /p OLD_TAG=<"%STATE_FILE%"

echo.
echo Deploying version %NEW_TAG%
echo Previous version: !OLD_TAG!
echo.

set "IMAGE_TAG=%NEW_TAG%"

docker compose --env-file "%ENV_FILE%" -f "%COMPOSE_FILE%" config --quiet
if errorlevel 1 exit /b 1

docker compose --env-file "%ENV_FILE%" -f "%COMPOSE_FILE%" pull app nginx queue-worker
if errorlevel 1 exit /b 1

docker compose --env-file "%ENV_FILE%" -f "%COMPOSE_FILE%" up -d --no-build
if errorlevel 1 goto rollback

echo Waiting for API...
set "HEALTHY=0"

for /L %%i in (1,1,15) do (
    curl -fsS --max-time 3 "%HEALTH_URL%" >nul 2>&1
    if not errorlevel 1 (
        set "HEALTHY=1"
        goto health_ok
    )
    timeout /t 2 /nobreak >nul
)

:health_ok

if /I "!SIMULATE_FAILURE!"=="--fail-health" (
    echo TEST: Simulating failed health check...
    set "HEALTHY=0"
)

if "!HEALTHY!"=="0" goto rollback

echo Running migrations...
docker compose --env-file "%ENV_FILE%" -f "%COMPOSE_FILE%" exec -T app php artisan migrate --force
if errorlevel 1 goto rollback

echo %NEW_TAG%>"%STATE_FILE%"
echo Deployment successful: %NEW_TAG%
exit /b 0

:rollback
echo.
echo ERROR: Deployment failed.

if "!OLD_TAG!"=="" (
    echo No previous version recorded. Automatic rollback unavailable.
    exit /b 1
)

echo Rolling back to !OLD_TAG!...
set "IMAGE_TAG=!OLD_TAG!"

docker compose --env-file "%ENV_FILE%" -f "%COMPOSE_FILE%" up -d --no-build
if errorlevel 1 (
    echo ERROR: Rollback failed.
    exit /b 1
)

curl -fsS --max-time 5 "%HEALTH_URL%" >nul 2>&1
if errorlevel 1 (
    echo WARNING: Previous version is not healthy.
    exit /b 1
)

echo Rollback completed: !OLD_TAG!
exit /b 1
