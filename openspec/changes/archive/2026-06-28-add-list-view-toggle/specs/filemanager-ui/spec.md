## ADDED Requirements

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
