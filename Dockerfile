# Multi-stage Dockerfile for HR Management System
FROM python:3.9-slim as python-base

# Set environment variables
ENV PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1 \
    PIP_NO_CACHE_DIR=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1

# Install system dependencies
RUN apt-get update && apt-get install -y \
    gcc \
    default-libmysqlclient-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Install Python dependencies
WORKDIR /app/python
COPY python/requirements.txt .
RUN pip install -r requirements.txt

# Copy Python application
COPY python/ .

# PHP stage
FROM php:8.1-apache as php-base

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy PHP application
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Configure Apache
RUN a2enmod rewrite
COPY .htaccess /var/www/html/

# Final stage
FROM php-base

# Install Python runtime
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    && rm -rf /var/lib/apt/lists/*

# Copy Python application from python-base stage
COPY --from=python-base /app/python /var/www/html/python

# Create Python virtual environment
RUN python3 -m venv /var/www/html/python/venv
RUN /var/www/html/python/venv/bin/pip install -r /var/www/html/python/requirements.txt

# Create startup script
RUN echo '#!/bin/bash\n\
# Start Python service in background\n\
cd /var/www/html/python && /var/www/html/python/venv/bin/python app.py &\n\
\n\
# Start Apache in foreground\n\
apache2-foreground' > /usr/local/bin/start-services.sh

RUN chmod +x /usr/local/bin/start-services.sh

# Expose ports
EXPOSE 80 5000

# Start services
CMD ["/usr/local/bin/start-services.sh"]
