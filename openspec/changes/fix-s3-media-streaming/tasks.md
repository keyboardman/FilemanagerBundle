## 1. Lecteur par plages (MediaRangeReader)

- [x] 1.1 Créer l'interface `MediaRangeReader` et l'implémentation locale (`readStream` + `fseek`)
- [x] 1.2 Créer `AwsS3MediaRangeReader` utilisant `GetObject` avec paramètre `Range`
- [x] 1.3 Créer une factory/resolver qui sélectionne le reader selon l'adapter du disk (AWS S3 vs local vs fallback)
- [x] 1.4 Enregistrer les services dans `config/services.yaml`

## 2. MediaController

- [x] 2.1 Refactoriser `MediaController::serve()` pour déléguer la lecture de plage au `MediaRangeReader`
- [x] 2.2 Ajouter le support `HEAD` (métadonnées sans corps, en-têtes `Accept-Ranges` / `Content-Length`)
- [x] 2.3 Améliorer la gestion d'erreurs (exceptions S3/Flysystem → 404/502 explicites, journalisation)
- [x] 2.4 Conserver le comportement existant pour le stockage local (régression zéro)

## 3. Résolution d'URL unifiée

- [x] 3.1 Implémenter la génération d'URLs présignées S3 dans `DiskManager::publicUrl()` quand `signed_urls: true`
- [x] 3.2 Ajouter le paramètre optionnel `signed_url_ttl` dans la config disk (défaut 3600 s)
- [x] 3.3 Faire appeler `DiskManager::publicUrl()` depuis `FilemanagerExtension::resolveUrl()`
- [x] 3.4 Vérifier la cohérence des URLs entre preview Twig et réponses API upload

## 4. Tests

- [x] 4.1 Test fonctionnel : requête `Range` valide sur disk local → `206` + contenu correct
- [x] 4.2 Test fonctionnel : requête `Range` invalide → `416`
- [x] 4.3 Test unitaire/fonctionnel : `AwsS3MediaRangeReader` avec mock client S3 (GetObject + Range)
- [x] 4.4 Test : `DiskManager::publicUrl()` avec `signed_urls: true` retourne une URL présignée
- [x] 4.5 Test : cohérence `resolve_url` Twig ↔ `publicUrl()`

## 5. Documentation

- [x] 5.1 Ajouter la section « Streaming vidéo S3 » dans le README (CORS, proxy vs direct, signed_urls)
- [x] 5.2 Documenter le dépannage (erreurs 500, seek qui revient au début)
- [x] 5.3 Mettre à jour `config/reference.php` si nouveau paramètre `signed_url_ttl`
