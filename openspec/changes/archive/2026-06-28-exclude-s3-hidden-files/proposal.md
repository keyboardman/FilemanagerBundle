# Change: Exclure les fichiers et dossiers cachés du listing S3

## Why

Avec un disque S3, le gestionnaire de médias affiche des fichiers et dossiers dont le nom commence par un point (`.DS_Store`, `._fichier`, dossiers de métadonnées, etc.). Ces entrées ne sont pas des médias utiles et polluent la vue cards/liste. Elles doivent être ignorées comme sur un système de fichiers local.

## What Changes

- Exclure systématiquement du listing les fichiers et dossiers dont le **nom direct** (segment de chemin) commence par `.`, pour tous les disques, avec une attention particulière au chemin de listage S3 (`SafeAsyncAwsS3Lister`).
- Centraliser la règle de filtrage « entrée cachée » pour qu'elle s'applique de façon cohérente à `DiskManager::list()` et aux usages internes du listage (ex. vérification de dossier vide).
- Ajouter des tests unitaires et/ou d'intégration couvrant le cas S3 (objets et CommonPrefixes dont le nom commence par `.`).

## Capabilities

### New Capabilities

- `media-listing`: Règles de listage des médias sur disque local et S3, incluant l'exclusion des entrées cachées (nom commençant par `.`).

### Modified Capabilities

_(aucune — pas de changement de comportement UI ou API au-delà du contenu retourné par le listing)_

## Impact

- Affected specs: `media-listing` (nouvelle).
- Affected code: `src/Disk/DiskManager.php`, `src/Disk/SafeAsyncAwsS3Lister.php`, `tests/Unit/` et/ou `tests/Integration/S3ContentListTest.php`.
