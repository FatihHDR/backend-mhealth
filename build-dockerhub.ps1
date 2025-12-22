# Build and Push Docker Image to Docker Hub
# Usage: .\build-dockerhub.ps1 -Username "your-dockerhub-username"

param(
    [Parameter(Mandatory=$true)]
    [string]$Username,
    
    [Parameter(Mandatory=$false)]
    [string]$RepoName = "backend-mhealth",
    
    [Parameter(Mandatory=$false)]
    [switch]$Latest = $true,
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipBuild = $false
)

# Get commit SHA for tagging
$CommitSHA = (git rev-parse --short HEAD 2>$null)
if (-not $CommitSHA) {
    $CommitSHA = "dev"
    Write-Warning "Not a git repo or git not found. Using 'dev' as tag."
}

$ImageName = "$Username/$RepoName"
$TagWithSHA = "${ImageName}:${CommitSHA}"
$TagLatest = "${ImageName}:latest"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Docker Hub Build & Push Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Image: $ImageName" -ForegroundColor Green
Write-Host "Tags: $CommitSHA, latest" -ForegroundColor Green
Write-Host ""

# Build the image
if (-not $SkipBuild) {
    Write-Host "[1/3] Building Docker image..." -ForegroundColor Yellow
    
    $BuildArgs = @(
        "build",
        "-t", $TagWithSHA,
        "-t", $TagLatest,
        "--file", "Dockerfile",
        "."
    )
    
    docker @BuildArgs
    
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Docker build failed with exit code $LASTEXITCODE"
        exit $LASTEXITCODE
    }
    
    Write-Host "✓ Build completed successfully" -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host "[1/3] Skipping build (--SkipBuild flag set)" -ForegroundColor Yellow
    Write-Host ""
}

# Push commit SHA tag
Write-Host "[2/3] Pushing $TagWithSHA..." -ForegroundColor Yellow
docker push $TagWithSHA

if ($LASTEXITCODE -ne 0) {
    Write-Error "Failed to push $TagWithSHA"
    exit $LASTEXITCODE
}

Write-Host "✓ Pushed $TagWithSHA" -ForegroundColor Green
Write-Host ""

# Push latest tag
if ($Latest) {
    Write-Host "[3/3] Pushing $TagLatest..." -ForegroundColor Yellow
    docker push $TagLatest
    
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Failed to push $TagLatest"
        exit $LASTEXITCODE
    }
    
    Write-Host "✓ Pushed $TagLatest" -ForegroundColor Green
} else {
    Write-Host "[3/3] Skipping latest tag (--Latest not set)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✓ All Done!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Your image is available at:" -ForegroundColor White
Write-Host "  docker pull $TagWithSHA" -ForegroundColor Cyan
Write-Host "  docker pull $TagLatest" -ForegroundColor Cyan
Write-Host ""
Write-Host "To run the image:" -ForegroundColor White
Write-Host "  docker run -d -p 8080:80 --name backend-mhealth --env-file .env.production $TagLatest" -ForegroundColor Cyan
Write-Host ""
