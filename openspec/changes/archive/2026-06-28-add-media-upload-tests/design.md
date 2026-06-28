## Context
Le bundle Symfony n'a pas encore d'infrastructure de test : le répertoire `tests/` est vide et `package.json` ne définit aucun runner JS. L'upload média repose sur trois couches testables distinctement :

- **PHP unitaire** : `UploadLimitResolver`, `ChunkUploadManager` (logique métier, sans HTTP).
- **PHP fonctionnel** : `ApiController::upload` et `ApiController::uploadChunk` (contrat HTTP/JSON).
- **JS unitaire** : `filemanager_upload_controller.js` (logique client XHR, file d'attente, progression).

Le bundle n'inclut pas d'application Symfony hôte ; les tests fonctionnels utiliseront un `KernelTestCase` minimal avec Flysystem en mémoire.

## Goals / Non-Goals
- Goals:
  - Couvrir les scénarios critiques de `media-upload` (succès monolithique, succès fragmenté, erreurs de validation, progression).
  - Fournir des commandes reproductibles : `composer test` et `npm test`.
  - Mocker `XMLHttpRequest` côté JS pour tester la logique sans navigateur réel.
- Non-Goals:
  - Tests E2E navigateur (Playwright, Cypress).
  - Tests de performance ou de charge.
  - Couverture exhaustive de tous les composants du filemanager hors upload.

## Decisions
- Decision: **PHPUnit 11** comme runner PHP.
  - Alternatives : Pest — syntaxe plus concise mais dépendance supplémentaire peu répandue dans les bundles Symfony.
- Decision: kernel de test minimal avec **Flysystem in-memory** pour les tests fonctionnels API.
  - Évite une dépendance à un projet hôte ou à un filesystem réel.
- Decision: **Vitest** comme runner JS.
  - Déjà aligné sur Vite ; configuration minimale, support natif des modules ES et de `happy-dom`/`jsdom`.
- Decision: extraire les fonctions pures testables du contrôleur Stimulus si nécessaire (ex. calcul de progression fragmentée, parsing de réponse) plutôt que de sur-mocker le DOM Stimulus.
  - Alternative rejetée : tester uniquement via intégration DOM complète — plus fragile et lent.
- Decision: mocker `XMLHttpRequest` et `crypto.randomUUID` dans les tests JS pour contrôler progression et réponses serveur.

## Risks / Trade-offs
- Kernel de test Symfony à maintenir → Mitigation : rester minimal (un seul disk, routes upload uniquement).
- Contrôleur Stimulus couplé au DOM → Mitigation : tests ciblés sur les méthodes isolables ; mock léger de `progressContainerTarget`.
- `ChunkUploadManager` utilise le filesystem réel pour les temporaires → Mitigation : injecter `$tempBase` vers un répertoire temporaire de test (déjà supporté par le constructeur).

## Open Questions
- Faut-il un job CI GitHub Actions ? → Hors périmètre initial ; documenter les commandes locales, CI en changement ultérieur si demandé.
