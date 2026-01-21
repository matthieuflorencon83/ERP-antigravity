/**
 * Vitrage Module Logic
 * Calculator for Surface & Weight
 */

function initVitrageModule() {
    console.log("Vitrage Module Init");
    calculateWeight();
}

function toggleFormeMode() {
    const isSpecial = document.getElementById('switch_forme').checked;
    const rectGroups = document.querySelectorAll('.group-rect');
    const formeGroups = document.querySelectorAll('.group-forme');

    if (isSpecial) {
        rectGroups.forEach(el => el.style.display = 'none');
        formeGroups.forEach(el => el.style.display = 'block');
        // Disable required on rect inputs
        document.getElementById('vit_largeur').required = false;
        document.getElementById('vit_hauteur').required = false;
    } else {
        rectGroups.forEach(el => el.style.display = 'block');
        formeGroups.forEach(el => el.style.display = 'none');
        document.getElementById('vit_largeur').required = true;
        document.getElementById('vit_hauteur').required = true;
    }
}

function calculateWeight() {
    const w = parseFloat(document.getElementById('vit_largeur').value) || 0;
    const h = parseFloat(document.getElementById('vit_hauteur').value) || 0;

    // Surface
    const surface = (w * h) / 1000000;

    // Glass Thickness Heuristic
    let thickness = 4 + 4; // Default Double 4/16/4 = 8mm glass

    const type = document.getElementById('vit_type').value;
    const compo = document.querySelector('input[name="composition"]').value.toLowerCase();

    if (type === 'TRIPLE') thickness = 4 + 4 + 4; // 12
    if (type === 'SIMPLE') thickness = 6;
    if (type === 'FEUILLETE') thickness = 8 + 4; // 44.2 + 4 approx
    if (compo.includes('44.2')) thickness += 4; // Adjust
    if (compo.includes('sp10')) thickness += 6;

    // Weight: 2.5kg per mm per m2
    const weight = surface * thickness * 2.5;

    document.getElementById('calc_result').innerHTML = `
        Surface: ${surface.toFixed(2)} m² <br> 
        Poids: ~${Math.round(weight)} kg
    `;
    document.getElementById('input_poids_estime').value = Math.round(weight);
}

// Global Expose
window.toggleFormeMode = toggleFormeMode;
window.calculateWeight = calculateWeight;
window.initVitrageModule = initVitrageModule;
