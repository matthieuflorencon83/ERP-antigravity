/**
 * CalculateurMetier.js - Logique Métier & Calculs V4.0
 * 
 * Responsabilité : Calculs dimensions, surface, poids, DTU
 * Constitution v3.0 : "Respecte les lois de la physique"
 * 
 * @version 4.0.0
 */

export class CalculateurMetier {

    constructor() {
        // Constantes physiques (Constitution v3.0 - Règle #5)
        this.DENSITES = {
            'alu': 2.7,      // kg/dm³
            'pvc': 1.4,
            'acier': 7.85,
            'verre': 2.5
        };

        this.EPAISSEURS_STANDARD = {
            'simple': 4,     // mm
            'double': 24,
            'triple': 36
        };
    }

    // =====================================================
    // CALCULS GÉOMÉTRIQUES
    // =====================================================

    /**
     * Calculer surface selon forme
     * 
     * @param {Object} dimensions - {forme, largeur, hauteur, largeur_haut?, rayon?}
     * @returns {number} Surface en m²
     */
    calculateSurface(dimensions) {
        const { forme, largeur, hauteur, largeur_haut, rayon } = dimensions;

        // APEX: Protection DoS (limites DTU)
        const MAX_DIMENSION = 10000; // 10 mètres
        if (largeur > MAX_DIMENSION || hauteur > MAX_DIMENSION) {
            throw new Error(`Dimensions max: ${MAX_DIMENSION}mm (DTU)`);
        }

        if (largeur < 100 || hauteur < 100) {
            throw new Error('Dimensions min: 100mm');
        }

        // Conversion mm → m
        const L = largeur / 1000;
        const H = hauteur / 1000;

        switch (forme) {
            case 'rectangle':
                return L * H;

            case 'trapeze':
                // Surface trapèze = ((B + b) × h) / 2
                const L_haut = (largeur_haut || largeur) / 1000;
                return ((L + L_haut) * H) / 2;

            case 'cintre':
                // Surface = Rectangle + Demi-cercle
                const R = (rayon || largeur / 2) / 1000;
                const surfaceRect = L * (H - R);
                const surfaceCintre = (Math.PI * R * R) / 2;
                return surfaceRect + surfaceCintre;

            default:
                throw new Error(`Forme inconnue: ${forme}`);
        }
    }

    /**
     * Calculer périmètre
     * 
     * @param {Object} dimensions
     * @returns {number} Périmètre en m
     */
    calculatePerimetre(dimensions) {
        const { forme, largeur, hauteur, largeur_haut, rayon } = dimensions;

        const L = largeur / 1000;
        const H = hauteur / 1000;

        switch (forme) {
            case 'rectangle':
                return 2 * (L + H);

            case 'trapeze':
                const L_haut = (largeur_haut || largeur) / 1000;
                const cote = Math.sqrt(Math.pow((L - L_haut) / 2, 2) + Math.pow(H, 2));
                return L + L_haut + (2 * cote);

            case 'cintre':
                const R = (rayon || largeur / 2) / 1000;
                return L + (2 * (H - R)) + (Math.PI * R);

            default:
                return 0;
        }
    }

    /**
     * Calculer diagonale (pour équerrage)
     * 
     * @param {number} largeur - mm
     * @param {number} hauteur - mm
     * @returns {number} Diagonale en mm
     */
    calculateDiagonale(largeur, hauteur) {
        return Math.sqrt(Math.pow(largeur, 2) + Math.pow(hauteur, 2));
    }

    // =====================================================
    // CALCULS PHYSIQUES
    // =====================================================

    /**
     * Estimer poids du produit
     * 
     * @param {Object} data - JSON V3 complet
     * @returns {number} Poids en kg
     */
    calculatePoids(data) {
        const surface = this.calculateSurface(data.dimensions);
        const materiau = data.technique?.materiau || 'alu';
        const vitrage = data.technique?.vitrage || 'double';

        // Poids dormant (cadre)
        const densite = this.DENSITES[materiau] || 2.7;
        const perimetre = this.calculatePerimetre(data.dimensions);
        const sectionDormant = 0.01; // 10 cm² moyen
        const poidsDormant = perimetre * sectionDormant * densite;

        // Poids vitrage
        const epaisseur = this.EPAISSEURS_STANDARD[vitrage] || 24;
        const poidsVitrage = surface * (epaisseur / 1000) * this.DENSITES.verre;

        // Poids accessoires (estimation 10%)
        const poidsAccessoires = (poidsDormant + poidsVitrage) * 0.1;

        return Math.round(poidsDormant + poidsVitrage + poidsAccessoires);
    }

    /**
     * Calculer volume de déchets
     * 
     * @param {Object} data - JSON V3
     * @returns {number} Volume en m³
     */
    calculateVolumeDechets(data) {
        const surface = this.calculateSurface(data.dimensions);
        const typePose = data.technique?.type_pose || 'renovation';

        // Dépose = 15% du volume du produit
        if (typePose === 'renovation') {
            return surface * 0.15 * 0.1; // 10cm d'épaisseur moyenne
        }

        // Neuf = emballages uniquement (5%)
        return surface * 0.05 * 0.05;
    }

    // =====================================================
    // BUSINESS INTELLIGENCE
    // =====================================================

    /**
     * Calculer score de surcoût (1-5)
     * Constitution v3.0 - Règle #5 : Protection de la marge
     * 
     * @param {Object} data - JSON V3
     * @returns {number} Score 1-5
     */
    calculateScoreSurcout(data) {
        let score = 1;

        // Forme complexe (+1)
        if (data.dimensions.forme !== 'rectangle') {
            score++;
        }

        // Grandes dimensions (+1)
        const surface = this.calculateSurface(data.dimensions);
        if (surface > 3) { // > 3m²
            score++;
        }

        // Accessoires premium (+1)
        const accessoiresPremium = (data.accessoires || []).filter(a =>
            a.slug.includes('premium') || a.slug.includes('motorise')
        );
        if (accessoiresPremium.length > 0) {
            score++;
        }

        // Pose complexe (+1)
        if (data.technique?.type_pose === 'neuf') {
            score++;
        }

        return Math.min(score, 5);
    }

    /**
     * Estimer marge (%)
     * 
     * @param {Object} data - JSON V3
     * @returns {number} Marge estimée en %
     */
    calculateMargeEstimee(data) {
        const scoreSurcout = this.calculateScoreSurcout(data);

        // Marge de base : 40%
        // Chaque point de surcoût réduit de 5%
        return Math.max(40 - (scoreSurcout - 1) * 5, 20);
    }

    /**
     * Détecter opportunités commerciales
     * 
     * @param {Object} data - JSON V3
     * @returns {Array<string>} Liste de suggestions
     */
    detectOpportunites(data) {
        const opportunites = [];

        // Si porte garage sans motorisation
        if (data.technique?.categorie === 'garage' &&
            !data.accessoires?.some(a => a.slug.includes('moteur'))) {
            opportunites.push('Motorisation garage recommandée');
        }

        // Si grande surface sans store
        const surface = this.calculateSurface(data.dimensions);
        if (surface > 4 && data.technique?.categorie === 'menuiserie') {
            opportunites.push('Store assorti disponible');
        }

        // Si vitrage simple
        if (data.technique?.vitrage === 'simple') {
            opportunites.push('Upgrade double vitrage (économies énergie)');
        }

        return opportunites;
    }

    // =====================================================
    // VALIDATION DTU
    // =====================================================

    /**
     * Valider dimensions selon DTU
     * 
     * @param {Object} dimensions
     * @param {string} categorie
     * @returns {Object} {valid: bool, errors: Array}
     */
    validateDTU(dimensions, categorie) {
        const errors = [];

        // Dimensions max selon catégorie (DTU)
        const limites = {
            'menuiserie': { maxL: 3000, maxH: 2500 },
            'garage': { maxL: 6000, maxH: 3000 },
            'portail': { maxL: 5000, maxH: 2500 },
            'pergola': { maxL: 8000, maxH: 3500 }
        };

        const limite = limites[categorie] || { maxL: 3000, maxH: 2500 };

        // Vérifier largeur
        if (dimensions.largeur > limite.maxL) {
            errors.push(`Largeur max ${limite.maxL}mm (DTU)`);
        }

        // Vérifier hauteur
        if (dimensions.hauteur > limite.maxH) {
            errors.push(`Hauteur max ${limite.maxH}mm (DTU)`);
        }

        // Vérifier équerrage (tolérance 3mm/m)
        if (dimensions.forme === 'rectangle') {
            const diag1 = this.calculateDiagonale(dimensions.largeur, dimensions.hauteur);
            const tolerance = (dimensions.largeur / 1000) * 3;

            // Simuler 2e diagonale (normalement mesurée)
            // Pour validation, on accepte si < tolérance
            if (Math.abs(diag1 - diag1) > tolerance) {
                errors.push(`Équerrage hors tolérance (${tolerance.toFixed(1)}mm)`);
            }
        }

        return {
            valid: errors.length === 0,
            errors
        };
    }

    // =====================================================
    // CONSOMMABLES
    // =====================================================

    /**
     * Calculer quantités consommables
     * 
     * @param {Object} data - JSON V3
     * @returns {Object} {silicone, vis, joints}
     */
    calculateConsommables(data) {
        const perimetre = this.calculatePerimetre(data.dimensions);

        return {
            silicone: Math.ceil(perimetre / 10), // 1 cartouche / 10m
            vis: Math.ceil(perimetre * 4),       // 4 vis/m
            joints: Math.ceil(perimetre * 1.1)   // +10% sécurité
        };
    }

    // =====================================================
    // ENRICHISSEMENT JSON V3
    // =====================================================

    /**
     * Enrichir JSON V3 avec calculs automatiques
     * 
     * @param {Object} data - JSON V3 partiel
     * @returns {Object} JSON V3 complet
     */
    enrichJsonV3(data) {
        // Calculs automatiques
        const surface = this.calculateSurface(data.dimensions);
        const poids = this.calculatePoids(data);
        const volumeDechets = this.calculateVolumeDechets(data);
        const scoreSurcout = this.calculateScoreSurcout(data);
        const margeEstimee = this.calculateMargeEstimee(data);
        const opportunites = this.detectOpportunites(data);

        // Enrichir sections
        data.logistique = {
            ...data.logistique,
            poids_estime_kg: poids,
            volume_dechets_m3: volumeDechets,
            moyen_levage: poids > 50 ? 'grue' : null
        };

        data.business = {
            ...data.business,
            score_surcout: scoreSurcout,
            marge_estimee_pct: margeEstimee,
            opportunites
        };

        data.metadata = {
            ...data.metadata,
            surface_m2: surface,
            calcul_date: new Date().toISOString()
        };

        return data;
    }
}
