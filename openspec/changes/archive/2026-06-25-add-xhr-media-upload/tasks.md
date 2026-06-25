## 1. Dépendances et Stimulus
- [x] 1.1 Ajouter `@hotwired/stimulus` et `@symfony/ux-dropzone` dans `package.json` ; documenter la suggestion `symfony/ux-dropzone` dans `composer.json`.
- [x] 1.2 Initialiser une application Stimulus dans `frontend/js/filemanager.js` et enregistrer le contrôleur UX Dropzone + `filemanager-upload`.
- [x] 1.3 Importer le CSS UX Dropzone dans le build Vite (avec surcharge Bootstrap optionnelle dans `filemanager.css`).

## 2. Interface dropzone (Symfony UX Dropzone)
- [x] 2.1 Créer `templates/components/upload_dropzone.html.twig` reprenant le markup attendu par le contrôleur `symfony--ux-dropzone--dropzone` (`multiple`, `accept="image/*,video/*"`).
- [x] 2.2 Inclure la dropzone dans `main.html.twig` avec `data-controller="symfony--ux-dropzone--dropzone filemanager-upload"` et les data-attributes d'URL (`upload`, `upload-chunk`, `filesystem`, `path`, `chunk-size`, `chunk-threshold`).
- [x] 2.3 Ajouter le conteneur de barres de progression géré par `filemanager-upload`.
- [x] 2.4 Relier le bouton « Upload » du header au déclenchement de la dropzone (clic sur l'input interne).

## 3. Contrôleur Stimulus `filemanager-upload`
- [x] 3.1 Écouter `dropzone:change` pour récupérer les fichiers sélectionnés ou déposés et alimenter la file d'upload.
- [x] 3.2 Implémenter l'upload monolithique en XHR (`FormData`, `xhr.upload.onprogress`) vers `POST /api/filemanager/upload`.
- [x] 3.3 Découper les fichiers > `chunk_threshold` via `File.prototype.slice` et envoyer les fragments séquentiellement vers `upload-chunk`.
- [x] 3.4 Agréger la progression globale et afficher nom + pourcentage par fichier ; gérer succès, erreur et `beforeunload`.

## 4. Upload fragmenté (serveur)
- [x] 4.1 Ajouter la configuration bundle `chunk_size` (défaut 5 Mo) et `chunk_threshold` (défaut 8 Mo).
- [x] 4.2 Créer `ChunkUploadManager` : réception, validation, stockage temporaire et assemblage des fragments.
- [x] 4.3 Ajouter `POST /api/filemanager/upload-chunk` dans `ApiController`.
- [x] 4.4 Au dernier fragment : assembler, appeler `DiskManager::upload`, nettoyer le temporaire, retourner la réponse finale.

## 5. Validation
- [x] 5.1 Rafraîchir la vue après succès de tous les uploads de la file.
- [x] 5.2 Tester : image légère (upload simple + dropzone UX), vidéo > `upload_max_filesize` (upload fragmenté), annulation navigation (`beforeunload`).
