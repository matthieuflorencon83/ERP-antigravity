import { spawn } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

class BesoinService {

    /**
     * Entry point for optimization calculation.
     * Expects a list of needed cuts with properties: { length, quantity, ral, material ... }
     * Returns grouped results processed by Python.
     */
    async calculateOptimization(needs) {
        // 1. Group by RAL / Material
        // "Prépare la logique pour les RAL"
        const groups = this._groupBy(needs, (item) => `${item.ral || 'STD'}_${item.material || 'ALU'}`);

        const results = {};

        for (const key in groups) {
            const groupNeeds = groups[key];
            // Transform needs to simple list of cuts for the optimizer
            // Example: [{length: 1200, quantity: 2}] -> [1200, 1200]
            const cuts = [];
            for (const item of groupNeeds) {
                for (let i = 0; i < item.quantity; i++) {
                    cuts.push(item.length);
                }
            }

            // Call Python Bridge
            // "Stock Standard" is assumed 6000 for now, could be dynamic per group later.
            try {
                const optimized = await this._callPythonOptimizer({
                    stock_length: 6000,
                    cuts: cuts,
                    blade_width: 4 // Standard blade width example
                });

                // "Implémente la sauvegarde des résultats dans la table besoin_ligne"
                // "Le plan de coupe détaillé doit être stocké dans la colonne JSON config_calcul"
                const savedLine = await db('besoin_ligne').insert({
                    groupe_calcul: key,
                    config_calcul: JSON.stringify(optimized)
                });

                // Add the DB ID to the result structure for frontend ref
                results[key] = {
                    ...optimized,
                    db_id: savedLine[0]
                };

            } catch (err) {
                console.error(`Error optimizing group ${key}:`, err);
                results[key] = { error: err.message };
            }
        }

        return results;
    }

    _groupBy(array, keyGetter) {
        const map = {};
        array.forEach((item) => {
            const key = keyGetter(item);
            if (!map[key]) {
                map[key] = [];
            }
            map[key].push(item);
        });
        return map;
    }

    /**
     * "Pont (via appel de script)" strictly as requested.
     */
    _callPythonOptimizer(inputData) {
        return new Promise((resolve, reject) => {
            // Path to python script: root/backend-python/optimizer.py
            // We are in src/modules/liste_besoin/services/
            const scriptPath = path.resolve(__dirname, '../../../../../backend-python/optimizer.py');

            const pythonProcess = spawn('python', [scriptPath]);

            let output = '';
            let errorOutput = '';

            // Send JSON to stdin
            pythonProcess.stdin.write(JSON.stringify(inputData));
            pythonProcess.stdin.end();

            pythonProcess.stdout.on('data', (data) => {
                output += data.toString();
            });

            pythonProcess.stderr.on('data', (data) => {
                errorOutput += data.toString();
            });

            pythonProcess.on('close', (code) => {
                if (code !== 0) {
                    reject(new Error(`Python script exited with code ${code}: ${errorOutput}`));
                } else {
                    try {
                        const result = JSON.parse(output);
                        if (result.error) {
                            reject(new Error(result.error));
                        } else {
                            resolve(result);
                        }
                    } catch (e) {
                        reject(new Error(`Failed to parse Python output: ${e.message}. Raw output: ${output}`));
                    }
                }
            });
        });
    }
}

export default new BesoinService();
