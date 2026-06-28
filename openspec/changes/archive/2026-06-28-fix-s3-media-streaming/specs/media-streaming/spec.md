## ADDED Requirements

### Requirement: Servir les média via HTTP Range

Le proxy média Symfony MUST accepter les requêtes `GET` et `HEAD` avec en-tête `Range` et répondre avec `206 Partial Content` lorsque la plage demandée est valide.

#### Scenario: Requête Range valide sur une vidéo

- **WHEN** le client envoie `GET /kbd/filemanager/media/{disk}/{path}` avec `Range: bytes=1024-2047`
- **AND** le fichier existe et fait au moins 2048 octets
- **THEN** la réponse a le statut `206`
- **AND** l'en-tête `Content-Range` vaut `bytes 1024-2047/{taille_totale}`
- **AND** le corps contient exactement 1024 octets correspondant à la plage demandée

#### Scenario: Requête Range invalide

- **WHEN** le client envoie `Range: bytes=999999-` pour un fichier de 1000 octets
- **THEN** la réponse a le statut `416 Requested Range Not Satisfiable`
- **AND** l'en-tête `Content-Range` vaut `bytes */1000`

---

### Requirement: Streaming S3 compatible seek vidéo

Pour un disk configuré avec l'adapter AWS S3, le proxy média MUST lire les plages via l'API S3 (`GetObject` avec paramètre Range) et MUST NOT dépendre de `fseek` sur un flux non seekable.

#### Scenario: Seek vidéo sur disk S3

- **WHEN** un utilisateur ouvre la prévisualisation d'une vidéo stockée sur un disk S3
- **AND** il déplace la barre de progression à mi-parcours
- **THEN** le navigateur reçoit une réponse `206` valide
- **AND** la lecture reprend à la position demandée sans revenir au début

#### Scenario: Erreur S3 lors de la lecture

- **WHEN** la lecture d'une plage S3 échoue (credentials, objet introuvable côté API)
- **THEN** le serveur répond avec un code HTTP explicite (`404` ou `502`)
- **AND** le serveur MUST NOT renvoyer une erreur 500 générique non journalisée

---

### Requirement: En-têtes de streaming standard

Toute réponse média servie par le proxy MUST inclure les en-têtes nécessaires à la lecture progressive.

#### Scenario: Réponse complète sans Range

- **WHEN** le client envoie une requête sans en-tête `Range`
- **THEN** la réponse a le statut `200`
- **AND** les en-têtes `Content-Type`, `Accept-Ranges: bytes` et `Content-Length` sont présents

#### Scenario: Support HEAD

- **WHEN** le client envoie `HEAD /kbd/filemanager/media/{disk}/{path}`
- **THEN** la réponse inclut `Content-Type`, `Accept-Ranges` et `Content-Length`
- **AND** aucun corps n'est renvoyé

---

### Requirement: Résolution d'URL publique unifiée

La résolution d'URL publique des fichiers MUST être centralisée et cohérente entre l'API et les templates Twig.

#### Scenario: Disk sans default_uri

- **WHEN** un disk n'a ni `default_uri` ni `signed_urls`
- **THEN** l'URL publique pointe vers la route proxy `keyboardman_filemanager_media`

#### Scenario: Disk S3 avec signed_urls

- **WHEN** un disk S3 a `signed_urls: true`
- **THEN** l'URL publique est une URL présignée S3 valide pour la durée configurée

#### Scenario: Cohérence Twig et API

- **WHEN** le filtre Twig `resolve_url` génère l'URL d'un fichier
- **THEN** l'URL obtenue est identique à celle retournée par `DiskManager::publicUrl()` pour les mêmes paramètres
