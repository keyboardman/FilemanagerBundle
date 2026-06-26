## 1. Dépendances et extension

- [x] 1.1 Ajouter `league/flysystem` et `league/flysystem-bundle` en `require` dans `composer.json`
- [x] 1.2 Étendre `Configuration.php` : remplacer le scalar `storage` par un nœud `storage` (adapter, visibility, options)
- [x] 1.3 Implémenter dans `KeyboardmanFilemanagerExtension` le `prependExtensionConfig('flysystem', …)` à partir des disks
- [x] 1.4 Référencer les storages internes `keyboardman_filemanager.{name}.storage` lors de l'enregistrement des services `Disk`

## 2. Documentation

- [x] 2.1 Mettre à jour `README.md` : une seule config `keyboardman_filemanager.yaml`, suppression de l'étape flysystem.yaml obligatoire
- [x] 2.2 Documenter la migration depuis l'ancien format `storage: service_id`
- [x] 2.3 Mettre à jour les prérequis : Flysystem installé automatiquement, `FlysystemBundle` toujours à enregistrer

## 3. Tests

- [x] 3.1 Adapter `TestKernel` pour charger le bundle via son extension (config inline) plutôt que des services Flysystem manuels
- [x] 3.2 Ajouter un test fonctionnel ou d'intégration DI vérifiant qu'un disk avec adapter `local` en mémoire ou temporaire est résolu correctement
- [x] 3.3 Vérifier que les tests upload existants passent avec la nouvelle configuration

## 4. Validation

- [x] 4.1 Exécuter `composer test` et `composer phpstan`
- [x] 4.2 Valider la proposition OpenSpec : `openspec validate embed-flysystem-storage-config --strict`
