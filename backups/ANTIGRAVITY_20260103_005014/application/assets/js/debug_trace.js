/**
 * DEBUG: Script de diagnostic JavaScript
 * À insérer temporairement dans metrage_studio.php pour tracer l'exécution
 */

// Ajouter AVANT le chargement de metrage_studio_v11.js
console.log('=== DEBUG METRAGE STUDIO ===');
console.log('METRAGE_ID:', typeof METRAGE_ID !== 'undefined' ? METRAGE_ID : 'UNDEFINED');
console.log('INTERVENTION:', typeof INTERVENTION !== 'undefined' ? INTERVENTION : 'UNDEFINED');
console.log('LIGNES:', typeof LIGNES !== 'undefined' ? LIGNES : 'UNDEFINED');
console.log('TYPES:', typeof TYPES !== 'undefined' ? TYPES?.length : 'UNDEFINED');

// Vérifier DOM
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Ready - Checking containers...');
    const containers = ['assistant_messages', 'input_container', 'input_zone_wrapper', 'tree_products'];
    containers.forEach(id => {
        const el = document.getElementById(id);
        console.log(`#${id}:`, el ? 'EXISTS' : 'MISSING');
    });
});

// Intercepter les erreurs
window.addEventListener('error', (e) => {
    console.error('GLOBAL ERROR:', e.message, 'at', e.filename, 'line', e.lineno);
});
