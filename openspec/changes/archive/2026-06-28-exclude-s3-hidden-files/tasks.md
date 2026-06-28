## 1. Helper de détection

- [x] 1.1 Extraire la logique `isHidden` dans un helper réutilisable (ex. `HiddenPath::isHidden(string $path): bool`) avec normalisation `trim` + `basename`
- [x] 1.2 Remplacer l'appel privé `DiskManager::isHidden()` par le helper centralisé

## 2. Filtrage S3

- [x] 2.1 Filtrer les objets `Contents` cachés dans `SafeAsyncAwsS3Lister::listContents()` avant chaque `yield`
- [x] 2.2 Filtrer les dossiers `CommonPrefixes` cachés dans `SafeAsyncAwsS3Lister::listContents()` avant chaque `yield`

## 3. Cohérence DiskManager

- [x] 3.1 Conserver le filtre hidden dans `DiskManager::list()` via le helper (filet de sécurité disques locaux)
- [x] 3.2 Ignorer les entrées cachées dans `deleteEmptyDirectory()` lors de la vérification « dossier vide »

## 4. Tests

- [x] 4.1 Ajouter tests unitaires du helper (`HiddenPath`) : racine, sous-dossier, trailing slash, noms visibles
- [x] 4.2 Ajouter test unitaire de `SafeAsyncAwsS3Lister` (mock S3 client) : objets et CommonPrefixes `.xxx` exclus
- [x] 4.3 Ajouter assertion dans `S3ContentListTest` : aucun élément listé n'a un `name` commençant par `.` (si bucket configuré)

## 5. Validation

- [x] 5.1 Exécuter la suite de tests PHPUnit (`composer test`) et corriger les échecs éventuels
