#!/usr/bin/env bash
# Activa HTTPS con Let's Encrypt para el Sistema Web Vida Salud (necesario para el microfono - CU29).
# Uso (en el servidor EC2, dentro de la carpeta del proyecto):
#   bash docker/https/activar-https.sh 18-218-0-2.sslip.io micorreo@gmail.com
# sslip.io convierte el nombre en la IP publica (18-218-0-2 -> 18.218.0.2) sin comprar un dominio.
set -euo pipefail

DOMINIO="${1:?Indique el dominio, por ejemplo 18-218-0-2.sslip.io}"
CORREO="${2:?Indique un correo para los avisos de Let's Encrypt}"
PROYECTO="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$PROYECTO"

echo "==> 1/5 Instalando certbot (si hace falta)"
if ! command -v certbot >/dev/null 2>&1; then
    sudo apt-get update -y && sudo apt-get install -y certbot
fi

echo "==> 2/5 Verificando que Nginx responde en el puerto 80"
docker compose up -d nginx
mkdir -p docker/certbot/www
curl -fsS -o /dev/null "http://${DOMINIO}/" || { echo "ERROR: http://${DOMINIO} no responde. Abra el puerto 80 en el Security Group de AWS."; exit 1; }

echo "==> 3/5 Solicitando el certificado a Let's Encrypt"
sudo certbot certonly --webroot -w "$PROYECTO/docker/certbot/www" -d "$DOMINIO" \
     --email "$CORREO" --agree-tos --non-interactive --keep-until-expiring

echo "==> 4/5 Activando el sitio HTTPS en Nginx"
sed "s/__DOMINIO__/${DOMINIO}/g" docker/nginx/laravel-ssl.conf.template > docker/nginx/conf.d/laravel-ssl.conf
docker compose up -d nginx
docker compose exec nginx nginx -t
docker compose restart nginx

echo "==> 5/5 Programando la renovacion automatica (cron diario a las 03:30)"
LINEA="30 3 * * * certbot renew --quiet --deploy-hook 'cd $PROYECTO && docker compose restart nginx'"
( sudo crontab -l 2>/dev/null | grep -v 'certbot renew' ; echo "$LINEA" ) | sudo crontab -

echo ""
echo "Listo: https://${DOMINIO}"
echo "Recuerde poner APP_URL=https://${DOMINIO} en el .env y ejecutar: docker compose exec app php artisan config:clear"
