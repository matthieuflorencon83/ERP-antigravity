import db from '../../../config/knex.js';

class CommandeService {
    async getAll() {
        return db('commande')
            .leftJoin('fournisseur', 'commande.code_fou', 'fournisseur.code_fou')
            .leftJoin('affaire', 'commande.num_cde_vente', 'affaire.num_cde_vente')
            .select(
                'commande.*',
                'fournisseur.nom_client as nom_fournisseur',
                'affaire.code_cli as code_client_affaire'
            );
    }

    async getById(id) {
        return db('commande').where('num_oa', id).first();
    }

    async create(data) {
        if (!data.num_oa || !data.code_fou) {
            throw new Error('Champs obligatoires manquants (num_oa, code_fou)');
        }

        const cmdData = {
            ...data,
            Statut: data.Statut || 'BROUILLON',
            date_a_cde: data.date_a_cde || new Date()
        };

        await db('commande').insert(cmdData);
        return cmdData;
    }

    async update(id, data) {
        await db('commande').where('num_oa', id).update(data);
        return this.getById(id);
    }

    // Special Lifecycle Method
    async updateStatus(id, newStatus, extraData = {}) {
        const updatePayload = { Statut: newStatus };

        // Logique Metier
        if (newStatus === 'CONFIRMEE') { // ARC Received
            updatePayload.date_conf = extraData.date_conf || new Date();
            if (extraData.num_cde_fou) updatePayload.num_cde_fou = extraData.num_cde_fou;
        }
        else if (newStatus === 'LIVREE') {
            updatePayload.date_liv = extraData.date_liv || new Date();
        }

        await db('commande').where('num_oa', id).update(updatePayload);
        return this.getById(id);
    }
}

export default new CommandeService();
