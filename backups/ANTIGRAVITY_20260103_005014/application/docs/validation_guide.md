# Guide d'Utilisation - Validation des Formulaires

## 📋 Règles de Validation Standardisées

Ce document explique comment utiliser les patterns de validation HTML5 dans tous les formulaires de l'application.

## 🎯 Patterns Disponibles

### Email

```php
<input type="email" name="email" class="form-control" 
       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" 
       title="Format: nom@domaine.com" 
       placeholder="exemple@domaine.com">
```

### Téléphone Fixe (France)

```php
<input type="tel" name="telephone" class="form-control" 
       pattern="^(?:(?:\+|00)33|0)[1-59](?:[\s.-]*\d{2}){4}$" 
       title="Format: 01 22 33 44 55" 
       placeholder="01 23 45 67 89">
```

### Téléphone Mobile (France)

```php
<input type="tel" name="mobile" class="form-control" 
       pattern="^(?:(?:\+|00)33|0)[67](?:[\s.-]*\d{2}){4}$" 
       title="Format: 06 12 34 56 78" 
       placeholder="06 12 34 56 78">
```

### Code Postal (France)

```php
<input type="text" name="code_postal" class="form-control" 
       pattern="[0-9]{5}" 
       title="5 chiffres obligatoires" 
       placeholder="33000">
```

### Site Web / URL

```php
<input type="url" name="site_web" class="form-control" 
       pattern="https?://.+" 
       title="Doit commencer par http:// ou https://" 
       placeholder="https://www.exemple.com">
```

### SIRET (14 chiffres)

```php
<input type="text" name="siret" class="form-control" 
       pattern="[0-9]{14}" 
       title="14 chiffres obligatoires" 
       placeholder="12345678901234">
```

### TVA Intracommunautaire

```php
<input type="text" name="tva_intra" class="form-control" 
       pattern="FR[0-9]{11}" 
       title="Format: FR12345678901" 
       placeholder="FR12345678901">
```

## 🚀 Utilisation avec validation_patterns.php

Pour utiliser les constantes centralisées :

```php
<?php require_once 'validation_patterns.php'; ?>

<!-- Email -->
<input <?= input_email_attrs() ?> name="email" class="form-control">

<!-- Téléphone Fixe -->
<input <?= input_tel_fixe_attrs() ?> name="telephone" class="form-control">

<!-- Mobile -->
<input <?= input_tel_mobile_attrs() ?> name="mobile" class="form-control">

<!-- Code Postal -->
<input <?= input_code_postal_attrs() ?> name="code_postal" class="form-control">

<!-- URL -->
<input <?= input_url_attrs() ?> name="site_web" class="form-control">
```

## ✅ Fichiers Déjà Validés

- ✅ `fournisseurs_detail.php` - Complet (Main Form + Modals)
- ⏳ `affaires_detail.php` - À faire
- ⏳ `commandes_detail.php` - À faire
- ⏳ `catalogue_detail.php` - À faire

## 🎨 Comportement UX

Lorsqu'un utilisateur entre des données invalides :

1. Le champ devient rouge (`:invalid` CSS)
2. Un message d'erreur s'affiche au survol (attribut `title`)
3. Le formulaire ne peut pas être soumis tant que les données sont invalides

## 🔧 Maintenance

Pour modifier un pattern :

1. Éditer `validation_patterns.php`
2. Le changement s'applique automatiquement partout
3. Pas besoin de modifier chaque formulaire individuellement
