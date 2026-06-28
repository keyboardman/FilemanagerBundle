## Context

Le bundle s'appuie sur Flysystem pour le stockage multi-adapter (local, S3, etc.). Actuellement, le consommateur doit :

1. Installer `league/flysystem-bundle` manuellement
2. Enregistrer `FlysystemBundle` dans `bundles.php`
3. Créer `config/packages/flysystem.yaml`
4. Référencer les storages dans `keyboardman_filemanager.disks.*.storage`

L'extension enregistre ensuite des services `Disk` avec `new Reference($diskConfig['storage'])`.

## Goals / Non-Goals

- Goals:
  - Une seule surface de configuration : `keyboardman_filemanager`
  - Installation transparente de Flysystem via Composer
  - Conserver le support des adapters Flysystem (local, S3, etc.) via flysystem-bundle
  - Compatibilité avec les disks multiples existants (label, visibility, signed_urls, default_uri)
- Non-Goals:
  - Réimplémenter les factories d'adapters Flysystem (on délègue à flysystem-bundle)
  - Supporter une config hybride `storage: service_id` + inline (complexité inutile)
  - Gérer l'enregistrement automatique de `FlysystemBundle` dans `bundles.php` sans Flex recipe (hors scope Symfony standard)

## Decisions

### Decision: Configuration inline par disk

Chaque disk définit son storage directement :

```yaml
keyboardman_filemanager:
  disks:
    default:
      label: Default
      storage:
        adapter: local
        visibility: public
        options:
          directory: "%kernel.project_dir%/public/uploads/default"
      signed_urls: true
      default_uri: "%env(resolve:DEFAULT_URI)%/uploads/default"
```

**Alternatives considérées :**
- Garder `storage` comme référence de service → rejeté (c'est le problème actuel)
- Créer les Filesystem sans flysystem-bundle → rejeté (perte du support multi-adapter et de la maintenance upstream)

### Decision: prependExtensionConfig pour Flysystem

L'extension parcourt les disks et appelle `prependExtensionConfig('flysystem', ['storages' => …])` avec des identifiants normalisés : `keyboardman_filemanager.{disk_name}.storage`.

Les services `Disk` référencent ensuite ce storage interne. Le consommateur n'a jamais à connaître ces identifiants.

### Decision: Dépendances Composer en require

```json
"require": {
  "league/flysystem": "^3.0",
  "league/flysystem-bundle": "^3.0"
}
```

Composer installe automatiquement Flysystem avec le bundle. L'enregistrement de `FlysystemBundle` reste documenté (ou via Flex recipe future) car Symfony ne permet pas l'auto-enregistrement de bundles tiers.

### Decision: Identifiants de storage internes

Convention : `keyboardman_filemanager.{disk_name}.storage` — préfixe du bundle pour éviter les collisions avec d'éventuels storages Flysystem existants dans le projet hôte.

## Risks / Trade-offs

- **Collision avec flysystem.yaml existant** → Les storages filemanager sont préfixés `keyboardman_filemanager.*` ; pas de conflit si le consommateur garde ses propres storages pour d'autres usages.
- **BREAKING pour projets existants** → Documenter la migration dans le README ; l'ancien champ `storage: service_id` n'est plus supporté.
- **FlysystemBundle toujours à enregistrer** → Mitigation : doc simplifiée + recipe Flex optionnelle.

## Migration Plan

1. Remplacer dans chaque disk :
   - **Avant** : `storage: default.storage` (référence flysystem.yaml)
   - **Après** : bloc `storage:` avec adapter/options copiés depuis l'ancien storage flysystem
2. Supprimer les entrées `flysystem.storages` devenues inutiles (si exclusivement utilisées par le filemanager)
3. Retirer `composer require league/flysystem-bundle` des projets déjà configurés (dépendance transitive)
4. Vérifier que `FlysystemBundle` reste dans `bundles.php`

## Open Questions

- Faut-il fournir une Flex recipe pour enregistrer automatiquement `FlysystemBundle` et générer un `keyboardman_filemanager.yaml` minimal ? (hors scope initial, à traiter séparément)
