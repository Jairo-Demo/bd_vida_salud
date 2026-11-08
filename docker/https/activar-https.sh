#!/usr/bin/env bash
set -euo pipefail

DOMINIO="${1:-18-218-0-2.sslip.io}"
CORREO="${2:-47896jhonpro@gmail.com}"
PROYECTO=$(pwd)

echo "==> 1/4 Instalando certbot"
which certbot >/dev/null 2>&1 || sudo apt-get install -y certbot

echo "==> 2/4 Solicitando certificado SSL para $DOMINIO"
mkdir -p "$PROYECTO/docker/certbot/www"
sudo certbot certonly --webroot -w "$PROYECTO/docker/certbot/www" -d "$DOMINIO" --email "$CORREO" --agree-tos --non-interactive --keep-until-expiring

echo "==> 3/4 Configurando Nginx con SSL"
sed "s/__DOMINIO__/${DOMINIO}/g" "$PROYECTO/docker/nginx/laravel-ssl.conf.template" > "$PROYECTO/docker/nginx/conf.d/laravel-ssl.conf"
docker compose restart nginx

echo "==> 4/4 Actualizando APP_URL a HTTPS"
sed -i "s|^APP_URL=.*|APP_URL=https://${DOMINIO}|g" "$PROYECTO/.env"
docker compose exec app php artisan config:clear

echo "Certificado activado con exito: https://${DOMINIO}"
