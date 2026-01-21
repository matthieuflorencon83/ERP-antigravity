ANTIGRAVITY - SAUVEGARDE COMPLETE
================================

Date: 03/01/2026  0:50:19,45
Application: C:\laragon\www\antigravity
Base de donnees: antigravity_v3

Contenu de la sauvegarde:
- /application/ : Tous les fichiers du logiciel
- /database/ : Export SQL complet de la base

Pour restaurer:
1. Copier le contenu de /application/ vers C:\laragon\www\antigravity\
2. Importer le fichier SQL: mysql -u root antigravity_v3 < database\*.sql
