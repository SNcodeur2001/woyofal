FROM php:8.3-fpm

# 🧩 Installer les dépendances
RUN apt-get update && apt-get install -y \
    nginx \
    libpq-dev \
    supervisor \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# 📁 Créer les dossiers nécessaires avec les bonnes permissions
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/www/html/public \
    && mkdir -p /run/nginx \
    && mkdir -p /var/lib/nginx/body \
    && mkdir -p /var/lib/nginx/fastcgi \
    && mkdir -p /var/lib/nginx/proxy \
    && mkdir -p /var/lib/nginx/scgi \
    && mkdir -p /var/lib/nginx/uwsgi \
    && chown -R www-data:www-data /var/lib/nginx \
    && chown -R www-data:www-data /var/log/nginx

# 📂 Définir le répertoire de travail et copier le code
WORKDIR /var/www/html
COPY . .

# 🔧 Installer Composer et les dépendances PHP
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 📄 Copier les configurations
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 📁 Supprimer la config nginx par défaut
RUN rm -f /etc/nginx/sites-enabled/default

# 📁 Définir les permissions finales
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/log \
    && chmod -R 755 /etc/nginx

# 🎯 Créer un fichier simple pour tester
RUN echo '<?php echo "PHP fonctionne!"; ?>' > /var/www/html/public/test.php

# 🚪 Exposer le port HTTP
EXPOSE 80

# 🔍 Script de sanité
HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
  CMD curl -f http://localhost:80/health || exit 1

# 🧠 Lancer Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]