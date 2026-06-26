## MODIFIED Requirements

### Requirement: Documentation des prérequis

La documentation d'installation SHALL lister les prérequis nécessaires pour utiliser le bundle.

#### Scenario: Prérequis listés

- **WHEN** un développeur consulte la documentation d'installation
- **THEN** il trouve la liste des prérequis : PHP 8.2+, Symfony 8.x, symfony/asset-mapper
- **AND** il est indiqué que `league/flysystem` et `league/flysystem-bundle` sont installés automatiquement avec le bundle
- **AND** il est indiqué que `League\FlysystemBundle\FlysystemBundle` doit être enregistré dans `config/bundles.php`

---

### Requirement: Documentation de l'installation Composer

La documentation SHALL expliquer comment installer le bundle via Composer.

#### Scenario: Installation Composer

- **WHEN** un développeur suit la documentation
- **THEN** il peut installer le bundle avec `composer require keyboardman/filemanager-bundle` ou via un dépôt path
- **AND** il sait enregistrer le bundle dans `config/bundles.php`
- **AND** il n'a pas besoin d'exécuter une commande Composer séparée pour Flysystem

---

## REMOVED Requirements

### Requirement: Documentation de la configuration Flysystem

**Reason** : La configuration Flysystem est désormais intégrée dans `keyboardman_filemanager` ; un fichier `flysystem.yaml` dédié n'est plus requis pour le filemanager.

**Migration** : Déplacer les paramètres d'adapter et d'options dans la section `storage` de chaque disk `keyboardman_filemanager`. Conserver un `flysystem.yaml` uniquement si d'autres parties de l'application utilisent des storages indépendants du filemanager.

---

## MODIFIED Requirements

### Requirement: Documentation de la configuration keyboardman_filemanager

La documentation SHALL expliquer comment configurer les disks du filemanager avec une section `storage` inline définissant l'adapter Flysystem.

#### Scenario: Configuration disks avec stockage inline

- **WHEN** un développeur configure keyboardman_filemanager
- **THEN** il trouve un exemple `keyboardman_filemanager.yaml` avec la structure disks
- **AND** chaque disk définit `label`, `storage` (adapter, visibility, options), `signed_urls`, `default_uri`
- **AND** la documentation précise qu'aucun fichier `flysystem.yaml` séparé n'est requis pour le filemanager
