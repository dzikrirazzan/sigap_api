#!/bin/bash

# Emergency API Management Script
# Script untuk manage aplikasi yang sudah di-deploy

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Variabel
PROJECT_DIR="/var/www/Emergency_API"
DOCKER_COMPOSE_FILE="docker-compose.yml"

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_menu() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE} Emergency API Management Menu ${NC}"
    echo -e "${BLUE}================================${NC}"
    echo ""
    echo "1) Start all services"
    echo "2) Stop all services"
    echo "3) Restart all services"
    echo "4) View all logs"
    echo "5) View backend logs"
    echo "6) Check container status"
    echo "7) Update from Git"
    echo "8) Rebuild containers"
    echo "9) Run Laravel commands"
    echo "10) Database backup"
    echo "11) Database restore"
    echo "12) Clear Laravel cache"
    echo "13) Monitor resources"
    echo "14) Access container shell"
    echo "0) Exit"
    echo ""
}

start_services() {
    print_status "Starting all services..."
    cd $PROJECT_DIR
    docker-compose -f $DOCKER_COMPOSE_FILE up -d
    print_status "Services started!"
}

stop_services() {
    print_status "Stopping all services..."
    cd $PROJECT_DIR
    docker-compose -f $DOCKER_COMPOSE_FILE down
    print_status "Services stopped!"
}

restart_services() {
    print_status "Restarting all services..."
    cd $PROJECT_DIR
    docker-compose -f $DOCKER_COMPOSE_FILE restart
    print_status "Services restarted!"
}

view_logs() {
    local service=$1
    cd $PROJECT_DIR
    if [ -z "$service" ]; then
        docker-compose -f $DOCKER_COMPOSE_FILE logs -f
    else
        docker-compose -f $DOCKER_COMPOSE_FILE logs -f $service
    fi
}

check_status() {
    print_status "Container status:"
    cd $PROJECT_DIR
    docker-compose -f $DOCKER_COMPOSE_FILE ps
    echo ""
    print_status "Resource usage:"
    docker stats --no-stream
}

update_from_git() {
    print_status "Updating from Git..."
    cd $PROJECT_DIR
    git pull origin main
    
    print_status "Rebuilding backend..."
    docker-compose -f $DOCKER_COMPOSE_FILE build app
    
    print_status "Restarting services..."
    docker-compose -f $DOCKER_COMPOSE_FILE up -d
    
    print_status "Running Laravel maintenance commands..."
    docker-compose -f $DOCKER_COMPOSE_FILE exec app composer install --no-dev --optimize-autoloader
    docker-compose -f $DOCKER_COMPOSE_FILE exec app php artisan migrate --force
    docker-compose -f $DOCKER_COMPOSE_FILE exec app php artisan config:cache
    docker-compose -f $DOCKER_COMPOSE_FILE exec app php artisan route:cache
    docker-compose -f $DOCKER_COMPOSE_FILE exec app php artisan view:cache
    
    print_status "Update completed!"
}

rebuild_containers() {
    print_status "Rebuilding all containers..."
    cd $PROJECT_DIR
    docker-compose -f $DOCKER_COMPOSE_FILE down
    docker-compose -f $DOCKER_COMPOSE_FILE build --no-cache
    docker-compose -f $DOCKER_COMPOSE_FILE up -d
    print_status "Containers rebuilt!"
}

run_laravel_command() {
    echo "Enter Laravel artisan command (without 'php artisan'):"
    read -r command
    cd $PROJECT_DIR
    docker-compose -f $DOCKER_COMPOSE_FILE exec app php artisan $command
}

backup_database() {
    local backup_name="emergency_backup_$(date +%Y%m%d_%H%M%S).sql"
    print_status "Creating database backup: $backup_name"
    cd $PROJECT_DIR
    docker-compose -f $DOCKER_COMPOSE_FILE exec db mysqldump -u emergency_user -pemergency_password_2024 emergency_api > $backup_name
    print_status "Backup created: $backup_name"
}

restore_database() {
    echo "Enter backup file name:"
    read -r backup_file
    if [ -f "$backup_file" ]; then
        print_status "Restoring database from: $backup_file"
        cd $PROJECT_DIR
        docker-compose -f $DOCKER_COMPOSE_FILE exec -T db mysql -u emergency_user -pemergency_password_2024 emergency_api < $backup_file
        print_status "Database restored!"
    else
        print_error "Backup file not found: $backup_file"
    fi
}

clear_cache() {
    print_status "Clearing Laravel cache..."
    cd $PROJECT_DIR
    docker-compose -f $DOCKER_COMPOSE_FILE exec app php artisan cache:clear
    docker-compose -f $DOCKER_COMPOSE_FILE exec app php artisan config:clear
    docker-compose -f $DOCKER_COMPOSE_FILE exec app php artisan route:clear
    docker-compose -f $DOCKER_COMPOSE_FILE exec app php artisan view:clear
    print_status "Cache cleared!"
}

monitor_resources() {
    print_status "Real-time resource monitoring (Press Ctrl+C to exit)"
    docker stats
}

access_shell() {
    echo "Select container:"
    echo "1) Backend (Laravel)"
    echo "2) Database (MySQL)"
    echo "3) Redis"
    read -r choice
    
    cd $PROJECT_DIR
    case $choice in
        1) docker-compose -f $DOCKER_COMPOSE_FILE exec app bash ;;
        2) docker-compose -f $DOCKER_COMPOSE_FILE exec db mysql -u emergency_user -pemergency_password_2024 emergency_api ;;
        3) docker-compose -f $DOCKER_COMPOSE_FILE exec redis redis-cli -a redis_emergency_2024 ;;
        *) print_error "Invalid choice" ;;
    esac
}

# Main menu loop
while true; do
    print_menu
    echo -n "Select option: "
    read -r choice
    echo ""
    
    case $choice in
        1) start_services ;;
        2) stop_services ;;
        3) restart_services ;;
        4) view_logs ;;
        5) view_logs "app" ;;
        6) check_status ;;
        7) update_from_git ;;
        8) rebuild_containers ;;
        9) run_laravel_command ;;
        10) backup_database ;;
        11) restore_database ;;
        12) clear_cache ;;
        13) monitor_resources ;;
        14) access_shell ;;
        0) print_status "Goodbye!"; exit 0 ;;
        *) print_error "Invalid option" ;;
    esac
    
    echo ""
    echo "Press Enter to continue..."
    read -r
    clear
done