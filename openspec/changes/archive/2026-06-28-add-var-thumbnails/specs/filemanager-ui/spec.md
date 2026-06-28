## ADDED Requirements

### Requirement: Aperçu image via miniature en grille et liste

En vue cartes et en vue liste, les fichiers image MUST afficher leur aperçu via l'URL LiipImagine (`/media/cache/resolve/filemanager_thumb_{disk}/...`) et non via l'URL du média pleine résolution.

#### Scenario: Carte image en vue grille

- **WHEN** l'utilisateur consulte un dossier en vue cartes
- **AND** un fichier est de type image
- **THEN** la balise `<img>` de la carte utilise l'URL LiipImagine du filter set correspondant au disk actif
- **AND** l'attribut `loading="lazy"` est conservé

#### Scenario: Ligne image en vue liste

- **WHEN** l'utilisateur consulte un dossier en vue liste
- **AND** un fichier est de type image
- **THEN** la miniature compacte utilise l'URL LiipImagine

#### Scenario: Prévisualisation modale pleine résolution

- **WHEN** l'utilisateur clique sur l'aperçu d'une image en vue cartes ou liste
- **THEN** la modale de prévisualisation charge l'URL du média original (pleine résolution)
- **AND** non l'URL de miniature

#### Scenario: Vidéo, audio et autres types inchangés

- **WHEN** un fichier n'est pas de type image
- **THEN** l'affichage d'aperçu conserve le comportement existant (icône ou lecteur)
