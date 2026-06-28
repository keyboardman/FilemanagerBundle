## Context

Le filemanager affiche les aperçus d'images via `file_preview.html.twig`, qui pointe directement vers `resolve_url` (proxy `/kbd/filemanager/media/...` ou URL S3 signée). Sur un dossier de 50+ images haute résolution, le navigateur télécharge des mégaoctets avant d'afficher la grille.

Le bundle intègre déjà Flysystem de manière transparente : `prependExtensionConfig('flysystem', …)` génère un storage `keyboardman_filemanager.{disk}.storage` par disk configuré. LiipImagineBundle supporte nativement un **Flysystem loader** pointant vers ces mêmes services — l'intégration suit le même pattern.

## Goals / Non-Goals

**Goals:**

- Générer et servir des miniatures d'images (~320 px) via LiipImagineBundle
- Configurer automatiquement loaders et filter sets à partir de `keyboardman_filemanager.disks`
- Réduire le temps de chargement des vues cartes et liste
- Purger le cache LiipImagine lors de suppressions et renommages
- Conserver la prévisualisation modale en pleine résolution

**Non-Goals:**

- Service maison `ThumbnailGenerator` / `ThumbnailController` (délégué à LiipImagine)
- Cache dans `var/` (LiipImagine redirige vers une URL publique après génération ; `web_path` dans `public/` est le modèle standard)
- Miniatures vidéo (poster frame)
- Traitement asynchrone (Symfony Messenger)
- Stockage du cache miniature sur S3

## Decisions

### 1. LiipImagineBundle en dépendance `require`

**Choix :** ajouter `liip/imagine-bundle: ^2.13` en `require` de `composer.json`, comme `league/flysystem-bundle`.

**Alternatives :**
- *Service maison GD* : autonome mais code à maintenir
- *Suggest optionnel* : l'utilisateur devrait configurer Liip manuellement

**Rationale :** cohérence avec le pattern Flysystem ; zéro config Liip côté consommateur.

### 2. Configuration automatique via `prependExtensionConfig`

**Choix :** dans `KeyboardmanFilemanagerExtension::load()`, générer la config LiipImagine à partir des disks :

```yaml
liip_imagine:
  driver: gd
  loaders:
    filemanager_{disk}:
      flysystem:
        filesystem_service: keyboardman_filemanager.{disk}.storage
  resolvers:
    filemanager_thumbs:
      web_path:
        web_root: '%kernel.project_dir%/public'
        cache_prefix: 'media/cache/filemanager'
  filter_sets:
    filemanager_thumb_{disk}:
      quality: 82          # défaut Configuration.php (non requis côté consommateur)
      data_loader: filemanager_{disk}
      cache: filemanager_thumbs
      filters:
        thumbnail: { size: [320, 320], mode: inset }
```

Un filter set par disk car LiipImagine associe un seul `data_loader` par filter set.

**Rationale :** réutilise les storages Flysystem déjà créés ; aucun `liip_imagine.yaml` requis côté consommateur.

### 3. Valeurs par défaut fixes dans `keyboardman_filemanager`

**Choix :** section `thumbnail` avec `addDefaultsIfNotSet()` dans `Configuration.php` — **aucune déclaration requise** côté consommateur (même pattern que `upload` et `iframe`) :

| Paramètre | Défaut | Rôle |
|-----------|--------|------|
| `max_size` | `320` | Taille max du filtre `thumbnail` (mode inset) |
| `quality` | `82` | Qualité JPEG de sortie |

```php
->arrayNode('thumbnail')
    ->addDefaultsIfNotSet()
    ->children()
        ->integerNode('max_size')->defaultValue(320)->end()
        ->integerNode('quality')->defaultValue(82)->end()
    ->end()
->end()
```

L'extension lit ces valeurs pour construire les filter sets LiipImagine. Le consommateur n'a **pas** à toucher cette section : les valeurs sont stables et adaptées au filemanager. Surcharge possible uniquement pour des cas avancés.

### 4. Cache : `public/media/cache/filemanager/`

**Choix :** resolver `web_path` avec `cache_prefix: media/cache/filemanager`.

**Alternatives :**
- *Cache dans `var/`* : incompatible avec le redirect 302 de LiipImagine vers l'URL publique du cache
- *Flysystem resolver S3* : surdimensionné ; les miniatures sont petites et locales

**Rationale :** modèle standard LiipImagine ; fichiers servis directement par le web server après la première génération.

### 5. Intégration Twig : filtre `imagine_thumbnail`

**Choix :** ajouter un filtre Twig dans `FilemanagerExtension` :

```php
$cacheManager->getBrowserPath($path, 'filemanager_thumb_' . $filesystem);
```

Dans `file_preview.html.twig` :
- `<img src="{{ file.path|imagine_thumbnail(filter.filesystem) }}">` pour les images
- `data-file="{{ file.path|resolve_url(filter.filesystem) }}"` inchangé pour la modale

**Fallback :** si `LiipImagineBundle` n'est pas enregistré (cas test minimal), le filtre retourne `resolve_url`.

### 6. Purge du cache : `CacheManager::remove()` dans `DiskManager`

**Choix :** injecter `Liip\ImagineBundle\Service\FilterService` ou `CacheManager` dans `DiskManager` ; appeler `remove($path, 'filemanager_thumb_' . $filesystem)` dans `deleteFile` et `rename` (ancien chemin).

LiipImagine ne purge pas automatiquement à la modification du fichier source ; la régénération se fait à la prochaine requête si le cache a été purgé manuellement. Pour l'invalidation à la modification sans suppression, utiliser `liip:imagine:cache:remove` ou appeler `remove` après upload/remplacement (hors scope v1 : upload écrase le chemin, purge explicite post-upload en tâche future).

### 7. Enregistrement du bundle tiers

**Choix :** documenter l'enregistrement de `Liip\ImagineBundle\LiipImagineBundle` dans `bundles.php`, comme pour `FlysystemBundle`. Pas d'auto-enregistrement (limitation Symfony).

## Risks / Trade-offs

- **[LiipImagineBundle non enregistré]** → Le filtre Twig fallback sur `resolve_url` ; log warning au boot si config prepend sans bundle actif.
- **[Première visite lente]** → Génération à la demande + lecture S3 ; cache persistant ensuite.
- **[Multi-disk = N filter sets]** → Acceptable ; nommage `filemanager_thumb_{disk}`.
- **[Cache public accumulé]** → Purge à la suppression/renommage ; `liip:imagine:cache:remove` documenté pour maintenance.
- **[Collision cache_prefix]** → Préfixe `media/cache/filemanager` dédié au bundle.

## Migration Plan

1. `composer update` installe `liip/imagine-bundle`
2. Ajouter `LiipImagineBundle` dans `config/bundles.php`
3. Aucune config `liip_imagine.yaml` requise (générée par prepend)
4. Rollback : revert du code ; supprimer `public/media/cache/filemanager/` si nécessaire

## Open Questions

- Faut-il ajouter `public/media/cache/filemanager/` au `.gitignore` du bundle (doc) ? → **Oui**, documenter côté consommateur.
- Purge post-upload (remplacement fichier même chemin) ? → **Hors scope v1** ; Liip sert l'ancienne miniature tant que le cache n'est pas purgé.
- Exposer `cache_prefix` ou `mode` du filtre en config ? → **Non** ; constantes internes, seuls `max_size` et `quality` ont des défauts (rarement surchargés).
