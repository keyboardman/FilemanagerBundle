## 1. Implementation
- [x] 1.1 Ajouter un endpoint API de suppression de fichier avec validation du filesystem et du chemin cible.
- [x] 1.2 Ajouter un endpoint API de suppression de dossier qui refuse la suppression si le dossier n'est pas vide.
- [x] 1.3 Ajouter une fenetre modale de confirmation avant suppression depuis l'interface du filemanager.
- [x] 1.4 Mettre a jour les actions UI (fichier/dossier) pour appeler l'API de suppression et rafraichir la vue apres succes.
- [x] 1.5 Afficher des erreurs explicites cote UI pour les cas "dossier non vide", "cible introuvable" et erreurs serveur.

## 2. Validation
- [x] 2.1 Verifier manuellement la suppression d'un fichier apres confirmation modale.
- [x] 2.2 Verifier manuellement qu'un dossier non vide ne peut pas etre supprime.
- [x] 2.3 Verifier manuellement qu'un dossier vide est supprime avec succes.
- [x] 2.4 Executer les tests/controles qualite disponibles sur les composants modifies.
