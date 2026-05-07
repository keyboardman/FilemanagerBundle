# Change: Ajouter la gestion des sous-dossiers média

## Why
Le gestionnaire de média permet déjà la navigation dans les dossiers et le renommage via l'API, mais il manque une capacité explicite de création de sous-dossiers depuis l'interface. Cette limite freine l'organisation des médias et oblige les utilisateurs à créer l'arborescence en dehors de l'outil.

## What Changes
- Ajouter la capacité de créer un sous-dossier dans le chemin courant du filesystem sélectionné.
- Formaliser le renommage des dossiers depuis l'interface, avec validation des entrées.
- Clarifier les réponses API attendues en cas de succès et d'erreur pour la création et le renommage de dossiers.

## Impact
- Affected specs: `media-directory-management` (nouvelle capacité).
- Affected code: `src/Controller/ApiController.php`, `src/Disk/DiskManager.php`, `templates/filemanager/header.html.twig`, `templates/filemanager/sidebar.html.twig`, `frontend/js/components/modal-rename.js`, scripts JS liés à l'UI du filemanager.
