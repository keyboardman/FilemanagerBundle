# media-directory-management Specification

## Purpose
TBD - created by archiving change add-media-subfolder-management. Update Purpose after archive.
## Requirements
### Requirement: Création de sous-dossiers
Le gestionnaire de média SHALL permettre à l'utilisateur de créer un sous-dossier dans le répertoire courant du filesystem sélectionné.

#### Scenario: Création d'un sous-dossier valide
- **WHEN** l'utilisateur saisit un nom de dossier valide depuis l'interface et confirme l'action
- **THEN** le système crée le dossier dans le chemin courant
- **AND** l'interface affiche le nouveau dossier dans la liste des répertoires

#### Scenario: Rejet d'un nom invalide
- **WHEN** l'utilisateur soumet un nom vide ou non valide
- **THEN** le système refuse la création
- **AND** l'interface affiche un message d'erreur explicite sans modifier l'arborescence

### Requirement: Renommage de dossier
Le gestionnaire de média SHALL permettre le renommage d'un dossier existant dans le filesystem sélectionné.

#### Scenario: Renommage d'un dossier avec succès
- **WHEN** l'utilisateur choisit un dossier existant, saisit un nouveau nom valide et confirme
- **THEN** le système renomme le dossier
- **AND** l'interface reflète le nouveau nom après rafraîchissement de la vue

#### Scenario: Échec de renommage avec erreur métier
- **WHEN** le dossier cible n'existe pas, ou que le nouveau nom est invalide, ou qu'un conflit de nom existe
- **THEN** le système retourne une erreur explicite
- **AND** l'interface conserve l'état courant sans renommage partiel

### Requirement: Contrat API de gestion de dossiers
Les endpoints API de création et renommage de dossiers MUST retourner des réponses JSON cohérentes pour les clients JavaScript du filemanager.

#### Scenario: Réponse de succès API
- **WHEN** une opération de création ou de renommage aboutit
- **THEN** l'API retourne `success: true`
- **AND** l'API inclut au minimum les informations utiles au rafraîchissement de la vue (chemin ou nom mis à jour)

#### Scenario: Réponse d'erreur API
- **WHEN** une opération de création ou de renommage échoue
- **THEN** l'API retourne `success: false` avec un message d'erreur lisible
- **AND** le code HTTP reflète une erreur client pour une entrée invalide, ou une erreur serveur en cas d'échec interne

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

