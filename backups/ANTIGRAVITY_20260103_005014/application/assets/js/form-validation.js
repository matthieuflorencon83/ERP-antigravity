/**
 * assets/js/form-validation.js
 * Gestion centralisée du formatage et de l'auto-complétion
 *
 * Règles :
 * 1. NOM (input[name*="nom"], .uppercase) -> TOUPPERCASE
 * 2. Prénom (input[name*="prenom"], .capitalize) -> Capitalize
 * 3. Auto-complétion CP <-> Ville via API Gouv
 * 4. Validation visuelle (Email, Tel, Pattern)
 */

document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // 1. FORMATAGE AUTOMATIQUE (Uppercase / Capitalize)
    // ==========================================

    // Sélecteurs larges pour attraper "nom", "nom_principal", "prenom", etc.
    const inputsUppercase = document.querySelectorAll('input[name*="nom"], .uppercase');
    const inputsCapitalize = document.querySelectorAll('input[name*="prenom"], .capitalize');

    inputsUppercase.forEach(input => {
        // On exclut les types radio/checkbox/file/hidden au cas où
        if (['text', 'search'].includes(input.type)) {
            input.addEventListener('input', function () {
                this.value = this.value.toUpperCase();
            });
        }
    });

    inputsCapitalize.forEach(input => {
        if (['text', 'search'].includes(input.type)) {
            input.addEventListener('input', function () {
                if (this.value.length > 0) {
                    this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
                }
            });
        }
    });

    // ==========================================
    // 2. AUTO-COMPLÉTION ADRESSE (API Gouv)
    // ==========================================

    // On cherche des paires de champs CP/Ville dans le même conteneur (form, row, modal...)
    // Fonction utilitaire pour attacher l'event
    function setupGeoAutocomplete(cpInput, villeInput) {
        if (!cpInput || !villeInput) return;

        // CP -> Ville
        cpInput.addEventListener('blur', function () {
            const code = this.value;
            if (code.length === 5) {
                fetch(`https://geo.api.gouv.fr/communes?codePostal=${code}&fields=nom&format=json&geometry=centre`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            villeInput.value = data[0].nom.toUpperCase();
                        }
                    })
                    .catch(err => console.error("GeoAPI Error:", err));
            }
        });

        // Ville -> CP
        villeInput.addEventListener('blur', function () {
            const ville = this.value;
            if (ville.length > 3 && cpInput.value.length === 0) {
                fetch(`https://geo.api.gouv.fr/communes?nom=${ville}&fields=codesPostaux&boost=population&limit=1`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            cpInput.value = data[0].codesPostaux[0];
                        }
                    })
                    .catch(err => console.error("GeoAPI Error:", err));
            }
        });
    }

    // Détection automatique des couples CP/Ville standards
    // Cas 1 : Champs globaux dans la page
    const mainCP = document.querySelector('input[name="code_postal"], input[name="cp"]');
    const mainVille = document.querySelector('input[name="ville"]');
    setupGeoAutocomplete(mainCP, mainVille);

    // Cas 2 : Champs dans des modales (ex: Contact, Adresse)
    // On observe l'ouverture des modales ou on scanne tout le document
    // Pour faire simple, on scanne tous les inputs CP et on cherche leur voisin Ville
    const allCPs = document.querySelectorAll('input[name="code_postal"], input[name="cp"]');
    allCPs.forEach(cp => {
        // Si c'est le mainCP déjà traité, on passe
        if (cp === mainCP) return;

        // On cherche un champ ville dans le même formulaire parent
        const form = cp.closest('form');
        if (form) {
            const ville = form.querySelector('input[name="ville"]');
            setupGeoAutocomplete(cp, ville);
        }
    });


    // ==========================================
    // 3. VALIDATION VISUELLE (Bootstrap)
    // ==========================================
    const inputsToValidate = document.querySelectorAll('input[type="email"], input[type="tel"], input[type="url"], input[pattern]');

    inputsToValidate.forEach(input => {
        input.addEventListener('blur', () => {
            // Si vide, pas d'invalidité sauf si required (géré par navigateur)
            if (input.value === '') {
                input.classList.remove('is-invalid', 'is-valid');
                return;
            }

            if (input.checkValidity()) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
            }
        });
    });
});
