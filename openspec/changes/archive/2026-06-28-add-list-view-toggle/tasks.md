## 1. Backend — paramètre de vue

- [x] 1.1 Ajouter la propriété `view` (`card`|`list`, défaut `card`) à `QueryFilterDTO` et à la factory `create()`
- [x] 1.2 Extraire et valider le paramètre `view` dans `QueryFilterFactory` (valeur invalide → `card`)

## 2. Templates — vue liste

- [x] 2.1 Créer `templates/components/file_list_item.html.twig` (miniature compacte, nom complet avec `text-break`, actions réutilisées)
- [x] 2.2 Modifier `templates/filemanager/main.html.twig` pour afficher la grille cartes ou la liste selon `filter.view`
- [x] 2.3 Ajouter le sélecteur de vue (`<select id="view-toggle">`) dans `templates/filemanager/header.html.twig`

## 3. Frontend — bascule de vue

- [x] 3.1 Créer `frontend/js/components/view-toggle.js` (change event → `params.set("view", value)` → reload)
- [x] 3.2 Enregistrer et appeler `viewToggle()` dans `frontend/js/filemanager.js`

## 4. Styles

- [x] 4.1 Ajouter les styles CSS pour la ligne liste (miniature fixe, alignement horizontal, espacement) dans `frontend/css/filemanager.css`

## 5. Vérification

- [x] 5.1 Vérifier manuellement : vue cartes par défaut, bascule liste, nom complet visible, persistance du `view` lors de la navigation et des changements de filtre
- [x] 5.2 Recompiler les assets frontend si nécessaire (`npm run build` ou équivalent)
