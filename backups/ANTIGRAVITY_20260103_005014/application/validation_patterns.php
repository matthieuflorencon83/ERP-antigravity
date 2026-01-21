<?php
/**
 * Validation Patterns - Constantes de validation HTML5
 * À inclure dans tous les formulaires pour assurer la cohérence
 */

// EMAIL
define('PATTERN_EMAIL', '[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$');
define('TITLE_EMAIL', 'Format: nom@domaine.com');
define('PLACEHOLDER_EMAIL', 'exemple@domaine.com');

// TÉLÉPHONE FIXE (France)
define('PATTERN_TEL_FIXE', '^(?:(?:\+|00)33|0)[1-59](?:[\s.-]*\d{2}){4}$');
define('TITLE_TEL_FIXE', 'Format: 01 22 33 44 55');
define('PLACEHOLDER_TEL_FIXE', '01 23 45 67 89');

// TÉLÉPHONE MOBILE (France)
define('PATTERN_TEL_MOBILE', '^(?:(?:\+|00)33|0)[67](?:[\s.-]*\d{2}){4}$');
define('TITLE_TEL_MOBILE', 'Format: 06 12 34 56 78');
define('PLACEHOLDER_TEL_MOBILE', '06 12 34 56 78');

// CODE POSTAL (France)
define('PATTERN_CODE_POSTAL', '[0-9]{5}');
define('TITLE_CODE_POSTAL', '5 chiffres obligatoires');
define('PLACEHOLDER_CODE_POSTAL', '33000');

// URL / SITE WEB
define('PATTERN_URL', 'https?://.+');
define('TITLE_URL', 'Doit commencer par http:// ou https://');
define('PLACEHOLDER_URL', 'https://www.exemple.com');

// SIRET (14 chiffres)
define('PATTERN_SIRET', '[0-9]{14}');
define('TITLE_SIRET', '14 chiffres obligatoires');
define('PLACEHOLDER_SIRET', '12345678901234');

// TVA INTRACOMMUNAUTAIRE (France: FR + 11 chiffres)
define('PATTERN_TVA_INTRA', 'FR[0-9]{11}');
define('TITLE_TVA_INTRA', 'Format: FR12345678901');
define('PLACEHOLDER_TVA_INTRA', 'FR12345678901');

/**
 * Fonctions Helper pour générer les attributs HTML
 */
function input_email_attrs() {
    return 'type="email" pattern="' . PATTERN_EMAIL . '" title="' . TITLE_EMAIL . '" placeholder="' . PLACEHOLDER_EMAIL . '"';
}

function input_tel_fixe_attrs() {
    return 'type="tel" pattern="' . PATTERN_TEL_FIXE . '" title="' . TITLE_TEL_FIXE . '" placeholder="' . PLACEHOLDER_TEL_FIXE . '"';
}

function input_tel_mobile_attrs() {
    return 'type="tel" pattern="' . PATTERN_TEL_MOBILE . '" title="' . TITLE_TEL_MOBILE . '" placeholder="' . PLACEHOLDER_TEL_MOBILE . '"';
}

function input_code_postal_attrs() {
    return 'type="text" pattern="' . PATTERN_CODE_POSTAL . '" title="' . TITLE_CODE_POSTAL . '" placeholder="' . PLACEHOLDER_CODE_POSTAL . '"';
}

function input_url_attrs() {
    return 'type="url" pattern="' . PATTERN_URL . '" title="' . TITLE_URL . '" placeholder="' . PLACEHOLDER_URL . '"';
}

function input_siret_attrs() {
    return 'type="text" pattern="' . PATTERN_SIRET . '" title="' . TITLE_SIRET . '" placeholder="' . PLACEHOLDER_SIRET . '"';
}

function input_tva_intra_attrs() {
    return 'type="text" pattern="' . PATTERN_TVA_INTRA . '" title="' . TITLE_TVA_INTRA . '" placeholder="' . PLACEHOLDER_TVA_INTRA . '"';
}
