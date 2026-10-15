# OWNER — Espace documentaire (MVP)

Test technique : espace client + administrateur pour le dépôt, le suivi et l'analyse simulée de pièces comptables, juridiques et sociales.


## Le projet

OWNER accompagne des dirigeants dans l'optimisation fiscale et sociale. Ce MVP permet :

- **Au client** : déposer ses pièces, les retrouver, suivre leur analyse
- **À l'admin** : superviser plusieurs dossiers, relancer les analyses en échec

---

## Démarrage rapide

```bash
git clone https://github.com/Hsarah20/Owner-Technology.git owner-mvp
cd owner-mvp
make init
```

L'application est prête sur **https://localhost:8443**.

---

## Comptes de test

| Rôle        | Email                  | Mot de passe |
|-----------  |--- --------------      |--- ----------|
| Admin OWNER | `admin@owner.fr`       | `admin1234`  |
| Client ACME | `client-acme@owner.fr` | `client1234` |
| Client BETA | `client-beta@owner.fr` | `client1234` |

---

## Stack

| Composant          | Version                      |
|---                 |---                           |
| PHP                | 8.3                          |
| Symfony            | 7.4 LTS                      |
| PostgreSQL         | 16                           |
| Caddy              | 2.8                          |
| Turbo + Stimulus   | via `symfony/ux-turbo`       |
| AssetMapper        | pas de Node.js, pas de build |
| Messenger          | worker async                 |
| Flysystem          | stockage abstrait (S3-ready) |

---

## Documentation

| Document                                     | Contenu                           |
|----------------------------------------------|-----------------------------------|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Le pattern 3 bus, Voter, UseCases |
| [docs/AI_USAGE.md](docs/AI_USAGE.md)         | Usage de l'IA et bugs corrigés    |
| [Makefile](Makefile)                         | Toutes les commandes utiles       |

---

## Commandes les plus utilisées

```bash
make help            # liste toutes les commandes
make init            # build + up + migrate + fixtures
make logs-worker     # surveiller le worker Messenger
make reset           # repartir de zéro
make db-shell        # psql interactif
```

Voir le [Makefile](Makefile) pour la liste complète.

---

## Architecture en un coup d'œil

```
src/
├── Controller/
│   ├── Admin/             Espace admin
│   └── Client/            Espace client
├── Entity/                Client, User, Upload, Document,
│                          AnalysisAttempt, TimelineEvent
├── Messenger/             3 bus (command/query/event)
├── Security/Voter/        DocumentVoter (cloisonnement strict)
├── Service/Storage/       DocumentStorage (Flysystem)
└── UseCase/
    └── Document/
        ├── Analyze/       Analyse simulée
        ├── Retry/         Relance admin
        └── Upload/        Dépôt client
```

Détails dans [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).


---

**OWNER · Test technique · Octobre 2026**