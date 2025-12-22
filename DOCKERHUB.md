# Docker Hub Deployment Guide

## Quick Start (Local Build & Push)

### 1. Login to Docker Hub
```powershell
docker login
```

### 2. Build and Push Using Script
```powershell
.\build-dockerhub.ps1 -Username "your-dockerhub-username"
```

Or manually:
```powershell
# Get commit SHA
$sha = git rev-parse --short HEAD

# Build
docker build -t your-username/backend-mhealth:latest -t your-username/backend-mhealth:$sha .

# Push
docker push your-username/backend-mhealth:$sha
docker push your-username/backend-mhealth:latest
```

### 3. Run the Image
```powershell
# Copy environment template
Copy-Item .env.docker .env.production

# Edit .env.production with your values
notepad .env.production

# Run container
docker run -d `
  --name backend-mhealth `
  -p 8080:80 `
  --env-file .env.production `
  your-username/backend-mhealth:latest
```

## GitLab CI/CD Automated Push

### Setup CI Variables

In your GitLab project, go to **Settings > CI/CD > Variables** and add:

| Variable | Value | Protected | Masked |
|----------|-------|-----------|--------|
| `DOCKERHUB_USERNAME` | your-dockerhub-username | ✓ | - |
| `DOCKERHUB_TOKEN` | your-access-token | ✓ | ✓ |

**Get Docker Hub Access Token:**
1. Go to https://hub.docker.com/settings/security
2. Click "New Access Token"
3. Name: `gitlab-ci`
4. Permissions: Read, Write, Delete
5. Copy the token (you won't see it again)

### Trigger Build

Push to `main` or `master` branch:
```bash
git add .
git commit -m "Deploy to Docker Hub"
git push origin main
```

The pipeline will automatically:
- Build the Docker image
- Push to **Docker Hub** with tags: `<commit-sha>` and `latest`
- Push to **GitLab Container Registry** (if configured)

## Pull and Deploy

### On Any Server
```bash
# Pull from Docker Hub
docker pull your-username/backend-mhealth:latest

# Run with docker-compose (create docker-compose.prod.yml first)
docker-compose -f docker-compose.prod.yml up -d
```

### Example docker-compose.prod.yml
```yaml
version: '3.8'

services:
  app:
    image: your-username/backend-mhealth:latest
    ports:
      - "80:80"
    env_file:
      - .env.production
    restart: unless-stopped
    depends_on:
      - db
      - redis
    networks:
      - app-network

  db:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/postgresql/data
    networks:
      - app-network

  redis:
    image: redis:7-alpine
    networks:
      - app-network

volumes:
  db_data:

networks:
  app-network:
    driver: bridge
```

## Script Options

```powershell
# Build and push with custom repo name
.\build-dockerhub.ps1 -Username "myuser" -RepoName "my-laravel-app"

# Skip build (if already built)
.\build-dockerhub.ps1 -Username "myuser" -SkipBuild

# Don't tag as latest
.\build-dockerhub.ps1 -Username "myuser" -Latest:$false
```

## Verify Deployment

```bash
# Check container logs
docker logs -f backend-mhealth

# Check container health
docker ps

# Access the app
curl http://localhost:8080/api/health
```

## Troubleshooting

### Build fails
- Ensure Docker Desktop is running
- Check Dockerfile syntax
- Review `.dockerignore` to ensure needed files aren't excluded

### Push fails (unauthorized)
- Re-run `docker login`
- Check your username/password
- For CI: verify DOCKERHUB_TOKEN is valid

### Container crashes
- Check logs: `docker logs backend-mhealth`
- Verify `.env.production` has all required variables
- Ensure `APP_KEY` is set
- Check database connection

## Multi-Architecture Build (Optional)

Build for both AMD64 and ARM64:

```powershell
# Create buildx builder
docker buildx create --use --name multiarch

# Build and push multi-arch
docker buildx build `
  --platform linux/amd64,linux/arm64 `
  -t your-username/backend-mhealth:latest `
  -t your-username/backend-mhealth:$(git rev-parse --short HEAD) `
  --push .
```

This creates images that work on:
- x86_64 servers (AWS, DigitalOcean, etc.)
- ARM servers (AWS Graviton, Oracle Cloud ARM, Raspberry Pi)
