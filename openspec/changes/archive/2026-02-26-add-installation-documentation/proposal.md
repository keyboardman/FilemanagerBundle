# Change: Documentation d'installation du bundle Filemanager

## Why

Le bundle Keyboardman Filemanager n'a pas de documentation d'installation. Les développeurs doivent explorer le code et la configuration pour comprendre comment l'intégrer dans un projet Symfony utilisant Flysystem. Une documentation claire réduit le temps d'intégration et évite les erreurs de configuration.

## What Changes

- Création d'un fichier `README.md` ou `docs/installation.md` décrivant l'installation pas à pas
- Documentation des prérequis (PHP 8.2+, Symfony, Flysystem, Asset Mapper)
- Configuration Flysystem avec exemples pour plusieurs types de stockage
- Configuration `keyboardman_filemanager` avec mapping des disks vers les storages Flysystem
- Enregistrement du bundle et des routes
- Build des assets (Vite)
- Utilisation du `FilemanagerType` dans les formulaires
- Inclusion du modal et du script `filemanager-field.js` dans les templates
- Variables d'environnement requises (`DEFAULT_URI`, `FILEMANAGER_TOKENS`, `FILEMANAGER_TOKEN_ENABLED`)

## Impact

- Affected specs: `installation-docs` (nouvelle capacité)
- Affected code: aucun (documentation uniquement)
- Fichiers créés: `README.md` ou `docs/installation.md`
