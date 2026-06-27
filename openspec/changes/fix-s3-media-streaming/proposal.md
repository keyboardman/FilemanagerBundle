# Change: Corriger le streaming média S3 (erreurs 500 et seek vidéo)

## Why

Avec un disk S3 configuré, la lecture et la prévisualisation de vidéos échouent : des requêtes HTTP renvoient des erreurs 500 et le lecteur vidéo revient systématiquement au début lors d'un déplacement dans la timeline. Le proxy `MediaController` utilise `readStream()` + `fseek()` pour honorer les requêtes `Range`, ce qui ne fonctionne pas de manière fiable sur les flux S3 Flysystem. De plus, la configuration S3 (`default_uri`, `signed_urls`, CORS) n'est pas alignée avec le comportement réel du bundle.

## What Changes

- Corriger le streaming par plages (`Range` / `206 Partial Content`) pour les disks distants (S3 en priorité) en utilisant une lecture native par offset plutôt que `fseek` sur un flux non seekable.
- Améliorer la gestion d'erreurs du `MediaController` (exceptions Flysystem/S3 → réponses HTTP explicites au lieu de 500 génériques).
- Aligner la résolution d'URL publique (`DiskManager::publicUrl`, filtre Twig `resolve_url`) : proxy Symfony pour les buckets privés, URL directe ou signée pour les buckets publics selon la config.
- Implémenter ou documenter le comportement de `signed_urls` pour S3 (URLs présignées quand le bucket n'est pas public).
- Documenter la configuration S3 requise (CORS, `default_uri`, visibilité) dans le README.
- Ajouter des tests PHPUnit couvrant le streaming par plages (local + mock S3).

## Capabilities

### New Capabilities

- `media-streaming`: Servir les fichiers média (image, vidéo, audio) via le proxy Symfony avec support HTTP Range, compatible stockage local et distant (S3).

### Modified Capabilities

- `installation-docs`: Ajouter la documentation S3 pour le streaming vidéo (CORS, `default_uri`, `signed_urls`, dépannage des erreurs 500 / seek).

## Impact

- Affected specs: `media-streaming` (nouvelle), `installation-docs` (delta).
- Affected code: `src/Controller/MediaController.php`, `src/Disk/DiskManager.php`, `src/Twig/FilemanagerExtension.php`, `src/Disk/` (éventuel service de lecture par plage), `README.md`, `tests/Functional/` ou `tests/Unit/`.
