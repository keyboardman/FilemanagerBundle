## ADDED Requirements

### Requirement: Documentation S3 pour le streaming vidéo

La documentation d'installation MUST inclure une section dédiée à la configuration S3 pour la lecture et le seek de vidéos/audio.

#### Scenario: Configuration CORS documentée

- **WHEN** un développeur configure un disk S3 avec `default_uri`
- **THEN** il trouve un exemple de politique CORS S3 autorisant `GET`, `HEAD` et exposant `Content-Range`, `Accept-Ranges`, `Content-Length`
- **AND** la documentation explique que sans CORS le seek vidéo échoue côté navigateur

#### Scenario: Choix proxy vs URL directe

- **WHEN** un développeur hésite entre proxy Symfony et URL S3 directe
- **THEN** la documentation explique que le proxy est recommandé pour les buckets privés
- **AND** que `default_uri` + bucket public + CORS convient pour décharger le serveur
- **AND** que `signed_urls: true` génère des URLs présignées pour les buckets privés sans exposer le bucket

#### Scenario: Dépannage erreurs 500 et seek

- **WHEN** un développeur rencontre des erreurs 500 ou un seek vidéo qui revient au début
- **THEN** la documentation liste les causes courantes (flux S3 non seekable, CORS manquant, credentials invalides, `default_uri` incorrect)
- **AND** propose les vérifications à effectuer (requête Range manuelle, logs Symfony, configuration disk)
