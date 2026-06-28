## ADDED Requirements

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
