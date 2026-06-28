## Why

La vue cartes et la vue liste chargent actuellement les images en pleine résolution via l'URL média (proxy ou S3), ce qui ralentit fortement le rendu des dossiers contenant de nombreux fichiers. L'intégration de **LiipImagineBundle** — sur le même modèle que Flysystem — permettra de générer des miniatures à la demande, avec cache local persistant, sans réimplémenter la logique de redimensionnement.

## What Changes

- Ajout de `liip/imagine-bundle` en dépendance Composer `require` (installée automatiquement avec le bundle)
- Configuration automatique de LiipImagine via `prependExtensionConfig` à partir des disks `keyboardman_filemanager` (loaders Flysystem + filter sets par disk)
- Cache des miniatures dans `public/media/cache/filemanager/` (convention LiipImagine `web_path`)
- Mise à jour des templates d'aperçu (`file_preview`) pour utiliser `imagine_filter` en vue cartes et liste ; la modale conserve le média original
- Purge du cache LiipImagine lors de la suppression ou du renommage d'un fichier image (`CacheManager::remove`)
- Section `thumbnail` dans `Configuration.php` avec valeurs par défaut (`max_size: 320`, `quality: 82`) — **aucune config requise** côté consommateur
- Documentation : enregistrement de `LiipImagineBundle` dans `bundles.php`, prérequis `ext-gd`

## Capabilities

### New Capabilities

- `media-thumbnails`: génération via LiipImagine, cache local, invalidation et exposition HTTP des miniatures d'images

### Modified Capabilities

- `filemanager-ui`: les vues cartes et liste MUST charger les miniatures pour les images au lieu du média pleine résolution
- `installation-docs`: documentation de l'enregistrement de `LiipImagineBundle` et de la configuration automatique

## Impact

- `composer.json` : `liip/imagine-bundle` en `require`
- `KeyboardmanFilemanagerExtension` : `prependExtensionConfig('liip_imagine', …)` miroir du pattern Flysystem
- `DiskManager` : injection de `CacheManager` pour purge à la suppression/renommage
- Templates Twig (`file_preview.html.twig`) et filtre Twig `imagine_thumbnail` (wrapper autour de `CacheManager::getBrowserPath`)
- `config/bundles.php` côté consommateur : `Liip\ImagineBundle\LiipImagineBundle`
- Répertoire `public/media/cache/filemanager/` (à ajouter au `.gitignore` du projet hôte ou laisser généré)
- Tests sur la config prepend, purge cache et rendu Twig
