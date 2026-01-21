
def htmx_search_articles(request):
    fournisseur_id = request.GET.get('fournisseur')
    famille = request.GET.get('famille')
    sous_famille = request.GET.get('sous_famille')
    search_query = request.GET.get('search', '')
    
    queryset = Article.objects.all().select_related('fournisseur').order_by('designation')
    
    if fournisseur_id:
        queryset = queryset.filter(fournisseur_id=fournisseur_id)
    if famille:
        queryset = queryset.filter(famille=famille)
    if sous_famille:
        queryset = queryset.filter(sous_famille=sous_famille)
    if search_query:
        queryset = queryset.filter(
            Q(designation__icontains=search_query) | 
            Q(ref_fournisseur__icontains=search_query)
        )[:50] # Limit results for speed
    else:
        # If no search, maybe limit to top 20 to avoid massive render?
        # Or if filters are active, show all? Let's limit default view.
        if not (fournisseur_id or famille or sous_famille):
            queryset = queryset[:20] 
    
    last_ral = request.session.get('last_ral', '')
    
    return render(request, 'core/partials/article_search_results.html', {
        'articles': queryset,
        'last_ral': last_ral
    })
