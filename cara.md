(untuk windows jangan lupa CRLF ubah ke LF)
docker compose build --no-cache
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app chmod -R 777 storage bootstrap/cache


permissions:
    chmod +x fix-permissions.sh
    ./fix-permissions.sh



.PHONY: setup build up down logs restart shell permissions

setup:
    @make build
    @make permissions
    @make up
    docker compose exec app composer install
    docker compose exec app php artisan key:generate
    docker compose exec app php artisan config:cache
    docker compose exec app php artisan route:cache
    docker compose exec app php artisan view:cache

permissions:
    chmod +x fix-permissions.sh
    ./fix-permissions.sh

build:
    docker compose build --no-cache

up:
    docker compose up -d

down:
    docker compose down

logs:
    docker compose logs -f

restart:
    @make down
    @make up

shell:
    docker compose exec app bash


# ... REDIS ...

redis-cli:
    docker compose exec redis redis-cli

redis-flush:
    docker compose exec redis redis-cli FLUSHALL

redis-monitor:
    docker compose exec redis redis-cli MONITOR

redis-info:
    docker compose exec redis redis-cli INFO

redis-memory:
    docker compose exec redis redis-cli INFO memory

redis-clients:
    docker compose exec redis redis-cli CLIENT LIST

redis-stats:
    @echo "=== Redis Statistics ==="
    @echo "Keys in database:"
    @docker compose exec redis redis-cli DBSIZE
    @echo "\nMemory usage:"
    @docker compose exec redis redis-cli INFO | grep used_memory_human
    @echo "\nConnected clients:"
    @docker compose exec redis redis-cli INFO | grep connected_clients


# supervisor
supervisorctl status
supervisorctl reread
supervisorctl update
supervisorctl restart all



# ... UNTUK DEPLOY ...
php artisan git:deploy





curl -o /dev/null -s -w "Total: %{time_total}s\nDNS: %{time_namelookup}s\nConnect: %{time_connect}s\nTTFB: %{time_starttransfer}s\n" \
-H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTkyLjE2OC4xNTAuMTEyOjM1MDEvYXBpL3YxL2xvZ2luIiwiaWF0IjoxNzQ1NDE4MzM5LCJleHAiOjE3NDU0NDcxMzksIm5iZiI6MTc0NTQxODMzOSwianRpIjoiTk9FTnlOUkFiYTdJbjhlNiIsInN1YiI6IjEiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.gBBsj19Y0vYEuZYcGKzM1yt0tQLq_fpJEEfI6yInUJc" \
http://192.168.150.112:3501/api/simrs/master/pegawai/listnakes
