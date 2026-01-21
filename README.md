# Antigravity ERP

**Mission**: Enterprise Resource Planning (ERP) System tailored for seamless business management.
**Version**: 2.0 (Refactored Modular Architecture)

## 🏗️ Architecture

The project has been refactored from a Monolith to a Modular Domain-Driven Design:

### Core Applications

- **`apps.core`**: Base System, Dashboard, Settings, Authentication.
- **`apps.tiers`**: Customer (Client) & Supplier (Fournisseur) Management.
- **`apps.catalogue`**: Product Catalog, Families, Pricing.
- **`apps.ventes`**: Sales Pipeline, Projects (Affaires), Needs (Besoins).
- **`apps.achats`**: Purchasing Pipeline, Orders (Commandes), Supplier Relations.
- **`apps.ged`**: Document Management System (AI Analysis, Uploads).

### Key Features

- **Dashboard**: Real-time sales & purchasing metrics.
- **HTMX**: Reactive frontend without full SPA complexity.
- **AI Integration**: Document parsing (PDF/Image) for automatic data entry.

## 🚀 Installation

1. **Clone & Setup**:

   ```bash
   git clone ...
   cd ERP
   python -m venv venv
   source venv/bin/activate  # or venv\Scripts\activate on Windows
   pip install -r requirements.txt
   ```

2. **Environment**:
   Create a `.env` file based on `.env.example`:

   ```ini
   DEBUG=True
   SECRET_KEY=your_secret_key
   DB_NAME=antigravity_db
   DB_USER=postgres
   DB_PASSWORD=your_password
   ```

3. **Run**:

   ```bash
   python manage.py migrate
   python manage.py runserver
   ```

## 🛠️ Maintenance

- **Scripts**: Maintenance scripts (Audit, Fixes) are located in the `maintenance/` directory.
- **Tests**: Run `python manage.py test` to verify integrity.

## 📜 History

- **Phase 1-5**: 360° Audit & Critical Security Fixes.
- **Phase 6-8**: Modular Refactoring (Split "God App").
- **Phase 9**: Dashboard Analytics Implementation.

---
*Built with Django 6 & HTMX.*
