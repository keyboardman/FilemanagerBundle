# Change: Upload média XHR avec dropzone, progression et upload fragmenté

## Why
L'upload actuel utilise `fetch` sans suivi de progression et un simple sélecteur de fichier caché. Pour des images et vidéos volumineuses, l'utilisateur n'a aucun retour visuel pendant le transfert. De plus, les vidéos dépassant `post_max_size` ou `upload_max_filesize` échouent silencieusement ou avec une erreur opaque : un upload monolithique ne peut pas les traiter sans reconfigurer PHP. Un upload via `XMLHttpRequest` avec dropzone, barre de progression et découpage en fragments est nécessaire pour rendre ces transferts fiables.

## What Changes
- Intégrer **[Symfony UX Dropzone](https://symfony.com/bundles/ux-dropzone/current/index.html)** pour l'interface glisser-déposer (Stimulus), à la place d'une dropzone maison.
- Ajouter un contrôleur Stimulus dédié (`filemanager-upload`) qui écoute `dropzone:change` et déclenche l'upload XHR avec barre de progression.
- Remplacer l'upload `fetch` par `XMLHttpRequest` exposant les événements de progression (`xhr.upload.onprogress`).
- Ajouter un **upload fragmenté** côté client et serveur pour les fichiers dépassant un seuil configurable.
- Conserver l'endpoint `POST /api/filemanager/upload` pour les fichiers sous le seuil (rétrocompatible).
- Ajouter un endpoint `POST /api/filemanager/upload-chunk` pour recevoir, assembler et finaliser les fragments.
- Migrer l'entrée JS `filemanager.js` vers une application Stimulus (Vite) embarquant les contrôleurs UX Dropzone et upload.
- Formaliser le contrat d'upload dans une nouvelle capacité `media-upload`.

## Impact
- Affected specs: `media-upload` (nouvelle capacité).
- Affected code: `frontend/js/` (Stimulus), `templates/filemanager/`, `frontend/css/filemanager.css`, `src/Controller/ApiController.php`, service d'assemblage des fragments, `composer.json` (suggestion `symfony/ux-dropzone`), `package.json` (`@symfony/ux-dropzone`, `@hotwired/stimulus`).
