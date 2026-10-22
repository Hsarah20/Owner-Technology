# Usage de l'IA

## Outils et méthode

1. **Conception** : je définis l'architecture (3 bus, Voter, UseCases).
2. **Génération** : l'IA produit le code répétitif.
3. **Vérification** : je teste systématiquement (SQL, logs, navigateur).
4. **Décisions** : je tranche sur les arbitrages.


## Tâches confiées à l'IA

- **Boilerplate** : entités Doctrine, getters/setters, enums
- **Templates Twig** : structure HTML des listes, formulaires, timelines
- **Stimulus controllers** : drag & drop, gestion des cibles
- **DataFixtures** : jeux de données de démo
- **Rédaction** : commentaires, sections du README

## Ce que j'ai fait moi-même

- **Architecture** : 3 bus Messenger, séparation sync/async, Voter, UseCases
- **Modélisation** : 6 entités + 4 enums
- **Sécurité** : DocumentVoter, firewall
- **Priorisation** : parcours client + admin complet plutôt qu'une couverture large
- **Décisions techniques** : AssetMapper, Flysystem, KnpPaginator
- **Vérifications** : chaque brique testée manuellement

---

## Corrections apportées manuellement

| # | Symptôme | Cause | Correction |
|---|---|---|---|
| 1 | `Undefined property: MessengerCommandBus::$commandBus` | Conflit avec la propriété privée de `HandleTrait` | Renommage en `$innerBus` |
| 2 | `Message handled zero times` sur `AnalyzeDocumentCommand` | `HandleTrait` incompatible avec un routage async | Séparation `dispatch()` (async) / `dispatchSync()` (sync) |
| 3 | `mkdir(): Permission denied` sur `var/storage` | Volume Docker créé en `root`, PHP-FPM en UID 1000 | Suppression du volume nommé + `user: HOST_UID:HOST_GID` |
| 4 | `Form responses must redirect to another location` | Turbo refuse un `render()` après POST | Redirections systématiques + `data-turbo="false"` |
| 5 | Handler exécuté deux fois sur `command.bus` | Double déclaration `#[AsMessageHandler]` + `_instanceof` | Un seul mécanisme conservé |

## Vérifications

### Tests automatisés (PHPUnit 12)

12 tests couvrent la logique critique. `make test` valide tout en 1 seconde.

**Tests unitaires (7) — `DocumentVoterTest` :**

| Cas testé | Résultat |
|---|---|
| Admin voit tous les documents | ✅ Autorisé |
| Client voit son propre document | ✅ Autorisé |
| Client étranger → document d'un autre | ❌ Refusé |
| Client sans dossier | ❌ Refusé |
| Client veut relancer une analyse (RETRY) | ❌ Refusé |
| Client édite son document (EDIT) | ✅ Autorisé |
| Anonyme | ❌ Refusé |

**Tests fonctionnels (5) — `DocumentAccessTest` :**

| Cas testé | Résultat |
|---|---|
| Client B → accès doc client A (HTTP) | 403 attendu |
| Client A → accès propre doc (HTTP) | 200 attendu |
| Client B → téléchargement doc client A | 403 attendu |
| Admin → accès page admin | 200 attendu |
| Anonyme → redirection login | 302 attendu |

Base de test isolée (`owner_test`) avec rollback automatique via Dama Doctrine Test Bundle. Aucune pollution de la base de dev.

### Tests manuels (en complément)

Les tests automatisés couvrent la logique, mais certains scénarios restent vérifiés manuellement :

- **Upload** : dépôt de 1 à N fichiers, formats PDF/JPG/PNG/DOCX/XLSX, limites 25 Mo
- **Analyse asynchrone** : dispatch → queue → worker → timeline mise à jour
- **Persistance** : documents et timelines consultables après `docker compose restart`
- **Volumétrie** : fixtures de 120 documents pour valider la pagination
- **Cloisonnement (manuel)** : navigation dans deux navigateurs (ACME + BETA) pour confirmer visuellement les 403
- **Relance admin** : clic sur "Relancer l'analyse" → nouvelle tentative en base
- **Édition nature/période** : modification via formulaire → vérification en base

