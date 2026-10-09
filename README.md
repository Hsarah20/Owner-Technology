# OWNER — Espace documentaire (MVP)

Test technique : espace client + admin pour le dépôt et le suivi d'analyse de pièces.

## Stack technique

| Composant | Version |
|---|---|
| PHP | 8.3 |
| Symfony | 7.4 LTS |
| PostgreSQL | 16 |
| Caddy | 2.8 |
| Turbo + Stimulus | via `symfony/ux-turbo` |
| AssetMapper | remplace Webpack (pas de Node.js) |
| Messenger | transport Doctrine (worker dédié) |
| Mailpit | serveur SMTP de dev |
| pgAdmin | interface web PostgreSQL |

## Prérequis

- Docker Engine ≥ 24
- Docker Compose v2.20+

Aucun PHP, Composer ou Node.js n'est nécessaire sur la machine hôte : tout tourne dans Docker.

## Démarrage rapide

```bash
# 1. Cloner le projet
git clone <url-du-repo> owner-mvp
cd owner-mvp

# 2. Créer le fichier .env (déjà fourni, à adapter si besoin)
#    Par défaut : APP_SECRET=change-me-in-local-only

# 3. Lancer la stack
export APP_SECRET=dev-secret
docker compose build
docker compose up -d

# 4. Vérifier
docker compose ps
# 6 conteneurs doivent être "Up" (dont database et mailer "healthy")
```

## Accès aux services

| Service | URL | Identifiants |
|---|---|---|
| Application | https://localhost:8443 | — |
| Mailpit (emails) | http://localhost:8026 | — |
| pgAdmin | http://localhost:8081 | `admin@yopmail.com` / `admin` |
| PostgreSQL | `localhost:5433` | `owner` / `owner` / db `owner` |

⚠️ Le certificat HTTPS est auto-signé en dev. Accepte l'avertissement dans le navigateur.

## Commandes utiles

```bash
# Console Symfony
docker compose exec php php bin/console

# Composer
docker compose exec php composer <cmd>

# Migrations
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console doctrine:migrations:status

# Base de données (SQL direct)
docker compose exec php php bin/console dbal:run-sql "SELECT version()"
docker compose exec database psql -U owner -d owner

# Logs
docker compose logs -f php
docker compose logs -f worker
docker compose logs -f caddy

# Redémarrer un service
docker compose restart php
docker compose restart worker
```

## Arrêt et nettoyage

```bash
# Arrêter sans perdre les données (base, storage)
docker compose down

# Tout supprimer, repartir de zéro
docker compose down -v --remove-orphans
docker volume ls | grep owner-mvp   # vérifier qu'il ne reste rien
```

## Architecture Docker

| Conteneur | Rôle |
|---|---|
| `owner_php` | PHP-FPM 8.3, exécute Symfony |
| `owner_worker` | Consomme les messages Messenger asynchrones |
| `owner_caddy` | Reverse proxy HTTPS, sert `/public` |
| `owner-mvp-database-1` | PostgreSQL 16 |
| `owner_mailer` | Mailpit, capture les emails de dev |
| `owner_pgadmin` | Interface web d'administration PostgreSQL |

Tous les services partagent le réseau Docker `owner-mvp_default`, ce qui permet la résolution DNS interne (`database`, `php`, `mailer`, etc.).

## Environnements

| Environnement | Fichiers chargés | Commande |
|---|---|---|
| Dev | `docker-compose.yml` + `docker-compose.override.yml` (auto) | `docker compose up -d` |
| Prod | `docker-compose.yml` + `docker-compose.prod.yml` | `docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d` |

## Connexion à la base depuis pgAdmin

Une fois connecté à http://localhost:8081 :

1. Clic droit sur **Servers** → **Register** → **Server…**
2. Onglet **General** : Name = `OWNER`
3. Onglet **Connection** :
   - Host name/address : `database` (⚠️ pas `localhost`)
   - Port : `5432`
   - Maintenance database : `owner`
   - Username : `owner`
   - Password : `owner`
   - Cocher **Save password**
4. **Save**

> Dans pgAdmin, `database` correspond au nom du service Docker. `localhost` pointerait vers le conteneur pgAdmin lui-même, ce qui échouerait.


## Structure du projet

```
owner-mvp/
├── assets/                   # JS/CSS (AssetMapper)
├── bin/console
├── config/                   # Configuration Symfony
├── docker/                   # Fichiers Docker (php, caddy)
├── migrations/               # Migrations Doctrine
├── public/index.php
├── src/                      # Code métier
├── templates/                # Templates Twig
├── var/                      # Cache, logs, storage
├── composer.json
├── docker-compose.yml        # Base
├── docker-compose.override.yml  # Dev
├── docker-compose.prod.yml   # Prod
├── Dockerfile                # Multi-stage PHP-FPM + Caddy
├── importmap.php             # Déclaration des JS
└── .env
```

