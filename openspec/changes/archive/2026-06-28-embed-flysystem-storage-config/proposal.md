# Change: Masquer Flysystem derrière la configuration keyboardman_filemanager

## Why

Aujourd'hui, l'intégration du filemanager impose deux étapes distinctes : configurer un fichier `flysystem.yaml` avec des storages, puis déclarer des disks `keyboardman_filemanager` qui référencent ces services Flysystem par identifiant. Cette double configuration est source d'erreurs (références cassées, storages oubliés) et alourdit l'installation pour un besoin qui devrait être transparent.

## What Changes

- **BREAKING** : le champ `storage` (référence à un service Flysystem externe) est remplacé par une section `storage` inline par disk (adapter, visibility, options).
- L'extension `KeyboardmanFilemanagerExtension` enregistre automatiquement les storages Flysystem à partir de la config `keyboardman_filemanager` (via `prependExtensionConfig('flysystem', …)`).
- `league/flysystem-bundle` et `league/flysystem` passent en dépendance `require` du bundle (installés automatiquement avec Composer, plus d'étape manuelle `composer require league/flysystem-bundle`).
- Le README et la spec `installation-docs` sont mis à jour : un seul fichier `keyboardman_filemanager.yaml`, plus de `flysystem.yaml` obligatoire côté consommateur.
- Tests fonctionnels adaptés pour valider l'enregistrement automatique des storages.

## Impact

- Affected specs: `bundle-configuration` (nouvelle), `installation-docs` (modifiée)
- Affected code: `Configuration.php`, `KeyboardmanFilemanagerExtension.php`, `composer.json`, `README.md`, `tests/TestKernel.php`
- Migration: remplacer `storage: default.storage` par une définition inline ; supprimer les entrées redondantes dans `flysystem.yaml` si elles ne servaient qu'au filemanager
