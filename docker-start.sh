#!/bin/bash

# Restaurant Backend - Docker Startup Script

echo "🚀 Starting Restaurant Backend with Docker..."
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker daemon is not running!"
    echo "Please start Docker Desktop and try again."
    exit 1
fi

# Navigate to project directory
cd "$(dirname "$0")"

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    
    # Update .env for Docker
    echo "🔧 Configuring .env for Docker..."
    
    # Update database settings
    sed -i '' 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
    sed -i '' 's/# DB_HOST=127.0.0.1/DB_HOST=mysql/' .env
    sed -i '' 's/# DB_PORT=3306/DB_PORT=3306/' .env
    sed -i '' 's/# DB_DATABASE=laravel/DB_DATABASE=restaurant_db/' .env
    sed -i '' 's/# DB_USERNAME=root/DB_USERNAME=restaurant_user/' .env
    sed -i '' 's/# DB_PASSWORD=/DB_PASSWORD=restaurant_password/' .env
    
    # Update Redis settings
    sed -i '' 's/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/' .env
    
    # Update APP_URL
    sed -i '' 's|APP_URL=http://localhost|APP_URL=http://localhost:8881|' .env
    
    echo "✅ .env file configured for Docker"
fi

# Stop any existing containers
echo "🛑 Stopping any existing containers..."
docker-compose down 2>/dev/null

# Build and start containers
echo "🔨 Building Docker images..."
docker-compose build

echo "🚀 Starting containers..."
docker-compose up -d

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 10

# Install dependencies if vendor doesn't exist
echo "📥 Installing PHP dependencies..."
docker-compose exec apache composer install --no-interaction || echo "Dependencies may already be installed"

# Generate application key if not set
echo "🔑 Generating application key..."
docker-compose exec apache php artisan key:generate --force 2>/dev/null || echo "Key may already be set"

# Run migrations
echo "📦 Running database migrations..."
docker-compose exec apache php artisan migrate --force

# Set permissions
echo "🔐 Setting storage permissions..."
docker-compose exec apache chmod -R 775 storage bootstrap/cache
docker-compose exec apache chown -R www-data:www-data storage bootstrap/cache

echo ""
echo "✅ Docker containers are running!"
echo ""
echo "📍 Access the application at: http://localhost:8881"
echo "🗄️  MySQL is available at: localhost:3308"
echo "📊 Redis is available at: localhost:6380"
echo ""
echo "📋 Useful commands:"
echo "   - View logs: docker-compose logs -f"
echo "   - Stop containers: docker-compose down"
echo "   - Restart containers: docker-compose restart"
echo "   - Access container shell: docker-compose exec apache bash"
echo "   - Run artisan commands: docker-compose exec apache php artisan [command]"
echo ""
