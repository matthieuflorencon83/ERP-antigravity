# Les Règles d'Or du Projet ANTIGRAVITY (v2.0 - 2026)

Ces règles sont impératives. Tout manquement entraînera un rejet immédiat du code.

## 1. Règle de Séparation & "Frontend Stupide"

* **Isolation Totale** : Aucun calcul métier (calepinage, prix, taxes, géométrie) dans Angular.
* **Rôle du Front** : Affichage pur, capture d'événements, validation de format (ex: email valide).
* **Rôle du Back** : Seul garant de la vérité métier et des données.

## 2. Contrat d'Interface & Typage de Fer

* **Partage de Source** : Utilisation obligatoire de `shared/interfaces.ts`.
* **Strict-Type** : Interdiction totale du type `any`. Chaque objet doit être typé à 100%.
* **Synchronisation** : Toute modification d'un modèle en DB doit être immédiatement répercutée dans les interfaces TypeScript.

## 3. Architecture Hybride SQL/JSON (Performance & Flexibilité)

* **SQL (Rigide)** : Colonnes indexées pour : Recherche, Jointures, Tris, Prix, Stocks, Dates.
* **JSON (Souple)** : Uniquement pour les fiches techniques variables, méta-données IA, et configurations spécifiques à un article.

## 4. Protocole Dual-Back (Le Pont)

* **Node.js** : Maître de l'orchestration, de la sécurité (JWT), de la validation des entrées et de la base de données.
* **Python** : Moteur de calcul pur. Ne doit JAMAIS accéder directement à MySQL.
* **Standard I/O** : Communication Node <-> Python via flux JSON sur stdin/stdout (ou HTTP internal).

## 5. Règle de Double Analyse (Double Check Protocol)

AVANT de générer le moindre code, l'agent DOIT :

1. **Phase 1 (Analyse)** : Lire le code existant et identifier les dépendances.
2. **Phase 2 (Audit)** : Vérifier la conformité avec `DATABASE_MEMO.md` et `shared/interfaces.ts`.
3. **Phase 3 (Proposition)** : Si une amélioration ou un changement d'architecture est nécessaire, l'agent doit s'arrêter et dire : "Je propose le changement suivant : [Détails]. Attends-je votre confirmation ?"

## 6. Standard "Anti-Spaghetti" (Modularité)

* **Limite de 300 lignes** : Un fichier dépassant 300 lignes est une erreur de conception. Découpage immédiat en sous-composants ou services.
* **Responsabilité Unique (SRP)** : Une fonction = une seule action claire.

## 7. Standards "Clean Code 2026" & Performance

* **AI-Friendliness** : Commentaires explicatifs sur le "Pourquoi" (l'intention) et non le "Comment".
* **Sobriété SQL** : Interdiction du `SELECT *`. On ne récupère que le strict nécessaire (Green-IT).
* **Sécurité Shift-Left** : Validation des schémas de données obligatoire avec Zod (Node) ou Pydantic (Python).

## 8. Processus de Validation (Workflow)

1. L'IA analyse la demande.
2. L'IA identifie les risques d'effets de bord.
3. L'IA propose le plan d'action.
4. **ATTENTE DE CONFIRMATION ÉCRITE DE L'UTILISATEUR.**
5. Génération du code après validation du plan.
