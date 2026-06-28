## Context

Le filemanager affiche actuellement les fichiers uniquement en grille de cartes (`main.html.twig` + `file_card.html.twig`). Les filtres (disque, type média, tri) sont déjà gérés via des paramètres de requête HTTP et des composants JS qui rechargent la page (`media-filter.js`, `media-sort.js`). Le `QueryFilterDTO` centralise ces paramètres côté PHP.

L'utilisateur souhaite une vue liste pour lire les noms complets des médias, tout en conservant la vue cartes pour un parcours visuel rapide.

## Goals / Non-Goals

**Goals:**

- Offrir deux modes d'affichage : cartes (défaut) et liste
- Afficher le nom complet du fichier en mode liste (pas de `text-truncate`)
- Permettre de basculer entre les vues depuis l'en-tête
- Persister le choix via le paramètre `view` dans l'URL, comme les autres filtres
- Réutiliser `file_preview.html.twig` et les actions existantes (download, rename, delete, select)

**Non-Goals:**

- Vue liste pour les dossiers (la sidebar reste inchangée)
- Persistance localStorage (l'URL suffit, cohérent avec le reste)
- Colonnes triables ou métadonnées supplémentaires (taille, date) en vue liste
- Pagination ou virtualisation

## Decisions

### 1. Paramètre de requête `view` (valeurs : `card` | `list`)

**Choix :** Ajouter `view` au `QueryFilterDTO` avec défaut `card`, extrait par `QueryFilterFactory`.

**Alternatives :**
- *localStorage seul* — ne survit pas au partage d'URL ni à l'ouverture en iframe avec paramètres
- *Toggle JS sans rechargement* — plus complexe, incohérent avec le pattern existant des filtres

**Rationale :** Cohérence avec `media`, `sort`, `filesystem`. Le choix de vue est conservé lors de la navigation entre dossiers via les liens existants qui propagent `app.request.query.all()`.

### 2. Nouveau composant Twig `file_list_item.html.twig`

**Choix :** Créer un composant dédié plutôt que d'enrichir `file_card.html.twig` avec des conditions.

**Rationale :** Structures HTML différentes (ligne horizontale vs carte verticale). Séparation claire, maintenance facilitée.

**Structure proposée :**
```
┌─────────────────────────────────────────────────────────┐
│ [miniature 48×48]  nom-complet-du-fichier.jpg  [actions]│
└─────────────────────────────────────────────────────────┘
```

- Miniature : conteneur fixe (~48 px) incluant `file_preview.html.twig`
- Nom : classe Bootstrap `text-break` pour retour à la ligne sur noms longs
- Actions : mêmes boutons que `file_card.html.twig`

### 3. Rendu conditionnel dans `main.html.twig`

**Choix :** `{% if filter.view == 'list' %}` pour afficher une liste (`<div class="list-group">` ou lignes flex), sinon la grille actuelle.

**Rationale :** Simple, pas de logique JS côté rendu. Le serveur décide du markup.

### 4. Sélecteur de vue dans `header.html.twig`

**Choix :** `<select id="view-toggle">` avec icônes Bootstrap Icons (grille / liste), placé à côté des filtres existants.

**JS :** Nouveau composant `view-toggle.js` calqué sur `media-sort.js` — change event → `params.set("view", value)` → reload.

### 5. Validation du paramètre `view`

**Choix :** Accepter uniquement `card` et `list` ; toute autre valeur retombe sur `card`.

**Rationale :** Évite les valeurs invalides sans erreur HTTP.

## Risks / Trade-offs

- **[Noms très longs en liste]** → Utiliser `text-break` ; la ligne peut s'agrandir verticalement, acceptable pour ce cas d'usage
- **[Duplication des boutons d'action]** → Extraire un partial `file_actions.html.twig` si la duplication devient gênante ; sinon duplication minimale acceptable dans un premier temps
- **[Miniature en liste pour gros fichiers]** → Réutiliser le preview existant (icône ou thumbnail) dans un conteneur réduit, pas de changement de logique preview

## Migration Plan

Changement additif, sans migration de données. Déploiement direct : les utilisateurs existants voient la vue cartes par défaut (comportement inchangé).

## Open Questions

_(aucune — le périmètre est clair)_
