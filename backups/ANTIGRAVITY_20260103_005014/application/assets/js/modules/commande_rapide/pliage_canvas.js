/**
 * Pliage Module Logic (Canvas Drawing)
 */

let currentShape = 'L';
const canvasId = 'pliageCanvas';

function initPliageModule() {
    console.log("Pliage Module Init");
    setPliageShape('L'); // Default
}

function setPliageShape(shape) {
    currentShape = shape;
    updateInputs(shape);
    drawPliage();
}

function updateInputs(shape) {
    const container = document.getElementById('inputs_container');
    container.innerHTML = ''; // Request clear

    // Helper to create input
    const addInput = (label, id, val) => {
        container.innerHTML += `
        <div class="mb-2 input-row">
            <label class="form-label small fw-bold">${label}</label>
            <input type="number" class="form-control" id="${id}" value="${val}" oninput="drawPliage()">
        </div>`;
    };

    if (shape === 'L') {
        addInput('Cote A (mm)', 'cote_A', 100);
        addInput('Cote B (mm)', 'cote_B', 50);
        addInput('Angle (°)', 'angle_1', 90);
    } else if (shape === 'U') {
        addInput('Cote A (mm)', 'cote_A', 40);
        addInput('Cote B (mm)', 'cote_B', 100);
        addInput('Cote C (mm)', 'cote_C', 40);
    } else if (shape === 'Z') {
        addInput('Cote A (mm)', 'cote_A', 30);
        addInput('Cote B (mm)', 'cote_B', 100);
        addInput('Cote C (mm)', 'cote_C', 50);
    } else if (shape === 'PLAT') {
        addInput('Largeur A (mm)', 'cote_A', 80);
    }
}

function drawPliage() {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const w = canvas.width;
    const h = canvas.height;

    // Clear
    ctx.clearRect(0, 0, w, h);
    ctx.strokeStyle = '#333';
    ctx.lineWidth = 4;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.font = '14px Arial';
    ctx.fillStyle = '#000';

    // Get Values
    const val = (id) => parseFloat(document.getElementById(id)?.value || 0);
    const A = val('cote_A');
    const B = val('cote_B');
    const C = val('cote_C');

    const scale = 200 / Math.max(A + B + C, 200); // Auto scale
    const cx = w / 2;
    const cy = h / 2;

    ctx.beginPath();

    if (currentShape === 'L') {
        // Simple L shape: Start top, go down (A), go right (B)
        // Center it roughly
        const startX = cx - (B * scale) / 2;
        const startY = cy - (A * scale) / 2;

        ctx.moveTo(startX, startY);
        ctx.lineTo(startX, startY + A * scale); // Vertical A
        ctx.lineTo(startX + B * scale, startY + A * scale); // Horizontal B

        ctx.stroke();

        // Labels
        ctx.fillText(`A: ${A}`, startX - 35, startY + (A * scale) / 2);
        ctx.fillText(`B: ${B}`, startX + (B * scale) / 2 - 10, startY + A * scale + 20);

        // Laquage indication (Red line parallel)
        drawLaquage(ctx, startX, startY, A, B, scale, 'L');
    }

    else if (currentShape === 'U') {
        // U shape: Down A, Right B, Up C
        const startX = cx - (B * scale) / 2;
        const startY = cy - Math.max(A, C) * scale / 2; // Center Y based on tallest leg? No.

        ctx.moveTo(startX, cy - (A * scale) / 2);
        ctx.lineTo(startX, cy + (A * scale) / 2 + 20); // A leg
        // Wait, Logic is simpler if we draw relative to a corner

        // Re-calc specific for U

        // Pt 1 (High Left) -> Pt 2 (Low Left) -> Pt 3 (Low Right) -> Pt 4 (High Right)
        const x1 = cx - B * scale / 2;
        const yBase = cy + 50;

        const pt1 = { x: x1, y: yBase - A * scale };
        const pt2 = { x: x1, y: yBase };
        const pt3 = { x: x1 + B * scale, y: yBase };
        const pt4 = { x: x1 + B * scale, y: yBase - C * scale };

        ctx.beginPath();
        ctx.moveTo(pt1.x, pt1.y);
        ctx.lineTo(pt2.x, pt2.y);
        ctx.lineTo(pt3.x, pt3.y);
        ctx.lineTo(pt4.x, pt4.y);
        ctx.stroke();

        ctx.fillText(`A: ${A}`, pt1.x - 30, pt1.y + (pt2.y - pt1.y) / 2);
        ctx.fillText(`B: ${B}`, pt2.x + (pt3.x - pt2.x) / 2 - 10, pt2.y + 20);
        ctx.fillText(`C: ${C}`, pt4.x + 10, pt4.y + (pt3.y - pt4.y) / 2);
    }
    // ... Implement Z and PLAT similarly
}

function drawLaquage(ctx, x, y, A, B, scale, shape) {
    // Determine inner or outer logic
    const side = document.querySelector('input[name="laquage_side"]:checked').value; // INT or EXT

    ctx.beginPath();
    ctx.strokeStyle = '#dc3545'; // RED
    ctx.lineWidth = 2;
    ctx.setLineDash([5, 5]);

    const offset = (side === 'EXT') ? -10 : 10;
    // Simplified: Just draw a parallel line indicating paint side
    // For L shape 'EXT' means outside the corner. 'INT' means inside the L.

    if (shape === 'L') {
        if (side === 'EXT') {
            // Outside
            ctx.moveTo(x - 5, y);
            ctx.lineTo(x - 5, y + A * scale + 5);
            ctx.lineTo(x + B * scale, y + A * scale + 5);
        } else {
            // Inside
            ctx.moveTo(x + 5, y);
            ctx.lineTo(x + 5, y + A * scale - 5);
            ctx.lineTo(x + B * scale, y + A * scale - 5);
        }
    }

    ctx.stroke();
    // Reset
    ctx.setLineDash([]);
    ctx.strokeStyle = '#333';
    ctx.lineWidth = 4;
}

window.setPliageShape = setPliageShape;
window.drawPliage = drawPliage;
window.initPliageModule = initPliageModule;

window.getPliageCanvasData = function () {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;
    return canvas.toDataURL('image/png');
};
