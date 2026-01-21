<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Style Guide - Antigravity System</title>
    
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* =========================================
           1. IDENTITÉ VISUELLE & VARIABLES
           ========================================= */
        :root {
            /* Branding Core - Antigravity Blue */
            --ag-primary: #0f4c75; 
            --ag-primary-hover: #0a3554;
            --ag-accent: #3282b8;
            
            /* Ergonomics */
            --touch-target-min: 44px;
            --font-base: 16px;
            --border-radius-card: 12px;
            --border-radius-btn: 8px;
        }

        /* Override Bootstrap Primary variable safely */
        [data-bs-theme="light"], [data-bs-theme="dark"] {
            --bs-primary: var(--ag-primary);
            --bs-primary-rgb: 15, 76, 117;
            --bs-body-font-family: 'Inter', sans-serif;
            --bs-body-font-size: var(--font-base);
        }

        body {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            -webkit-font-smoothing: antialiased;
        }

        /* =========================================
           2. ERGONOMIE MOBILE (Priority #1)
           ========================================= */
        
        /* Zones de touche minimales & Boutons */
        .btn {
            min-height: var(--touch-target-min);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            border-radius: var(--border-radius-btn);
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
        }

        .btn-icon {
            width: var(--touch-target-min);
            padding: 0;
        }

        /* Inputs plus confortables */
        .form-control, .form-select {
            min-height: var(--touch-target-min);
            border-radius: var(--border-radius-btn);
            font-size: 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--ag-accent);
            box-shadow: 0 0 0 0.25rem rgba(15, 76, 117, 0.25);
        }

        /* =========================================
           3. COMPOSANTS STANDARDS
           ========================================= */

        /* --- CARD STANDARD --- */
        /* Design "Clean" & Industriel : Bordure fine, ombre légère */
        .card-ag {
            border: 1px solid var(--bs-border-color-translucent);
            /* border-left: 4px solid var(--ag-primary); Style optionnel : Bordure latérale */
            border-radius: var(--border-radius-card);
            background-color: var(--bs-body-bg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }

        .card-ag:active {
            transform: scale(0.99); /* Feedback tactile */
        }

        /* Header de carte standardisé */
        .card-ag-header {
            padding: 1rem 1.25rem;
            background-color: var(--bs-tertiary-bg); /* Sémantique Bootstrap = Gris très clair ou très sombre */
            border-bottom: 1px solid var(--bs-border-color-translucent);
            font-weight: 600;
            color: var(--bs-heading-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-ag-body {
            padding: 1.25rem;
        }

        /* --- KPI CARD MINI --- */
        /* Version Dashboard simplifiée */
        .kpi-card {
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: var(--border-radius-card);
            padding: 1rem;
            background-color: var(--bs-body-bg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .kpi-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            background-color: rgba(15, 76, 117, 0.1); /* Primary tint */
            color: var(--ag-primary);
        }

        .kpi-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 0;
            color: var(--bs-body-color);
        }

        .kpi-label {
            font-size: 0.85rem;
            color: var(--bs-secondary-color);
            margin-bottom: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* --- TABLEAUX --- */
        /* Wrapper responsive obligatoire */
        .table-responsive-ag {
            border-radius: var(--border-radius-btn);
            border: 1px solid var(--bs-border-color-translucent);
            overflow: hidden; /* Pour les coins arrondis */
        }

        .table-ag {
            margin-bottom: 0;
            width: 100%;
            vertical-align: middle;
        }

        .table-ag th {
            background-color: var(--bs-tertiary-bg);
            color: var(--bs-secondary-color);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            padding: 1rem 0.75rem;
            border-bottom: 2px solid var(--bs-border-color-translucent);
        }

        .table-ag td {
            padding: 1rem 0.75rem; /* Espacement confortable pour le mobile */
            border-bottom: 1px solid var(--bs-border-color-subtle);
        }
        
        /* Badges ergonomiques */
        .badge-ag {
            padding: 0.5em 0.8em;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        /* =========================================
           4. UTILITAIRES DE DEMO
           ========================================= */
        .demo-section {
            padding: 2rem 0;
            border-bottom: 1px dashed var(--bs-border-color);
        }
        
        .section-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--ag-primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: block;
        }

        /* Theme Switcher Flottant */
        .theme-switch-wrapper {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 50px;
            padding: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
        }
        
        .theme-btn {
            border: none;
            background: transparent;
            color: var(--bs-secondary-color);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .theme-btn.active {
            background-color: var(--ag-primary);
            color: white;
        }

    </style>
</head>
<body>

    <!-- Theme Toggle Floating -->
    <div class="theme-switch-wrapper">
        <button class="theme-btn active" onclick="setTheme('light')" id="btn-light"><i class="fas fa-sun"></i></button>
        <button class="theme-btn" onclick="setTheme('dark')" id="btn-dark"><i class="fas fa-moon"></i></button>
    </div>

    <!-- NAVBAR SIMULATION -->
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#"><i class="fas fa-cube me-2"></i>ANTIGRAVITY</a>
            <span class="navbar-text text-white-50 small d-none d-sm-block">Design System v1.0</span>
        </div>
    </nav>

    <div class="container py-4">
        
        <!-- HEADER PAGE STANDARD -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Style Guide</h1>
                <p class="text-secondary mb-0">Maquette de référence UI/UX Mobile & Dark Mode</p>
            </div>
            <div>
                <button class="btn btn-outline-primary"><i class="fas fa-download me-2"></i>Exporter</button>
            </div>
        </div>

        <!-- SECTION 1: KPIS (DASHBOARD) -->
        <div class="demo-section">
            <span class="section-title">1. Cartes KPIs (Dashboard Standard)</span>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="kpi-card">
                        <div>
                            <p class="kpi-label">Chiffre d'Affaires</p>
                            <p class="kpi-value">124 500 €</p>
                        </div>
                        <div class="kpi-icon-wrapper text-primary bg-primary-subtle">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kpi-card">
                        <div>
                            <p class="kpi-label">Commandes en cours</p>
                            <p class="kpi-value">12</p>
                        </div>
                        <div class="kpi-icon-wrapper text-warning bg-warning-subtle">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kpi-card">
                        <div>
                            <p class="kpi-label">Retards Livraison</p>
                            <p class="kpi-value text-danger">3</p>
                        </div>
                        <div class="kpi-icon-wrapper text-danger bg-danger-subtle">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 2: FORMULAIRES (MOBILE FIRST) -->
        <div class="demo-section">
            <span class="section-title">2. Formulaires Ergonomiques (Touch Zones > 44px)</span>
            <div class="card-ag">
                <div class="card-ag-header">
                    <span><i class="fas fa-edit me-2 text-primary"></i>Saisie Mouvement Stock</span>
                    <button class="btn btn-sm btn-link text-decoration-none p-0"><i class="fas fa-question-circle"></i></button>
                </div>
                <div class="card-ag-body">
                    <form>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Article ou Référence</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-tertiary"><i class="fas fa-barcode"></i></span>
                                    <input type="text" class="form-control" placeholder="Scanner ou taper ref...">
                                    <button class="btn btn-outline-secondary" type="button"><i class="fas fa-camera"></i></button>
                                </div>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Fournisseur</label>
                                <select class="form-select">
                                    <option selected>Sélectionner un fournisseur...</option>
                                    <option>Sepalumic</option>
                                    <option>Wurth</option>
                                    <option>Descours & Cabaud</option>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Quantité</label>
                                <input type="number" class="form-control" value="1">
                            </div>

                            <div class="col-6">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Date</label>
                                <input type="date" class="form-control">
                            </div>

                            <div class="col-12 mt-4">
                                <button type="button" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Enregistrer Mouvement
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SECTION 3: TABLEAUX (RESPONSIVE) -->
        <div class="demo-section border-bottom-0 pb-0">
            <span class="section-title">3. Tableaux de Données (Responsive)</span>
            
            <div class="table-responsive-ag shadow-sm">
                <table class="table table-hover table-ag mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Réf</th>
                            <th scope="col">Désignation</th>
                            <th scope="col">État</th>
                            <th scope="col" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold text-primary">CMD-2024-001</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-medium">Profilé Alu 40x40</span>
                                    <span class="small text-secondary">Sepalumic Distribution</span>
                                </div>
                            </td>
                            <td><span class="badge rounded-pill text-bg-warning badge-ag">En Attente</span></td>
                            <td class="text-end">
                                <button class="btn btn-outline-secondary btn-icon btn-sm"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-primary">CMD-2024-002</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-medium">Vis Inox A4</span>
                                    <span class="small text-secondary">Wurth France</span>
                                </div>
                            </td>
                            <td><span class="badge rounded-pill text-bg-primary badge-ag">Commandée</span></td>
                            <td class="text-end">
                                <button class="btn btn-outline-secondary btn-icon btn-sm"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-primary">CMD-2024-003</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-medium">Joint EPDM Noir</span>
                                    <span class="small text-secondary">Joints & Co</span>
                                </div>
                            </td>
                            <td><span class="badge rounded-pill text-bg-success badge-ag">Reçue</span></td>
                            <td class="text-end">
                                <button class="btn btn-outline-secondary btn-icon btn-sm"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 text-center">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled"><a class="page-link" href="#"><i class="fas fa-chevron-left"></i></a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#"><i class="fas fa-chevron-right"></i></a></li>
                    </ul>
                </nav>
            </div>
        </div>

    </div>

    <!-- Script Theme Switcher -->
    <script>
        function setTheme(theme) {
            document.documentElement.setAttribute('data-bs-theme', theme);
            
            // Persist preference (Optional for demo)
            localStorage.setItem('theme', theme);

            // Update buttons state
            document.getElementById('btn-light').classList.remove('active');
            document.getElementById('btn-dark').classList.remove('active');
            document.getElementById('btn-' + theme).classList.add('active');
        }

        // Check for saved theme preference or default to light
        const savedTheme = localStorage.getItem('theme') || 'light';
        setTheme(savedTheme);
    </script>
</body>
</html>
