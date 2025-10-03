#!/bin/bash

# =========================================
# SIGAP UNDIP Next.js Deployment Script
# Ubuntu 22.04 - One Shot Deployment
# =========================================

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="sigap-undip-frontend"
APP_DOMAIN="152.42.171.87"  # Your VM IP
APP_PORT=3000
PM2_APP_NAME="sigap-frontend"
NGINX_SITE_NAME="sigap-frontend"
USER_HOME="/home/$(whoami)"
APP_DIR="$USER_HOME/apps/$APP_NAME"
GIT_REPO="https://github.com/mariosianturi19/SIGAP-UNDIP.git"

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Update system
update_system() {
    log_info "Updating system packages..."
    sudo apt update && sudo apt upgrade -y
    log_success "System updated successfully"
}

# Install Node.js 20 LTS
install_nodejs() {
    log_info "Installing Node.js 20 LTS..."
    
    # Remove any existing Node.js installations
    sudo apt remove -y nodejs npm || true
    
    # Install Node.js 20 LTS using NodeSource repository
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt-get install -y nodejs
    
    # Verify installation
    node_version=$(node --version)
    npm_version=$(npm --version)
    
    log_success "Node.js installed: $node_version"
    log_success "NPM installed: $npm_version"
}

# Install PM2
install_pm2() {
    log_info "Installing PM2..."
    sudo npm install -g pm2
    
    # Setup PM2 startup
    sudo pm2 startup systemd -u $(whoami) --hp $USER_HOME
    
    log_success "PM2 installed successfully"
}

# Install and configure Nginx
install_nginx() {
    log_info "Installing and configuring Nginx..."
    sudo apt install -y nginx
    
    # Enable and start Nginx
    sudo systemctl enable nginx
    sudo systemctl start nginx
    
    log_success "Nginx installed and started"
}

# Setup firewall
setup_firewall() {
    log_info "Configuring firewall..."
    
    # Enable UFW
    sudo ufw --force enable
    
    # Allow SSH, HTTP, HTTPS, and our app port
    sudo ufw allow ssh
    sudo ufw allow 80
    sudo ufw allow 443
    sudo ufw allow $APP_PORT
    
    log_success "Firewall configured"
}

# Create application directory
create_app_directory() {
    log_info "Creating application directory..."
    
    mkdir -p $USER_HOME/apps
    mkdir -p $APP_DIR
    
    log_success "Application directory created: $APP_DIR"
}

# Clone repository
clone_repository() {
    log_info "Cloning repository..."
    
    # Prompt for GitHub repository URL if not set
    if [[ "$GIT_REPO" == *"mariosianturi19"* ]]; then
        log_info "Using repository: $GIT_REPO"
    fi
    
    if [ -d "$APP_DIR/.git" ]; then
        log_info "Repository already exists, pulling latest changes..."
        cd $APP_DIR
        git pull origin main || git pull origin master
    else
        log_info "Cloning fresh repository..."
        git clone $GIT_REPO $APP_DIR
        cd $APP_DIR
    fi
    
    log_success "Repository cloned/updated successfully"
}

# Install dependencies and build
build_application() {
    log_info "Installing dependencies and building application..."
    
    cd $APP_DIR
    
    # Install dependencies
    npm ci --production=false
    
    # Build the application
    npm run build
    
    log_success "Application built successfully"
}

# Create environment file
create_env_file() {
    log_info "Creating environment file..."
    
    cd $APP_DIR
    
    # Create .env.local if it doesn't exist
    if [ ! -f ".env.local" ]; then
        cat > .env.local << EOF
# Next.js Environment Configuration
NODE_ENV=production
PORT=$APP_PORT

# API Configuration - Update with your Laravel backend URL
NEXT_PUBLIC_API_URL=http://$APP_DOMAIN:8000
NEXT_PUBLIC_BACKEND_URL=http://$APP_DOMAIN:8000

# App Configuration
NEXT_PUBLIC_APP_URL=http://$APP_DOMAIN:$APP_PORT
NEXT_PUBLIC_APP_NAME=SIGAP UNDIP

# Add your other environment variables here
EOF
        log_warning "Environment file created. Please update .env.local with your actual configuration."
    else
        log_info "Environment file already exists"
    fi
}

# Setup PM2 configuration
setup_pm2_config() {
    log_info "Setting up PM2 configuration..."
    
    cd $APP_DIR
    
    # Create PM2 ecosystem file
    cat > ecosystem.config.js << EOF
module.exports = {
  apps: [{
    name: '$PM2_APP_NAME',
    script: 'npm',
    args: 'start',
    cwd: '$APP_DIR',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: $APP_PORT
    },
    error_file: '$USER_HOME/logs/$PM2_APP_NAME-error.log',
    out_file: '$USER_HOME/logs/$PM2_APP_NAME-out.log',
    log_file: '$USER_HOME/logs/$PM2_APP_NAME.log',
    time: true
  }]
};
EOF
    
    # Create logs directory
    mkdir -p $USER_HOME/logs
    
    log_success "PM2 configuration created"
}

# Configure Nginx
configure_nginx() {
    log_info "Configuring Nginx..."
    
    # Create Nginx site configuration
    sudo tee /etc/nginx/sites-available/$NGINX_SITE_NAME > /dev/null << EOF
server {
    listen 80;
    server_name $APP_DOMAIN;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;
    
    # Handle Next.js static files
    location /_next/static {
        alias $APP_DIR/.next/static;
        expires 365d;
        access_log off;
    }
    
    # Handle public files
    location /public {
        alias $APP_DIR/public;
        expires 30d;
        access_log off;
    }
    
    # Handle favicon and other root files
    location ~* \.(ico|css|js|gif|jpe?g|png|svg)$ {
        root $APP_DIR/public;
        expires 30d;
        access_log off;
    }
    
    # Proxy all other requests to Next.js
    location / {
        proxy_pass http://localhost:$APP_PORT;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
        
        # Timeouts
        proxy_connect_timeout       60s;
        proxy_send_timeout          60s;
        proxy_read_timeout          60s;
    }
    
    # Health check endpoint
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
}
EOF
    
    # Enable the site
    sudo ln -sf /etc/nginx/sites-available/$NGINX_SITE_NAME /etc/nginx/sites-enabled/
    
    # Remove default site if it exists
    sudo rm -f /etc/nginx/sites-enabled/default
    
    # Test Nginx configuration
    sudo nginx -t
    
    # Reload Nginx
    sudo systemctl reload nginx
    
    log_success "Nginx configured successfully"
}

# Start application
start_application() {
    log_info "Starting application with PM2..."
    
    cd $APP_DIR
    
    # Stop existing PM2 process if running
    pm2 stop $PM2_APP_NAME 2>/dev/null || true
    pm2 delete $PM2_APP_NAME 2>/dev/null || true
    
    # Start application
    pm2 start ecosystem.config.js
    
    # Save PM2 configuration
    pm2 save
    
    log_success "Application started successfully"
}

# Create deployment script for future updates
create_update_script() {
    log_info "Creating update script..."
    
    cat > $USER_HOME/update-sigap-frontend.sh << 'EOF'
#!/bin/bash

# SIGAP Frontend Update Script
APP_DIR="/home/$(whoami)/apps/sigap-undip-frontend"
PM2_APP_NAME="sigap-frontend"

echo  "Updating SIGAP Frontend..."

cd $APP_DIR

# Pull latest changes
echo "Pulling latest changes..."
git pull origin main || git pull origin master

# Install dependencies
echo "Installing dependencies..."
npm ci --production=false

# Build application
echo "Building application..."
npm run build

# Restart PM2
echo "Restarting application..."
pm2 restart $PM2_APP_NAME

echo "Update completed successfully!"
echo "Application available at: http://152.42.171.87"
EOF
    
    chmod +x $USER_HOME/update-sigap-frontend.sh
    
    log_success "Update script created at $USER_HOME/update-sigap-frontend.sh"
}

# Display final information
display_final_info() {
    echo ""
    echo "================================================"
    log_success "SIGAP UNDIP Frontend Deployment Complete!"
    echo "================================================"
    echo ""
    log_info "Deployment Summary:"
    echo "   • Application URL: http://$APP_DOMAIN"
    echo "   • Application Directory: $APP_DIR"
    echo "   • PM2 App Name: $PM2_APP_NAME"
    echo "   • Nginx Site: $NGINX_SITE_NAME"
    echo ""
    log_info "Useful Commands:"
    echo "   • Check app status: pm2 status"
    echo "   • View app logs: pm2 logs $PM2_APP_NAME"
    echo "   • Restart app: pm2 restart $PM2_APP_NAME"
    echo "   • Update app: $USER_HOME/update-sigap-frontend.sh"
    echo "   • Check Nginx status: sudo systemctl status nginx"
    echo ""
    log_warning "Important Notes:"
    echo "   • Update .env.local file with your actual configuration"
    echo "   • Update GIT_REPO variable in this script with your repository URL"
    echo "   • Configure your Laravel backend to allow CORS for this domain"
    echo "   • Consider setting up SSL certificates for production"
    echo ""
}

# Main execution
main() {
    log_info "Starting SIGAP UNDIP Frontend deployment..."
    
    update_system
    install_nodejs
    install_pm2
    install_nginx
    setup_firewall
    create_app_directory
    clone_repository
    create_env_file
    build_application
    setup_pm2_config
    configure_nginx
    start_application
    create_update_script
    display_final_info
}

# Run main function
main "$@"