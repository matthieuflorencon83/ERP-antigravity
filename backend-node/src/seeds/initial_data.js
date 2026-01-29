import db from '../config/knex.js';

async function seed() {
    try {
        console.log('🌱 Seeding Reference Data...');

        // 1. Units
        await db('unite').insert([
            { code: 'ML', unite_1: 'Mètre Linéaire', coeff_conv: 1.0 },
            { code: 'M2', unite_1: 'Mètre Carré', coeff_conv: 1.0 },
            { code: 'U', unite_1: 'Unité', coeff_conv: 1.0 },
        ]).onConflict('code').ignore();
        console.log('✅ Units seeded');

        // 2. Clients
        await db('client').insert([
            { code_cli: 'CLI001', nom_client: 'Dupont Réalisations', type: 'Pro', tel: '0600000000' },
            { code_cli: 'CLI002', nom_client: 'Martin Particulier', type: 'Particulier', tel: '0700000000' }
        ]).onConflict('code_cli').ignore();
        console.log('✅ Clients seeded');

        // 3. Fournisseurs
        await db('fournisseur').insert([
            { code_fou: 'FOU001', nom_client: 'Alu Systems', nom_court: 'ALUSYS', type: 'Industriel' },
            { code_fou: 'FOU002', nom_client: 'Verre & Transparence', nom_court: 'V&T', type: 'Vitrage' }
        ]).onConflict('code_fou').ignore();
        console.log('✅ Fournisseurs seeded');

        // 4. Articles
        await db('article').insert([
            {
                code_art: 'PROF-7016',
                designation: 'Profilé Alu 7016',
                tenu_en_stock: true,
                unite: 'ML',
                famille: 'Profilés'
            },
            {
                code_art: 'VIS-INOX-50',
                designation: 'Vis Inox 50mm',
                tenu_en_stock: false,
                unite: 'U',
                famille: 'Quincaillerie'
            }
        ]).onConflict('code_art').ignore();
        console.log('✅ Articles seeded');

        console.log('🎉 Seeding Complete!');
        process.exit(0);

    } catch (err) {
        console.error('❌ Seeding Failed:', err);
        process.exit(1);
    }
}

seed();
