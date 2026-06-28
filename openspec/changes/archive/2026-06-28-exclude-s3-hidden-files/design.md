## Context

Le filemanager liste les médias via `DiskManager::list()`, qui itère sur `iterateListing()`. Pour S3, le listage passe par `SafeAsyncAwsS3Lister::listContents()` (contournement pagination QNAP / CommonPrefixes).

`DiskManager` contient déjà une méthode privée `isHidden()` et un filtre dans `list()` qui ignore les entrées dont le `basename` commence par `.`. Malgré cela, des fichiers cachés apparaissent encore avec S3 — probablement à cause de chemins normalisés différemment (clés S3, stripPrefix, trailing slash) ou parce que le filtre n'est pas appliqué assez tôt / de façon uniforme sur les CommonPrefixes.

Les buckets S3 hébergent souvent des artefacts système (`.DS_Store`, `._*`, dossiers `.Spotlight-V100`, etc.) qui ne doivent jamais être proposés comme médias.

## Goals / Non-Goals

**Goals:**
- Garantir que fichiers et dossiers dont le **nom direct** commence par `.` n'apparaissent jamais dans `DiskManager::list()`.
- Appliquer le filtre de façon fiable pour S3 (objets `Contents` et dossiers `CommonPrefixes`).
- Centraliser la logique « entrée cachée » pour éviter la duplication et les écarts local/S3.
- Couvrir le comportement par des tests automatisés.

**Non-Goals:**
- Masquer des segments cachés **au milieu** d'un chemin profond lors d'un listage récursif profond (le listage UI est non-récursif, un niveau à la fois).
- Filtrer côté AWS avec un prefix négatif (S3 ne le supporte pas nativement).
- Supprimer physiquement les fichiers cachés du bucket.
- Modifier l'UI (cards/liste) — seul le contenu API/listing change.

## Decisions

### 1. Extraire et réutiliser une fonction de détection « hidden »

Promouvoir la logique de `DiskManager::isHidden()` en helper réutilisable (méthode statique sur une petite classe utilitaire, ou méthode package-private partagée) basée sur le **dernier segment** du chemin :

```php
// Exemple : isHiddenPath('photos/.DS_Store') → true
// Exemple : isHiddenPath('photos/vacances.jpg') → false
```

Normaliser le chemin avant test : `trim($path, '/')`, puis `basename()`.

**Pourquoi :** une seule source de vérité, testable unitairement.

**Alternative rejetée — regex sur la clé S3 complète :** plus fragile selon le prefix Flysystem configuré.

### 2. Filtrer dans `SafeAsyncAwsS3Lister` en plus de `DiskManager`

Appliquer le filtre hidden **dans** `SafeAsyncAwsS3Lister::listContents()` avant chaque `yield`, pour les objets et les CommonPrefixes.

Conserver aussi le filtre dans `DiskManager::list()` comme filet de sécurité pour le listage Flysystem standard (disques locaux).

**Pourquoi :** le lister S3 est la source du bug observé ; filtrer à la source évite que des entrées cachées influencent d'autres usages de `iterateListing()` (ex. `deleteEmptyDirectory` ne doit pas considérer un `.placeholder` comme contenu empêchant la suppression si on décide de l'ignorer — voir décision 3).

**Alternative rejetée — filtre uniquement dans DiskManager::list() :** insuffisant si le chemin S3 stripé ne correspond pas au format attendu par `basename()` dans certains cas limites.

### 3. Ignorer les entrées cachées pour `deleteEmptyDirectory`

Lors de la vérification « dossier vide » dans `deleteEmptyDirectory`, ignorer les entrées cachées comme pour le listing UI.

**Pourquoi :** cohérence — un dossier ne contenant que `.DS_Store` devrait pouvoir être supprimé si l'utilisateur le souhaite (hors scope UI de suppression de `.DS_Store` individuellement, mais le dossier parent ne doit pas être bloqué par un artefact caché).

### 4. Tests

| Niveau | Cible |
|--------|--------|
| Unit | Helper `isHiddenPath` avec cas limites (racine, trailing slash, sous-dossier) |
| Unit | `SafeAsyncAwsS3Lister` mocké : Contents + CommonPrefixes avec noms `.xxx` |
| Integration (optionnel, `@group s3`) | Assertion dans `S3ContentListTest` : aucun `name` retourné ne commence par `.` |

**Alternative rejetée — test d'intégration S3 seul :** trop lent et dépendant d'un bucket réel pour valider la logique de base.

## Risks / Trade-offs

- **[Risque] Faux positif sur un média légitime nommé `.something.jpg`** → Acceptable : convention Unix des fichiers cachés ; peu probable pour des médias utilisateur.
- **[Risque] Dossier réellement nommé `.archive` masqué** → Comportement voulu (fichiers/dossiers cachés exclus volontairement).
- **[Compromis] Double filtrage (S3 lister + DiskManager)** → Légère redondance mais robustesse accrue ; coût négligeable.

## Migration Plan

1. Implémenter helper + filtre S3 lister.
2. Ajouter tests unitaires.
3. Déployer — aucune migration de données ; effet immédiat au prochain listage.
4. Rollback : revert du commit (comportement précédent : entrées cachées visibles).

## Open Questions

_Aucune — le périmètre est clair (nom direct commençant par `.`)._
