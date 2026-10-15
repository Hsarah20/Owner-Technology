# Architecture

## Vue d'ensemble

Le projet applique une **séparation en couches légère**:

```
src/
├── Command/              Commandes CLI (seed, tests)
├── Controller/
│   ├── Admin/            Espace admin
│   └── Client/           Espace client
├── DataFixtures/         Données de démo
├── Entity/               6 entités Doctrine
├── Enum/                 4 enums typés
├── Messenger/            3 bus (command/query/event)
├── Repository/           Requêtes Doctrine
├── Security/Voter/       DocumentVoter (cloisonnement)
├── Service/Storage/      DocumentStorage (Flysystem)
└── UseCase/
    └── Document/
        ├── Analyze/      Analyse simulée
        ├── Retry/        Relance admin
        └── Upload/       Dépôt client
```

## Principes appliqués

1. **Une intention = une classe** (UseCase)
2. **Le métier ne connaît pas HTTP** (contrôleurs minces)
3. **Le stockage est abstrait** (Flysystem → S3-ready)
4. **La sécurité est centralisée** (Voter)
5. **Le long est asynchrone** (worker Messenger)

---

## Le pattern 3 bus Messenger

### Pourquoi 3 bus ?

Symfony Messenger permet de déclarer **plusieurs bus** avec des middlewares différents. Séparer en 3 apporte :

| Bus           | Middleware                        | Rôle           |
|---------------|-----------------------------------|----------------|
| `command.bus` | validation + doctrine_transaction | Modifie l'état |
| `query.bus`   | validation                        | Lit l'état     |
| `event.bus`   | validation + allow_no_handlers    | Notifie        |

### Ce que ça apporte

1. **Clarté sémantique** : un `Command` exprime une intention (« analyser ce document »), un `Event` un fait passé (« analyse terminée »).
2. **Middlewares adaptés** : `doctrine_transaction` uniquement sur `command.bus` — une commande qui échoue rollback, un event non.
3. **Auto-tag** via `services.yaml` : un handler qui implémente l'interface marker est automatiquement enregistré sur le bon bus.

```yaml
_instanceof:
    App\Messenger\Command\CommandHandler:
        tags: [{ name: messenger.message_handler, bus: command.bus }]
    App\Messenger\Query\QueryHandler:
        tags: [{ name: messenger.message_handler, bus: query.bus }]
    App\Messenger\Event\EventListener:
        tags: [{ name: messenger.message_handler, bus: event.bus }]
```

Tout handler qui implémente l'interface marker est **automatiquement** enregistré sur le bon bus.

### Sync vs Async

Le `CommandBus` expose **deux méthodes** :

- `dispatch()` : fire & forget, accepte les messages routés vers `async`
- `dispatchSync()` : retourne la valeur du handler (utilise `HandleTrait`)

| Cas | Méthode | Raison |
|---|---|---|
| Upload (l'utilisateur attend) | `dispatchSync()` | Retourne les `Document[]` créés |
| Analyse (long) | `dispatch()` | Fire & forget, va en queue |
| Relance admin | `dispatch()` | Redispatch un `AnalyzeDocumentCommand` async |

### Le worker Messenger

Service `owner_worker` (conteneur dédié) exécute :

```bash
php bin/console messenger:consume async --time-limit=3600 -vv
```

**Pourquoi un worker séparé ?**

- L'analyse simulée prend 3 à 8 secondes.
- Sans worker : l'utilisateur attend devant une page blanche.
- Avec worker : réponse **immédiate** (~50 ms), traitement en arrière-plan.

---

## Modèle de données

| Entité            | Rôle                                  |
|-------------------|---------------------------------------|
| `Client`          | Société cliente (personne morale)     |
| `User`            | Personne physique (dirigeant, admin)  |
| `Upload`          | Lot de dépôt (1 envoi = N fichiers)   |
| `Document`        | Pièce individuelle                    |
| `AnalysisAttempt` | Tentative d'analyse (succès ou échec) |
| `TimelineEvent`   | Événement horodaté sur un document    |

**Relations** :

- `Client` 1—N `User` (nullable : l'admin n'a pas de client)
- `Client` 1—N `Upload` 1—N `Document`
- `Document` 1—N `AnalysisAttempt`
- `Document` 1—N `TimelineEvent`

---

## Sécurité et cloisonnement

Le composant Security est utilisé **au-delà du firewall** via un Voter :

```php
class DocumentVoter extends Voter
{
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        if (in_array(User::ROLE_ADMIN, $user->getRoles(), true)) return true;

        $document = $subject;
        if ($document->getClient() !== $user->getClient()) return false;

        if ($attribute === self::RETRY) return false;

        return true;
    }
}
```

Utilisation :

```php
$this->denyAccessUnlessGranted(DocumentVoter::VIEW, $document);
```

**Ce que ça garantit** :

- Un client **ne peut pas** accéder à un document d'un autre client, même via URL directe.
- Un client **ne peut pas** déclencher une relance.

---

## Abstraire le stockage (Flysystem)

Sans abstraction, `store()` serait couplé au disque :

```php
// ❌ Couplé au disque
$path = '/var/storage/' . uniqid() . '.pdf';
move_uploaded_file($file->getRealPath(), $path);
```

Avec Flysystem, `DocumentStorage` ne connaît que l'interface :

```php
// ✅ Agnostic du backend
public function __construct(
    private FilesystemOperator $documentsStorage,
) {}

public function store(UploadedFile $file, ...): string
{
    $this->documentsStorage->writeStream($path, $stream);
    return $path;
}
```

**Changer local → S3, c'est 4 lignes de YAML** :

```yaml
flysystem:
    storages:
        documents.storage:
            adapter: 'aws'    # ← au lieu de 'local'
            options:
                client: 'aws_client_service'
                bucket: '%env(S3_BUCKET)%'
```

==> Zéro ligne de PHP touchée.
    
## Volumétrie et historique

Le cahier des charges mentionne ~50 documents/mois/client, soit 600/an et 3 000 sur 5 ans.

**Stratégie actuelle** :
- Pagination serveur (20 par page)
- Tri par date de dépôt DESC
- Limite configurable via `?limit=`

**Limite connue** : le client ne peut pas filtrer par période (mois/année) ni par nature.
Avec 3 000 documents, la navigation devient pénible.

**Prochaine priorité** : filtre année + mois + nature côté client, en réutilisant
le pattern déjà implémenté côté admin (`DocumentRepository::paginateForAdmin`).
Le repository exposerait `findAvailableYears()` pour ne proposer que les années
réellement présentes dans le dossier du client.