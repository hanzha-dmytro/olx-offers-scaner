FROM php:8.1-fpm

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1001 -d /home/laravel laravel
RUN mkdir -p /home/laravel/.composer && \
    chown -R laravel:laravel /home/laravel

USER root

# Add docker php ext repo
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install php extensions
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions zip exif pcntl memcached

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    unzip \
    git \
    curl \
    lua-zlib-dev \
    libmemcached-dev

# Install PYTHON3
RUN apt-get update && apt-get install -y \
    python3 \
    python3-dev \
    python3-venv \
    build-essential \
    curl \
    ca-certificates \
 && PYTHON_PATH=$(python3 -c "import sys; print(sys.prefix)") \
 && EXTERNALLY_MANAGED=$(find $PYTHON_PATH -name EXTERNALLY-MANAGED 2>/dev/null | head -n 1) \
 && [ -z "$EXTERNALLY_MANAGED" ] || rm -f "$EXTERNALLY_MANAGED" \
 && curl -sS https://bootstrap.pypa.io/get-pip.py -o get-pip.py \
 && python3 get-pip.py \
 && rm get-pip.py \
 && python3 -m pip install --no-cache-dir --upgrade pip virtualenv supervisor \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# Install SUPERVISOR:
RUN python3 -m pip install --upgrade supervisor

# Install composer
RUN curl -ss https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install ImageMagick
RUN apt-get update; \
    apt-get install -y libmagickwand-dev; \
    pecl install imagick; \
    docker-php-ext-enable imagick;

# Set entrypoint
ENTRYPOINT ["supervisord", "-n", "-c",  "/etc/supervisord.conf"]

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Set user
USER laravel

# Set working directory
WORKDIR /var/www

# Expose port 9000 and start php-fpm server
EXPOSE 9000

