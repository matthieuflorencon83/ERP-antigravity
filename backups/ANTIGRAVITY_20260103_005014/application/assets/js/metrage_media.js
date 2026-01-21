/**
 * METRAGE MEDIA ENGINE V1.0
 * Handles Photo Upload, Canvas Annotation (Draw/Arrow/Text), and Base64 Export.
 */

const MetrageMedia = {
    canvas: null,
    ctx: null,
    isDrawing: false,
    currentTool: 'pen', // pen, arrow, text
    currentColor: 'red',
    activeImage: null,
    targetInputId: null, // The hidden input storing the Base64

    init: () => {
        // Init Canvas
        const canvasEl = document.getElementById('annotation-canvas');
        if (!canvasEl) return;
        MetrageMedia.canvas = canvasEl;
        MetrageMedia.ctx = canvasEl.getContext('2d');

        // Events
        canvasEl.addEventListener('mousedown', MetrageMedia.startDraw);
        canvasEl.addEventListener('mousemove', MetrageMedia.draw);
        canvasEl.addEventListener('mouseup', MetrageMedia.stopDraw);
        canvasEl.addEventListener('touchstart', MetrageMedia.startDraw);
        canvasEl.addEventListener('touchmove', MetrageMedia.draw);
        canvasEl.addEventListener('touchend', MetrageMedia.stopDraw);

        console.log("Media Engine Loaded 📸");
    },

    // 1. UPLOAD & OPEN ANNOTATOR
    handleUpload: (input, targetId) => {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                MetrageMedia.targetInputId = targetId; // Where to save result
                MetrageMedia.openAnnotator(e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    },

    openAnnotator: (imgSrc) => {
        const img = new Image();
        img.onload = () => {
            // Resize logic (Fit to screen)
            const maxWidth = window.innerWidth * 0.9;
            const maxHeight = window.innerHeight * 0.8;

            let w = img.width;
            let h = img.height;

            if (w > maxWidth) { h = h * (maxWidth / w); w = maxWidth; }
            if (h > maxHeight) { w = w * (maxHeight / h); h = maxHeight; }

            MetrageMedia.canvas.width = w;
            MetrageMedia.canvas.height = h;

            // Draw BG
            MetrageMedia.ctx.drawImage(img, 0, 0, w, h);
            MetrageMedia.activeImage = img; // Keep ref for undo/reset (not implemented fully in V1)

            // Show Modal
            $('#modal-annotator').fadeIn();
        };
        img.src = imgSrc;
    },

    // 2. DRAWING LOGIC (PEN)
    startDraw: (e) => {
        MetrageMedia.isDrawing = true;
        const pos = MetrageMedia.getPos(e);
        MetrageMedia.ctx.beginPath();
        MetrageMedia.ctx.moveTo(pos.x, pos.y);
        MetrageMedia.ctx.strokeStyle = MetrageMedia.currentColor;
        MetrageMedia.ctx.lineWidth = 3;
    },

    draw: (e) => {
        if (!MetrageMedia.isDrawing) return;
        e.preventDefault(); // Stop scroll
        const pos = MetrageMedia.getPos(e);
        MetrageMedia.ctx.lineTo(pos.x, pos.y);
        MetrageMedia.ctx.stroke();
    },

    stopDraw: () => {
        MetrageMedia.isDrawing = false;
        MetrageMedia.ctx.closePath();
    },

    getPos: (e) => {
        const rect = MetrageMedia.canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    },

    // 3. TOOLS
    setTool: (tool) => {
        MetrageMedia.currentTool = tool;
        // Visual feedback on buttons would go here
    },

    setColor: (color) => {
        MetrageMedia.currentColor = color;
    },

    // 4. SAVE
    save: () => {
        const dataUrl = MetrageMedia.canvas.toDataURL('image/jpeg', 0.8);

        // Update Thumbnail on UI
        const targetId = MetrageMedia.targetInputId;
        $('#thumb_' + targetId).attr('src', dataUrl).show();
        $('#btn_' + targetId).hide(); // Hide upload btn, show edit btn maybe?

        // Store in Hidden Input
        $('#' + targetId).val(dataUrl);

        // Close
        $('#modal-annotator').fadeOut();
    }
};

$(document).ready(() => {
    MetrageMedia.init();
});
