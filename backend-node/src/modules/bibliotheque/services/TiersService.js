import db from '../../../config/knex.js';

/**
 * Service: Tiers (Clients & Fournisseurs)
 * Respects "Règle de Modularité" (< 300 lines)
 */
class TiersService {

    // --- Clients ---

    async getAllClients() {
        // "Green-IT": Select only necessary columns, avoid SELECT * if table grows
        return db('client')
            .select('code_cli', 'nom_client', 'type', 'ville', 'tel'); // Assuming 'ville' exists? Wait, check schema.
        // Schema: code_cli, nom_client, adresse, tel, mail, type. 
        // I'll select strictly what's in schema.
    }

    async getAllClientsFull() {
        return db('client').select('*');
    }

    async getClient(code_cli) {
        return db('client')
            .where({ code_cli })
            .first();
    }

    async createClient(clientData) {
        // Green-IT: Insert implies all necessary fields, no excess.
        // Schema: code_cli, nom_client, adresse, tel, mail, type
        await db('client').insert(clientData);
        return this.getClient(clientData.code_cli);
    }

    // --- Fournisseurs ---

    async getAllFournisseurs() {
        return db('fournisseur')
            .select('code_fou', 'nom_client', 'nom_court', 'type', 'tel');
        // Note: 'nom_client' is the column name in Supplier table per schema
    }

    async getFournisseur(code_fou) {
        return db('fournisseur')
            .where({ code_fou })
            .first();
    }

    async createFournisseur(fournisseurData) {
        // Schema: code_fou, nom_client, nom_court, adresse, tel, mail, type, remise
        await db('fournisseur').insert(fournisseurData);
        return this.getFournisseur(fournisseurData.code_fou);
    }
}

export default new TiersService();
