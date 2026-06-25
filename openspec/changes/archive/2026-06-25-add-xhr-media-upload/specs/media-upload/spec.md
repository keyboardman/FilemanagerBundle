## ADDED Requirements

### Requirement: Zone de dépôt pour l'upload de médias
Le gestionnaire de média SHALL exposer une dropzone basée sur **Symfony UX Dropzone** (contrôleur Stimulus `symfony--ux-dropzone--dropzone`) permettant de déposer ou de sélectionner des fichiers image et vidéo dans le répertoire courant du filesystem sélectionné.

#### Scenario: Dépôt par glisser-déposer
- **WHEN** l'utilisateur glisse un ou plusieurs fichiers image ou vidéo sur la dropzone et les relâche
- **THEN** le contrôleur UX Dropzone émet l'événement `dropzone:change` avec les fichiers sélectionnés
- **AND** le contrôleur `filemanager-upload` ajoute ces fichiers à la file d'upload
- **AND** la dropzone affiche le retour visuel fourni par UX Dropzone pendant le survol

#### Scenario: Sélection par clic
- **WHEN** l'utilisateur clique sur la dropzone ou sur le bouton « Upload » du header
- **THEN** le navigateur ouvre un sélecteur de fichiers filtré sur les types image et vidéo
- **AND** l'événement `dropzone:change` déclenche l'ajout des fichiers à la file d'upload

### Requirement: Upload XHR avec suivi de progression
Le contrôleur Stimulus `filemanager-upload` MUST envoyer chaque fichier via `XMLHttpRequest` en réponse à `dropzone:change` et exposer la progression du transfert à l'utilisateur, en mode monolithique ou fragmenté selon la taille du fichier.

#### Scenario: Progression affichée pendant un upload monolithique
- **WHEN** un fichier dont la taille est inférieure ou égale au seuil `chunk_threshold` est en cours de transfert
- **THEN** le client utilise `XMLHttpRequest` avec un corps `FormData` contenant `file`, `filesystem` et `path` vers `POST /api/filemanager/upload`
- **AND** une barre de progression affiche le pourcentage transféré via les événements `progress` de `xhr.upload`

#### Scenario: Progression affichée pendant un upload fragmenté
- **WHEN** un fichier dont la taille dépasse `chunk_threshold` est en cours de transfert
- **THEN** le client découpe le fichier en fragments de taille `chunk_size` et les envoie séquentiellement via `POST /api/filemanager/upload-chunk`
- **AND** la barre de progression reflète l'avancement global du fichier (fragments déjà envoyés + progression du fragment courant)

#### Scenario: Upload séquentiel de plusieurs fichiers
- **WHEN** l'utilisateur dépose ou sélectionne plusieurs fichiers
- **THEN** le système traite les fichiers un par un dans l'ordre d'ajout
- **AND** chaque fichier dispose de sa propre barre de progression pendant son transfert

#### Scenario: Succès d'upload
- **WHEN** l'API retourne une réponse JSON avec `success: true` (upload monolithique ou dernier fragment assemblé)
- **THEN** la barre de progression du fichier indique un état de succès
- **AND** après le traitement de tous les fichiers de la file, l'interface rafraîchit la liste des médias du répertoire courant

#### Scenario: Échec d'upload
- **WHEN** l'API retourne une erreur HTTP ou un JSON avec `success: false` ou `error`
- **THEN** la barre de progression du fichier concerné affiche un état d'erreur avec le message retourné
- **AND** les fichiers restants de la file continuent d'être traités

### Requirement: Upload fragmenté pour fichiers volumineux
Le système MUST permettre l'upload de fichiers dépassant `post_max_size` ou `upload_max_filesize` en les transmettant par fragments dont chaque requête reste sous les limites PHP.

#### Scenario: Bascule automatique vers l'upload fragmenté
- **WHEN** la taille d'un fichier dépasse `chunk_threshold`
- **THEN** le client génère un `uploadId` unique et envoie les fragments avec `chunkIndex`, `totalChunks`, `totalSize` et `filename`
- **AND** aucun fragment individuel ne dépasse `chunk_size`

#### Scenario: Réception et stockage d'un fragment
- **WHEN** le serveur reçoit un fragment valide pour un `uploadId` donné
- **THEN** le fragment est enregistré dans un répertoire temporaire dédié
- **AND** le serveur retourne `success: true` avec confirmation de réception du fragment

#### Scenario: Assemblage au dernier fragment
- **WHEN** le serveur reçoit le fragment d'index `totalChunks - 1` et que tous les fragments attendus sont présents
- **THEN** le serveur assemble les fragments en un fichier unique
- **AND** le fichier assemblé est enregistré dans le filesystem cible via `DiskManager`
- **AND** le répertoire temporaire de l'`uploadId` est supprimé
- **AND** la réponse JSON inclut `success: true`, `name`, `path`, `mimeType`, `size` et `url`

#### Scenario: Fragment invalide ou séquence incorrecte
- **WHEN** un fragment est reçu avec un `chunkIndex` incohérent, un `uploadId` inconnu ou des métadonnées invalides
- **THEN** le serveur retourne une erreur HTTP 400 avec un message explicite
- **AND** aucun fichier partiel n'est publié dans le filesystem cible

### Requirement: Contrat API d'upload de médias
Les endpoints d'upload MUST accepter des requêtes multipart et retourner des réponses JSON cohérentes pour le client XHR.

#### Scenario: Requête d'upload monolithique valide
- **WHEN** le client envoie une requête `POST` multipart à `/api/filemanager/upload` avec un champ `file` et les champs `filesystem` et `path`
- **THEN** l'API enregistre le fichier dans le chemin cible du filesystem
- **AND** l'API retourne `success: true` avec au minimum `name`, `path`, `mimeType`, `size` et `url`

#### Scenario: Requête sans fichier
- **WHEN** le client envoie une requête à `/api/filemanager/upload` sans champ `file` valide
- **THEN** l'API retourne un code HTTP 400
- **AND** le corps JSON contient un message d'erreur explicite

### Requirement: Protection contre la perte d'upload en cours
Le gestionnaire de média SHALL avertir l'utilisateur avant de quitter la page tant qu'un upload est actif.

#### Scenario: Navigation pendant un upload actif
- **WHEN** au moins un fichier est en cours de transfert (monolithique ou fragmenté)
- **AND** l'utilisateur tente de quitter ou de recharger la page
- **THEN** le navigateur affiche un avertissement de confirmation avant de quitter
