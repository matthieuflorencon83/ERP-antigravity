/**
 * MetrageCanvas.js - Moteur de Rendu Visuel 2D
 * 
 * Responsabilité : Dessiner le produit en temps réel dans le Canvas
 * Capacités : Rendu Fenêtre, Porte, Baie, Cotations dynamiques
 * 
 * @version 1.0.0
 */

export class MetrageCanvas {

    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.width = this.canvas.width;
        this.height = this.canvas.height;

        // Configuration Rendu
        this.padding = 50;
        this.scale = 0.1; // px/mm

        // Styles
        this.styles = {
            wall: '#2d2d2d',
            frame: '#ffffff',
            glass: '#a8d5e5', // Bleu vitrage
            glassOpacity: 0.3,
            dimensionLine: '#0d6efd',
            dimensionText: '#ffffff'
        };

        // Redimensionnement auto
        this.resize();
        window.addEventListener('resize', () => this.resize());
    }

    resize() {
        const parent = this.canvas.parentElement;
        this.canvas.width = parent.clientWidth;
        this.canvas.height = parent.clientHeight;
        this.width = this.canvas.width;
        this.height = this.canvas.height;

        // Redessiner si un produit est chargé, sinon placeholder
        if (this.lastProduct) {
            this.draw(this.lastProduct);
        } else {
            this.drawPlaceholder();
        }
    }

    drawPlaceholder() {
        this.clear();
        this.ctx.fillStyle = '#252525';
        this.ctx.fillRect(0, 0, this.width, this.height);

        // Grid
        this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
        this.ctx.lineWidth = 1;
        const step = 50;

        for (let x = 0; x < this.width; x += step) {
            this.ctx.beginPath();
            this.ctx.moveTo(x, 0);
            this.ctx.lineTo(x, this.height);
            this.ctx.stroke();
        }
        for (let y = 0; y < this.height; y += step) {
            this.ctx.beginPath();
            this.ctx.moveTo(0, y);
            this.ctx.lineTo(this.width, y);
            this.ctx.stroke();
        }

        // Text
        this.ctx.fillStyle = 'rgba(255, 255, 255, 0.2)';
        this.ctx.font = '24px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        this.ctx.fillText("Visualisation 2D", this.width / 2, this.height / 2);
    }

    /**
     * Dessiner le produit
     * @param {Object} product - Données du WizardState
     */
    draw(product) {
        this.lastProduct = product;
        this.clear();

        const dims = product.data.dimensions || {};
        const largeur = parseInt(dims.largeur) || 1000;
        const hauteur = parseInt(dims.hauteur) || 1000;
        const forme = dims.forme || 'rectangle';

        // Calculer l'échelle pour centrer
        this.calculateScale(largeur, hauteur);

        // 1. Dessiner le mur (Fond)
        this.drawWall();

        // 2. Dessiner la menuiserie
        this.drawJoinery(largeur, hauteur, product.categorie, forme);

        // 3. Dessiner les cotations
        this.drawDimensions(largeur, hauteur);
    }

    clear() {
        this.ctx.clearRect(0, 0, this.width, this.height);
    }

    calculateScale(l, h) {
        // Marge de 10%
        const maxW = this.width * 0.8;
        const maxH = this.height * 0.8;

        const scaleX = maxW / l;
        const scaleY = maxH / h;

        this.scale = Math.min(scaleX, scaleY);

        // Centrage
        this.offsetX = (this.width - (l * this.scale)) / 2;
        this.offsetY = (this.height - (h * this.scale)) / 2;
    }

    drawWall() {
        this.ctx.fillStyle = this.styles.wall;
        this.ctx.fillRect(0, 0, this.width, this.height);

        // Trou dans le mur (Maçonnerie)
        // Simulation en "gommant" le mur ou en dessinant un rect plus clair
        // Ici on va juste laisser le fond noir pour le contraste du cadre blanc
    }

    drawJoinery(l, h, categorie, forme) {
        const x = this.offsetX;
        const y = this.offsetY;
        const w = l * this.scale;
        const hh = h * this.scale;

        this.ctx.save();

        // Cadre (Dormant)
        this.ctx.strokeStyle = this.styles.frame;
        this.ctx.lineWidth = 4;
        this.ctx.fillStyle = `rgba(168, 213, 229, ${this.styles.glassOpacity})`; // Vitrage

        this.ctx.beginPath();
        if (forme === 'rectangle') {
            this.ctx.rect(x, y, w, hh);
        } else if (forme === 'cintre') {
            // Cintre surbaissé ou plein cintre
            // Pour simplifier : Cintre = Rectangle + Arc en haut
            const radius = w / 2;
            const hRect = hh - radius;
            this.ctx.moveTo(x, y + hRect);
            this.ctx.lineTo(x, y + hh);
            this.ctx.lineTo(x + w, y + hh);
            this.ctx.lineTo(x + w, y + hRect);
            this.ctx.arc(x + radius, y + hRect, radius, 0, Math.PI, true);
        }

        this.ctx.fill();
        this.ctx.stroke();

        // Montant central (si largeur > 900mm pour fenêtre standard = 2 vantaux)
        if (l > 900 && categorie?.includes('FEN')) {
            this.ctx.beginPath();
            this.ctx.moveTo(x + w / 2, y);
            this.ctx.lineTo(x + w / 2, y + hh);
            this.ctx.lineWidth = 2;
            this.ctx.stroke();

            // Poignée (vantail principal - souvent droite vue int)
            this.drawHandle(x + w / 2 - 10, y + hh / 2);
        }
        else if (categorie?.includes('PORTE')) {
            // Seuil
            this.ctx.fillStyle = '#666';
            this.ctx.fillRect(x, y + hh - 5, w, 5);

            // Poignée
            this.drawHandle(x + w - 40, y + hh / 2);
        }

        this.ctx.restore();
    }

    drawHandle(cx, cy) {
        this.ctx.fillStyle = '#fff';
        this.ctx.fillRect(cx, cy - 15, 8, 30);
    }

    drawDimensions(l, h) {
        const x = this.offsetX;
        const y = this.offsetY;
        const w = l * this.scale;
        const hh = h * this.scale;
        const pad = 20;

        this.ctx.strokeStyle = this.styles.dimensionLine;
        this.ctx.fillStyle = this.styles.dimensionText;
        this.ctx.lineWidth = 1;
        this.ctx.font = '16px monospace';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';

        // Largeur (Bas)
        this.ctx.beginPath();
        this.ctx.moveTo(x, y + hh + pad);
        this.ctx.lineTo(x + w, y + hh + pad);
        // Flèches
        this.ctx.moveTo(x, y + hh + pad - 5); this.ctx.lineTo(x, y + hh + pad + 5);
        this.ctx.moveTo(x + w, y + hh + pad - 5); this.ctx.lineTo(x + w, y + hh + pad + 5);
        this.ctx.stroke();

        // Texte Largeur
        this.ctx.fillText(`${l} mm`, x + w / 2, y + hh + pad + 15);

        // Hauteur (Gauche)
        this.ctx.beginPath();
        this.ctx.moveTo(x - pad, y);
        this.ctx.lineTo(x - pad, y + hh);
        // Flèches
        this.ctx.moveTo(x - pad - 5, y); this.ctx.lineTo(x - pad + 5, y);
        this.ctx.moveTo(x - pad - 5, y + hh); this.ctx.lineTo(x - pad + 5, y + hh);
        this.ctx.stroke();

        // Texte Hauteur
        this.ctx.save();
        this.ctx.translate(x - pad - 15, y + hh / 2);
        this.ctx.rotate(-Math.PI / 2);
        this.ctx.fillText(`${h} mm`, 0, 0);
        this.ctx.restore();
    }
}
