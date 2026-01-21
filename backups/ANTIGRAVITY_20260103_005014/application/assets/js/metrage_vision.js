/**
 * MASTER ASSISTANT METRAGE - VISUAL MEASURE MODULE
 * Prise de photo et annotation (Dessin de cotes) sur le terrain.
 */

const VisualMeasure = {
    stream: null,
    targetFieldId: null,

    init: function () {
        // Injection du Modal Photo/Canvas
        $('body').append(`
            <div id="modal-vision" class="modal fade" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content bg-dark text-white">
                        <div class="modal-header border-0">
                            <h5 class="modal-title"><i class="fas fa-camera text-warning me-2"></i>Photo-Cote</h5>
                            <button type="button" class="btn-close btn-close-white" onclick="VisualMeasure.close()"></button>
                        </div>
                        <div class="modal-body p-0 position-relative d-flex align-items-center justify-content-center bg-black">
                            <!-- VIDEO STREAM -->
                            <video id="vision-video" style="width:100%; max-height:100%; object-fit:contain;" autoplay playsinline></video>
                            
                            <!-- CANVAS DESSIN (Superposé) -->
                            <canvas id="vision-canvas" style="position:absolute; top:0; left:0; display:none; touch-action: none;"></canvas>

                            <!-- BOUTONS CONTROLE -->
                            <div id="vision-controls" style="position:absolute; bottom:30px; left:0; width:100%; text-center;">
                                <button class="btn btn-light rounded-circle shadow p-4" onclick="VisualMeasure.snap()" id="btn-snap">
                                    <div style="width:20px; height:20px; background:red; border-radius:50%;"></div>
                                </button>
                            </div>

                            <div id="vision-edit-controls" style="position:absolute; bottom:30px; left:0; width:100%; text-center; display:none;">
                                <div class="btn-group shadow">
                                    <button class="btn btn-danger btn-lg" onclick="VisualMeasure.reset()"><i class="fas fa-trash"></i> Refaire</button>
                                    <button class="btn btn-success btn-lg" onclick="VisualMeasure.save()"><i class="fas fa-check"></i> Valider</button>
                                </div>
                                <p class="mt-2 text-white-50 small">Tracez un trait au doigt pour indiquer la cote.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    },

    start: function (fieldId) {
        this.targetFieldId = fieldId;
        const modal = new bootstrap.Modal(document.getElementById('modal-vision'));
        modal.show();

        // Démarrage Caméra
        navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
            .then(stream => {
                this.stream = stream;
                const video = document.getElementById('vision-video');
                video.srcObject = stream;
            })
            .catch(err => {
                alert("Erreur Caméra: " + err);
                modal.hide();
            });
    },

    snap: function () {
        const video = document.getElementById('vision-video');
        const canvas = document.getElementById('vision-canvas');
        const ctx = canvas.getContext('2d');

        // Fixer la tailler du canvas à celle de la vidéo affichée
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        // CSS Display dimensions
        $(canvas).css({
            width: $(video).width(),
            height: $(video).height(),
            top: $(video).position().top,
            left: $(video).position().left
        });

        // Dessiner la frame vidéo
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        // Arrêter le flux vidéo
        video.pause();
        $(video).hide();
        $(canvas).show();

        // UI Switch
        $('#vision-controls').hide();
        $('#vision-edit-controls').show();

        // Activer le dessin tactile
        this.enableDrawing(canvas);
    },

    enableDrawing: function (canvas) {
        let isDrawing = false;
        let startX = 0;
        let startY = 0;
        const ctx = canvas.getContext('2d');
        const overlayText = $('#' + this.targetFieldId).val() + " mm";

        const getPos = (e) => {
            const rect = canvas.getBoundingClientRect();
            // Facteur d'échelle entre CSS pixels et Canvas pixels
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;

            let clientX = e.touches ? e.touches[0].clientX : e.clientX;
            let clientY = e.touches ? e.touches[0].clientY : e.clientY;

            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        };

        const start = (e) => { isDrawing = true; const pos = getPos(e); startX = pos.x; startY = pos.y; };
        const end = (e) => {
            if (!isDrawing) return;
            isDrawing = false;
            const pos = e.changedTouches ? getPos(e.changedTouches[0]) : getPos(e);

            // Dessiner Flèche finale
            this.drawArrow(ctx, startX, startY, pos.x, pos.y, overlayText);
        };

        // Touch events
        canvas.addEventListener('touchstart', start);
        canvas.addEventListener('touchend', end);
        // Mouse events (Desktop debug)
        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mouseup', end);
    },

    drawArrow: function (ctx, x1, y1, x2, y2, text) {
        const headlen = 20;
        const angle = Math.atan2(y2 - y1, x2 - x1);

        ctx.lineWidth = 5;
        ctx.strokeStyle = '#e74c3c'; // Rouge Expert
        ctx.lineCap = "round";

        // Ligne
        ctx.beginPath();
        ctx.moveTo(x1, y1);
        ctx.lineTo(x2, y2);
        ctx.stroke();

        // Tête fin
        ctx.beginPath();
        ctx.moveTo(x2, y2);
        ctx.lineTo(x2 - headlen * Math.cos(angle - Math.PI / 6), y2 - headlen * Math.sin(angle - Math.PI / 6));
        ctx.lineTo(x2 - headlen * Math.cos(angle + Math.PI / 6), y2 - headlen * Math.sin(angle + Math.PI / 6));
        ctx.fillStyle = '#e74c3c';
        ctx.fill();

        // Texte Cote
        ctx.font = "bold 60px Arial";
        ctx.fillStyle = "white";
        ctx.strokeStyle = "black";
        ctx.lineWidth = 3;
        const midX = (x1 + x2) / 2;
        const midY = (y1 + y2) / 2;
        ctx.save();
        ctx.translate(midX, midY); // Centre au milieu du trait
        // Optionnel: rotation du texte
        // ctx.rotate(angle); 
        ctx.strokeText(text, 0, -20);
        ctx.fillText(text, 0, -20);
        ctx.restore();
    },

    reset: function () {
        const video = document.getElementById('vision-video');
        const canvas = document.getElementById('vision-canvas');

        video.play();
        $(video).show();
        $(canvas).hide();
        $('#vision-controls').show();
        $('#vision-edit-controls').hide();
    },

    close: function () {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
        }
        $('#modal-vision').modal('hide');
    },

    save: function () {
        const canvas = document.getElementById('vision-canvas');
        const dataURL = canvas.toDataURL('image/jpeg', 0.8);

        // Simulation Envoi (Dans V4 réelle, upload AJAX ici)
        console.log("Image Saved (Base64 length): ", dataURL.length);

        // Feedback visuel sur le bouton déclencheur
        const btnId = 'btn-cam-' + this.targetFieldId;
        $('#' + btnId).removeClass('btn-secondary').addClass('btn-success');
        $('#' + btnId).html('<i class="fas fa-check"></i>');

        this.close();
    }
};

$(document).ready(function () {
    VisualMeasure.init();
});
