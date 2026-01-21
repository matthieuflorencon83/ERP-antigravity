
@login_required
@require_POST
def htmx_update_ligne_commande(request, pk):
    line = get_object_or_404(LigneCommande, pk=pk)
    
    # Update fields
    try:
        new_qty = Decimal(request.POST.get('quantite', line.quantite))
        new_price = Decimal(request.POST.get('prix_unitaire', line.prix_unitaire))
        
        line.quantite = new_qty
        line.prix_unitaire = new_price
        line.save()
        
        # Recalculate Commande Totals
        commande = line.commande
        update_commande_totals(commande)
        
        # Return updated row AND totals footer
        context = {'line': line, 'form': CommandeForm(instance=commande)}
        return render(request, 'core/partials/commande_line_row.html', context)
        
    except Exception as e:
        logger.error(f"Error updating line: {e}")
        return HttpResponse(status=400)

@login_required
@require_POST
def htmx_delete_ligne_commande(request, pk):
    line = get_object_or_404(LigneCommande, pk=pk)
    commande = line.commande
    line.delete()
    
    update_commande_totals(commande)
    
    # Return empty string to remove row, plus OOB update for footer
    return render(request, 'core/partials/commande_line_delete.html', {'commande': commande})

@login_required
@require_POST
def htmx_update_statut_commande(request, pk, statut):
    commande = get_object_or_404(Commande, pk=pk)
    commande.statut = statut
    commande.save()
    
    messages.success(request, f"Statut mis à jour : {commande.get_statut_display()}")
    
    # Return updated header or full form re-render? 
    # Let's refresh the whole form container to switch read-only states if needed
    return redirect('commande_edit', pk=pk)

def update_commande_totals(commande):
    # Helper simple
    lines = commande.lignes.all()
    total_ht = sum(l.quantite * l.prix_unitaire for l in lines)
    commande.total_ht = total_ht
    commande.tva = total_ht * Decimal('0.20') # Hardcoded 20% for now
    commande.total_ttc = commande.total_ht + commande.tva
    commande.save()
