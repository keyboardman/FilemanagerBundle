## MODIFIED Requirements

### Requirement: Prérequis et dépendances du bundle

La documentation d'installation SHALL indiquer que `liip/imagine-bundle` est installé automatiquement avec le bundle et que `Liip\ImagineBundle\LiipImagineBundle` doit être enregistré dans `config/bundles.php`.

#### Scenario: Installation via Composer

- **WHEN** un développeur installe `keyboardman/filemanager-bundle`
- **THEN** `liip/imagine-bundle` est installé sans commande Composer supplémentaire
- **AND** la documentation précise l'enregistrement de `LiipImagineBundle` dans `bundles.php`

#### Scenario: Prérequis extension GD

- **WHEN** un développeur consulte les prérequis
- **THEN** il est indiqué que l'extension PHP GD est requise pour la génération de miniatures

---

### Requirement: Configuration unique keyboardman_filemanager

La documentation SHALL préciser qu'aucun fichier `liip_imagine.yaml` séparé n'est requis : la configuration LiipImagine est générée automatiquement à partir des disks `keyboardman_filemanager`.

#### Scenario: Miniatures sans configuration explicite

- **WHEN** un développeur installe le bundle sans section `thumbnail` dans `keyboardman_filemanager.yaml`
- **THEN** les miniatures fonctionnent avec les valeurs par défaut (`max_size: 320`, `quality: 82`)
- **AND** le cache est stocké dans `public/media/cache/filemanager/`

#### Scenario: Surcharge optionnelle des miniatures

- **WHEN** un développeur souhaite ajuster la taille ou la qualité des miniatures
- **THEN** il peut déclarer optionnellement la section `thumbnail` dans `keyboardman_filemanager.yaml`
- **AND** la documentation indique que cette section n'est pas requise pour un usage standard
