# Docker Setup Guide

## Prerequisites

1. **Docker Desktop** must be installed and running
   - Download from: https://www.docker.com/products/docker-desktop
   - Make sure Docker Desktop is started before running commands

## Quick Start

### Option 1: Using the startup script (Recommended)

```bash
./docker-start.sh
```

### Option 2: Manual setup

1. **Start Docker Desktop** (if not already running)

2. **Create .env file** (if it doesn't exist):
```bash
cp .env.example .env
```

3. **Update .env for Docker** - Make sure these settings are correct:
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=restaurant_db
DB_USERNAME=restaurant_user
DB_PASSWORD=restaurant_password

REDIS_HOST=redis
REDIS_PORT=6379

APP_URL=http://localhost:8881
```

4. **Build and start containers**:
```bash
docker-compose up -d --build
```

5. **Generate application key**:
```bash
docker-compose exec apache php artisan key:generate
```

6. **Run migrations**:
```bash
docker-compose exec apache php artisan migrate
```

7. **Install dependencies** (if vendor folder doesn't exist):
```bash
docker-compose exec apache composer install
```

8. **Set permissions**:
```bash
docker-compose exec apache chmod -R 775 storage bootstrap/cache
docker-compose exec apache chown -R www-data:www-data storage bootstrap/cache
```

## Access Points

- **Application**: http://localhost:8881
- **MySQL**: localhost:3308
- **Redis**: localhost:6380

## Docker Services

The setup includes:
- **Apache** (PHP 8.2) - Port 8881
- **MySQL 8.0** - Port 3308
- **Redis 7** - Port 6380

## Useful Commands

### View logs
```bash
docker-compose logs -f
```

### View specific service logs
```bash
docker-compose logs -f apache
docker-compose logs -f mysql
docker-compose logs -f redis
```

### Stop containers
```bash
docker-compose down
```

### Stop and remove volumes (clean slate)
```bash
docker-compose down -v
```

### Restart containers
```bash
docker-compose restart
```

### Access container shell
```bash
docker-compose exec apache bash
```

### Run Artisan commands
```bash
docker-compose exec apache php artisan [command]
```

### Run Composer commands
```bash
docker-compose exec apache composer [command]
```

### Run migrations
```bash
docker-compose exec apache php artisan migrate
```

### Run seeders
```bash
docker-compose exec apache php artisan db:seed
```

### Clear cache
```bash
docker-compose exec apache php artisan cache:clear
docker-compose exec apache php artisan config:clear
docker-compose exec apache php artisan view:clear
```

## Troubleshooting

### Docker daemon not running
- Make sure Docker Desktop is started
- Check: `docker info`

### Port already in use
- Change ports in `docker-compose.yml` if 8881, 3308, or 6380 are taken

### Permission errors
```bash
docker-compose exec apache chmod -R 775 storage bootstrap/cache
docker-compose exec apache chown -R www-data:www-data storage bootstrap/cache
```

### Database connection errors
- Wait a few seconds for MySQL to fully start
- Check MySQL logs: `docker-compose logs mysql`
- Verify .env has correct database credentials

### Container won't start
```bash
# Check logs
docker-compose logs

# Rebuild from scratch
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```
