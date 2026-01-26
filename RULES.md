# Les Règles d'Or du Projet (ERP Arts alu)

Ces règles sont impératives et doivent être respectées par tous les agents intervenant sur le projet.

## 1. Règle de Séparation (Briques)

**Aucun code de logique métier (calculs, calepinage) ne doit se trouver dans Angular.**
Le Frontend est "stupide" : il ne fait qu'afficher les données et capturer les entrées utilisateur. Toute intelligence de calcul doit être déportée au backend.

## 2. Règle du Contrat d'Interface

**Toute communication entre le Front et le Back doit passer par des interfaces TypeScript strictes.**
Cela garantit une cohérence totale des données et facilite la maintenance et le refactoring.

## 3. Règle de l'Hybride SQL/JSON

**Priorité aux colonnes SQL pour les données de recherche et de jointure (ID, prix, stock).**
L'utilisation du **JSON** est réservée aux propriétés variables ou complexes (fiches techniques, RAL spécifiques, configurations dynamiques).

## 4. Règle du Dual-Back

**Node.js gère les routes et la sécurité.**
**Python** est appelé uniquement pour les calculs "cerveau" (moteur de calcul, optimisation de découpe, etc.) via un pont interne. point de contact unique entre Node et Python.

## 5. Règle de Vigilance (Protocole MCP Virtuel)

Pour simuler l'analyse continue des serveurs MCP, vous devez appliquer ces vérifications strictes :

* **MCP Database** :
  * Avant toute écriture SQL, consulter `database/DATABASE_MEMO.md`.
  * Ne jamais supposer qu'une table existe sans l'avoir vérifiée via `SHOW TABLES` ou en lisant `schema.sql`.
* **MCP Python** :
  * Tout code Python doit être validé syntaxiquement (Linting) avant build.
  * Les types doivent être explicites (Type Hinting) pour faciliter la lecture par l'IA.
* **MCP Node/Angular** :
  * Respecter strictement les interfaces (`shared/interfaces.ts`).
  * Vérifier la compatibilité des modèles avec la base de données avant de créer une API.

## 6. Règle de Modularité (Anti-Monolithe)

**Aucun fichier ne doit dépasser 300 lignes.**
Si un fichier approche cette limite, il doit OBLIGATOIREMENT être découpé en sous-modules ou composants plus petits.
*Objectif : Maintenabilité et lisibilité maximale.*

## 7. Règle d'Analyse d'Impact (Think Before You Code)

**INTERDICTION DE CODER SANS RÉFLÉCHIR.**
Avant chaque modification de code (même mineure), vous devez :

1. Identifier les fichiers impactés (dépendances, imports).
2. Prédire les effets de bord potentiels (régression, casse de l'UI, erreur SQL).
3. Si le risque est > 0, proposer un plan de rollback ou de test avant de valider.

## 8. Standards "Clean Code 2026"

En vous basant sur les meilleures pratiques actuelles, voici les règles supplémentaires :

* **AI-Friendliness** : Le code doit être écrit pour être compris par une IA autant que par un humain.
  * *Explicite* : Pas de types `any`, pas de "magie" implicite.
  * *Docstring* : Les fonctions complexes doivent avoir une description de leur intention (pour le contexte de l'IA).
* **Green-IT (Sobriété)** :
  * *SQL* : Interdiction du `SELECT *` sur les grandes tables. Sélectionnez uniquement les colonnes nécessaires.
  * *Algorithme* : Évitez les boucles imbriquées inutiles (Complexité O(n²)).
* **Sécurité par Design (Shift-Left)** :
  * Validation des entrées (Zod/Pydantic) obligatoire aux frontières (API).
  * Aucun secret (API Key, pwd) en dur dans le code. Utilisation stricte de `.env`.
