## 1. Dépendances et configuration

- [x] 1.1 Ajouter `liip/imagine-bundle` en `require` dans `composer.json`
- [x] 1.2 Ajouter la section `thumbnail` dans `Configuration.php` avec `addDefaultsIfNotSet()` (`max_size: 320`, `quality: 82`)
- [x] 1.3 Implémenter `buildLiipImagineConfig()` dans `KeyboardmanFilemanagerExtension` avec `prependExtensionConfig('liip_imagine', …)`

## 2. Génération de la config LiipImagine

- [x] 2.1 Générer un loader Flysystem `filemanager_{disk}` par disk pointant vers `keyboardman_filemanager.{disk}.storage`
- [x] 2.2 Générer un filter set `filemanager_thumb_{disk}` avec filtre `thumbnail` (inset, taille/qualité depuis config)
- [x] 2.3 Configurer le resolver `web_path` avec `cache_prefix: media/cache/filemanager`

## 3. Intégration Twig et templates

- [x] 3.1 Ajouter le filtre Twig `imagine_thumbnail(path, filesystem)` dans `FilemanagerExtension` (wrapper `CacheManager::getBrowserPath`)
- [x] 3.2 Implémenter le fallback vers `resolve_url` si LiipImagine n'est pas disponible
- [x] 3.3 Mettre à jour `file_preview.html.twig` : `<img>` via `imagine_thumbnail`, `data-file` en pleine résolution pour la modale

## 4. Purge du cache

- [x] 4.1 Injecter `CacheManager` dans `DiskManager`
- [x] 4.2 Appeler `remove($path, 'filemanager_thumb_' . $filesystem)` dans `deleteFile` et `rename` (ancien chemin)

## 5. Tests

- [x] 5.1 Test unitaire `buildLiipImagineConfig` : N disks → N loaders + N filter sets
- [x] 5.2 Test fonctionnel : première requête génère la miniature, seconde requête sert le cache
- [x] 5.3 Test : purge cache après suppression/renommage

## 6. Documentation

- [x] 6.1 Mettre à jour le README : `LiipImagineBundle` dans `bundles.php`, miniatures actives sans config (défauts 320/82), surcharge optionnelle, cache `public/media/cache/filemanager/`
- [x] 6.2 Documenter le prérequis `ext-gd` et l'ajout de `public/media/cache/filemanager/` au `.gitignore` du projet hôte
