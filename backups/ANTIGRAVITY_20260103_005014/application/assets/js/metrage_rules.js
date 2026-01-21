/**
 * METRAGE BUSINESS RULES ENGINE V1.0
 * Handles real-time calculations, safety checks (DTU), and visual guidance.
 */

const MetrageRules = {

    // --- UTILS ---

    // Convert string to float (handles comma)
    parseVal: (selector) => {
        let val = $(selector).val();
        if (!val) return 0;
        return parseFloat(val.replace(',', '.'));
    },

    // Show SweetAlert Toast
    toast: (icon, msg) => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        Toast.fire({ icon: icon, title: msg });
    },

    // --- MODULE A: MENUISERIE ---

    // Calculate Cote Fabrication (Tableau - Jeu)
    calcMenuiserie: () => {
        const jeu = 5; // mm standard rules
        const w_tableau = MetrageRules.parseVal('#largeur_tableau');
        const h_tableau = MetrageRules.parseVal('#hauteur_tableau');

        if (w_tableau > 0) {
            $('#largeur_fab').val(w_tableau - jeu);
            $('#badge_jeu_w').fadeIn();
        }
        if (h_tableau > 0) {
            $('#hauteur_fab').val(h_tableau - jeu);
            $('#badge_jeu_h').fadeIn();
        }
    },

    // Check Renovation Risk
    checkRenoRisk: (elem) => {
        const state = $(elem).val();
        if (state === 'POURRI') {
            Swal.fire({
                icon: 'error',
                title: 'STOP ! DANGER SAV',
                text: 'Le dormant est pourri. La pose en rénovation est INTERDITE par le DTU. Proposez une dépose totale.',
                footer: '<a href="#">Voir DTU 36.5</a>'
            });
            $(elem).addClass('is-invalid');
        } else {
            $(elem).removeClass('is-invalid').addClass('is-valid');
        }
    },

    // --- MODULE B: VERANDA ---

    // Check Equerrage (Diagonales)
    checkEquerrage: () => {
        const dA = MetrageRules.parseVal('#diag_a');
        const dB = MetrageRules.parseVal('#diag_b');

        if (dA > 0 && dB > 0) {
            const diff = Math.abs(dA - dB);
            if (diff > 10) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Maçonnerie non d\'équerre',
                    text: `Écart de ${diff}mm détecté. Prévoyez un profil de départ orientable ou un rattrapage de maçonnerie.`
                });
                $('#alert_equerrage').slideDown().html(`<i class="fas fa-exclamation-triangle"></i> Écart: ${diff}mm`);
            } else {
                $('#alert_equerrage').slideUp();
                MetrageRules.toast('success', 'Équerrage OK');
            }
        }
    },

    // Check Roof Load (Portée)
    checkToiture: () => {
        const prof = MetrageRules.parseVal('#profondeur');
        const remplissage = $('#remplissage').val();

        if (remplissage === 'PANNEAU' && prof > 4000) {
            Swal.fire({
                icon: 'info',
                title: 'Attention Portée > 4m',
                text: 'Pour du panneau sandwich sur cette profondeur, avez-vous prévu des renforts acier dans les chevrons ?',
                showCancelButton: true,
                confirmButtonText: 'Oui, prévu',
                cancelButtonText: 'Non, noter à vérifier'
            }).then((result) => {
                if (!result.isConfirmed) {
                    $('#notes_observateur').val($('#notes_observateur').val() + "\n[ATTENTION] Vérifier renforts acier (Portée > 4m).");
                }
            });
        }
    },

    // --- MODULE C: PORTAIL ---

    // The "Banana" Rule (3 points)
    checkPiliers: () => {
        const w_haut = MetrageRules.parseVal('#largeur_haut');
        const w_milieu = MetrageRules.parseVal('#largeur_milieu');
        const w_bas = MetrageRules.parseVal('#largeur_bas');

        if (w_haut > 0 && w_milieu > 0 && w_bas > 0) {
            const min = Math.min(w_haut, w_milieu, w_bas);
            const max = Math.max(w_haut, w_milieu, w_bas);
            const ecart = max - min;

            // Auto-select min value (passage)
            $('#largeur_passage').val(min);

            if (ecart > 20) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Piliers "Bananés"',
                    html: `Écart de <b>${ecart}mm</b> constaté entre le haut et le bas.<br>Le jeu de fonctionnement standard risque d'être insuffisant.`
                });
                $('#alert_pilier').slideDown();
            } else {
                $('#alert_pilier').slideUp();
            }
        }
    },

    // Check Slope
    checkPente: (elem) => {
        const pente = parseFloat($(elem).val());
        const type = $('#type_ouverture').val(); // BATTANT or COULISSANT

        if (type === 'BATTANT' && pente > 3) {
            Swal.fire({
                icon: 'warning',
                title: 'Pente > 3%',
                text: 'Risque de frottement du portail battant. Avez-vous prévu des gonds régulateurs de pente ?'
            });
        }
    },

    // --- LOGISTICS / MANUTENTION ---
    checkLogistics: () => {
        const etage = parseInt($('#input_etage').val()) || 0;
        const ascenseur = $('#check_ascenseur').is(':checked');

        // Weights estimation (very rough)
        // Assume large generic inputs exist or we parse them
        const w = MetrageRules.parseVal('#largeur_tableau') || MetrageRules.parseVal('#largeur_fab') || 0;
        const h = MetrageRules.parseVal('#hauteur_tableau') || MetrageRules.parseVal('#hauteur_fab') || 0;

        // Weight Formula (Generic 30kg/m2 for double glazing)
        const approxWeight = (w / 1000) * (h / 1000) * 30;

        const isHeavy = approxWeight > 60;
        const isHigh = etage > 0;

        // Show block if complicated access
        if (isHigh || isHeavy) {
            $('#bloc_manutention').slideDown();

            // Alert logic
            if (isHigh && !ascenseur && isHeavy) {
                MetrageAssistant.say(`<b>⚠️ MANUTENTION CRITIQUE</b><br>Poids estimé: ${Math.round(approxWeight)}kg au ${etage}ème SANS ascenseur.<br>Le Monte-Meuble est fortement conseillé.`, 'danger', 'fas fa-dolly');
                if ($('#notes_observateur').val().indexOf('Monte-meuble') === -1) {
                    $('#notes_observateur').val($('#notes_observateur').val() + "\n[LOGISTIQUE] Prévoir Monte-Meuble (Poids > 60kg sans ascenseur).");
                }
            }
        } else {
            $('#bloc_manutention').slideUp();
        }
    },

    // --- SCENARIO A: MENUISERIE (Trap Clones) ---
    checkRejingot: () => {
        const h_rejingot = MetrageRules.parseVal('#haut_rejingot');
        if (h_rejingot < 0) { // e.g. -1 for "inexistent"
            MetrageAssistant.say("Absence de rejingot détectée ! Une bavette de recouvrement ou un pliage alu est nécessaire.", 'warning');
        }
    },

    checkTapee: (el) => {
        const iso = parseFloat($(el).val());
        let tap = 0;
        if (iso <= 100) tap = 100;
        else if (iso <= 120) tap = 120;
        else if (iso <= 140) tap = 140;
        else if (iso <= 160) tap = 160;
        else tap = 180; // Custom

        MetrageRules.toast('info', `Tapée conseillée : ${tap}mm pour isolation de ${iso}mm`);
    },

    checkVrHandleConflict: () => {
        const hasVR = $('#vr_conserve').is(':checked');
        if (hasVR) {
            MetrageAssistant.say("<b>PIÈGE DU VR EXISTANT</b><br>La poignée de la nouvelle fenêtre risque de taper dans le tablier du volet.<br>Solution : Traverse intermédiaire ou Poignée extra-plate.", "danger", "fas fa-exclamation-circle");
        }
    },

    // --- SCENARIO B: VOLET ROULANT ---
    checkVrCoffre: () => {
        const h_tableau = MetrageRules.parseVal('#hauteur_tableau');
        const h_coffre = MetrageRules.parseVal('#hauteur_coffre'); // To be added in form

        if (h_tableau > 0 && h_coffre > 0) {
            const clair = h_tableau - h_coffre;
            $('#info_clair_vitrage').html(`Clair de vitrage restant : <b>${clair}mm</b>`);
            if (h_coffre > 200) {
                MetrageAssistant.say(`Le coffre de ${h_coffre}mm est imposant. Avez-vous prévenu le client de la perte de clair de jour ?`, 'warning');
            }
        }
    },

    checkVrManoeuvre: (val) => {
        if (val === 'GAUCHE' || val === 'DROITE') {
            MetrageAssistant.say(`Vue INTÉRIEURE : Vérifiez la présence d'une alimentation électrique du côté ${val}.`, 'info');
        }
    },

    // --- SCENARIO C: PORTAIL ---
    checkPortailRefoulement: () => {
        const w_pilier = MetrageRules.parseVal('#largeur_entre_piliers'); // Assuming ID
        const w_degagement = MetrageRules.parseVal('#largeur_degagement');

        if (w_pilier > 0 && w_degagement > 0) {
            const min_required = w_pilier + 450;
            if (w_degagement < min_required) {
                Swal.fire('BLOQUANT', `Espace insuffisant pour le refoulement.<br>Besoin : ${min_required}mm<br>Dispo : ${w_degagement}mm`, 'error');
                $('#alert_refoulement').slideDown();
            } else {
                $('#alert_refoulement').slideUp();
            }
        }
    },

    // --- SCENARIO D: STORE BANNE ---
    checkStorePassage: () => {
        const h_fix = MetrageRules.parseVal('#hauteur_fixation');
        const avancee = MetrageRules.parseVal('#avancee_store');

        if (h_fix > 0 && avancee > 0) {
            // Pente 15 degres approx (25cm per meter)
            const drop = (avancee / 1000) * 250;
            const h_passage = h_fix - drop; // Simple calc excluding cassette height

            $('#res_hauteur_passage').text(Math.round(h_passage) + ' mm');

            if (h_passage < 1900) {
                MetrageAssistant.say(`<b>DANGER TÊTE !</b><br>Hauteur de passage en bout de store estimée à ${Math.round(h_passage)}mm.<br>C'est trop bas. Remontez la fixation ou réduisez l'avancée.`, 'danger', 'fas fa-head-side-couch');
            }
        }
    },

    // --- GEOMETRY / SHAPES ---
    toggleShape: (mode) => {
        if (mode === 'SPECIAL') {
            $('#bloc_standard_rect').slideUp();
            $('#bloc_formes_speciales').slideDown();
            MetrageAssistant.say("Projet spécial (Cintre, Trapèze...). Choisissez la forme précise.", 'info');
        } else {
            $('#bloc_formes_speciales').slideUp();
            $('#bloc_standard_rect').slideDown();
            MetrageAssistant.say("Retour au standard rectangulaire.", 'info');
        }
    },

    selectShapeSubtype: (subtype) => {
        // Hide all specifics
        $('.shape-inputs').hide();
        $('.ag-card-selector').removeClass('active');

        // Active visual
        $(`input[value="${subtype}"]`).next('label').addClass('active');

        // Show relevant inputs
        if (subtype === 'TRAPEZE') {
            $('#inputs_trapeze').fadeIn();
            MetrageAssistant.say("<b>TRAPÈZE</b><br>Prenez H1 (le plus petit côté) et H2 (le grand). Vue intérieure toujours.", 'warning');
        } else if (subtype === 'CINTRE') {
            $('#inputs_cintre').fadeIn();
            MetrageAssistant.say("<b>CINTRE</b><br>Mesurez la hauteur sous naissance (H1) et la flèche au centre.", 'info');
        } else if (subtype === 'GABARIT') {
            $('#inputs_gabarit').fadeIn();
            MetrageAssistant.say("<b>FORMES LIBRES</b><br>Un gabarit physique est OBLIGATOIRE pour la fabrication. Prenez-le en photo posé sur l'ouverture.", 'danger');
        } else if (subtype === 'TRIANGLE') {
            // Basic Triangle inputs (Reuse Trapeze maybe or add specific if needed, generic fallback for now)
            MetrageAssistant.say("Pour un triangle, utilisez 'Trapèze' avec H1=0 ou notez les dimensions A/B/C en observation.", 'info');
        }
    },

    validateShape: (type) => {
        if (type === 'TRAPEZE') {
            const h1 = parseFloat($('input[name="fields[cote_h1]"]').val()) || 0;
            const h2 = parseFloat($('input[name="fields[cote_h2]"]').val()) || 0;
            if (h2 > 0 && h2 <= h1) {
                Swal.fire('Erreur Géométrique', 'Le Grand Côté (H2) doit être supérieur au Petit Côté (H1).', 'error');
                $('input[name="fields[cote_h2]"]').addClass('is-invalid');
            } else {
                $('input[name="fields[cote_h2]"]').removeClass('is-invalid');
            }
        }
    },

    // --- QUALITY & FINISHES (ZERO DEFAUT) ---
    checkRecouvrement: () => {
        const aile = parseInt($('#aile_reno_select').val()) || 0;
        const dormant = parseInt($('#larg_dormant_existant').val()) || 0;

        if (aile > 0 && dormant > 0) {
            if (dormant > aile) {
                const diff = dormant - aile;
                $('#alert_recouvrement').slideDown().html(`<i class="fas fa-exclamation-triangle text-danger me-1"></i>Ancien bois visible de <b>${diff}mm</b> !<br>Passez en aile de 70mm ou ajoutez une cornière.`);
                MetrageAssistant.say(`Attention Esthétique ! L'ancien dormant dépasse de ${diff}mm. Proposez une aile plus large.`, 'warning');
            } else {
                $('#alert_recouvrement').slideUp();
            }
        }
    },

    checkObstacles: () => {
        const plinthe = parseInt($('input[name="fields[obstacle_plinthe]"]').val()) || 0;
        const radiateur = parseInt($('input[name="fields[obstacle_radiateur]"]').val()) || 0;
        let msg = "";

        if (plinthe > 0) {
            msg += `<div><i class="fas fa-arrow-right"></i> Prévoir Élargisseur de Tapée ${plinthe}mm.</div>`;
        }
        if (radiateur > 0) {
            msg += `<div><i class="fas fa-arrow-right"></i> Attention ouverture vantail ! Vérifiez dégagement.</div>`;
        }

        $('#res_obstacle_action').html(msg);
        if (msg !== "") MetrageAssistant.say("Obstacles détectés. J'ai ajouté les élargisseurs nécessaires.", 'info');
    },

    calcVMC: () => {
        const piece = $('#vmc_piece').val();
        const surf = parseFloat($('#vmc_surface').val()) || 0;
        const vol = surf * 2.5; // Standard height
        let debit = 0;
        let ref = "";

        if (piece === 'SEJOUR') {
            debit = 45;
            ref = "GRILLE_45_ACOUSTIQUE";
        } else {
            if (vol < 30) {
                debit = 15;
                ref = "GRILLE_15";
            } else {
                debit = 30;
                ref = "GRILLE_30";
            }
        }

        $('#res_vmc_debit').text(`Débit conseillé : ${debit} m³/h (${piece})`);
        $('#vmc_debit_ref').val(ref);
    },

    // --- GLOBAL ---
    updatePoseContext: (type) => {
        // Reset
        $('#bloc_reno, #bloc_neuf, #bloc_depose').hide();
        MetrageAssistant.clear();

        if (type === 'RENOVATION') {
            $('#bloc_reno').fadeIn();
            MetrageAssistant.say("En rénovation, vérifiez bien l'état du dormant existant.", 'warning');
            MetrageAssistant.setChecklist(['Sonder le bois (tournevis)', 'Vérifier niveau traverse basse', 'Mesurer aile recouvrement']);
        }
        else if (type === 'APPLIQUE') {
            $('#bloc_neuf').fadeIn();
            MetrageAssistant.say("Pose à neuf : L'épaisseur de l'isolation détermine la tapée.", 'info');
            MetrageAssistant.setChecklist(['Mesurer isolant total (placo inclus)', 'Vérifier appui maçonnerie']);
        }
        else if (type === 'DEPOSE_TOTALE') {
            $('#bloc_depose').fadeIn();
            MetrageAssistant.say("ATTENTION : La dépose totale risque d'abîmer les doublages. Prévenir le client.", 'danger');
            MetrageAssistant.setChecklist(['Profondeur feuillure', 'Hauteur Rejingot', 'Risque casse placo']);
        }
    },

    // --- OCCULTATION RULES ---
    checkOccultationSupport: (el) => {
        const val = $(el).val();
        if (val === 'ITE') {
            MetrageAssistant.say("⚠️ SUPPORT ITE DÉTECTÉ ! Scellement chimique OBLIGATOIRE avec tamis longs et rupture de pont thermique.", 'danger', 'fas fa-skull-crossbones');
            Swal.fire('Attention ITE', 'Prévoir Kit Scellement Chimique + Tiges Filetées Longues', 'warning');
        }
    },

    checkElec: (el) => {
        const val = $(el).val();
        if (val === 'MANUEL' || val === 'SOLAIRE') {
            $('#bloc_elec_details').slideUp();
        } else {
            $('#bloc_elec_details').slideDown();
            MetrageAssistant.say("Pour l'électrique, repérez bien l'arrivée de courant vue de l'INTÉRIEUR.", 'info');
        }
    }

};

// Global Init
$(document).ready(function () {
    // console.log("Metrage Rules Loaded 🧠");
});
