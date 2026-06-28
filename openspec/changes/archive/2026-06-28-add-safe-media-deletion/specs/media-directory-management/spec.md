## ADDED Requirements

### Requirement: Suppression de fichier avec confirmation
Le gestionnaire de media MUST demander une confirmation explicite via une fenetre modale avant toute suppression de fichier.

#### Scenario: Confirmation positive de suppression de fichier
- **WHEN** l'utilisateur declenche la suppression d'un fichier et confirme dans la fenetre modale
- **THEN** le systeme supprime le fichier cible
- **AND** l'interface retire le fichier de la liste apres rafraichissement

#### Scenario: Annulation de suppression de fichier
- **WHEN** l'utilisateur ouvre la fenetre modale de suppression puis annule
- **THEN** aucun fichier n'est supprime
- **AND** l'interface conserve l'etat courant sans modification

### Requirement: Suppression de dossier conditionnee au vide
Le gestionnaire de media MUST autoriser la suppression d'un dossier uniquement si ce dossier est vide.

#### Scenario: Suppression d'un dossier vide
- **WHEN** l'utilisateur confirme la suppression d'un dossier vide
- **THEN** le systeme supprime le dossier
- **AND** l'interface met a jour l'arborescence sans ce dossier

#### Scenario: Refus de suppression d'un dossier non vide
- **WHEN** l'utilisateur demande la suppression d'un dossier qui contient au moins un fichier ou sous-dossier
- **THEN** le systeme refuse l'operation avec une erreur metier explicite
- **AND** le dossier reste present dans l'interface

### Requirement: Contrat API de suppression de medias
Les endpoints API de suppression de fichiers et dossiers MUST retourner des reponses JSON coherentes pour les clients JavaScript du filemanager.

#### Scenario: Reponse de succes API de suppression
- **WHEN** une suppression de fichier ou de dossier vide aboutit
- **THEN** l'API retourne `success: true`
- **AND** l'API inclut les informations minimales permettant a l'interface de rafraichir la vue courante

#### Scenario: Reponse d'erreur API de suppression
- **WHEN** une suppression echoue (cible absente, dossier non vide, entree invalide, echec interne)
- **THEN** l'API retourne `success: false` avec un message lisible
- **AND** le code HTTP retourne une erreur client pour les erreurs metier/validation et une erreur serveur en cas d'echec interne
