## ADDED Requirements

### Requirement: Configuration automatique de LiipImagine par disk

Le bundle MUST configurer LiipImagineBundle via `prependExtensionConfig` en générant, pour chaque disk `keyboardman_filemanager`, un loader Flysystem pointant vers `keyboardman_filemanager.{disk}.storage` et un filter set `filemanager_thumb_{disk}` avec filtre `thumbnail` (mode inset, `max_size` défaut 320, `quality` défaut 82 via `Configuration.php`).

#### Scenario: Valeurs par défaut sans config explicite

- **WHEN** le consommateur ne déclare pas de section `thumbnail` dans `keyboardman_filemanager.yaml`
- **THEN** le bundle applique `max_size: 320` et `quality: 82` aux filter sets LiipImagine

#### Scenario: Disk unique

- **WHEN** le consommateur configure un disk `default` dans `keyboardman_filemanager.yaml`
- **THEN** le bundle enregistre un loader `filemanager_default` et un filter set `filemanager_thumb_default`
- **AND** aucun fichier `liip_imagine.yaml` n'est requis côté consommateur

#### Scenario: Disks multiples

- **WHEN** le consommateur configure N disks
- **THEN** le bundle crée N loaders et N filter sets distincts
- **AND** chaque filter set utilise le loader Flysystem correspondant à son disk

---

### Requirement: Génération de miniatures d'images à la demande

Le bundle MUST générer des miniatures pour les fichiers image via LiipImagineBundle, en lisant le contenu depuis le storage Flysystem du disk et en appliquant le filter set associé.

#### Scenario: Première requête pour une image

- **WHEN** une miniature est demandée pour un fichier image existant
- **AND** aucune entrée n'est présente dans le cache LiipImagine
- **THEN** LiipImagine lit le fichier source via le loader Flysystem
- **AND** génère une miniature redimensionnée
- **AND** enregistre le résultat dans `public/media/cache/filemanager/`

#### Scenario: Fichier source introuvable

- **WHEN** une miniature est demandée pour un chemin inexistant
- **THEN** LiipImagine répond avec une erreur 404

#### Scenario: Fichier non image

- **WHEN** un filter set est appliqué à un fichier non image
- **THEN** LiipImagine échoue avec une erreur appropriée
- **AND** le template n'applique le filtre que pour les MIME `image/*`

---

### Requirement: Cache local des miniatures

Les miniatures MUST être stockées via le resolver `web_path` de LiipImagine sous `public/media/cache/filemanager/`.

#### Scenario: Cache hit

- **WHEN** une miniature a déjà été générée
- **AND** le client demande l'URL résolue
- **THEN** le fichier cache est servi sans régénération depuis la source distante

#### Scenario: Cache navigateur

- **WHEN** le client demande une miniature déjà générée
- **THEN** la réponse permet la mise en cache côté navigateur

---

### Requirement: Route HTTP LiipImagine

Les miniatures MUST être accessibles via la route LiipImagine standard `/media/cache/resolve/{filter}/{path}` (avec redirect vers le fichier cache résolu).

#### Scenario: URL de résolution

- **WHEN** le template demande l'URL miniature d'un fichier `photo.jpg` sur le disk `default`
- **THEN** l'URL générée utilise le filter set `filemanager_thumb_default`
- **AND** pointe vers `/media/cache/resolve/filemanager_thumb_default/photo.jpg`

---

### Requirement: Purge du cache miniature lors des opérations sur les fichiers

Lors de la suppression ou du renommage d'un fichier, le bundle MUST appeler `CacheManager::remove()` pour le filter set et le chemin concernés.

#### Scenario: Suppression d'un fichier image

- **WHEN** un fichier est supprimé via l'API ou l'interface
- **THEN** le cache LiipImagine associé à ce chemin et filter set est supprimé

#### Scenario: Renommage d'un fichier image

- **WHEN** un fichier est renommé
- **THEN** le cache LiipImagine de l'ancien chemin est supprimé
- **AND** une nouvelle miniature sera générée à la prochaine requête pour le nouveau chemin
