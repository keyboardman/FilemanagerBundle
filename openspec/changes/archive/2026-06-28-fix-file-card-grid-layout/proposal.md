# Change: Ajuster la grille de cartes et la taille des titres

## Why

Dans l'interface du filemanager, les titres des fichiers (cartes principales) et des dossiers (barre latérale) apparaissent trop grands par rapport au reste de l'UI. La grille affiche actuellement jusqu'à 6 cartes par ligne (`row-cols-md-6`), ce qui rend les cartes étroites et les titres peu lisibles. Un affichage plus compact avec au maximum 4 cartes par ligne améliorera la lisibilité et l'équilibre visuel.

## What Changes

- Réduire la taille typographique du titre des fichiers dans `file_card.html.twig` (remplacer le `<h6>` par un style plus discret avec troncature conservée).
- Réduire la taille typographique des noms de dossiers dans la barre latérale (`sidebar.html.twig`).
- Limiter la grille principale à un maximum de 4 cartes par ligne sur les viewports moyens et larges (au lieu de 6).
- Conserver le comportement responsive existant (1 colonne sur mobile).

## Capabilities

### New Capabilities

- `filemanager-ui`: Exigences d'affichage de l'interface filemanager (grille de fichiers, titres, barre latérale des dossiers).

### Modified Capabilities

_(aucune — changement purement visuel, sans modification de comportement métier existant dans les specs actuelles)_

## Impact

- Affected templates: `templates/filemanager/main.html.twig`, `templates/components/file_card.html.twig`, `templates/filemanager/sidebar.html.twig`
- Affected specs: `filemanager-ui` (nouvelle)
- Pas d'impact API, PHP, JavaScript ou tests automatisés existants
