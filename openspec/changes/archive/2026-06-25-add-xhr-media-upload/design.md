## Context
Le filemanager expose déjà un endpoint `POST /api/filemanager/upload` acceptant un `FormData` avec les champs `file`, `filesystem` et `path`. Le composant `file-upload.js` envoie un seul fichier via `fetch`, sans progression ni glisser-déposer. Les médias ciblés (images et vidéos) peuvent dépasser les limites PHP (`upload_max_filesize`, `post_max_size`).

Le bundle utilise **Vite** pour builder ses assets (`frontend/js/filemanager.js`) et n'intègre pas encore Stimulus. [Symfony UX Dropzone](https://symfony.com/bundles/ux-dropzone/current/index.html) fournit une dropzone légère basée sur Stimulus ; elle gère la sélection de fichiers (drag & drop, clic, événements `dropzone:change`) mais **pas** l'upload XHR ni la progression — ce comportement reste à implémenter dans un contrôleur Stimulus complémentaire.

## Goals / Non-Goals
- Goals:
  - Dropzone via **Symfony UX Dropzone** (UI, accessibilité, styles par défaut adaptables).
  - Upload XHR avec progression et upload fragmenté pour contourner les limites PHP.
  - File d'attente séquentielle pour plusieurs fichiers.
  - Assemblage côté serveur et écriture finale via `DiskManager::upload`.
- Non-Goals:
  - Upload reprise après interruption (resumable) — hors périmètre initial.
  - Utiliser `DropzoneType` dans un formulaire Symfony — le filemanager n'est pas un formulaire classique ; le markup Twig reprend la structure attendue par le contrôleur Stimulus UX Dropzone.
  - Compression ou transcodage côté client.

## Decisions
- Decision: utiliser **Symfony UX Dropzone** pour la dropzone.
  - Alternatives considérées: dropzone maison en vanilla JS — plus de code à maintenir pour un comportement déjà couvert par UX ; librairie Dropzone.js — dépendance lourde et hors écosystème Symfony.
  - UX Dropzone couvre: zone drag & drop, input fichier masqué, événements `dropzone:connect`, `dropzone:change`, `dropzone:clear`, feuille de style optionnelle.
  - L'upload (XHR, fragments, barres de progression) est implémenté dans un contrôleur Stimulus `filemanager-upload` co-déclaré sur le même élément (`data-controller="symfony--ux-dropzone--dropzone filemanager-upload"`).
- Decision: embarquer Stimulus dans le build Vite du bundle.
  - Le bundle reste autonome : `package.json` ajoute `@hotwired/stimulus` et `@symfony/ux-dropzone` ; `filemanager.js` démarre une `Application` Stimulus et enregistre les contrôleurs.
  - `composer.json` suggère `symfony/ux-dropzone` pour les projets hôtes utilisant AssetMapper / StimulusBundle (alignement versions), sans obligation si les assets sont fournis pré-buildés par le bundle.
- Decision: stratégie hybride selon la taille du fichier.
  - Fichier ≤ `chunk_threshold` (défaut 8 Mo) → upload monolithique via `POST /api/filemanager/upload`.
  - Fichier > `chunk_threshold` → fragments de `chunk_size` (défaut 5 Mo) via `POST /api/filemanager/upload-chunk`.
- Decision: protocole d'upload fragmenté (un seul endpoint).
  - Champs `FormData`: `filesystem`, `path`, `filename`, `uploadId` (UUID), `chunkIndex`, `totalChunks`, `totalSize`, `chunk`.
  - Réponse intermédiaire: `{ "success": true, "uploadId": "...", "chunkIndex": N, "received": true }`.
  - Réponse finale: même contrat que l'upload monolithique (`success`, `name`, `path`, `mimeType`, `size`, `url`).
- Decision: placement UI.
  - Dropzone UX dans `main.html.twig` au-dessus de la grille de fichiers.
  - Le bouton « Upload » du header déclenche le clic sur l'input interne de la dropzone (via le contrôleur Stimulus ou `dropzone:connect`).
  - Barres de progression rendues dans un conteneur dédié géré par `filemanager-upload` (hors scope UX Dropzone).
- Decision: styles.
  - Importer la feuille UX Dropzone par défaut depuis Vite ; surcharger dans `filemanager.css` pour cohérence Bootstrap si nécessaire.

## Risks / Trade-offs
- Double dépendance Stimulus (bundle Vite + éventuel StimulusBundle hôte) → Mitigation: assets pré-buildés ; pas de conflit si le filemanager charge uniquement son propre `filemanager.js`.
- UX Dropzone orienté formulaires → Mitigation: reproduire le markup du `form_theme` UX Dropzone dans un partial Twig `components/upload_dropzone.html.twig` sans instancier de `DropzoneType`.
- Espace disque temporaire (fragments) → Mitigation: nettoyage après assemblage et TTL pour uploads abandonnés.

## Migration Plan
1. Ajouter les dépendances npm (`@hotwired/stimulus`, `@symfony/ux-dropzone`) et configurer Stimulus dans `filemanager.js`.
2. Remplacer `file-upload.js` par le contrôleur Stimulus `filemanager-upload`.
3. Intégrer le partial Twig dropzone et retirer l'input caché du header.
4. Implémenter le service serveur et l'endpoint `upload-chunk`.
5. Documenter dans le README la suggestion `composer require symfony/ux-dropzone` pour les projets en AssetMapper.

## Open Questions
- Faut-il exposer `chunk_size` / `chunk_threshold` dans la configuration Symfony du bundle ? → Oui, avec valeurs par défaut documentées.
- Faut-il désactiver le CSS par défaut UX Dropzone au profit d'un style 100 % Bootstrap ? → À trancher à l'implémentation ; commencer avec le CSS UX puis ajuster.
