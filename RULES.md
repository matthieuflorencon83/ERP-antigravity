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
