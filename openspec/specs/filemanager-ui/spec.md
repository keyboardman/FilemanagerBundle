# filemanager-ui

Exigences d'affichage de l'interface utilisateur du filemanager (grille de fichiers, vue liste, titres, barre latérale des dossiers).

## Requirements

### Requirement: Grille de fichiers limitée à 4 colonnes

La zone principale du filemanager MUST afficher les cartes de fichiers sur une grille responsive avec un maximum de 4 cartes par ligne sur les viewports moyens (≥768 px) et larges.

#### Scenario: Affichage sur viewport large

- **WHEN** l'utilisateur consulte le filemanager sur un viewport ≥768 px
- **AND** le dossier courant contient au moins 5 fichiers
- **THEN** chaque ligne affiche au maximum 4 cartes de fichiers
- **AND** les cartes supplémentaires passent à la ligne suivante

#### Scenario: Affichage sur viewport mobile

- **WHEN** l'utilisateur consulte le filemanager sur un viewport <768 px
- **THEN** les cartes s'affichent en une seule colonne

---

### Requirement: Titre de fichier compact

Chaque carte de fichier MUST afficher le nom du fichier dans un style typographique compact (plus petit que le titre de niveau heading par défaut), avec troncature si le nom dépasse la largeur de la carte.

#### Scenario: Nom de fichier long

- **WHEN** un fichier possède un nom dépassant la largeur de la carte
- **THEN** le nom est tronqué avec ellipsis (`text-truncate`)
- **AND** la taille de police est réduite par rapport à un `<h6>` Bootstrap standard

#### Scenario: Nom de fichier court

- **WHEN** un fichier possède un nom court
- **THEN** le nom complet est visible sans débordement

---

### Requirement: Nom de dossier compact dans la barre latérale

La barre latérale MUST afficher les noms de dossiers dans un style typographique compact, cohérent avec les titres de fichiers.

#### Scenario: Liste de dossiers dans la sidebar

- **WHEN** l'utilisateur consulte la barre latérale contenant des dossiers
- **THEN** chaque nom de dossier est affiché en taille réduite
- **AND** les noms longs sont tronqués pour ne pas déborder de la zone latérale

---

### Requirement: Bascule vue cartes / vue liste

L'en-tête du filemanager MUST proposer un contrôle permettant de choisir entre la vue cartes et la vue liste. La vue cartes est le mode par défaut.

#### Scenario: Affichage du sélecteur de vue

- **WHEN** l'utilisateur consulte le filemanager
- **THEN** un contrôle de sélection de vue est visible dans l'en-tête
- **AND** la vue cartes est sélectionnée par défaut si aucun paramètre `view` n'est présent

#### Scenario: Bascule vers la vue liste

- **WHEN** l'utilisateur sélectionne la vue liste
- **THEN** la zone principale affiche les fichiers en mode liste
- **AND** le paramètre de requête `view=list` est ajouté à l'URL
- **AND** les autres filtres actifs (disque, chemin, type, tri) sont conservés

#### Scenario: Bascule vers la vue cartes

- **WHEN** l'utilisateur sélectionne la vue cartes
- **THEN** la zone principale affiche les fichiers en grille de cartes
- **AND** le paramètre de requête `view=card` est ajouté à l'URL (ou omis si `card` est la valeur par défaut)

#### Scenario: Persistance du choix de vue lors de la navigation

- **WHEN** l'utilisateur a sélectionné la vue liste
- **AND** il navigue vers un autre dossier ou change un filtre (type, tri)
- **THEN** la vue liste reste active

---

### Requirement: Vue liste avec nom complet

En mode liste, chaque fichier MUST être affiché sur une ligne horizontale avec une miniature compacte, le nom complet du fichier (sans troncature) et les actions disponibles (télécharger, renommer, supprimer, sélectionner si applicable).

#### Scenario: Affichage du nom complet

- **WHEN** l'utilisateur consulte le filemanager en vue liste
- **AND** un fichier possède un nom long dépassant la largeur disponible en vue cartes
- **THEN** le nom complet du fichier est visible en mode liste (retour à la ligne ou défilement horizontal autorisé, sans ellipsis)

#### Scenario: Miniature et actions en vue liste

- **WHEN** l'utilisateur consulte un fichier en vue liste
- **THEN** une miniature d'aperçu compacte est affichée à gauche du nom
- **AND** les boutons d'action (télécharger, renommer, supprimer, sélectionner) sont accessibles sur la même ligne

#### Scenario: Liste vide

- **WHEN** l'utilisateur consulte un dossier vide en vue liste
- **THEN** aucune ligne de fichier n'est affichée
- **AND** la zone d'upload reste disponible

---

### Requirement: Vue cartes inchangée par défaut

La vue cartes MUST conserver le comportement existant (grille responsive max 4 colonnes, titres compacts avec troncature) lorsqu'elle est sélectionnée ou par défaut.

#### Scenario: Vue cartes par défaut

- **WHEN** l'utilisateur ouvre le filemanager sans paramètre `view`
- **THEN** les fichiers s'affichent en grille de cartes selon les exigences existantes de grille et de titre compact

---

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
