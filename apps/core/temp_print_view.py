
@login_required
def commande_print(request, pk):
    commande = get_object_or_404(Commande, pk=pk)
    # Get parameters (Company Info)
    # Optimization: ensure we have at least one or create default
    params = CoreParametre.objects.first()
    if not params:
        params = CoreParametre.objects.create(
            id=uuid.uuid4(),
            nom_societe="Ma Société",
            chemin_stockage_local="C:/Dev/storage"
        )
    
    return render(request, 'core/commande_print.html', {
        'commande': commande,
        'societe': params,
        'lines': commande.lignes.all()
    })
