# installation-docs Specification

## Purpose
TBD - created by archiving change add-installation-documentation. Update Purpose after archive.
## Requirements
### Requirement: Documentation des prérequis

La documentation d'installation SHALL lister les prérequis nécessaires pour utiliser le bundle.

#### Scenario: Prérequis listés

- **WHEN** un développeur consulte la documentation d'installation
- **THEN** il trouve la liste des prérequis : PHP 8.2+, Symfony 8.x, symfony/asset-mapper
- **AND** il est indiqué que `league/flysystem` et `league/flysystem-bundle` sont installés automatiquement avec le bundle
- **AND** il est indiqué que `League\FlysystemBundle\FlysystemBundle` doit être enregistré dans `config/bundles.php`

---

### Requirement: Documentation de l'installation Composer

La documentation SHALL expliquer comment installer le bundle via Composer.

#### Scenario: Installation Composer

- **WHEN** un développeur suit la documentation
- **THEN** il peut installer le bundle avec `composer require keyboardman/filemanager-bundle` ou via un dépôt path
- **AND** il sait enregistrer le bundle dans `config/bundles.php`
- **AND** il n'a pas besoin d'exécuter une commande Composer séparée pour Flysystem

---

### Requirement: Documentation de la configuration keyboardman_filemanager

La documentation SHALL expliquer comment configurer les disks du filemanager avec une section `storage` inline définissant l'adapter Flysystem.

#### Scenario: Configuration disks avec stockage inline

- **WHEN** un développeur configure keyboardman_filemanager
- **THEN** il trouve un exemple `keyboardman_filemanager.yaml` avec la structure disks
- **AND** chaque disk définit `label`, `storage` (adapter, visibility, options), `signed_urls`, `default_uri`
- **AND** la documentation précise qu'aucun fichier `flysystem.yaml` séparé n'est requis pour le filemanager

---

### Requirement: Documentation des variables d'environnement

La documentation SHALL lister les variables d'environnement utilisées par le bundle.

#### Scenario: Variables d'environnement documentées

- **WHEN** un développeur configure son projet
- **THEN** il trouve DEFAULT_URI (base URL pour les URIs par défaut)
- **AND** il trouve FILEMANAGER_TOKENS (tokens pour l'accès iframe cross-domain)
- **AND** il trouve FILEMANAGER_TOKEN_ENABLED (activation du contrôle par token)

---

### Requirement: Documentation du build des assets

La documentation SHALL expliquer comment compiler les assets JavaScript et CSS du filemanager.

#### Scenario: Build Vite

- **WHEN** un développeur veut utiliser le filemanager
- **THEN** il trouve les commandes pour builder les assets (ex. `npm run build` ou `vite build`)
- **AND** la documentation précise qu'il existe deux entrées : filemanager.js (app complète) et filemanager-field.js (widget modal)

---

### Requirement: Documentation de l'utilisation du FilemanagerType

La documentation SHALL montrer comment utiliser le FilemanagerType dans un formulaire Symfony.

#### Scenario: Intégration dans un formulaire

- **WHEN** un développeur veut ajouter un champ filemanager à son formulaire
- **THEN** il trouve un exemple avec `->add('media', FilemanagerType::class, [...])`
- **AND** les options documentées incluent crossdomain, media, token

---

### Requirement: Documentation de l'inclusion du modal

La documentation SHALL expliquer comment inclure le modal et le script filemanager-field dans les templates.

#### Scenario: Inclusion du modal

- **WHEN** un développeur utilise le FilemanagerType dans une page
- **THEN** il sait qu'il doit inclure `@KeyboardmanFilemanager/iframe/modal.html.twig` dans le template
- **AND** le modal fournit la structure HTML (#filemanager-modal) et charge filemanager-field.css et filemanager-field.js

---

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

