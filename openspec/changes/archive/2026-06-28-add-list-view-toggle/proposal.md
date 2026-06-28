# Change: Ajouter une vue liste avec bascule card/liste

## Why

Le filemanager n'offre actuellement qu'une vue en cartes avec aperçu visuel (images, vidéos, etc.). Les noms de fichiers y sont tronqués pour tenir dans la largeur de la carte, ce qui rend difficile l'identification de médias aux noms longs. Une vue liste permettant d'afficher le nom complet et un basculement entre les deux modes répondent à des besoins complémentaires : parcours visuel rapide en cartes, lecture exhaustive des noms en liste.

## What Changes

- Ajouter une vue liste des fichiers affichant le nom complet, une miniature compacte et les actions existantes (télécharger, renommer, supprimer, sélectionner).
- Ajouter un contrôle dans l'en-tête pour basculer entre la vue cartes (défaut) et la vue liste.
- Persister le choix de vue via un paramètre de requête (`view=card` ou `view=list`), cohérent avec les filtres existants (type, tri).
- Conserver la vue cartes actuelle inchangée comme mode par défaut.
- Réutiliser les composants existants (`file_preview`, actions) pour éviter la duplication de logique.

## Capabilities

### New Capabilities

_(aucune — extension de l'interface existante)_

### Modified Capabilities

- `filemanager-ui`: Ajout des exigences de vue liste, bascule card/liste et affichage du nom complet en mode liste.

## Impact

- Affected templates: `templates/filemanager/header.html.twig`, `templates/filemanager/main.html.twig`, nouveau `templates/components/file_list_item.html.twig`
- Affected DTO: `src/DTO/QueryFilterDTO.php`, `src/DTO/QueryFilterFactory.php`
- Affected frontend: `frontend/js/filemanager.js` (gestion du sélecteur de vue)
- Affected specs: `filemanager-ui` (delta)
- Pas d'impact API backend ni de changement de comportement métier des opérations fichier
