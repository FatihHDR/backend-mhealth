# Docker Image for GitLab Container Registry

This project is now configured to build and push Docker images to GitLab Container Registry.

## 🐳 Docker Setup

### What's Included

1. **Multi-stage Dockerfile** - Optimized for production with Nginx + PHP-FPM
2. **GitLab CI/CD Pipeline** - Automated builds and pushes
3. **Supervisor** - Manages both Nginx and PHP-FPM in a single container

### Image Architecture

The Dockerfile uses a multi-stage build:
- **Stage 1**: Composer dependencies
- **Stage 2**: PHP-FPM base with extensions
- **Stage 3**: Application build with optimizations
- **Stage 4**: Production image with Nginx + Supervisor

## 📦 Manual Build and Push

### Build the Image Locally

```bash
# Build with tag
docker build -t registry.gitlab.com/your-username/backend-mhealth:latest .

# Build with specific version
docker build -t registry.gitlab.com/your-username/backend-mhealth:v1.0.0 .
```

### Push to GitLab Container Registry

```bash
# Login to GitLab Container Registry
docker login registry.gitlab.com

# Push the image
docker push registry.gitlab.com/your-username/backend-mhealth:latest

# Push specific version
docker push registry.gitlab.com/your-username/backend-mhealth:v1.0.0
```

### Test the Image Locally

```bash
# Run the container
docker run -d \
  --name laravel-app \
  -p 8080:80 \
  -e APP_ENV=production \
  -e APP_KEY=base64:YOUR_APP_KEY_HERE \
  -e DB_HOST=your-db-host \
  -e DB_DATABASE=your-db \
  -e DB_USERNAME=your-user \
  -e DB_PASSWORD=your-password \
  registry.gitlab.com/your-username/backend-mhealth:latest

# View logs
docker logs -f laravel-app

# Access the app
# Open http://localhost:8080
```

## 🔄 Automatic CI/CD with GitLab

### Setup GitLab CI/CD

1. **Enable Container Registry** in your GitLab project:
   - Go to: Settings > General > Visibility, project features, permissions
   - Enable "Container Registry"

2. **Ensure GitLab Runner** is configured with Docker executor

3. **Push to trigger build**:
   ```bash
   git add .
   git commit -m "Add Docker support"
   git push origin main
   ```

### Pipeline Behavior

- **On `main`/`master`/`develop` branches**:
  - Runs tests and quality checks
  - Builds Docker image automatically
  - Tags with commit SHA and `latest`
  - Pushes to GitLab Container Registry

- **On feature branches**:
  - Runs tests only
  - Manual trigger for Docker build
  - Tags with branch name

- **On tags** (e.g., `v1.0.0`):
  - Builds and pushes with tag name

### Registry URLs

Your images will be available at:
```
registry.gitlab.com/<your-username>/backend-mhealth:latest
registry.gitlab.com/<your-username>/backend-mhealth:<commit-sha>
registry.gitlab.com/<your-username>/backend-mhealth:<branch-name>
```

## 🚀 Deployment

### Pull and Run from Registry

```bash
# Pull the image
docker pull registry.gitlab.com/your-username/backend-mhealth:latest

# Run with docker-compose (create docker-compose.prod.yml)
docker-compose -f docker-compose.prod.yml up -d
```

### Example docker-compose.prod.yml

```yaml
version: '3.8'

services:
  app:
    image: registry.gitlab.com/your-username/backend-mhealth:latest
    ports:
      - "80:80"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_KEY=${APP_KEY}
      - DB_HOST=${DB_HOST}
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}
    restart: unless-stopped
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

volumes:
  db_data:

networks:
  app-network:
    driver: bridge
```

## 🔧 Environment Variables

Required environment variables for the container:

```env
APP_NAME=Laravel
APP_ENV=production
APP_KEY=base64:your-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Add other vars as needed
```

## 📊 Health Check

The Docker image includes a health check endpoint:
```bash
curl http://localhost/api/health
```

Make sure to create this endpoint in your Laravel app:

```php
// routes/api.php
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
```

## 🔍 Troubleshooting

### View container logs
```bash
docker logs -f <container-name>
```

### Access container shell
```bash
docker exec -it <container-name> sh
```

### Check permissions
```bash
docker exec <container-name> ls -la /var/www/html/storage
```

### Rebuild without cache
```bash
docker build --no-cache -t registry.gitlab.com/your-username/backend-mhealth:latest .
```

## 📝 Notes

- Image size is optimized using Alpine Linux
- PHP OPcache is enabled for better performance
- Logs are sent to stdout/stderr for Docker compatibility
- Both Nginx and PHP-FPM run in the same container via Supervisor
- Storage and cache permissions are set during build

## 🎯 Next Steps

1. Replace `your-username/backend-mhealth` with your actual GitLab path
2. Configure environment variables for production
3. Set up deployment automation (optional)
4. Configure load balancer/reverse proxy
5. Set up SSL/TLS certificates
