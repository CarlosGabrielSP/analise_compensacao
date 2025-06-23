#!/bin/bash

echo "Iniciando deploy do Monitor de Temperatura..."

# Construir os contêineres
docker compose build app

# Iniciar os serviços
docker compose up -d

# Gerar chave da aplicação (se ainda não existir)
echo "Gerando chave da aplicação..."
docker compose exec app php artisan key:generate --no-interaction

# Executar migrações
echo "Executando migrações..."
docker compose exec app php artisan migrate --force --seed

# Limpar cache
echo "Limpar cache..."
docker compose exec app php artisan optimize:clear

# Inicia worker de fila
echo "Inicia worker de fila..."
docker compose exec app php artisan queue:work --tries=3

echo "Deploy finalizado!"

chmod -x docker-deploy.sh
