# Change: Ajouter la suppression securisee des medias

## Why
Le gestionnaire de media ne formalise pas encore la suppression des fichiers et des dossiers, ce qui cree un risque de suppression accidentelle et des comportements incoherents selon le type d'element. Une confirmation explicite et une regle stricte sur les dossiers vides sont necessaires pour proteger les contenus.

## What Changes
- Ajouter la capacite de supprimer un fichier depuis le gestionnaire de media avec une confirmation modale obligatoire avant execution.
- Ajouter la capacite de supprimer un dossier uniquement s'il est vide, avec message d'erreur explicite sinon.
- Formaliser le contrat API de suppression (succes, erreurs metier, codes HTTP) pour les clients JavaScript.

## Impact
- Affected specs: `media-directory-management`.
- Affected code: `src/Controller/ApiController.php`, services de gestion de disques, composants UI/JS du filemanager (liste, actions contextuelles, modal de confirmation).
