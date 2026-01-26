# Plan de Construction (Roadmap) - ERP Arts alu

Ce document définit les étapes clés du développement du projet.

## Phase 1 : Fondations Data (MySQL)

**Objectif :** Mise en place de la base de données.

- Structuration de la Bibliothèque (Module M3).
- Dépendance : Indispensable pour tous les autres modules.
- Règle appliquée : **Hybride SQL/JSON**.

## Phase 2 : Le Cœur de Calcul (Python/Node)

**Objectif :** Moteur d'intelligence métier.

- Développement des algorithmes de calepinage.
- Logique de calcul des besoins (Module M2).
- Règle appliquée : **Dual-Back** (Calculs intensifs en Python).

## Phase 3 : L'Orchestration (Node.js)

**Objectif :** Serveur d'application et flux de données.

- Création des APIs REST.
- Gestion du cycle de vie des commandes (Module M1).
- Règle appliquée : **Séparation des responsabilités**.

## Phase 4 : L'Interface (Angular)

**Objectif :** Expérience utilisateur.

- Création du tableau de bord responsive.
- Design System (Modes clair/sombre, animations).
- Règle appliquée : **Frontend "stupide"** (uniquement affichage et capture).

## Phase 5 : Intelligence Artificielle (M4)

**Objectif :** Automatisation avancée.

- Intégration des fonctions d'analyse de documents.
- OCR et extraction intelligente pour les commandes.
