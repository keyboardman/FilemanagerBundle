## Context

Le bundle sert les médias via `MediaController` (`/kbd/filemanager/media/{filesystem}/{path}`). Pour les requêtes avec en-tête `Range`, le contrôleur ouvre un flux Flysystem (`readStream`), tente un `fseek($stream, $start)` puis lit par chunks.

Ce mécanisme fonctionne pour le stockage local (fichiers seekables) mais **échoue sur S3** : les flux retournés par l'adapter AWS ne sont pas seekables de manière fiable. Le navigateur envoie des requêtes `Range` lors du seek vidéo/audio ; une réponse incorrecte ou une exception non gérée produit des **500** et le lecteur revient au début.

Par ailleurs, la résolution d'URL est incohérente :
- `DiskManager::publicUrl()` utilise `default_uri` si défini (URL S3 directe).
- Le filtre Twig `resolve_url` force toujours le proxy Symfony.

L'option `signed_urls` est documentée mais non implémentée.

## Goals / Non-Goals

**Goals:**
- Permettre la lecture et le seek de vidéos/audio hébergés sur un disk S3 sans erreur 500.
- Supporter correctement HTTP `206 Partial Content` avec en-têtes `Content-Range`, `Accept-Ranges`, `Content-Length`.
- Unifier la stratégie de résolution d'URL entre Twig et l'API.
- Documenter la configuration S3 (CORS, visibilité, `default_uri`, `signed_urls`).
- Couvrir le comportement par des tests automatisés.

**Non-Goals:**
- Support Range pour tous les adapters distants (Azure, GCS, SFTP…) dans cette itération — seulement local + AWS S3 (le plus courant).
- CDN, transcoding vidéo, ou HLS/DASH.
- Modification du frontend (`modal-preview.js`) — le problème est côté serveur/streaming.

## Decisions

### 1. Lecteur par plages adapter-aware (`MediaRangeReader`)

Introduire une interface `MediaRangeReader` (ou service équivalent) injectée dans `MediaController` :

| Adapter | Stratégie |
|---------|-----------|
| Local / seekable | `readStream` + `fseek` (comportement actuel) |
| AWS S3 | `GetObject` avec paramètre `Range: bytes=start-end` via le client S3 sous-jacent |
| Autres | `readStream` depuis le début + skip bytes (fallback, documenté comme limité) |

**Pourquoi :** `fseek` sur un flux S3 PHP est la cause racine des 500 et du seek cassé. S3 supporte nativement les requêtes Range via l'API.

**Alternative rejetée — redirect vers URL S3 publique :** plus performant mais nécessite bucket public + CORS + pas de contrôle d'accès Symfony. Conservé comme option via `default_uri` pour les buckets publics, pas comme seule solution.

### 2. Détection de l'adapter S3

Inspecter le filesystem Flysystem pour retrouver le client S3 (adapter `AwsS3V3Adapter` ou storage tagué `aws`). Extraire bucket + prefix depuis la config disk.

Si l'adapter n'est pas S3, utiliser le fallback seekable/local.

**Alternative rejetée — config explicite `range_strategy: s3|local` :** plus verbeux pour l'utilisateur ; la détection automatique suffit pour AWS.

### 3. Unification de la résolution d'URL

Centraliser dans `DiskManager::publicUrl()` et faire appeler cette méthode depuis `FilemanagerExtension::resolveUrl()`.

Règles :
1. Si `signed_urls: true` et adapter S3 → URL présignée (TTL configurable, ex. 1 h).
2. Sinon si `default_uri` défini → URL directe `{default_uri}/{path}`.
3. Sinon → route proxy `keyboardman_filemanager_media`.

**Pourquoi :** évite que la preview Twig passe par le proxy alors que l'API renvoie une URL S3 directe (ou l'inverse).

### 4. Gestion d'erreurs explicite dans `MediaController`

- `FilesystemException` lors de la lecture → `404 Not Found` ou `502 Bad Gateway` selon le contexte (existence déjà vérifiée → 502).
- Erreurs S3 (credentials, bucket) → log + `502` avec message générique (pas de fuite de détails).
- Plage invalide → `416 Requested Range Not Satisfiable` (déjà partiellement implémenté).

### 5. Documentation S3 CORS

Documenter la configuration CORS minimale pour le seek vidéo quand `default_uri` pointe vers S3 :

```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedOrigins": ["https://votre-app.example"],
    "ExposeHeaders": ["Content-Range", "Accept-Ranges", "Content-Length", "ETag"]
  }
]
```

## Risks / Trade-offs

- **[Risque] Accès au client S3 interne à Flysystem** → Mitigation : wrapper dédié avec fallback sur le proxy seekable ; test d'intégration avec mock S3.
- **[Risque] URLs présignées expirées pendant une longue session** → Mitigation : TTL suffisant (1 h) ; la preview recharge l'URL à chaque ouverture de modal.
- **[Risque] Performance proxy vs URL directe** → Mitigation : recommander `default_uri` + CORS pour la prod ; proxy reste le défaut sécurisé.
- **[Trade-off] Fallback skip-bytes pour adapters non-S3** → acceptable pour l'instant ; documenter la limitation.

## Migration Plan

1. Déployer la correction du `MediaController` — rétrocompatible, pas de changement de config requis.
2. Les projets avec `default_uri` S3 peuvent continuer ; ajouter CORS si seek direct depuis le navigateur.
3. Activer `signed_urls: true` pour buckets privés (nouveau comportement implémenté).
4. Rollback : revert du commit ; aucune migration de données.

## Open Questions

- Faut-il un TTL configurable pour les URLs présignées (`signed_url_ttl`) ? → Proposer 3600 s par défaut, paramètre optionnel dans la config disk.
- Faut-il supporter AsyncAws S3 dans la même itération ? → Oui si le client est facilement accessible ; sinon itération suivante.
