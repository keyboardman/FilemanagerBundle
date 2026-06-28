# media-listing Specification

## Purpose
TBD - created by archiving change exclude-s3-hidden-files. Update Purpose after archive.
## Requirements
### Requirement: Exclusion des entrées cachées au listage
Le gestionnaire de médias SHALL exclure du résultat de listage tout fichier ou dossier dont le nom direct (dernier segment du chemin relatif au répertoire courant) commence par un point (`.`).

#### Scenario: Fichier caché à la racine S3
- **WHEN** le bucket S3 contient un objet `.DS_Store` à la racine
- **THEN** `DiskManager::list()` ne retourne pas cet objet dans les éléments listés

#### Scenario: Dossier caché via CommonPrefix S3
- **WHEN** le bucket S3 contient un préfixe commun `.metadata/` au même niveau que les dossiers médias
- **THEN** `DiskManager::list()` ne retourne pas ce dossier dans les éléments listés

#### Scenario: Fichier caché dans un sous-répertoire
- **WHEN** l'utilisateur liste le répertoire `photos/` et que S3 contient `photos/.hidden.jpg`
- **THEN** le fichier `.hidden.jpg` n'apparaît pas dans la liste retournée

#### Scenario: Entrées visibles inchangées
- **WHEN** le répertoire courant contient des fichiers et dossiers dont le nom ne commence pas par `.`
- **THEN** ces entrées restent présentes dans la liste retournée, avec le même tri et filtrage média qu'avant

### Requirement: Cohérence local et S3
La règle d'exclusion des entrées cachées MUST s'appliquer de la même manière quel que soit le type de disque Flysystem (local ou S3 AsyncAws).

#### Scenario: Listing local
- **WHEN** un disque local contient un fichier `.gitkeep` dans le répertoire listé
- **THEN** ce fichier n'apparaît pas dans la liste retournée

#### Scenario: Listing S3 via SafeAsyncAwsS3Lister
- **WHEN** le disque utilise l'adaptateur AsyncAws S3 et le lister dédié `SafeAsyncAwsS3Lister`
- **THEN** les objets et CommonPrefixes cachés sont exclus avant ou pendant l'agrégation finale de `DiskManager::list()`
