# Despliegue en AWS EC2 – Sistema Web Vida Salud (Ciclo 2)

Servidor: AWS EC2 Ubuntu 24.04 con Docker Compose (contenedores `app` PHP 8.2-FPM, `scheduler`, `nginx` y `postgres` 16).

## 1. Security Group (puertos de entrada)

| Puerto | Uso |
|---|---|
| 22 | SSH (PuTTY) |
| 80 | HTTP y validación del certificado de Let's Encrypt |
| 443 | HTTPS (necesario para el micrófono del comando de voz, CU29) |
| 8000 | Acceso original `http://IP:8000` |
| 5432 | PostgreSQL para pgAdmin 4. **Recomendado:** origen = "Mi IP" en lugar de 0.0.0.0/0 |

## 2. Actualizar el código y la base de datos

```bash
cd ~/bd_vida_salud            # carpeta del proyecto en el servidor
git pull origin main
docker compose up -d --build  # levanta app, scheduler, nginx y postgres
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=PermisoSeeder --force
docker compose exec app php artisan db:seed --class=CatalogoClinicoSeeder --force
```

## 3. `.env` de producción

```bash
cp .env .env.respaldo                 # respaldo del actual
cp .env.production.example .env      # completar DB_PASSWORD, MAIL_* y APP_URL
docker compose exec app php artisan key:generate --force   # solo si APP_KEY está vacío
docker compose exec app php artisan optimize
docker compose restart scheduler
```

> Si el `.env` anterior ya tenía `APP_KEY`, cópielo al nuevo: cambiar la clave cierra las sesiones abiertas.

## 4. HTTPS con Let's Encrypt (sin comprar dominio)

`sslip.io` traduce `18-218-0-2.sslip.io` a la IP `18.218.0.2`.

```bash
bash docker/https/activar-https.sh 18-218-0-2.sslip.io sucorreo@gmail.com
```

Luego en `.env`: `APP_URL=https://18-218-0-2.sslip.io` y `docker compose exec app php artisan config:clear`.
El certificado se renueva solo (cron diario). Si la IP pública cambia (sin Elastic IP), vuelva a ejecutar el script con el nuevo nombre.

## 5. Verificación

```bash
docker compose ps                                       # 4 contenedores "Up"
docker compose exec app php artisan schedule:list       # recordatorios:enviar cada hora
docker compose exec app php artisan recordatorios:enviar
curl -I https://18-218-0-2.sslip.io/login               # HTTP/2 200
```

## 6. pgAdmin 4

Host: IP pública del servidor · Puerto: 5432 · Base: `bd_vida_salud` · Usuario: `postgres` · Contraseña: la de `POSTGRES_PASSWORD`.
