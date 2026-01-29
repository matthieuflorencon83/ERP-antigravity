import db from '../../../config/knex.js';

class AffaireService {
    async getAll() {
        // Join with client to get client name
        return db('affaire')
            .leftJoin('client', 'affaire.code_cli', 'client.code_cli')
            .select('affaire.*', 'client.nom_client');
    }

    async getById(id) {
        return db('affaire').where('num_cde_vente', id).first();
    }

    async create(data) {
        // Validate required fields
        if (!data.num_cde_vente || !data.code_cli) {
            throw new Error('Champs obligatoires manquants (num_cde_vente, code_cli)');
        }

        // Default status
        const affaireData = {
            ...data,
            Statut: data.Statut || 'EN_COURS',
            date_creation: data.date_creation || new Date()
        };

        await db('affaire').insert(affaireData);
        return affaireData;
    }

    async update(id, data) {
        await db('affaire').where('num_cde_vente', id).update(data);
        return this.getById(id);
    }
}

export default new AffaireService();
