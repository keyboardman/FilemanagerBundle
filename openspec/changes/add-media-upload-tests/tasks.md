## 1. Infrastructure PHPUnit
- [x] 1.1 Ajouter `phpunit/phpunit`, `symfony/phpunit-bridge` et dépendances Symfony minimales (`framework-bundle`, `http-kernel`, `flysystem`, `flysystem-memory`) en `require-dev`.
- [x] 1.2 Créer `phpunit.xml.dist` et le script Composer `test`.
- [x] 1.3 Créer un kernel de test minimal (`tests/TestKernel.php`) avec un disk Flysystem en mémoire et les routes upload.

## 2. Tests PHP unitaires
- [x] 2.1 `UploadLimitResolverTest` : parsing `ini` (`2M`, `8M`, `-1`), `maxSafeChunkSize`, `resolve` avec seuils configurés.
- [x] 2.2 `ChunkUploadManagerTest` : réception du premier fragment, fragments intermédiaires, assemblage au dernier fragment, rejet d'`uploadId` invalide, rejet de séquence hors ordre, rejet de métadonnées incohérentes.

## 3. Tests PHP fonctionnels API
- [x] 3.1 `ApiUploadTest::testMonolithicUploadSuccess` : `POST /api/filemanager/upload` avec fichier valide → JSON `success: true` et champs attendus.
- [x] 3.2 `ApiUploadTest::testMonolithicUploadWithoutFile` : requête sans `file` → HTTP 400.
- [x] 3.3 `ApiUploadChunkTest::testChunkedUploadSuccess` : envoi séquentiel de N fragments → fichier présent sur le disk et réponse finale complète.
- [x] 3.4 `ApiUploadChunkTest::testChunkedUploadInvalidParams` : paramètres manquants ou `uploadId` invalide → HTTP 400.

## 4. Infrastructure Vitest
- [x] 4.1 Ajouter `vitest`, `happy-dom` (ou `jsdom`) en `devDependencies`.
- [x] 4.2 Créer `vitest.config.js` et le script npm `test`.
- [x] 4.3 Configurer les alias de modules si nécessaire pour importer le contrôleur Stimulus.

## 5. Tests JS
- [x] 5.1 `filemanager_upload_controller.test.js` : `isAllowedMedia` accepte image/vidéo et rejette les autres types.
- [x] 5.2 Test de bascule : fichier ≤ `chunkThreshold` appelle l'URL monolithique ; fichier > seuil découpe et appelle `upload-chunk`.
- [x] 5.3 Test de progression monolithique et fragmentée via mock `xhr.upload.onprogress`.
- [x] 5.4 Test d'erreur API (`success: false`, HTTP 4xx) affiche l'état d'erreur sans bloquer la file restante.
- [x] 5.5 Test `beforeunload` : handler actif pendant `uploading`, inactif après fin de file.

## 6. Documentation et validation
- [x] 6.1 Documenter `composer test` et `npm test` dans le README.
- [x] 6.2 Vérifier que les deux suites passent en local.
