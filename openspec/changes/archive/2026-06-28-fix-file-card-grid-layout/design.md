## Context

L'interface du filemanager utilise Bootstrap 5 via les classes utilitaires du bundle (`frontend/css/bootstrap.css`). La grille de fichiers est définie dans `templates/filemanager/main.html.twig` avec `row row-cols-1 row-cols-md-6 g-3`, ce qui affiche jusqu'à 6 cartes par ligne dès le breakpoint `md` (768 px). Chaque carte (`templates/components/file_card.html.twig`) affiche le nom du fichier dans un `<h6 class="text-truncate">`. Les dossiers sont listés dans la barre latérale (`templates/filemanager/sidebar.html.twig`) avec le style par défaut des `nav-link` Bootstrap, sans contrainte de taille ni troncature.

## Goals / Non-Goals

**Goals:**
- Limiter la grille à 4 cartes maximum par ligne sur viewports ≥768 px.
- Réduire la taille typographique des titres de fichiers et des noms de dossiers.
- Conserver la troncature des noms longs.
- Ne modifier que les templates Twig (pas de CSS custom ni de JS).

**Non-Goals:**
- Refonte complète du design system ou des composants Bootstrap.
- Modification du layout header, dropzone ou modal de prévisualisation.
- Ajout de tests automatisés visuels ou E2E.

## Decisions

### 1. Grille Bootstrap : `row-cols-md-4` au lieu de `row-cols-md-6`

Remplacer `row-cols-md-6` par `row-cols-md-4` dans `main.html.twig`.

**Pourquoi :** correspond exactement à l'exigence « max 4 cartes par ligne » en utilisant les utilitaires Bootstrap déjà en place.

**Alternative rejetée — breakpoint `lg` uniquement :** `row-cols-md-4` s'applique dès 768 px, ce qui évite 5–6 colonnes étroites sur tablettes.

### 2. Titre fichier : `<div class="small text-truncate mb-1">` au lieu de `<h6>`

Remplacer le `<h6>` par un élément non-heading avec la classe utilitaire Bootstrap `small` (~0.875 rem).

**Pourquoi :** un `<h6>` reste sémantiquement un titre de section ; le nom de fichier est un libellé, pas un heading. `small` réduit la taille sans CSS additionnel.

**Alternative rejetée — `fs-6` seul :** `fs-6` équivaut à la taille body par défaut ; `small` est visuellement plus compact.

### 3. Noms de dossiers sidebar : `small text-truncate` sur le lien

Ajouter `small text-truncate` à la classe du `<a class="nav-link">` et s'assurer que le conteneur flex permet la troncature (`min-width: 0` via `text-truncate` sur l'élément flex enfant, ou wrapper du texte).

**Pourquoi :** cohérence visuelle avec les cartes fichiers ; la sidebar fait 250 px de large, les noms longs doivent être tronqués.

**Implémentation :** le `nav-link flex-grow-1` recevra `small text-truncate` ; le texte du nom peut être enveloppé si nécessaire pour que l'icône ne soit pas tronquée.

## Risks / Trade-offs

- **[Risque] Troncature icône + texte dans la sidebar** → Mitigation : appliquer `text-truncate` sur un `<span>` contenant uniquement le nom, l'icône restant hors du span tronqué.
- **[Trade-off] 4 colonnes sur tablette paysage** → les cartes seront plus larges qu'avec 6 colonnes ; c'est le comportement souhaité pour la lisibilité.

## Migration Plan

1. Modifier les 3 templates Twig concernés.
2. Recompiler ou rafraîchir les assets si le bundle est consommé via symlink — aucune migration de données.
3. Rollback : revert des changements Twig.

## Open Questions

_(aucune — périmètre clair et limité aux templates existants)_
