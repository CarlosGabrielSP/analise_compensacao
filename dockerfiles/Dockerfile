FROM php:8.4-fpm

# Argumentos definidos no docker-compose.yml
ARG user
ARG uid

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    nginx \
    supervisor \
    gnupg

# Instalar Node.js e npm
RUN curl -sL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm

# Limpar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Obter Composer mais recente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar nginx
COPY ./dockerfiles/nginx/conf.d/app.conf /etc/nginx/sites-available/default
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# Configurar supervisor
COPY ./dockerfiles/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Criar usuário do sistema para executar comandos Composer e Artisan
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

ENV TZ=America/Belem

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar composer.json e package.json primeiro para aproveitar o cache do Docker
COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

# Instalar dependências do Composer e Node.js
RUN composer install --no-scripts --no-autoloader --ansi --no-interaction
RUN npm install

# Copiar o restante dos arquivos do projeto
COPY --chown=$user:$user . /var/www

# Gerar autoloader otimizado e executar scripts do Composer
RUN composer dump-autoload --optimize --ansi --no-interaction

# Compilar assets
RUN npm run build

# Configurar permissões para o storage e bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    chown -R $user:www-data /var/www/storage /var/www/bootstrap/cache

# Copiar e tornar o script de entrypoint executável
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Definir o entrypoint
ENTRYPOINT ["docker-entrypoint"]

# Expor porta 80
EXPOSE 80

# Iniciar supervisor (que gerenciará nginx e php-fpm)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
