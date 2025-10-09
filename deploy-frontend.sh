#!/usr/bin/env bash

set -euo pipefail

APP_NAME="sigap-undip"
APP_USER="sigap"
APP_ROOT="/opt/${APP_NAME}"
APP_REPO_DIR="${APP_ROOT}/app"
APP_LOG_DIR="${APP_ROOT}/logs"
REPO_URL="https://github.com/mariosianturi19/SIGAP-UNDIP.git"
BRANCH="main"
NODE_MAJOR="20"
APP_PORT="3000"
# Optional: set to an absolute path of a prepared env file to be copied into place.
ENV_FILE_SOURCE=""

log() {
  printf '[INFO] %s\n' "$*"
}

warn() {
  printf '[WARN] %s\n' "$*" >&2
}

error() {
  printf '[ERROR] %s\n' "$*" >&2
}

abort() {
  error "Deployment failed on line ${BASH_LINENO[0]}."
  exit 1
}

trap abort ERR

require_root() {
  if [[ "${EUID}" -ne 0 ]]; then
    error "Please run this script as root (use sudo)."
    exit 1
  fi
}

run_as_app() {
  sudo -u "${APP_USER}" -H bash -lc "$1"
}

ensure_dependencies() {
  export DEBIAN_FRONTEND=noninteractive
  log "Updating apt cache and installing base packages..."
  apt-get update -y
  apt-get install -y ca-certificates curl gnupg git build-essential
}

install_node() {
  if command -v node >/dev/null 2>&1; then
    local current_major
    current_major="$(node -p 'process.versions.node.split(".")[0]')"
    if [[ "${current_major}" -ge "${NODE_MAJOR}" ]]; then
      log "Node.js $(node -v) already satisfies the requirement."
      return
    fi
    warn "Existing Node.js version $(node -v) is older than required ${NODE_MAJOR}.x."
  fi

  log "Installing Node.js ${NODE_MAJOR}.x from NodeSource..."
  curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
  apt-get install -y nodejs
}

ensure_app_user() {
  if ! id -u "${APP_USER}" >/dev/null 2>&1; then
    log "Creating system user ${APP_USER}..."
    useradd \
      --system \
      --create-home \
      --home "${APP_ROOT}" \
      --shell /bin/bash \
      "${APP_USER}"
  fi

  mkdir -p "${APP_ROOT}" "${APP_LOG_DIR}"
  chown -R "${APP_USER}:${APP_USER}" "${APP_ROOT}"
}

clone_or_update_repo() {
  mkdir -p "${APP_REPO_DIR}"
  chown -R "${APP_USER}:${APP_USER}" "${APP_ROOT}"

  if [[ -d "${APP_REPO_DIR}/.git" ]]; then
    log "Updating existing repository in ${APP_REPO_DIR}..."
    run_as_app "cd '${APP_REPO_DIR}' && git fetch --all --prune && git checkout '${BRANCH}' && git reset --hard origin/'${BRANCH}'"
  else
    if [[ -n "$(ls -A "${APP_REPO_DIR}" 2>/dev/null)" ]]; then
      error "Target directory ${APP_REPO_DIR} is not empty and not a git repo. Aborting."
      exit 1
    fi
    log "Cloning ${REPO_URL} (branch ${BRANCH}) into ${APP_REPO_DIR}..."
    run_as_app "git clone --branch '${BRANCH}' '${REPO_URL}' '${APP_REPO_DIR}'"
  fi
}

stage_env_file() {
  local target_env
  target_env="${APP_REPO_DIR}/.env.production"

  if [[ -n "${ENV_FILE_SOURCE}" ]]; then
    if [[ ! -f "${ENV_FILE_SOURCE}" ]]; then
      error "ENV_FILE_SOURCE=${ENV_FILE_SOURCE} does not exist."
      exit 1
    fi
    log "Copying environment file from ${ENV_FILE_SOURCE}..."
    cp "${ENV_FILE_SOURCE}" "${target_env}"
    chown "${APP_USER}:${APP_USER}" "${target_env}"
    chmod 600 "${target_env}"
  else
    warn "No ENV_FILE_SOURCE provided. Make sure ${target_env} exists before starting the service."
  fi
}

install_node_modules() {
  log "Installing npm dependencies..."
  run_as_app "cd '${APP_REPO_DIR}' && npm ci"
}

build_application() {
  log "Building the Next.js application..."
  run_as_app "cd '${APP_REPO_DIR}' && npm run build"
  log "Removing development dependencies..."
  run_as_app "cd '${APP_REPO_DIR}' && npm prune --omit=dev"
}

configure_systemd_service() {
  local service_file="/etc/systemd/system/${APP_NAME}.service"
  log "Creating systemd service ${APP_NAME}.service..."

  cat > "${service_file}" <<EOF
[Unit]
Description=SIGAP UNDIP Next.js service
After=network.target
Wants=network-online.target

[Service]
Type=simple
User=${APP_USER}
Group=${APP_USER}
WorkingDirectory=${APP_REPO_DIR}
Environment=NODE_ENV=production
Environment=PORT=${APP_PORT}
EnvironmentFile=-${APP_REPO_DIR}/.env.production
ExecStart=/usr/bin/env PORT=${APP_PORT} npm start
Restart=on-failure
RestartSec=5
StandardOutput=append:${APP_LOG_DIR}/app.log
StandardError=append:${APP_LOG_DIR}/app-error.log

[Install]
WantedBy=multi-user.target
EOF

  chmod 644 "${service_file}"
  systemctl daemon-reload
  systemctl enable "${APP_NAME}.service"
  systemctl restart "${APP_NAME}.service"
  systemctl status --no-pager "${APP_NAME}.service"
}

configure_firewall() {
  if command -v ufw >/dev/null 2>&1; then
    if ufw status | grep -q inactive; then
      warn "ufw is installed but inactive. Skipping firewall rule."
    else
      log "Allowing TCP port ${APP_PORT} through ufw..."
      ufw allow "${APP_PORT}/tcp"
    fi
  fi
}

main() {
  require_root
  ensure_dependencies
  install_node
  ensure_app_user
  clone_or_update_repo
  stage_env_file
  install_node_modules
  build_application
  configure_systemd_service
  configure_firewall
  log "Deployment completed. Application should be accessible on port ${APP_PORT}."
}

main "$@"
