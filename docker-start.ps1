# Francis Manage - Docker Helper Script
# PowerShell script for Windows users

param(
    [Parameter(Position=0)]
    [ValidateSet('start', 'stop', 'restart', 'build', 'logs', 'shell', 'mysql', 'migrate', 'seed', 'fresh', 'status', 'clean')]
    [string]$Command = 'start'
)

$ProjectName = "francis"

function Write-Header {
    param([string]$Message)
    Write-Host ""
    Write-Host "===========================================" -ForegroundColor Cyan
    Write-Host "  $Message" -ForegroundColor White
    Write-Host "===========================================" -ForegroundColor Cyan
    Write-Host ""
}

function Start-Docker {
    Write-Header "Starting Docker Environment"
    
    # Copy .env.docker to .env if .env doesn't exist
    if (-not (Test-Path ".env")) {
        Write-Host "Creating .env from .env.docker..." -ForegroundColor Yellow
        Copy-Item ".env.docker" ".env"
    }
    
    docker-compose up -d --build
    
    Write-Host ""
    Write-Host "Environment started successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Access your application at:" -ForegroundColor White
    Write-Host "  - Laravel App:    http://localhost:8000" -ForegroundColor Cyan
    Write-Host "  - phpMyAdmin:     http://localhost:8080" -ForegroundColor Cyan
    Write-Host "  - MySQL:          localhost:3306" -ForegroundColor Cyan
    Write-Host ""
}

function Stop-Docker {
    Write-Header "Stopping Docker Environment"
    docker-compose down
    Write-Host "Environment stopped." -ForegroundColor Green
}

function Restart-Docker {
    Write-Header "Restarting Docker Environment"
    docker-compose restart
    Write-Host "Environment restarted." -ForegroundColor Green
}

function Build-Docker {
    Write-Header "Building Docker Images"
    docker-compose build --no-cache
    Write-Host "Build complete." -ForegroundColor Green
}

function Show-Logs {
    Write-Header "Showing Docker Logs"
    docker-compose logs -f
}

function Enter-Shell {
    Write-Header "Entering App Container Shell"
    docker-compose exec app bash
}

function Enter-MySQL {
    Write-Header "Entering MySQL Shell"
    docker-compose exec mysql mysql -u francis -psecret francis_db
}

function Run-Migrate {
    Write-Header "Running Migrations"
    docker-compose exec app php artisan migrate
}

function Run-Seed {
    Write-Header "Running Seeders"
    docker-compose exec app php artisan db:seed
}

function Run-Fresh {
    Write-Header "Fresh Migration with Seed"
    docker-compose exec app php artisan migrate:fresh --seed
}

function Show-Status {
    Write-Header "Docker Status"
    docker-compose ps
}

function Clean-Docker {
    Write-Header "Cleaning Docker Environment"
    docker-compose down -v --rmi local
    Write-Host "Cleaned up containers, volumes, and local images." -ForegroundColor Green
}

# Execute command
switch ($Command) {
    'start'   { Start-Docker }
    'stop'    { Stop-Docker }
    'restart' { Restart-Docker }
    'build'   { Build-Docker }
    'logs'    { Show-Logs }
    'shell'   { Enter-Shell }
    'mysql'   { Enter-MySQL }
    'migrate' { Run-Migrate }
    'seed'    { Run-Seed }
    'fresh'   { Run-Fresh }
    'status'  { Show-Status }
    'clean'   { Clean-Docker }
    default   { Start-Docker }
}
