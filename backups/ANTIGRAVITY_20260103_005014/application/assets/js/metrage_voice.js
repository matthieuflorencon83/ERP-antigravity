/**
 * MASTER ASSISTANT METRAGE - VOICE COMMANDER MODULE
 * Gère la dictée vocale et le pilotage du formulaire par la voix.
 */

const VoiceCommander = {
    recognition: null,
    isListening: false,
    targetInput: null,

    init: function () {
        if (!('webkitSpeechRecognition' in window)) {
            console.warn("Web Speech API non supportée sur ce navigateur.");
            return;
        }

        this.recognition = new webkitSpeechRecognition();
        this.recognition.continuous = false;
        this.recognition.lang = 'fr-FR';
        this.recognition.interimResults = false;

        this.recognition.onstart = function () {
            VoiceCommander.isListening = true;
            VoiceCommander.showUI(true);
        };

        this.recognition.onend = function () {
            VoiceCommander.isListening = false;
            VoiceCommander.showUI(false);
        };

        this.recognition.onresult = function (event) {
            const transcript = event.results[0][0].transcript;
            console.log("Voice Result:", transcript);
            VoiceCommander.processCommand(transcript);
        };

        // Créer le bouton Flottant Micro
        $('body').append(`
            <button id="btn-voice-float" class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center" 
                style="position:fixed; bottom:20px; right:20px; width:60px; height:60px; z-index:10000; display:none;"
                onclick="VoiceCommander.toggle()">
                <i class="fas fa-microphone fa-lg"></i>
            </button>
            <div id="voice-overlay" class="d-none" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:10001; color:white; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                <div class="spinner-grow text-danger mb-3" style="width: 4rem; height: 4rem;"></div>
                <h3>Je vous écoute...</h3>
                <p class="text-white-50">Dites "Largeur 1200" ou "Observation Mur abimé"</p>
                <button class="btn btn-outline-light mt-4 rounded-pill px-4" onclick="VoiceCommander.stop()">Annuler</button>
            </div>
        `);

        // Afficher le bouton micro seulement si supporté
        $('#btn-voice-float').fadeIn();
    },

    toggle: function () {
        if (this.isListening) this.stop();
        else this.start();
    },

    start: function (targetElement = null) {
        this.targetInput = targetElement ? $(targetElement) : null;
        this.recognition.start();
    },

    stop: function () {
        this.recognition.stop();
    },

    showUI: function (listening) {
        if (listening) {
            $('#voice-overlay').removeClass('d-none').addClass('d-flex');
        } else {
            $('#voice-overlay').addClass('d-none').removeClass('d-flex');
        }
    },

    processCommand: function (text) {
        text = text.toLowerCase();

        // 1. Si un champ est ciblé spécifiquement (Focus + Click Micro)
        if (this.targetInput) {
            // Nettoyage audio -> numérique
            if (this.targetInput.attr('type') === 'number') {
                let num = text.replace(/[^0-9,.]/g, '').replace(',', '.');
                this.targetInput.val(num).trigger('change');
            } else {
                let current = this.targetInput.val();
                this.targetInput.val(current ? current + " " + text : text); // Append text
            }
            return;
        }

        // 2. Mode Commandant (Analyse sémantique)
        let processed = false;
        const numberPattern = /[0-9]+([.,][0-9]+)?/;
        const match = text.match(numberPattern);
        const val = match ? parseFloat(match[0].replace(',', '.')) : null;

        // Mapping Mots-clés -> Labels partiels
        const keywords = {
            'largeur': ['largeur'],
            'hauteur': ['hauteur'],
            'profondeur': ['profondeur', 'recul'],
            'allège': ['allège'],
            'écoinçon': ['écoinçon'],
            'observation': ['observation', 'note', 'remarque']
        };

        for (const [key, aliases] of Object.entries(keywords)) {
            if (aliases.some(alias => text.includes(alias))) {
                // Trouver le champ input qui contient ce label
                // On cherche le label qui contient le mot clé, puis l'input associé
                // Stratégie : Chercher tous les labels, voir si texte match

                $('label').each(function () {
                    let labelText = $(this).text().toLowerCase();
                    if (aliases.some(alias => labelText.includes(alias))) {
                        let targetId = $(this).attr('for');
                        let input = $('#' + targetId);

                        if (input.length) {
                            // Si c'est numérique
                            if (val !== null && input.attr('type') === 'number') {
                                input.val(val).trigger('change');
                                VoiceCommander.feedback(`Champ ${key} rempli : ${val}`);
                                processed = true;
                                return false; // Break loop
                            }
                            // Si c'est textuel (Observation)
                            else if (key === 'observation') {
                                // On enlève le mot clé "Note..."
                                let content = text.replace(/observation|note|remarque/gi, '').trim();
                                input.val(content);
                                VoiceCommander.feedback(`Note ajoutée.`);
                                processed = true;
                                return false;
                            }
                        }
                    }
                });
                if (processed) break;
            }
        }

        if (!processed) {
            VoiceCommander.feedback("Commande non comprise : " + text, 'warning');
        }
    },

    feedback: function (msg, type = 'success') {
        const toastId = 'toast-' + Date.now();
        $('body').append(`
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0 show" role="alert" style="position:fixed; top:20px; left:50%; transform:translateX(-50%); z-index:10002;">
              <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
              </div>
            </div>
        `);
        setTimeout(() => $(`#${toastId}`).remove(), 3000);
    }
};

$(document).ready(function () {
    VoiceCommander.init();
});
