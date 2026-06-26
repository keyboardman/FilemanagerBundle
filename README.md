# Keyboardman Filemanager Bundle

Bundle Symfony pour intégrer un filemanager dans vos formulaires, basé sur [Flysystem](https://flysystem.thephpleague.com/) pour gérer plusieurs types de stockage (local, S3, etc.).

- **Dépôt** : [https://github.com/keyboardman/FilemanagerBundle](https://github.com/keyboardman/FilemanagerBundle/tree/main)

## Prérequis

- **PHP** 8.2 ou supérieur
- **Symfony** 8.x
- **symfony/asset-mapper** (pour exposer les assets du bundle)

`league/flysystem`, `league/flysystem-bundle` et `league/flysystem-aws-s3-v3` sont installés automatiquement avec le bundle. Il reste à enregistrer `League\FlysystemBundle\FlysystemBundle` dans `config/bundles.php`.

## Installation

### 1. Installer le bundle avec Composer

**Depuis GitHub :**

Dans le `composer.json` du projet, ajoutez le dépôt puis installez :

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/keyboardman/FilemanagerBundle" }
    ],
    "require": {
        "keyboardman/filemanager-bundle": "dev-main"
    }
}
```

```bash
composer update keyboardman/filemanager-bundle
```

`league/flysystem`, `league/flysystem-bundle` et `league/flysystem-aws-s3-v3` sont installés en même temps ; aucune commande Composer supplémentaire n'est requise.

### 2. Enregistrer le bundle

Si ce n’est pas fait automatiquement (Flex), ajoutez dans `config/bundles.php` :

```php
return [
    // ...
    Keyboardman\FilemanagerBundle\KeyboardmanFilemanagerBundle::class => ['all' => true],
    League\FlysystemBundle\FlysystemBundle::class => ['all' => true],
];
```

Enregistrez `KeyboardmanFilemanagerBundle` **avant** `FlysystemBundle` afin que les storages soient créés automatiquement à partir de votre configuration.

### 3. Enregistrer les routes

Dans `config/routes/` (ou équivalent), ajoutez un fichier qui charge les routes du bundle, par exemple `keyboardman_filemanager_bundle_routes.yaml` :

```yaml
keyboardman_filemanager_bundle_routes:
  resource: '@KeyboardmanFilemanagerBundle/config/routes.yaml'
```

Le filemanager sera alors accessible sur la route nommée `keyboardman_filemanager` (ex. `/kbd/filemanager`).

---

## Configuration

Le filemanager se configure dans un seul fichier `config/packages/keyboardman_filemanager.yaml`. Chaque disk définit son stockage Flysystem inline ; **aucun fichier `flysystem.yaml` dédié n'est requis** pour le filemanager.

Paramètres communs à chaque disk :


| Paramètre     | Description                                                  |
| ------------- | ------------------------------------------------------------ |
| `label`       | Libellé affiché dans l'interface                             |
| `storage`     | Configuration Flysystem (voir exemples ci-dessous)           |
| `visibility`  | `public` ou `private` (propriété filemanager du disk)        |
| `signed_urls` | Génération d'URL signées si nécessaire                       |
| `default_uri` | (optionnel) Base d'URL publique pour les fichiers de ce disk |


Le bloc `storage` accepte le format Flysystem Bundle 3.x (recommandé : `local:`, `aws:`, etc.) ou le format legacy (`adapter` + `options`). Voir la [doc Flysystem Bundle](https://github.com/thephpleague/flysystem-bundle).

### Vue d'ensemble des adapters


| Clé `storage` | Usage                                  | Package Composer (si non inclus)        |
| ------------- | -------------------------------------- | --------------------------------------- |
| `local`       | Fichiers sur le serveur                | inclus via `league/flysystem-bundle`    |
| `aws`         | Amazon S3 (AWS SDK)                    | inclus (`league/flysystem-aws-s3-v3`)   |
| `asyncaws`    | Amazon S3 (AsyncAws, plus léger)       | `league/flysystem-async-aws-s3`         |
| `azure`       | Azure Blob Storage                     | `league/flysystem-azure-blob-storage`   |
| `gcloud`      | Google Cloud Storage                   | `league/flysystem-google-cloud-storage` |
| `ftp`         | Serveur FTP                            | `league/flysystem-ftp`                  |
| `sftp`        | Serveur SFTP                           | `league/flysystem-sftp-v3`              |
| `memory`      | Stockage en mémoire (tests)            | `league/flysystem-memory`               |
| `bunnycdn`    | BunnyCDN Storage                       | `platformcommunity/flysystem-bunnycdn`  |
| `webdav`      | Serveur WebDAV                         | `league/flysystem-webdav`               |
| `gridfs`      | MongoDB GridFS                         | `league/flysystem-gridfs`               |
| `service`     | Adapter personnalisé (service Symfony) | selon votre implémentation              |


Les exemples ci-dessous utilisent le format Flysystem Bundle 3.x (`local:`, `aws:`, etc.). Chaque adapter optionnel doit être installé avant utilisation : `composer require <package>`.

### Stockage local

Fichiers servis depuis un répertoire du serveur (ex. `public/uploads`).

```yaml
# config/packages/keyboardman_filemanager.yaml
keyboardman_filemanager:
  disks:
    default:
      label: Default
      storage:
        local:
          directory: "%kernel.project_dir%/public/uploads/default"
        visibility: public
      signed_urls: true
      default_uri: "%env(resolve:DEFAULT_URI)%/uploads/default"
```

Format legacy équivalent :

```yaml
storage:
  adapter: local
  visibility: public
  options:
    directory: "%kernel.project_dir%/public/uploads/default"
```

Assurez-vous que le répertoire existe et est accessible en écriture par PHP. Si les fichiers doivent être servis directement par le serveur web, placez-les sous `public/`.

### Stockage S3 (AWS)

L'adapter `league/flysystem-aws-s3-v3` est installé automatiquement avec le bundle. Il suffit de déclarer un client S3 (ex. dans `config/services.yaml`) :

```yaml
services:
  aws.s3.client:
    class: Aws\S3\S3Client
    arguments:
      -
        region: "%env(AWS_REGION)%"
        version: "latest"
        credentials:
          key: "%env(AWS_ACCESS_KEY_ID)%"
          secret: "%env(AWS_SECRET_ACCESS_KEY)%"
```

Puis configurez un disk S3 :

```yaml
# config/packages/keyboardman_filemanager.yaml
keyboardman_filemanager:
  disks:
    s3:
      label: Médias S3
      storage:
        aws:
          client: aws.s3.client
          bucket: "%env(AWS_S3_BUCKET)%"
          prefix: uploads
        visibility: public
      default_uri: "https://%env(AWS_S3_BUCKET)%.s3.%env(AWS_REGION)%.amazonaws.com/uploads"
```

Format legacy équivalent :

```yaml
storage:
  adapter: aws
  visibility: public
  options:
    client: aws.s3.client
    bucket: "%env(AWS_S3_BUCKET)%"
    prefix: uploads
```

Variables d'environnement S3 à ajouter dans `.env` :

```env
AWS_REGION=eu-west-3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_S3_BUCKET=my-bucket
```

Le `prefix` est optionnel : il préfixe toutes les clés d'objets dans le bucket (ex. `uploads/photo.jpg`).

### Stockage S3 (AsyncAws)

Alternative légère à l'AWS SDK pour S3. Installez `league/flysystem-async-aws-s3` puis déclarez un client AsyncAws :

```yaml
# config/services.yaml
services:
  asyncaws.s3.client:
    class: AsyncAws\S3\S3Client
    arguments:
      -
        region: "%env(AWS_REGION)%"
        accessKeyId: "%env(AWS_ACCESS_KEY_ID)%"
        accessKeySecret: "%env(AWS_SECRET_ACCESS_KEY)%"
```

```yaml
keyboardman_filemanager:
  disks:
    s3_async:
      label: Médias S3 (AsyncAws)
      storage:
        asyncaws:
          client: asyncaws.s3.client
          bucket: "%env(AWS_S3_BUCKET)%"
          prefix: uploads
```

### Stockage Azure Blob

```bash
composer require league/flysystem-azure-blob-storage
```

```yaml
# config/services.yaml
services:
  azure.blob.client:
    class: AzureOss\Storage\Blob\BlobServiceClient
    # configurez selon votre compte Azure

# config/packages/keyboardman_filemanager.yaml
keyboardman_filemanager:
  disks:
    azure:
      label: Azure
      storage:
        azure:
          client: azure.blob.client
          container: "%env(AZURE_STORAGE_CONTAINER)%"
          prefix: media
```

### Stockage Google Cloud Storage

```bash
composer require league/flysystem-google-cloud-storage
```

```yaml
# config/services.yaml
services:
  gcloud.storage.client:
    class: Google\Cloud\Storage\StorageClient
    arguments:
      - keyFilePath: "%env(resolve:GOOGLE_APPLICATION_CREDENTIALS)%"

# config/packages/keyboardman_filemanager.yaml
keyboardman_filemanager:
  disks:
    gcloud:
      label: GCS
      storage:
        gcloud:
          client: gcloud.storage.client
          bucket: "%env(GCS_BUCKET)%"
          prefix: uploads
```

### Stockage FTP

```bash
composer require league/flysystem-ftp
```

```yaml
keyboardman_filemanager:
  disks:
    ftp:
      label: FTP
      storage:
        ftp:
          host: "%env(FTP_HOST)%"
          username: "%env(FTP_USERNAME)%"
          password: "%env(FTP_PASSWORD)%"
          port: 21
          root: /uploads
          passive: true
          ssl: false
```

### Stockage SFTP

```bash
composer require league/flysystem-sftp-v3
```

```yaml
keyboardman_filemanager:
  disks:
    sftp:
      label: SFTP
      storage:
        sftp:
          host: "%env(SFTP_HOST)%"
          username: "%env(SFTP_USERNAME)%"
          password: "%env(SFTP_PASSWORD)%"
          # ou privateKey: "%kernel.project_dir%/config/ssh/id_rsa"
          port: 22
          root: /var/www/uploads
```

### Stockage mémoire (tests)

Utile en environnement de test ; aucune persistance disque.

```bash
composer require league/flysystem-memory
```

```yaml
keyboardman_filemanager:
  disks:
    memory:
      label: Test
      storage:
        memory: ~
```

### Stockage BunnyCDN

```bash
composer require platformcommunity/flysystem-bunnycdn
```

```yaml
# config/services.yaml
services:
  bunnycdn.client:
    class: PlatformCommunity\Flysystem\BunnyCDN\BunnyCDNClient
    # voir la doc de l'adapter BunnyCDN

# config/packages/keyboardman_filemanager.yaml
keyboardman_filemanager:
  disks:
    bunnycdn:
      label: BunnyCDN
      storage:
        bunnycdn:
          client: bunnycdn.client
          pull_zone: "%env(BUNNYCDN_PULL_ZONE)%"
```

### Stockage WebDAV

```bash
composer require league/flysystem-webdav
```

```yaml
# config/services.yaml
services:
  webdav.client:
    class: Sabre\DAV\Client
    arguments:
      - baseUri: "%env(WEBDAV_BASE_URI)%"
        userName: "%env(WEBDAV_USERNAME)%"
        password: "%env(WEBDAV_PASSWORD)%"

# config/packages/keyboardman_filemanager.yaml
keyboardman_filemanager:
  disks:
    webdav:
      label: WebDAV
      storage:
        webdav:
          client: webdav.client
          prefix: uploads
```

### Stockage GridFS (MongoDB)

```bash
composer require league/flysystem-gridfs
```

Via URI MongoDB :

```yaml
keyboardman_filemanager:
  disks:
    gridfs:
      label: MongoDB GridFS
      storage:
        gridfs:
          mongodb_uri: "%env(MONGODB_URI)%"
          database: "%env(MONGODB_DATABASE)%"
          prefix: media
```

Ou via un service bucket GridFS existant :

```yaml
storage:
  gridfs:
    bucket: app.gridfs.bucket
    prefix: media
```

### Adapter personnalisé

Pour un adapter Flysystem enregistré comme service Symfony :

```yaml
keyboardman_filemanager:
  disks:
    custom:
      label: Custom
      storage:
        service: app.flysystem.custom_adapter
```

### Exemple multi-disks (local + S3)

```yaml
keyboardman_filemanager:
  disks:
    default:
      label: Local
      storage:
        local:
          directory: "%kernel.project_dir%/public/uploads/default"
      default_uri: "%env(resolve:DEFAULT_URI)%/uploads/default"

    s3:
      label: Production S3
      storage:
        aws:
          client: aws.s3.client
          bucket: "%env(AWS_S3_BUCKET)%"
          prefix: media
      default_uri: "https://%env(AWS_S3_BUCKET)%.s3.%env(AWS_REGION)%.amazonaws.com/media"
```

### Migration depuis l'ancien format

Si vous référenciez auparavant un storage Flysystem externe :

```yaml
# Avant
keyboardman_filemanager:
  disks:
    default:
      storage: default.storage   # référence vers flysystem.yaml
```

Copiez la configuration du storage depuis `flysystem.yaml` dans le bloc `storage` du disk :

```yaml
# Après
keyboardman_filemanager:
  disks:
    default:
      storage:
        local:
          directory: "%kernel.project_dir%/public/uploads/default"
```

Les storages `flysystem.storages` ne sont plus nécessaires s'ils ne servaient qu'au filemanager.

---

## Types de médias (image, vidéo, audio)

Le filemanager gère trois familles de fichiers : **image**, **vidéo** et **audio**. Ce réglage ne se fait **pas** dans `keyboardman_filemanager.yaml` (qui ne couvre que le stockage Flysystem), mais via le paramètre d'URL `media` ou l'option `media` du `FilemanagerType`.

### Comportement par défaut

Sans filtre `media`, le filemanager :

- **affiche** uniquement les fichiers image, vidéo et audio (les autres types sont masqués dans la liste) ;
- **accepte à l'upload** les fichiers dont le type MIME commence par `image/`, `video/` ou `audio/` (dropzone `accept="image/*,video/*,audio/*"`).

Les trois types sont donc autorisés par défaut. Aucune configuration supplémentaire n'est requise pour les activer.

### Restreindre à un seul type

Pour limiter la sélection à **un** type, utilisez la valeur `image`, `video` ou `audio` :


| Valeur `media` | Effet                        |
| -------------- | ---------------------------- |
| *(absent)*     | Images, vidéos **et** audios |
| `image`        | Images uniquement            |
| `video`        | Vidéos uniquement            |
| `audio`        | Audios uniquement            |


> **Note** : il n'est pas possible aujourd'hui de combiner deux types seulement (ex. image + vidéo sans audio) via la configuration. Utilisez l'absence de filtre (les trois types) ou un filtre unique.

### Dans un formulaire (`FilemanagerType`)

Passez l'option `media` pour pré-filtrer l'iframe du filemanager :

```php
// Images seulement
$builder->add('cover', FilemanagerType::class, [
    'media' => 'image',
]);

// Vidéos seulement
$builder->add('clip', FilemanagerType::class, [
    'media' => 'video',
]);

// Audios seulement
$builder->add('podcast', FilemanagerType::class, [
    'media' => 'audio',
]);

// Images, vidéos et audios (défaut)
$builder->add('fichier', FilemanagerType::class, [
    'media' => null,
]);
```

L'option ajoute `?media=…` à l'URL du filemanager. En mode iframe, le sélecteur de filtre média est verrouillé sur ce type.

### En accès direct (URL)

Ouvrez le filemanager avec le paramètre de requête `media` :

```
/kbd/filemanager?filesystem=default&path=/&media=image
/kbd/filemanager?filesystem=default&path=/&media=video
/kbd/filemanager?filesystem=default&path=/&media=audio
```

Sans `media`, les trois types sont disponibles. L'utilisateur peut aussi changer le filtre via le menu déroulant **Images / Audio / Vidéo** dans l'en-tête du filemanager (sauf en mode iframe avec un filtre imposé).

### Côté upload

L'upload refuse tout fichier dont le MIME n'est pas `image/`*, `video/*` ou `audio/*`, indépendamment du filtre d'affichage. Un message d'erreur est affiché : *« Type de fichier non autorisé (images, vidéos et audios uniquement). »*

---

## Variables d’environnement

À définir dans votre `.env` (ou `.env.local`) :


| Variable                                                | Description                                                                                                                                 |
| ------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `DEFAULT_URI`                                           | URL de base de l’application (ex. `https://example.com`). Utilisée pour construire les URIs des fichiers quand `default_uri` est configuré. |
| `FILEMANAGER_TOKEN_ENABLED`                             | Si `true`, active la vérification par token pour l’accès au filemanager en iframe (cross-domain).                                           |
| `FILEMANAGER_TOKENS`                                    | Chemin vers un fichier JSON listant les tokens par domaine (voir ci-dessous). Utilisé quand `FILEMANAGER_TOKEN_ENABLED` est activé.         |
| `AWS_REGION`                                            | Région AWS (ex. `eu-west-3`). Requis pour un disk S3.                                                                                       |
| `AWS_ACCESS_KEY_ID`                                     | Clé d'accès AWS. Requis pour un disk S3.                                                                                                    |
| `AWS_SECRET_ACCESS_KEY`                                 | Secret AWS. Requis pour un disk S3.                                                                                                         |
| `AWS_S3_BUCKET`                                         | Nom du bucket S3. Requis pour un disk S3.                                                                                                   |
| `AZURE_STORAGE_CONTAINER`                               | Conteneur Azure Blob. Requis pour un disk `azure`.                                                                                          |
| `GOOGLE_APPLICATION_CREDENTIALS`                        | Chemin vers le fichier de credentials GCP. Requis pour un disk `gcloud`.                                                                    |
| `GCS_BUCKET`                                            | Bucket Google Cloud Storage. Requis pour un disk `gcloud`.                                                                                  |
| `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD`              | Connexion FTP. Requis pour un disk `ftp`.                                                                                                   |
| `SFTP_HOST`, `SFTP_USERNAME`, `SFTP_PASSWORD`           | Connexion SFTP. Requis pour un disk `sftp`.                                                                                                 |
| `BUNNYCDN_PULL_ZONE`                                    | Zone pull BunnyCDN. Requis pour un disk `bunnycdn`.                                                                                         |
| `WEBDAV_BASE_URI`, `WEBDAV_USERNAME`, `WEBDAV_PASSWORD` | Connexion WebDAV. Requis pour un disk `webdav`.                                                                                             |
| `MONGODB_URI`, `MONGODB_DATABASE`                       | Connexion MongoDB GridFS. Requis pour un disk `gridfs` (mode URI).                                                                          |


### Fichier JSON des tokens (`FILEMANAGER_TOKENS`)

Créez un fichier JSON (ex. `config/tokens/filemanager_tokens.json`) avec un objet **domaine → token** :

```json
{
    "www.example.com": "token1",
    "www.domaine2.fr": "token2"
}
```

Un fichier d’exemple est fourni : [docs/filemanager_tokens.json.example](docs/filemanager_tokens.json.example).

Dans `.env`, pointez vers ce fichier. Utilisez un chemin **relatif au répertoire du projet** (recommandé) ou un chemin absolu :

```env
DEFAULT_URI=https://mon-projet.local
FILEMANAGER_TOKEN_ENABLED=true
# Chemin relatif au répertoire du projet :
FILEMANAGER_TOKENS=config/tokens/filemanager_tokens.json
```

Le bundle utilise `%env(json:file:resolve:FILEMANAGER_TOKENS)%` : la valeur de `FILEMANAGER_TOKENS` doit être le chemin vers ce fichier JSON (le processeur `json:file` charge le contenu, `resolve` permet d’utiliser d’autres variables dans le chemin).

### Générer un token avec OpenSSL

Pour créer une chaîne aléatoire sécurisée à mettre dans le fichier JSON :

```bash
openssl rand -hex 32
```

Exemple de sortie : `a1b2c3d4e5f6...`. Utilisez cette valeur comme valeur dans `filemanager_tokens.json` pour un domaine, et passez le même token dans l’URL du filemanager en iframe (paramètre `token=...`).

---

## Utilisation dans un formulaire

### FilemanagerType

Utilisez le type de formulaire `FilemanagerType` pour un champ qui ouvre le filemanager dans un modal et enregistre le chemin (ou l’URI) du fichier sélectionné.

Exemple de formulaire :

```php
// src/Form/MyContentType.php
namespace App\Form;

use Keyboardman\FilemanagerBundle\Form\Type\FilemanagerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MyContentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('media', FilemanagerType::class, [
                'crossdomain' => true,   // si le filemanager est servi en iframe depuis un autre domaine
                'media' => 'image',      // filtre optionnel : image, video, etc.
                'token' => 'mon_token',  // optionnel, pour l’accès iframe sécurisé
            ])
        ;
    }
}
```

Options du `FilemanagerType` :


| Option        | Type    | Description                                                                                    |
| ------------- | ------- | ---------------------------------------------------------------------------------------------- |
| `crossdomain` | `bool`  | À `true` si l’iframe est chargée en cross-domain (nécessite la gestion des tokens si activée). |
| `media`       | `string | null`                                                                                          |
| `token`       | `string | null`                                                                                          |


Le champ rend un input texte (valeur = chemin ou URI du fichier) et un bouton « Parcourir » qui ouvre le filemanager dans un modal.

### Inclusion du modal dans le template

Sur toute page qui affiche un formulaire contenant un `FilemanagerType`, incluez le **modal** et les assets du widget. Sans cela, le bouton « Parcourir » ne pourra pas ouvrir le filemanager.

Dans votre template Twig (ex. la page qui affiche le formulaire) :

```twig
{% extends 'base.html.twig' %}

{% block body %}
    <div>
        {{ form(form) }}
    </div>
    {% include '@KeyboardmanFilemanager/iframe/modal.html.twig' %}
{% endblock %}
```

Le template `@KeyboardmanFilemanager/iframe/modal.html.twig` fournit :

- Le conteneur du modal (`#filemanager-modal`) avec overlay et zone de contenu.
- Le chargement des assets `filemanager-field.css` et `filemanager-field.js` (via les chemins Asset Mapper du bundle).

Le script `filemanager-field.js` écoute les clics sur les boutons `.open-filemanager`, ouvre le modal, charge l’iframe du filemanager avec l’URL fournie par le formulaire, et renvoie la sélection dans l’input correspondant.

### Utilisation du filemanager en iframe sans installer le bundle

Sur un site qui **n’utilise pas Symfony** (ou qui n’installe pas ce bundle), vous pouvez tout de même ouvrir le filemanager en iframe : il suffit d’héberger le filemanager sur un domaine où le bundle est installé, et d’afficher sur votre page le même **modal** + **comportement** que le bundle fournit.

Un exemple de code réel, basé sur `iframe/modal.html.twig` et `filemanager-field.js`, est fourni en **HTML/JS autonome** (sans bundle) :

- **Fichier** : [docs/standalone-iframe-example.html](docs/standalone-iframe-example.html)

Cet exemple contient :

- La structure HTML du modal (`#filemanager-modal`, overlay, zone de contenu).
- Les styles CSS équivalents à `filemanager-field.css`.
- Le script qui : écoute les clics sur `.open-filemanager`, ouvre le modal, charge l’iframe avec l’URL du filemanager (avec `data-url` et éventuellement `token`), répond à `REQUEST_PARENT_ORIGIN` pour le cross-domain, et écoute `postMessage` de type `filemanager:selected` pour remplir l’input et fermer le modal.

À adapter sur votre page :

1. **data-url** du bouton : URL complète du filemanager (ex. `https://domaine-avec-bundle.com/kbd/filemanager?mode=iframe&target=ID_DE_VOTRE_INPUT&crossdomain=1&token=VOTRE_TOKEN`).
2. **data-target** : l’`id` de l’input dans lequel écrire le chemin du fichier sélectionné.
3. Si le filemanager est sur un autre domaine, activez les tokens côté serveur (`FILEMANAGER_TOKEN_ENABLED=true`) et utilisez le même token dans l’URL que celui défini pour votre domaine dans `filemanager_tokens.json`.

---

## Exemple complet

1. **Configuration**
  - `config/packages/keyboardman_filemanager.yaml` : disk local (voir [Stockage local](#stockage-local)) ou S3 (voir [Stockage S3](#stockage-s3-aws)).
  - `.env` : `DEFAULT_URI`, optionnellement `FILEMANAGER_TOKEN_ENABLED`, `FILEMANAGER_TOKENS`, et variables AWS si disk S3.
2. **Formulaire**
  - Champ avec `FilemanagerType::class` et options si besoin (`crossdomain`, `media`, `token`).
3. **Template**
  - Afficher le formulaire avec `{{ form(form) }}`.  
  - Inclure `{% include '@KeyboardmanFilemanager/iframe/modal.html.twig' %}`.

Après avoir sélectionné un fichier dans le filemanager, la valeur du champ (chemin ou URI) est enregistrée dans l’input du formulaire et envoyée à la soumission.

---

## Tests

Le bundle fournit deux suites de tests pour l’upload média XHR :

### Tests PHP (PHPUnit)

```bash
composer install
composer test
```

Couverture :

- `UploadLimitResolver` et `ChunkUploadManager` (unitaires)
- `POST /api/filemanager/upload` et `/upload-chunk` (fonctionnels via kernel de test)

### Tests JavaScript (Vitest)

```bash
npm install
npm test
```

Couverture :

- Helpers d’upload (`frontend/js/upload/helpers.js`)
- Contrôleur Stimulus `filemanager-upload` (file d’attente, bascule monolithique/fragmenté, progression, erreurs, `beforeunload`)

