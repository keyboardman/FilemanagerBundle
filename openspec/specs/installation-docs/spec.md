# installation-docs Specification

## Purpose
TBD - created by archiving change add-installation-documentation. Update Purpose after archive.
## Requirements
### Requirement: Documentation des prérequis

La documentation d'installation SHALL lister les prérequis nécessaires pour utiliser le bundle.

#### Scenario: Prérequis listés

- **WHEN** un développeur consulte la documentation d'installation
- **THEN** il trouve la liste des prérequis : PHP 8.2+, Symfony 8.x, league/flysystem, league/flysystem-bundle, symfony/asset-mapper

---

### Requirement: Documentation de l'installation Composer

La documentation SHALL expliquer comment installer le bundle via Composer.

#### Scenario: Installation Composer

- **WHEN** un développeur suit la documentation
- **THEN** il peut installer le bundle avec `composer require keyboardman/filemanager-bundle` ou via un dépôt path
- **AND** il sait enregistrer le bundle dans `config/bundles.php`

---

### Requirement: Documentation de la configuration Flysystem

La documentation SHALL fournir des exemples de configuration Flysystem pour mapper les storages.

#### Scenario: Configuration multi-storage

- **WHEN** un développeur configure Flysystem
- **THEN** il trouve un exemple `flysystem.yaml` avec au moins deux storages (ex. default.storage, public.storage)
- **AND** chaque storage définit adapter, visibility et options (directory)

---

### Requirement: Documentation de la configuration keyboardman_filemanager

La documentation SHALL expliquer comment configurer les disks du filemanager et les lier aux storages Flysystem.

#### Scenario: Mapping disks vers Flysystem

- **WHEN** un développeur configure keyboardman_filemanager
- **THEN** il trouve un exemple `keyboardman_filemanager.yaml` avec la structure disks
- **AND** chaque disk définit label, storage (référence au service Flysystem), visibility, signed_urls, default_uri
- **AND** la documentation précise que le champ storage référence un storage défini dans flysystem.yaml

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

