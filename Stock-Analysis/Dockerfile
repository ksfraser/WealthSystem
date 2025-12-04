FROM ubuntu:22.04

LABEL maintainer="Stock Analysis Team"
LABEL description="Stock Analysis Application - PHP 8.2 + Python 3.11 + Apache + MySQL"

# Prevent interactive prompts during build
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=America/New_York

# Set application variables
ENV APP_ROOT=/var/www/stock-analysis
ENV PYTHON_VERSION=3.11
ENV PHP_VERSION=8.2

# Update and install system dependencies
RUN apt-get update && apt-get install -y \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    git \
    unzip \
    wget \
    vim \
    tzdata \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Configure timezone
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# ===== APACHE INSTALLATION =====
RUN apt-get update && apt-get install -y \
    apache2 \
    apache2-utils \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite ssl headers proxy proxy_http

# ===== PHP INSTALLATION =====
RUN add-apt-repository ppa:ondrej/php -y && \
    apt-get update && \
    apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-xmlrpc \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-imagick \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-opcache \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-redis \
        libapache2-mod-php${PHP_VERSION} \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP
RUN sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/${PHP_VERSION}/apache2/php.ini && \
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/${PHP_VERSION}/apache2/php.ini && \
    sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/${PHP_VERSION}/apache2/php.ini && \
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/${PHP_VERSION}/apache2/php.ini && \
    sed -i 's/;date.timezone =.*/date.timezone = America\/New_York/' /etc/php/${PHP_VERSION}/apache2/php.ini && \
    sed -i 's/display_errors = .*/display_errors = Off/' /etc/php/${PHP_VERSION}/apache2/php.ini && \
    sed -i 's/log_errors = .*/log_errors = On/' /etc/php/${PHP_VERSION}/apache2/php.ini

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ===== PYTHON INSTALLATION =====
RUN add-apt-repository ppa:deadsnakes/ppa -y && \
    apt-get update && \
    apt-get install -y \
        python${PYTHON_VERSION} \
        python${PYTHON_VERSION}-venv \
        python${PYTHON_VERSION}-dev \
        python3-pip \
    && rm -rf /var/lib/apt/lists/*

# Create python symlink
RUN ln -sf /usr/bin/python${PYTHON_VERSION} /usr/bin/python

# ===== MYSQL CLIENT =====
RUN apt-get update && apt-get install -y \
    mysql-client \
    default-libmysqlclient-dev \
    && rm -rf /var/lib/apt/lists/*

# ===== APPLICATION SETUP =====

# Create application directory
RUN mkdir -p ${APP_ROOT} && \
    chown -R www-data:www-data ${APP_ROOT}

# Set working directory
WORKDIR ${APP_ROOT}

# Copy application files
COPY --chown=www-data:www-data . ${APP_ROOT}/

# Install PHP dependencies
RUN if [ -f composer.json ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# Create Python virtual environment and install dependencies
RUN python${PYTHON_VERSION} -m venv ${APP_ROOT}/venv && \
    ${APP_ROOT}/venv/bin/pip install --upgrade pip && \
    if [ -f requirements.txt ]; then \
        ${APP_ROOT}/venv/bin/pip install -r requirements.txt; \
    else \
        ${APP_ROOT}/venv/bin/pip install pandas numpy ta requests python-dotenv; \
    fi

# Create necessary directories
RUN mkdir -p ${APP_ROOT}/logs \
             ${APP_ROOT}/data \
             ${APP_ROOT}/temp \
             ${APP_ROOT}/uploads \
             ${APP_ROOT}/data/cache \
             ${APP_ROOT}/data/sessions && \
    chown -R www-data:www-data ${APP_ROOT} && \
    chmod -R 755 ${APP_ROOT} && \
    chmod -R 775 ${APP_ROOT}/logs \
                  ${APP_ROOT}/data \
                  ${APP_ROOT}/temp \
                  ${APP_ROOT}/uploads

# ===== APACHE CONFIGURATION =====

# Copy virtual host configuration
COPY deployment/docker-vhost.conf /etc/apache2/sites-available/stock-analysis.conf

# Enable site and disable default
RUN a2ensite stock-analysis.conf && \
    a2dissite 000-default.conf

# ===== SUPERVISOR CONFIGURATION =====

# Copy supervisor configuration
COPY deployment/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ===== ENVIRONMENT =====

# Copy .env file (if exists)
RUN if [ -f .env.example ]; then cp .env.example .env; fi

# Set environment variables
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2
ENV APACHE_PID_FILE=/var/run/apache2/apache2.pid
ENV APACHE_RUN_DIR=/var/run/apache2
ENV APACHE_LOCK_DIR=/var/lock/apache2
ENV PYTHON_PATH=${APP_ROOT}/venv/bin/python

# Create run directories
RUN mkdir -p ${APACHE_RUN_DIR} ${APACHE_LOCK_DIR} && \
    chown -R www-data:www-data ${APACHE_RUN_DIR} ${APACHE_LOCK_DIR}

# ===== HEALTHCHECK =====

HEALTHCHECK --interval=30s --timeout=3s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# ===== PORTS =====

EXPOSE 80 443

# ===== ENTRYPOINT =====

# Copy entrypoint script
COPY deployment/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Default command: start supervisor (which manages Apache and cron)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
