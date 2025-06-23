#!/bin/bash

# Verificar se estamos em ambiente de produção
if [ "$APP_ENV" = "production" ]; then
    echo "Ambiente de produção detectado. Otimizando aplicação..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    echo "Ambiente de desenvolvimento detectado."
fi

# Iniciar o supervisor (que gerencia nginx e php-fpm)
exec "$@"
