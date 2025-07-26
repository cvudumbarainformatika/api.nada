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

redis-cli:
	docker compose exec redis redis-cli

redis-cli-db1:
	docker compose exec redis redis-cli -n 1

redis-flush:
	docker compose exec redis redis-cli FLUSHALL



shell:
	docker compose exec app bash

log:
	docker compose exec app tail -f storage/logs/laravel.log

down:
	docker compose down

up:
	docker compose up -d

build:
	docker compose build --no-cache

permissions:
	chmod +x fix-permissions.sh
	./fix-permissions.sh

setup:
	@make build
	@make permissions
	@make up
	docker compose exec app composer install
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan config:cache
	docker compose exec app php artisan route:cache
	docker compose exec app php artisan view:cache

re-conf:
	docker compose exec app php artisan config:cache && docker compose exec app php artisan route:cache && docker compose exec app php artisan view:cache

# swoole
swoole:
	docker compose exec app php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000 --workers=1 --task-workers=1
