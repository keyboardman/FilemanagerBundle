## ADDED Requirements

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
