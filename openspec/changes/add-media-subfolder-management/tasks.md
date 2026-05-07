## 1. Implémentation
- [ ] 1.1 Ajouter un endpoint API pour créer un dossier dans le chemin courant (filesystem + path parent + nom dossier) avec validation des noms.
- [ ] 1.2 Étendre le service de gestion des disques pour créer un dossier et normaliser le chemin retourné.
- [ ] 1.3 Brancher l'interface du filemanager pour déclencher la création de sous-dossier (action dans l'en-tête ou modal dédiée) et rafraîchir la vue après succès.
- [ ] 1.4 Garantir le renommage des dossiers depuis la sidebar avec gestion cohérente des erreurs utilisateur/API.

## 2. Validation
- [ ] 2.1 Vérifier manuellement le flux "créer un sous-dossier" depuis la racine et depuis un sous-répertoire.
- [ ] 2.2 Vérifier manuellement le flux "renommer un dossier" et les cas d'erreur (nom vide, dossier déjà existant, chemin invalide).
- [ ] 2.3 Exécuter les tests et/ou contrôles qualité disponibles sur les composants modifiés.
