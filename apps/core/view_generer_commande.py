
@login_required
@require_POST
def generer_commande(request, pk):
    """
    Génère une ou plusieurs commandes (Brouillon) à partir d'une sélection de besoins.
    pk: ID de l'Affaire
    """
    affaire = get_object_or_404(Affaire, pk=pk)
    besoin_ids = request.POST.getlist('besoin_ids')

    if not besoin_ids:
        messages.warning(request, "Aucun besoin sélectionné.")
        return redirect('besoins_affaire', pk=pk)

    # 1. Filtrer les besoins valides (pas déjà commandés)
    besoins = Besoin.objects.filter(
        id__in=besoin_ids, 
        affaire=affaire
    ).exclude(statut='COMMANDE').select_related('article', 'fournisseur', 'article__fournisseur')

    if not besoins.exists():
        messages.warning(request, "Les besoins sélectionnés sont déjà commandés ou invalides.")
        return redirect('besoins_affaire', pk=pk)

    # 2. Regrouper par Fournisseur
    grouped = {}
    sans_fournisseur = []

    for besoin in besoins:
        # Priorité : Fournisseur assigné au besoin > Fournisseur de l'article > None
        fournisseur = besoin.fournisseur or besoin.article.fournisseur
        
        if not fournisseur:
            sans_fournisseur.append(besoin)
            continue
            
        if fournisseur not in grouped:
            grouped[fournisseur] = []
        grouped[fournisseur].append(besoin)

    if sans_fournisseur:
        messages.error(request, f"{len(sans_fournisseur)} besoins ignorés car sans fournisseur défini.")

    # 3. Création des Commandes
    created_cmds = []
    
    try:
        with transaction.atomic():
            for fournisseur, needs in grouped.items():
                # Création En-tête Commande
                # On utilise un numéro temporaire ou on laisse vide, l'ID suffit pour brouillon
                cmd = Commande.objects.create(
                    affaire=affaire,
                    fournisseur=fournisseur,
                    statut='BROUILLON',
                    canal='EMAIL',
                    designation=f"Cde {fournisseur.nom_fournisseur} - {affaire.nom_affaire}"[:255]
                )
                
                # Création des Lignes
                for need in needs:
                    # Prix : Priorité besoin > article
                    # Note: Besoin model doesn't have price field currently? 
                    # Checking model... Besoin has quantite, unite, ral, finition. No price.
                    # So use Article price.
                    price = need.article.prix_unitaire_ht
                    
                    LigneCommande.objects.create(
                        commande=cmd,
                        article=need.article,
                        besoin_generateur=need,
                        designation=need.article.designation, # Snapshot
                        quantite=need.quantite,
                        prix_unitaire=price,
                        remise=0
                    )
                    
                    # Mise à jour du Besoin
                    need.statut = 'COMMANDE'
                    need.statut_commande = 'EN COMMANDE'
                    need.save()
                
                created_cmds.append(cmd)

        if created_cmds:
            if len(created_cmds) == 1:
                messages.success(request, "Commande brouillon créée avec succès.")
                return redirect('commande_edit', pk=created_cmds[0].pk)
            else:
                messages.success(request, f"{len(created_cmds)} commandes brouillons créées.")
        
    except Exception as e:
        logger.exception("Erreur lors de la génération de commande")
        messages.error(request, f"Erreur technique : {e}")

    return redirect('besoins_affaire', pk=pk)
