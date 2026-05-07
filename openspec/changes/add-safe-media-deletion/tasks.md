## 1. Implementation
- [ ] 1.1 Ajouter un endpoint API de suppression de fichier avec validation du filesystem et du chemin cible.
- [ ] 1.2 Ajouter un endpoint API de suppression de dossier qui refuse la suppression si le dossier n'est pas vide.
- [ ] 1.3 Ajouter une fenetre modale de confirmation avant suppression depuis l'interface du filemanager.
- [ ] 1.4 Mettre a jour les actions UI (fichier/dossier) pour appeler l'API de suppression et rafraichir la vue apres succes.
- [ ] 1.5 Afficher des erreurs explicites cote UI pour les cas "dossier non vide", "cible introuvable" et erreurs serveur.

## 2. Validation
- [ ] 2.1 Verifier manuellement la suppression d'un fichier apres confirmation modale.
- [ ] 2.2 Verifier manuellement qu'un dossier non vide ne peut pas etre supprime.
- [ ] 2.3 Verifier manuellement qu'un dossier vide est supprime avec succes.
- [ ] 2.4 Executer les tests/controles qualite disponibles sur les composants modifies.
