## ADDED Requirements

### Requirement: Suite de tests PHP pour l'upload média
Le bundle MUST fournir une suite de tests PHPUnit exécutable via `composer test` couvrant la logique serveur d'upload monolithique et fragmenté.

#### Scenario: Tests unitaires UploadLimitResolver
- **WHEN** `composer test` est exécuté
- **THEN** les tests vérifient le calcul de `maxSafeChunkSize` à partir des limites PHP simulées
- **AND** les tests vérifient que `resolve` borne `chunk_size` et `chunk_threshold` au minimum de 256 Ko

#### Scenario: Tests unitaires ChunkUploadManager
- **WHEN** `composer test` est exécuté
- **THEN** les tests couvrent la réception séquentielle de fragments valides jusqu'à l'assemblage final
- **AND** les tests rejettent un `uploadId` invalide, un fragment hors séquence et des métadonnées incohérentes

#### Scenario: Tests fonctionnels des endpoints upload
- **WHEN** `composer test` est exécuté
- **THEN** un upload monolithique valide retourne `success: true` avec `name`, `path`, `mimeType`, `size` et `url`
- **AND** une requête sans fichier retourne HTTP 400
- **AND** un upload fragmenté complet enregistre le fichier assemblé sur le filesystem de test

### Requirement: Suite de tests JavaScript pour le contrôleur d'upload
Le bundle MUST fournir une suite de tests Vitest exécutable via `npm test` couvrant le comportement du contrôleur Stimulus `filemanager-upload`.

#### Scenario: Filtrage des types de médias
- **WHEN** `npm test` est exécuté
- **THEN** les tests vérifient que seuls les fichiers `image/*` et `video/*` sont acceptés dans la file d'upload
- **AND** les autres types affichent une erreur sans être mis en file

#### Scenario: Bascule monolithique versus fragmenté
- **WHEN** `npm test` est exécuté
- **THEN** un fichier dont la taille est inférieure ou égale à `chunkThreshold` déclenche un POST vers l'URL monolithique
- **AND** un fichier plus volumineux envoie des fragments séquentiels vers l'URL `upload-chunk`

#### Scenario: Progression et gestion d'erreurs côté client
- **WHEN** `npm test` est exécuté
- **THEN** les tests simulent les événements `progress` de `xhr.upload` et vérifient la mise à jour du pourcentage affiché
- **AND** une réponse `success: false` ou une erreur HTTP marque le fichier en erreur sans interrompre le traitement des fichiers restants de la file

#### Scenario: Protection beforeunload
- **WHEN** `npm test` est exécuté
- **THEN** le handler `beforeunload` est actif tant qu'un upload est en cours
- **AND** il est désactivé une fois tous les transferts terminés
