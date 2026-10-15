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

- Tests manuels à chaque étape : upload, analyse async, timeline, relance
- Cloisonnement : accès URL directe entre deux clients → 403 attendu
- Persistance : documents et timelines consultables après `docker compose restart`
- Volumétrie : fixtures de 120 documents pour valider la pagination

