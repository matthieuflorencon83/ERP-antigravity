#!/usr/bin/env node

/**
 * Antigravity Synapse - MCP Server
 * 
 * Serveur MCP sécurisé pour accès BDD/Code/Logs
 * Protocole APEX : Utilisateur SQL restreint, Validation Zod, Protection Path Traversal
 * 
 * @version 1.0.0
 * @author Antigravity APEX v6.0
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
    CallToolRequestSchema,
    ListToolsRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';
import mysql from 'mysql2/promise';
import { z } from 'zod';
import * as dotenv from 'dotenv';
import * as fs from 'fs/promises';
import * as path from 'path';
import { simpleGit } from 'simple-git';
import puppeteer from 'puppeteer';

// Charger variables d'environnement
dotenv.config();

// ============================================
// CONFIGURATION & VALIDATION
// ============================================

const ConfigSchema = z.object({
    DB_HOST: z.string().default('localhost'),
    DB_PORT: z.string().regex(/^\d+$/).transform(Number).default('3306'),
    DB_USER: z.string(),
    DB_PASSWORD: z.string(),
    DB_NAME: z.string(),
    PROJECT_ROOT: z.string(),
    LOG_PATH: z.string().optional(),
    OPENWEATHER_API_KEY: z.string().optional(),
});

const config = ConfigSchema.parse({
    DB_HOST: process.env.DB_HOST,
    DB_PORT: process.env.DB_PORT,
    DB_USER: process.env.DB_USER,
    DB_PASSWORD: process.env.DB_PASSWORD,
    DB_NAME: process.env.DB_NAME,
    PROJECT_ROOT: process.env.PROJECT_ROOT,
    LOG_PATH: process.env.LOG_PATH,
    OPENWEATHER_API_KEY: process.env.OPENWEATHER_API_KEY,
});

// ============================================
// CONNEXION BASE DE DONNÉES
// ============================================

let dbPool: mysql.Pool;

async function initDatabase() {
    dbPool = mysql.createPool({
        host: config.DB_HOST,
        port: config.DB_PORT,
        user: config.DB_USER,
        password: config.DB_PASSWORD,
        database: config.DB_NAME,
        waitForConnections: true,
        connectionLimit: 10,
        queueLimit: 0,
    });

    // Test connexion
    try {
        const connection = await dbPool.getConnection();
        console.error('✅ Database connected');
        connection.release();
    } catch (error) {
        console.error('❌ Database connection failed:', error);
        throw error;
    }
}

// ============================================
// SÉCURITÉ : PATH VALIDATION
// ============================================

/**
 * Valide qu'un chemin est dans le projet et ne contient pas de traversal
 */
function validatePath(filepath: string): string {
    // Construire chemin absolu
    const fullPath = path.join(config.PROJECT_ROOT, filepath);

    // Résoudre le chemin (élimine .., ., etc.)
    const resolvedPath = path.resolve(fullPath);
    const resolvedRoot = path.resolve(config.PROJECT_ROOT);

    // Vérifier que le chemin résolu est dans PROJECT_ROOT
    if (!resolvedPath.startsWith(resolvedRoot)) {
        throw new Error('Path traversal detected: path outside project root');
    }

    return resolvedPath;
}

/**
 * Valide qu'une URL est localhost uniquement
 */
function validateLocalUrl(url: string): void {
    if (!url.startsWith('http://localhost') && !url.startsWith('http://127.0.0.1')) {
        throw new Error('Only localhost URLs are allowed for security reasons');
    }
}

// ============================================
// SCHÉMAS DE VALIDATION ZOD
// ============================================

const QueryDatabaseSchemaSchema = z.object({});

const ExecuteSafeSQLSchema = z.object({
    query: z.string()
        .min(1, 'Query cannot be empty')
        .refine(
            (q) => q.trim().toUpperCase().startsWith('SELECT'),
            'Query must start with SELECT'
        )
        .refine(
            (q) => !/\b(INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE)\b/i.test(q),
            'Query contains forbidden keywords'
        ),
});

const ReadCodeFileSchema = z.object({
    filepath: z.string()
        .min(1, 'Filepath cannot be empty')
        .refine(
            (p) => !p.includes('..'),
            'Path traversal detected (.. not allowed)'
        )
        .refine(
            (p) => /\.(php|js|json|css|sql|md|txt)$/i.test(p),
            'File extension not allowed (must be .php, .js, .json, .css, .sql, .md, .txt)'
        ),
});

const AnalyzeErrorLogsSchema = z.object({
    lines: z.number().int().min(1).max(500).default(50),
});

// Nouveaux schémas pour write access
const WriteFileSchema = z.object({
    filepath: z.string().min(1, 'Filepath cannot be empty'),
    content: z.string(),
    createDirs: z.boolean().default(true),
});

const CreateDirectorySchema = z.object({
    dirpath: z.string().min(1, 'Directory path cannot be empty'),
});

const ListFilesSchema = z.object({
    directory: z.string().default('.'),
    recursive: z.boolean().default(false),
});

const RunGitCommandSchema = z.object({
    command: z.enum(['status', 'add', 'commit', 'branch', 'checkout']),
    message: z.string().optional(),
    branchName: z.string().optional(),
});

const TakeScreenshotSchema = z.object({
    url: z.string().url('Invalid URL format'),
    device: z.enum(['mobile', 'tablet', 'desktop']).default('desktop'),
});

const AuditAccessibilitySchema = z.object({
    url: z.string().url('Invalid URL format'),
});

const CheckChantierWeatherSchema = z.object({
    latitude: z.number().min(-90).max(90),
    longitude: z.number().min(-180).max(180),
    date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
});

const EstimateInterventionDurationSchema = z.object({
    surface_m2: z.number().min(1).max(10000),
    difficulty_score: z.number().int().min(1).max(5),
    nb_installers: z.number().int().min(1).max(20),
});

// ============================================
// TOOL A : QUERY DATABASE SCHEMA
// ============================================

async function queryDatabaseSchema(): Promise<any> {
    const connection = await dbPool.getConnection();

    try {
        // 1. Lister toutes les tables
        const [tables] = await connection.query<mysql.RowDataPacket[]>(
            'SHOW TABLES'
        );

        const schema: Record<string, any> = {};

        // 2. Pour chaque table, récupérer la structure
        for (const tableRow of tables) {
            const tableName = Object.values(tableRow)[0] as string;

            const [columns] = await connection.query<mysql.RowDataPacket[]>(
                `DESCRIBE ${tableName}`
            );

            schema[tableName] = columns.map((col) => ({
                field: col.Field,
                type: col.Type,
                null: col.Null,
                key: col.Key,
                default: col.Default,
                extra: col.Extra,
            }));
        }

        return {
            database: config.DB_NAME,
            tables: Object.keys(schema),
            schema,
        };
    } finally {
        connection.release();
    }
}

// ============================================
// TOOL B : EXECUTE SAFE SQL
// ============================================

async function executeSafeSQL(query: string): Promise<any> {
    const connection = await dbPool.getConnection();

    try {
        const [rows] = await connection.query(query);

        return {
            success: true,
            rowCount: Array.isArray(rows) ? rows.length : 0,
            data: rows,
        };
    } catch (error: any) {
        return {
            success: false,
            error: error.message,
        };
    } finally {
        connection.release();
    }
}

// ============================================
// TOOL C : READ CODE FILE
// ============================================

async function readCodeFile(filepath: string): Promise<any> {
    // Construire chemin absolu
    const fullPath = path.join(config.PROJECT_ROOT, filepath);

    // Vérifier que le chemin résolu est bien dans PROJECT_ROOT
    const resolvedPath = path.resolve(fullPath);
    const resolvedRoot = path.resolve(config.PROJECT_ROOT);

    if (!resolvedPath.startsWith(resolvedRoot)) {
        throw new Error('Path traversal detected (resolved path outside project root)');
    }

    try {
        const content = await fs.readFile(resolvedPath, 'utf-8');

        return {
            filepath,
            fullPath: resolvedPath,
            size: content.length,
            content,
        };
    } catch (error: any) {
        if (error.code === 'ENOENT') {
            throw new Error(`File not found: ${filepath}`);
        }
        throw error;
    }
}

// ============================================
// TOOL D : ANALYZE ERROR LOGS
// ============================================

async function analyzeErrorLogs(lines: number): Promise<any> {
    if (!config.LOG_PATH) {
        throw new Error('LOG_PATH not configured in .env');
    }

    try {
        const content = await fs.readFile(config.LOG_PATH, 'utf-8');
        const allLines = content.split('\n').filter(l => l.trim());
        const lastLines = allLines.slice(-lines);

        return {
            logPath: config.LOG_PATH,
            totalLines: allLines.length,
            requestedLines: lines,
            returnedLines: lastLines.length,
            logs: lastLines,
        };
    } catch (error: any) {
        if (error.code === 'ENOENT') {
            throw new Error(`Log file not found: ${config.LOG_PATH}`);
        }
        throw error;
    }
}

// ============================================
// TOOL E : WRITE FILE
// ============================================

async function writeFile(filepath: string, content: string, createDirs: boolean): Promise<any> {
    // Valider le chemin
    const fullPath = validatePath(filepath);

    // Vérifier l'extension
    const ext = path.extname(fullPath).toLowerCase();
    const allowedExts = ['.php', '.js', '.json', '.css', '.sql', '.md', '.txt', '.html'];

    if (!allowedExts.includes(ext)) {
        throw new Error(`File extension not allowed: ${ext}. Allowed: ${allowedExts.join(', ')}`);
    }

    // Créer dossiers parents si nécessaire
    if (createDirs) {
        const dir = path.dirname(fullPath);
        await fs.mkdir(dir, { recursive: true });
    }

    // Écrire le fichier
    await fs.writeFile(fullPath, content, 'utf-8');

    return {
        success: true,
        filepath,
        fullPath,
        size: content.length,
    };
}

// ============================================
// TOOL F : CREATE DIRECTORY
// ============================================

async function createDirectory(dirpath: string): Promise<any> {
    // Valider le chemin
    const fullPath = validatePath(dirpath);

    // Créer le dossier (récursif)
    await fs.mkdir(fullPath, { recursive: true });

    return {
        success: true,
        dirpath,
        fullPath,
    };
}

// ============================================
// TOOL G : LIST FILES
// ============================================

async function listFiles(directory: string, recursive: boolean): Promise<any> {
    // Valider le chemin
    const fullPath = validatePath(directory);

    // Vérifier que c'est un dossier
    const stats = await fs.stat(fullPath);
    if (!stats.isDirectory()) {
        throw new Error('Path is not a directory');
    }

    const files: any[] = [];

    async function scanDir(dir: string, relativePath: string = '') {
        const entries = await fs.readdir(dir, { withFileTypes: true });

        for (const entry of entries) {
            const entryPath = path.join(relativePath, entry.name);
            const fullEntryPath = path.join(dir, entry.name);

            const stat = await fs.stat(fullEntryPath);

            files.push({
                name: entry.name,
                path: entryPath || entry.name,
                type: entry.isDirectory() ? 'directory' : 'file',
                size: entry.isFile() ? stat.size : undefined,
            });

            if (recursive && entry.isDirectory()) {
                await scanDir(fullEntryPath, entryPath);
            }
        }
    }

    await scanDir(fullPath);

    return {
        directory,
        fullPath,
        fileCount: files.filter(f => f.type === 'file').length,
        dirCount: files.filter(f => f.type === 'directory').length,
        files,
    };
}

// ============================================
// TOOL I : TAKE SCREENSHOT
// ============================================

async function takeScreenshot(url: string, device: string): Promise<any> {
    validateLocalUrl(url);

    const viewports = {
        mobile: { width: 375, height: 667, isMobile: true, hasTouch: true },
        tablet: { width: 768, height: 1024, isMobile: true, hasTouch: true },
        desktop: { width: 1920, height: 1080, isMobile: false, hasTouch: false },
    };

    const viewport = viewports[device as keyof typeof viewports];

    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    try {
        const page = await browser.newPage();
        await page.setViewport(viewport);

        if (device === 'mobile') {
            await page.setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15');
        } else if (device === 'tablet') {
            await page.setUserAgent('Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15');
        }

        await page.goto(url, {
            waitUntil: 'networkidle0',
            timeout: 30000,
        });

        const screenshot = await page.screenshot({
            type: 'png',
            fullPage: false,
        });

        const base64 = Buffer.from(screenshot).toString('base64');

        return {
            success: true,
            url,
            device,
            viewport: { width: viewport.width, height: viewport.height },
            screenshotBase64: base64,
            size: screenshot.length,
        };
    } finally {
        await browser.close();
    }
}

// ============================================
// TOOL J : AUDIT ACCESSIBILITY
// ============================================

async function auditAccessibility(url: string): Promise<any> {
    validateLocalUrl(url);

    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    try {
        const page = await browser.newPage();

        await page.goto(url, {
            waitUntil: 'networkidle0',
            timeout: 30000,
        });

        const snapshot = await page.accessibility.snapshot();
        const issues: any[] = [];

        function analyzeNode(node: any, path: string = '') {
            if (!node) return;

            const currentPath = path ? `${path} > ${node.role}` : node.role;

            if (['button', 'link', 'textbox'].includes(node.role) && !node.name) {
                issues.push({
                    type: 'missing-label',
                    role: node.role,
                    path: currentPath,
                    severity: 'warning',
                });
            }

            if (node.children) {
                node.children.forEach((child: any) => analyzeNode(child, currentPath));
            }
        }

        if (snapshot) {
            analyzeNode(snapshot);
        }

        const score = Math.max(0, 100 - issues.length * 5);

        return {
            success: true,
            url,
            issuesCount: issues.length,
            issues,
            score,
            accessibilityTree: snapshot,
        };
    } finally {
        await browser.close();
    }
}

// ============================================
// TOOL K : CHECK CHANTIER WEATHER
// ============================================

async function checkChantierWeather(latitude: number, longitude: number, date: string): Promise<any> {
    if (!config.OPENWEATHER_API_KEY) {
        throw new Error('OPENWEATHER_API_KEY not configured in .env');
    }

    const url = `https://api.openweathermap.org/data/2.5/forecast?lat=${latitude}&lon=${longitude}&appid=${config.OPENWEATHER_API_KEY}&units=metric`;

    const response = await fetch(url);
    if (!response.ok) {
        throw new Error(`OpenWeather API error: ${response.statusText}`);
    }

    const data: any = await response.json();

    const targetDate = new Date(date);
    const forecasts = data.list.filter((item: any) => {
        const forecastDate = new Date(item.dt * 1000);
        return forecastDate.toDateString() === targetDate.toDateString();
    });

    if (forecasts.length === 0) {
        throw new Error('No forecast data available for this date');
    }

    let hasRisk = false;
    const risks: string[] = [];

    for (const forecast of forecasts) {
        const weatherCode = forecast.weather[0].id;
        const windSpeed = forecast.wind.speed * 3.6;

        if (weatherCode >= 500 && weatherCode <= 531) {
            hasRisk = true;
            risks.push('Pluie');
        }

        if (weatherCode >= 600 && weatherCode <= 622) {
            hasRisk = true;
            risks.push('Neige');
        }

        if (weatherCode >= 200 && weatherCode <= 232) {
            hasRisk = true;
            risks.push('Orage');
        }

        if (windSpeed > 50) {
            hasRisk = true;
            risks.push(`Vent violent (${Math.round(windSpeed)} km/h)`);
        }
    }

    return {
        success: true,
        latitude,
        longitude,
        date,
        status: hasRisk ? 'CHANTIER À RISQUE' : 'Conditions OK',
        risks: [...new Set(risks)],
        forecasts: forecasts.map((f: any) => ({
            time: new Date(f.dt * 1000).toISOString(),
            weather: f.weather[0].description,
            temp: f.main.temp,
            windSpeed: Math.round(f.wind.speed * 3.6),
        })),
    };
}

// ============================================
// TOOL L : ESTIMATE INTERVENTION DURATION
// ============================================

async function estimateInterventionDuration(surface_m2: number, difficulty_score: number, nb_installers: number): Promise<any> {
    const baseDuration = surface_m2 * 1.5;
    const difficultyMultiplier = difficulty_score / 2;
    const totalHours = (baseDuration * difficultyMultiplier) / nb_installers;

    const roundedHours = Math.round(totalHours * 100) / 100;
    const days = Math.ceil(roundedHours / 8);

    return {
        success: true,
        inputs: {
            surface_m2,
            difficulty_score,
            nb_installers,
        },
        estimation: {
            hours: roundedHours,
            days,
        },
        breakdown: {
            baseDuration: Math.round(baseDuration * 100) / 100,
            difficultyMultiplier,
            perInstaller: Math.round((totalHours / nb_installers) * 100) / 100,
        },
    };
}

// ============================================
// TOOL H : RUN GIT COMMAND
// ============================================

async function runGitCommand(command: string, message?: string, branchName?: string): Promise<any> {
    const git = simpleGit(config.PROJECT_ROOT);

    let result: any;

    switch (command) {
        case 'status':
            result = await git.status();
            break;

        case 'add':
            result = await git.add('.');
            break;

        case 'commit':
            if (!message) {
                throw new Error('Commit message required');
            }
            result = await git.commit(message);
            break;

        case 'branch':
            result = await git.branch();
            break;

        case 'checkout':
            if (!branchName) {
                throw new Error('Branch name required for checkout');
            }
            result = await git.checkoutLocalBranch(branchName);
            break;

        default:
            throw new Error(`Unknown git command: ${command}`);
    }

    return {
        success: true,
        command,
        result,
    };
}

// ============================================
// SERVEUR MCP
// ============================================

const server = new Server(
    {
        name: 'antigravity-synapse',
        version: '1.0.0',
    },
    {
        capabilities: {
            tools: {},
        },
    }
);

// Liste des outils disponibles
server.setRequestHandler(ListToolsRequestSchema, async () => {
    return {
        tools: [
            {
                name: 'query_database_schema',
                description: 'Récupère la structure complète de la base de données (tables et colonnes). Aucun paramètre requis.',
                inputSchema: {
                    type: 'object',
                    properties: {},
                },
            },
            {
                name: 'execute_safe_sql',
                description: 'Exécute une requête SQL SELECT (lecture seule). Les requêtes INSERT/UPDATE/DELETE/DROP sont interdites.',
                inputSchema: {
                    type: 'object',
                    properties: {
                        query: {
                            type: 'string',
                            description: 'Requête SQL SELECT à exécuter',
                        },
                    },
                    required: ['query'],
                },
            },
            {
                name: 'read_code_file',
                description: 'Lit le contenu d\'un fichier de code (PHP, JS, JSON, CSS, SQL). Le chemin doit être relatif à la racine du projet.',
                inputSchema: {
                    type: 'object',
                    properties: {
                        filepath: {
                            type: 'string',
                            description: 'Chemin relatif du fichier (ex: "db.php" ou "assets/js/main.js")',
                        },
                    },
                    required: ['filepath'],
                },
            },
            {
                name: 'analyze_error_logs',
                description: 'Analyse les dernières lignes du fichier de log PHP pour diagnostiquer les erreurs.',
                inputSchema: {
                    type: 'object',
                    properties: {
                        lines: {
                            type: 'number',
                            description: 'Nombre de lignes à récupérer (défaut: 50, max: 500)',
                            default: 50,
                        },
                    },
                },
            },
            {
                name: 'write_file',
                description: 'Crée ou modifie un fichier. Extensions autorisées : .php, .js, .json, .css, .sql, .md, .txt, .html',
                inputSchema: {
                    type: 'object',
                    properties: {
                        filepath: {
                            type: 'string',
                            description: 'Chemin relatif du fichier (ex: "api/test.php")',
                        },
                        content: {
                            type: 'string',
                            description: 'Contenu du fichier',
                        },
                        createDirs: {
                            type: 'boolean',
                            description: 'Créer les dossiers parents si nécessaire (défaut: true)',
                            default: true,
                        },
                    },
                    required: ['filepath', 'content'],
                },
            },
            {
                name: 'create_directory',
                description: 'Crée un dossier (récursif)',
                inputSchema: {
                    type: 'object',
                    properties: {
                        dirpath: {
                            type: 'string',
                            description: 'Chemin relatif du dossier (ex: "api/v2")',
                        },
                    },
                    required: ['dirpath'],
                },
            },
            {
                name: 'list_files',
                description: 'Liste les fichiers et dossiers d\'un répertoire',
                inputSchema: {
                    type: 'object',
                    properties: {
                        directory: {
                            type: 'string',
                            description: 'Chemin relatif du dossier (défaut: ".")',
                            default: '.',
                        },
                        recursive: {
                            type: 'boolean',
                            description: 'Parcourir les sous-dossiers (défaut: false)',
                            default: false,
                        },
                    },
                },
            },
            {
                name: 'run_git_command',
                description: 'Exécute une commande Git sécurisée (status, add, commit, branch, checkout)',
                inputSchema: {
                    type: 'object',
                    properties: {
                        command: {
                            type: 'string',
                            enum: ['status', 'add', 'commit', 'branch', 'checkout'],
                            description: 'Commande Git à exécuter',
                        },
                        message: {
                            type: 'string',
                            description: 'Message de commit (requis si command=commit)',
                        },
                        branchName: {
                            type: 'string',
                            description: 'Nom de la branche (requis si command=checkout)',
                        },
                    },
                    required: ['command'],
                },
            },
            {
                name: 'take_screenshot',
                description: 'Prend une capture d\'écran responsive de l\'application (mobile/tablet/desktop)',
                inputSchema: {
                    type: 'object',
                    properties: {
                        url: {
                            type: 'string',
                            description: 'URL localhost à capturer (ex: "http://localhost/antigravity/dashboard.php")',
                        },
                        device: {
                            type: 'string',
                            enum: ['mobile', 'tablet', 'desktop'],
                            description: 'Type d\'appareil (défaut: desktop)',
                            default: 'desktop',
                        },
                    },
                    required: ['url'],
                },
            },
            {
                name: 'audit_accessibility',
                description: 'Audite l\'accessibilité d\'une page (labels, contrastes, rôles ARIA)',
                inputSchema: {
                    type: 'object',
                    properties: {
                        url: {
                            type: 'string',
                            description: 'URL localhost à auditer',
                        },
                    },
                    required: ['url'],
                },
            },
            {
                name: 'check_chantier_weather',
                description: 'Vérifie la météo d\'un chantier et détecte les risques (pluie, neige, vent)',
                inputSchema: {
                    type: 'object',
                    properties: {
                        latitude: {
                            type: 'number',
                            description: 'Latitude du chantier',
                        },
                        longitude: {
                            type: 'number',
                            description: 'Longitude du chantier',
                        },
                        date: {
                            type: 'string',
                            description: 'Date de l\'intervention (YYYY-MM-DD)',
                        },
                    },
                    required: ['latitude', 'longitude', 'date'],
                },
            },
            {
                name: 'estimate_intervention_duration',
                description: 'Estime la durée d\'une intervention en fonction de la surface, difficulté et nombre d\'installateurs',
                inputSchema: {
                    type: 'object',
                    properties: {
                        surface_m2: {
                            type: 'number',
                            description: 'Surface en m²',
                        },
                        difficulty_score: {
                            type: 'number',
                            description: 'Score de difficulté (1=facile, 5=très difficile)',
                        },
                        nb_installers: {
                            type: 'number',
                            description: 'Nombre d\'installateurs',
                        },
                    },
                    required: ['surface_m2', 'difficulty_score', 'nb_installers'],
                },
            },
        ],
    };
});

// Gestionnaire d'appels d'outils
server.setRequestHandler(CallToolRequestSchema, async (request) => {
    try {
        switch (request.params.name) {
            case 'query_database_schema': {
                QueryDatabaseSchemaSchema.parse(request.params.arguments);
                const result = await queryDatabaseSchema();
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            case 'execute_safe_sql': {
                const args = ExecuteSafeSQLSchema.parse(request.params.arguments);
                const result = await executeSafeSQL(args.query);
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            case 'read_code_file': {
                const args = ReadCodeFileSchema.parse(request.params.arguments);
                const result = await readCodeFile(args.filepath);
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            case 'analyze_error_logs': {
                const args = AnalyzeErrorLogsSchema.parse(request.params.arguments);
                const result = await analyzeErrorLogs(args.lines);
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            case 'write_file': {
                const args = WriteFileSchema.parse(request.params.arguments);
                const result = await writeFile(args.filepath, args.content, args.createDirs);
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            case 'create_directory': {
                const args = CreateDirectorySchema.parse(request.params.arguments);
                const result = await createDirectory(args.dirpath);
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            case 'list_files': {
                const args = ListFilesSchema.parse(request.params.arguments);
                const result = await listFiles(args.directory, args.recursive);
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            case 'run_git_command': {
                const args = RunGitCommandSchema.parse(request.params.arguments);
                const result = await runGitCommand(args.command, args.message, args.branchName);
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            case 'take_screenshot': {
                const args = TakeScreenshotSchema.parse(request.params.arguments);
                const result = await takeScreenshot(args.url, args.device);
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            case 'audit_accessibility': {
                const args = AuditAccessibilitySchema.parse(request.params.arguments);
                const result = await auditAccessibility(args.url);
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            case 'check_chantier_weather': {
                const args = CheckChantierWeatherSchema.parse(request.params.arguments);
                const result = await checkChantierWeather(args.latitude, args.longitude, args.date);
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            case 'estimate_intervention_duration': {
                const args = EstimateInterventionDurationSchema.parse(request.params.arguments);
                const result = await estimateInterventionDuration(args.surface_m2, args.difficulty_score, args.nb_installers);
                return {
                    content: [
                        {
                            type: 'text',
                            text: JSON.stringify(result, null, 2),
                        },
                    ],
                };
            }

            default:
                throw new Error(`Unknown tool: ${request.params.name}`);
        }
    } catch (error: any) {
        if (error instanceof z.ZodError) {
            throw new Error(`Validation error: ${error.errors.map(e => e.message).join(', ')}`);
        }
        throw error;
    }
});

// ============================================
// DÉMARRAGE
// ============================================

async function main() {
    await initDatabase();

    const transport = new StdioServerTransport();
    await server.connect(transport);

    console.error('🚀 Antigravity Synapse MCP Server started');
}

main().catch((error) => {
    console.error('Fatal error:', error);
    process.exit(1);
});
