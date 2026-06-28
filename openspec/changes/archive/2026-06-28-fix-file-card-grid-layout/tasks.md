## 1. Grille de fichiers

- [x] 1.1 Remplacer `row-cols-md-6` par `row-cols-md-4` dans `templates/filemanager/main.html.twig`

## 2. Titre des cartes fichiers

- [x] 2.1 Remplacer `<h6 class="text-truncate">` par `<div class="small text-truncate mb-1">` dans `templates/components/file_card.html.twig`

## 3. Noms de dossiers (sidebar)

- [x] 3.1 Ajouter `small` au lien de navigation des dossiers dans `templates/filemanager/sidebar.html.twig`
- [x] 3.2 Envelopper le nom du dossier dans un `<span class="text-truncate">` pour tronquer le texte sans affecter l'icône
- [x] 3.3 Appliquer la même troncature au lien « .. » (remontée parent) si applicable

## 4. Vérification manuelle

- [x] 4.1 Vérifier visuellement la grille (max 4 cartes/ligne sur viewport ≥768 px, 1 colonne sur mobile)
- [x] 4.2 Vérifier la taille réduite et la troncature des titres fichiers et dossiers avec des noms longs
