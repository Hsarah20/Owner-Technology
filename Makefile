# ============================================================
#  Variables d'environnement
# ============================================================
export APP_SECRET := $(shell [ -f .env ] && grep -E '^APP_SECRET=' .env | cut -d'=' -f2 || echo dev-secret)
export HOST_UID := $(shell id -u)
export HOST_GID := $(shell id -g)

COMPOSE      := docker compose
PHP          := $(COMPOSE) exec php
CONSOLE      := $(PHP) php bin/console
COMPOSER     := $(PHP) composer
DB           := $(COMPOSE) exec database
DC           := $(PHP) php -d memory_limit=-1

.DEFAULT_GOAL := help
.PHONY: help up down restart build rebuild logs logs-php logs-worker logs-db \
        sh worker bash composer console migrate migrate-diff migrate-status \
        fixtures fixtures-append db db-shell db-dump db-restore \
        test-upload test-analyze test-retry cs cs-fix stan \
        assets assets-clear cache cache-clear prod-check reset reset-hard \
        init demo

# ---------- AIDE ----------
help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
	  sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2}'

# ---------- DOCKER ----------
up: ## Démarre tous les conteneurs
	$(COMPOSE) up -d
	@echo "→ App : https://localhost:8443"
	@echo "→ pgAdmin : http://localhost:8081"
	@echo "→ Mailpit : http://localhost:8026"

down: ## Arrête les conteneurs (garde les volumes)
	$(COMPOSE) down

down-v: ## Arrête et supprime les volumes (⚠️ vide la base)
	$(COMPOSE) down -v --remove-orphans

restart: ## Redémarre php + worker
	$(COMPOSE) restart php worker

build: ## Build les images sans cache
	$(COMPOSE) build

rebuild: ## Rebuild + relance
	$(COMPOSE) build --no-cache
	$(COMPOSE) up -d --force-recreate

ps: ## État des conteneurs
	$(COMPOSE) ps

logs: ## Logs de tous les services
	$(COMPOSE) logs -f

logs-php: ## Logs du conteneur php
	$(COMPOSE) logs -f php

logs-worker: ## Logs du worker Messenger
	$(COMPOSE) logs -f worker

logs-db: ## Logs de PostgreSQL
	$(COMPOSE) logs -f database

sh: ## Ouvre un shell dans le conteneur php
	$(PHP) sh

bash: ## Ouvre un bash dans le conteneur php
	$(PHP) bash

# ---------- COMPOSER / SYMFONY ----------
composer: ## Lance composer (ex: make composer CMD="require xxx")
	$(COMPOSER) $(CMD)

console: ## Liste les commandes Symfony
	$(CONSOLE) list

cc: ## Vide le cache Symfony
	$(CONSOLE) cache:clear

# ---------- BASE DE DONNÉES ----------
migrate: ## Applique les migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

migrate-diff: ## Crée une migration à partir des entités
	$(CONSOLE) make:migration

migrate-status: ## État des migrations
	$(CONSOLE) doctrine:migrations:status

schema-validate: ## Valide le mapping Doctrine
	$(CONSOLE) doctrine:schema:validate

fixtures: ## Charge les fixtures (vide la base)
	$(CONSOLE) doctrine:fixtures:load --no-interaction

fixtures-append: ## Ajoute les fixtures sans purger
	$(CONSOLE) doctrine:fixtures:load --no-interaction --append

db-shell: ## Ouvre psql sur la base OWNER
	$(DB) psql -U owner -d owner

db-tables: ## Liste les tables
	$(DB) psql -U owner -d owner -c "\dt"

db-docs: ## Affiche les 5 derniers documents
	$(DB) psql -U owner -d owner -c "SELECT id, original_name, status FROM document ORDER BY id DESC LIMIT 5;"

db-attempts: ## Affiche les 5 dernières tentatives
	$(DB) psql -U owner -d owner -c "SELECT id, document_id, attempt_number, status, failure_reason FROM analysis_attempt ORDER BY id DESC LIMIT 5;"

db-timeline: ## Affiche les 10 derniers événements de timeline
	$(DB) psql -U owner -d owner -c "SELECT id, document_id, type, message FROM timeline_event ORDER BY id DESC LIMIT 10;"

db-queue: ## Affiche les messages Messenger en attente
	$(DB) psql -U owner -d owner -c "SELECT id, queue_name, available_at FROM messenger_messages;"

db-dump: ## Sauvegarde la base dans var/backup.sql
	@mkdir -p var/backup
	$(DB) pg_dump -U owner owner > var/backup/owner-$$(date +%Y%m%d-%H%M%S).sql
	@ls -la var/backup/

# ---------- TESTS MANUELS ----------
test-upload: ## Crée un document de test (client ACME)
	$(CONSOLE) app:seed-test-document

test-analyze: ## Lance une analyse sur un document (ex: make test-analyze ID=42)
	$(CONSOLE) app:test-analyze $(ID)

test-workflow: ## Test end-to-end : seed + analyse + log
	$(CONSOLE) app:seed-test-document
	@echo "→ Vérifie les logs : make logs-worker"

# ---------- QUALITÉ ----------
cs: ## Vérifie le style de code
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Corrige le style de code
	$(PHP) vendor/bin/php-cs-fixer fix

stan: ## Analyse statique PHPStan
	$(PHP) vendor/bin/phpstan analyse --memory-limit=512M

# ---------- ASSETS ----------
assets: ## Compile les assets (dev inutile, prod OK)
	$(CONSOLE) asset-map:compile

assets-clear: ## Supprime les assets compilés
	rm -rf public/assets

cache-clear: ## Vide tous les caches Symfony
	$(CONSOLE) cache:clear
	$(CONSOLE) cache:warmup

# ---------- PROD ----------
prod-check: ## Vérifie la config prod
	$(CONSOLE) cache:warmup --env=prod
	$(CONSOLE) lint:container
	$(CONSOLE) doctrine:schema:validate --env=prod

# ---------- INIT / RESET ----------
init: ## Init complète du projet (build + up + migrate + fixtures)
	@if [ ! -f .env ]; then \
		echo "→ Création du .env depuis .env.dist"; \
		cp .env.dist .env; \
	fi
	@echo "→ Build des images..."
	$(COMPOSE) build
	@echo "→ Démarrage..."
	$(COMPOSE) up -d
	@sleep 5
	@echo "→ Migrations..."
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	@echo "→ Fixtures..."
	$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo "→ Cache clear..."
	$(CONSOLE) cache:clear
	@echo ""
	@echo "✅ Projet prêt : https://localhost:8443"

reset: ## Remet la base à zéro (migrate + fixtures)
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo "✅ Base réinitialisée"

reset-hard: ## Reset + suppression des volumes Docker (⚠️ tout efface)
	$(COMPOSE) down -v --remove-orphans
	$(COMPOSE) up -d
	@sleep 8
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo "✅ Environnement complètement réinitialisé"

demo: ## Prépare une démo (fixtures + 1 document + analyse)
	$(CONSOLE) doctrine:fixtures:load --no-interaction
	$(CONSOLE) app:seed-test-document
	@echo "→ Lancer l'analyse via : make test-analyze ID=<id affiché ci-dessus>"
	@echo "→ Comptes de test :"
	@echo "    Admin : admin@owner.fr / admin1234"
	@echo "    Client ACME : client-acme@owner.fr / client1234"
	@echo "    Client BETA : client-beta@owner.fr / client1234"

# ---------- SCÉNARIO COMPLET DE DÉMO ----------
demo-full: ## Démo complète : reset + seed + analyse + logs
	@echo "=== RESET ==="
	$(MAKE) reset
	@echo ""
	@echo "=== SEED ==="
	$(CONSOLE) app:seed-test-document
	@echo ""
	@echo "=== FIXTURES VOLUMÉTRIE ==="
	$(CONSOLE) doctrine:fixtures:load --no-interaction --append
	@echo ""
	@echo "=== WORKER EN ARRIÈRE-PLAN ==="
	@echo "→ Ouvre un autre terminal : make logs-worker"
	@echo "→ Puis lance : make test-analyze ID=<id>"
	@echo ""
	@echo "✅ Prêt pour la démo"