FROM php:8.3-fpm

# 🧩 Installer les dépendances
RUN apt-get update && apt-get install -y \
    nginx \
    libpq-dev \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# 📁 Créer le dossier de logs supervisord
RUN mkdir -p /var/log/supervisor

# 📄 Copier la config NGINX
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# 📄 Copier la config de Supervisor
COPY docker/nginx/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 📂 Définir le répertoire de travail et copier le code
WORKDIR /var/www/html
COPY . .

# 🔧 Installer Composer et les dépendances PHP
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# 📁 Créer les dossiers nécessaires et définir les permissions
RUN mkdir -p /var/www/html/public \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 🚪 Exposer le port HTTP
EXPOSE 80

# 🧠 Lancer NGINX + PHP-FPM avec Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]