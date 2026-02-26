# Keyboardman Filemanager Bundle

Bundle Symfony pour intégrer un filemanager dans vos formulaires, basé sur [Flysystem](https://flysystem.thephpleague.com/) pour gérer plusieurs types de stockage (local, S3, etc.).

- **Dépôt** : [https://github.com/keyboardman/FilemanagerBundle](https://github.com/keyboardman/FilemanagerBundle/tree/main)

## Prérequis

- **PHP** 8.2 ou supérieur
- **Symfony** 8.x
- **league/flysystem** et **league/flysystem-bundle**
- **symfony/asset-mapper** (pour exposer les assets du bundle)

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

**Depuis Packagist** (si le package y est publié) :

```bash
composer require keyboardman/filemanager-bundle
```

**En développement local (dépôt path)** :

```json
{
    "repositories": [
        { "type": "path", "url": "./bundle/Keyboardman/FilemanagerBundle" }
    ],
    "require": {
        "keyboardman/filemanager-bundle": "^0.1.0"
    }
}
```

```bash
composer update keyboardman/filemanager-bundle
```

### 2. Enregistrer le bundle

Si ce n’est pas fait automatiquement (Flex), ajoutez dans `config/bundles.php` :

```php
return [
    // ...
    Keyboardman\FilemanagerBundle\KeyboardmanFilemanagerBundle::class => ['all' => true],
    League\FlysystemBundle\FlysystemBundle::class => ['all' => true],
];
```

### 3. Enregistrer les routes

Dans `config/routes/` (ou équivalent), ajoutez un fichier qui charge les routes du bundle, par exemple `keyboardman_filemanager_bundle_routes.yaml` :

```yaml
keyboardman_filemanager_bundle_routes:
  resource: '@KeyboardmanFilemanagerBundle/config/routes.yaml'
```

Le filemanager sera alors accessible sur la route nommée `keyboardman_filemanager` (ex. `/kbd/filemanager`).

---

## Configuration

### Configuration Flysystem

Le filemanager s’appuie sur les storages définis par **Flysystem**. Configurez au moins un storage dans `config/packages/flysystem.yaml` :

```yaml
# config/packages/flysystem.yaml
flysystem:
    storages:
        default.storage:
            adapter: local
            visibility: public
            options:
                directory: "%kernel.project_dir%/public/uploads/default"

        public.storage:
            adapter: local
            options:
                directory: "%kernel.project_dir%/var/storage/public"
```

Vous pouvez ajouter d’autres adapters (S3, FTP, etc.) selon la [doc Flysystem Bundle](https://github.com/thephpleague/flysystem-bundle).

### Configuration du filemanager

Dans `config/packages/keyboardman_filemanager.yaml`, déclarez des **disks** qui pointent vers vos storages Flysystem :

```yaml
# config/packages/keyboardman_filemanager.yaml
keyboardman_filemanager:
  disks:
    default:
      label: Default
      storage: default.storage          # identifiant du storage Flysystem
      visibility: public
      signed_urls: true
      default_uri: "%env(resolve:DEFAULT_URI)%/uploads/default"

    public:
      label: Public
      storage: public.storage
      visibility: public
      signed_urls: true
```

- **storage** : nom du service Flysystem (celui défini sous `flysystem.storages`).
- **label** : libellé affiché dans l’interface.
- **visibility** : `public` ou `private`.
- **signed_urls** : génération d’URL signées si nécessaire.
- **default_uri** : (optionnel) base d’URL pour les fichiers de ce disk (ex. domaine + chemin public).

---

## Variables d’environnement

À définir dans votre `.env` (ou `.env.local`) :

| Variable | Description |
|----------|-------------|
| `DEFAULT_URI` | URL de base de l’application (ex. `https://example.com`). Utilisée pour construire les URIs des fichiers quand `default_uri` est configuré. |
| `FILEMANAGER_TOKEN_ENABLED` | Si `true`, active la vérification par token pour l’accès au filemanager en iframe (cross-domain). |
| `FILEMANAGER_TOKENS` | Chemin vers un fichier JSON listant les tokens par domaine (voir ci-dessous). Utilisé quand `FILEMANAGER_TOKEN_ENABLED` est activé. |

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

## Build des assets

Les assets du filemanager (JavaScript et CSS) sont buildés avec **Vite**. À la racine du bundle :

```bash
cd vendor/keyboardman/filemanager-bundle
# ou, en développement local :
# cd bundle/Keyboardman/FilemanagerBundle

npm install
npm run build
```

- `npm run build` : build de l’app filemanager complète (`filemanager.js`) et du widget modal (`filemanager-field.js`).
- `npm run dev` ou `npm run watch` : build en mode watch pour le filemanager principal.
- `npm run watch-field` : build en mode watch pour le widget field.

Les fichiers générés sont dans `public/assets/` du bundle (puis exposés via Asset Mapper). En production, assurez-vous que les assets sont installés (ex. `php bin/console asset-map:compile` selon votre setup).

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

| Option | Type | Description |
|--------|------|-------------|
| `crossdomain` | `bool` | À `true` si l’iframe est chargée en cross-domain (nécessite la gestion des tokens si activée). |
| `media` | `string\|null` | Filtre de type de médias (ex. `image`, `video`). |
| `token` | `string\|null` | Token passé en query pour l’accès au filemanager en iframe. |

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
   - `config/packages/flysystem.yaml` : storages `default.storage` et `public.storage`.  
   - `config/packages/keyboardman_filemanager.yaml` : disks `default` et `public` pointant vers ces storages.  
   - `.env` : `DEFAULT_URI`, optionnellement `FILEMANAGER_TOKEN_ENABLED` et `FILEMANAGER_TOKENS`.

2. **Formulaire**  
   - Champ avec `FilemanagerType::class` et options si besoin (`crossdomain`, `media`, `token`).

3. **Template**  
   - Afficher le formulaire avec `{{ form(form) }}`.  
   - Inclure `{% include '@KeyboardmanFilemanager/iframe/modal.html.twig' %}`.

4. **Assets**  
   - Builder les assets du bundle avec `npm run build` dans le répertoire du bundle, et s’assurer que l’application expose bien les assets (Asset Mapper / `asset-map:compile` si utilisé).

Après avoir sélectionné un fichier dans le filemanager, la valeur du champ (chemin ou URI) est enregistrée dans l’input du formulaire et envoyée à la soumission.
