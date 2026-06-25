# Change: Tests automatisés pour l'upload média XHR

## Why
L'upload XHR avec dropzone, progression et découpage en fragments est en production (`media-upload`) mais sans couverture de tests automatisés. Les régressions sur `ChunkUploadManager`, les endpoints API et le contrôleur Stimulus `filemanager-upload` seraient difficiles à détecter sans suite PHPUnit et Vitest.

## What Changes
- Mettre en place **PHPUnit** dans le bundle (`phpunit.xml.dist`, dépendances dev, script `composer test`).
- Ajouter des tests unitaires PHP pour `UploadLimitResolver` et `ChunkUploadManager` (validation, séquence de fragments, assemblage).
- Ajouter des tests fonctionnels PHP pour `POST /api/filemanager/upload` et `POST /api/filemanager/upload-chunk` via un kernel Symfony minimal de test.
- Mettre en place **Vitest** (aligné sur Vite) avec script `npm test`.
- Ajouter des tests JS pour le contrôleur Stimulus `filemanager-upload` (file d'attente, bascule monolithique/fragmenté, progression, gestion d'erreurs, `beforeunload`).
- Documenter l'exécution des tests dans le README.

## Impact
- Affected specs: `media-upload` (exigences de couverture de tests ajoutées).
- Affected code: `tests/`, `frontend/js/controllers/filemanager_upload_controller.js` (refactor mineur si nécessaire pour testabilité), `composer.json`, `package.json`, `phpunit.xml.dist`, `vitest.config.js`, `README.md`.
