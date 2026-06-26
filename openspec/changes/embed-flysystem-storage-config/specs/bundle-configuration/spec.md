## ADDED Requirements

### Requirement: Configuration de stockage inline par disk

Chaque disk `keyboardman_filemanager` SHALL définir son stockage Flysystem via une section `storage` inline contenant au minimum `adapter`, et les `options` requises par l'adapter.

#### Scenario: Disk local configuré en une seule déclaration

- **WHEN** un développeur configure un disk avec `adapter: local` et `options.directory`
- **THEN** le bundle enregistre automatiquement le storage Flysystem correspondant
- **AND** le disk est opérationnel sans fichier `flysystem.yaml` dédié côté consommateur

#### Scenario: Disk avec visibility et options avancées

- **WHEN** un développeur configure un disk avec `storage.visibility`, `storage.adapter` et `storage.options` (ex. credentials S3)
- **THEN** le bundle transmet ces paramètres au storage Flysystem généré
- **AND** le disk conserve ses propriétés filemanager (`label`, `signed_urls`, `default_uri`)

---

### Requirement: Enregistrement automatique des storages Flysystem

Le bundle SHALL enregistrer les storages Flysystem internes à partir de la configuration `keyboardman_filemanager.disks` sans intervention manuelle du consommateur.

#### Scenario: Storage interne créé pour chaque disk

- **WHEN** la configuration définit N disks
- **THEN** le bundle crée N storages Flysystem avec l'identifiant `keyboardman_filemanager.{disk_name}.storage`
- **AND** chaque service `Disk` injecte le FilesystemOperator du storage correspondant

#### Scenario: Aucune référence de service externe requise

- **WHEN** un développeur configure uniquement `keyboardman_filemanager.yaml`
- **THEN** il n'a pas besoin de déclarer de service Flysystem ni de référencer un identifiant de storage externe dans le champ `storage`

---

### Requirement: Dépendance Flysystem incluse avec le bundle

Le bundle SHALL déclarer `league/flysystem`, `league/flysystem-bundle` et `league/flysystem-aws-s3-v3` comme dépendances Composer `require` afin qu'ils soient installés automatiquement avec le bundle.

#### Scenario: Installation Composer unique

- **WHEN** un développeur exécute `composer require keyboardman/filemanager-bundle`
- **THEN** `league/flysystem`, `league/flysystem-bundle` et `league/flysystem-aws-s3-v3` sont installés sans commande Composer supplémentaire
