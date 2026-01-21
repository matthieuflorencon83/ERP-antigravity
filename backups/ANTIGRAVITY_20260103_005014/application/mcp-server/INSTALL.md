# Antigravity Synapse - MCP Server Installation Guide

## 📦 ÉTAPE 1 : SCAFFOLDING

**Commandes PowerShell (exécuter dans l'ordre) :**

```powershell
# 1. Créer le dossier projet
cd C:\laragon\www
mkdir antigravity-mcp
cd antigravity-mcp

# 2. Initialiser Node.js
npm init -y

# 3. Installer dépendances TypeScript + MCP SDK
npm install @modelcontextprotocol/sdk mysql2 zod dotenv
npm install -D typescript @types/node tsx

# 4. Créer structure dossiers
mkdir src
New-Item .env.example -ItemType File
New-Item .gitignore -ItemType File
```

## 🔧 CONFIGURATION TYPESCRIPT

**Fichier : `tsconfig.json`**

```json
{
  "compilerOptions": {
    "target": "ES2022",
    "module": "Node16",
    "moduleResolution": "Node16",
    "outDir": "./dist",
    "rootDir": "./src",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "resolveJsonModule": true,
    "declaration": true,
    "declarationMap": true,
    "sourceMap": true
  },
  "include": ["src/**/*"],
  "exclude": ["node_modules", "dist"]
}
```

## 📝 GITIGNORE

**Fichier : `.gitignore`**

```
node_modules/
dist/
.env
*.log
.DS_Store
```

---

**✅ Après avoir exécuté ces commandes, passez à l'ÉTAPE 2.**
