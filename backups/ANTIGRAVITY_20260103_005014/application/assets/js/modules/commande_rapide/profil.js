/**
 * Profil Module Logic
 * Handles Bicoloration switch and Cut vs Bar mode
 */

function toggleBicoloration() {
    const isBico = document.getElementById('switch_bicoloration').checked;
    const monoGroup = document.getElementById('group_mono');
    const bicoGroup = document.getElementById('group_bico');

    if (isBico) {
        monoGroup.style.display = 'none';
        bicoGroup.style.display = 'block';
    } else {
        monoGroup.style.display = 'block';
        bicoGroup.style.display = 'none';
    }
}

function toggleCoupeMode() {
    const isCoupe = document.getElementById('cond_coupe').checked;
    const unitDisplay = document.getElementById('unit_display');
    const lenInput = document.getElementById('input_longueur_coupe');

    if (isCoupe) {
        unitDisplay.textContent = 'pièce(s)';
        lenInput.style.display = 'block';
        lenInput.querySelector('input').required = true;
    } else {
        unitDisplay.textContent = 'barres de 6.5m';
        lenInput.style.display = 'none';
        lenInput.querySelector('input').required = false;
    }
}

function checkProfilAlerts() {
    const supplier = document.getElementById('prof_fournisseur').value;
    const alertAkra = document.getElementById('alert_akraplast');

    if (supplier === 'AKRAPLAST') {
        alertAkra.style.display = 'block';
    } else {
        alertAkra.style.display = 'none';
    }
}

// Global checks on load (in case of re-render)
// Wait, functions are global, but we might want to init state
function initProfilModule() {
    // defaults
    toggleBicoloration();
    toggleCoupeMode();
    checkProfilAlerts();
}

window.toggleBicoloration = toggleBicoloration;
window.toggleCoupeMode = toggleCoupeMode;
window.checkProfilAlerts = checkProfilAlerts;
window.initProfilModule = initProfilModule;
