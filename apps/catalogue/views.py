from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from django.core.paginator import Paginator
from django.db.models import Q
from apps.catalogue.models import Article
from apps.core.forms import ArticleForm

@login_required
def article_list(request):
    queryset = Article.objects.select_related('fournisseur').all().order_by('designation')
    query = request.GET.get('q')
    
    # Filters
    famille = request.GET.get('famille')
    fournisseur_id = request.GET.get('fournisseur')

    if query:
        queryset = queryset.filter(
            Q(designation__icontains=query) | 
            Q(ref_fournisseur__icontains=query)
        )
    if famille:
        queryset = queryset.filter(famille=famille)
    if fournisseur_id:
        queryset = queryset.filter(fournisseur_id=fournisseur_id)

    paginator = Paginator(queryset, 50)
    page_number = request.GET.get('page')
    page_obj = paginator.get_page(page_number)

    # Context extra for filters
    familles = Article.objects.exclude(famille__exact='').values_list('famille', flat=True).distinct()

    if request.headers.get('HX-Request'):
        return render(request, 'catalogue/partials/article_table_body.html', {'page_obj': page_obj})

    return render(request, 'catalogue/article_list.html', {'page_obj': page_obj, 'familles': familles})

@login_required
def article_edit(request, pk=None):
    if pk:
        article = get_object_or_404(Article, pk=pk)
    else:
        article = None

    if request.method == 'POST':
        form = ArticleForm(request.POST, request.FILES, instance=article)
        if form.is_valid():
            obj = form.save()
            action = "modifié" if pk else "ajouté"
            messages.success(request, f"Article {obj.designation} {action}.")
            return redirect('catalogue:article_list')
    else:
        form = ArticleForm(instance=article)

    if request.headers.get('HX-Request'):
        return render(request, 'catalogue/partials/article_form_partial.html', {'form': form, 'is_htmx': True})

    return render(request, 'core/form_generic.html', {'form': form})

@login_required
def article_delete(request, pk):
    article = get_object_or_404(Article, pk=pk)
    if request.method == 'POST':
        article.delete()
        messages.success(request, "Article supprimé.")
        return redirect('catalogue:article_list')
    return render(request, 'core/confirm_delete.html', {'object': article})

@login_required
def htmx_article_detail(request, pk):
    article = get_object_or_404(Article, pk=pk)
    return render(request, 'catalogue/partials/article_detail_modal.html', {'article': article})

# --- HTMX SEARCH & OPTIONS ---

@login_required
def htmx_load_familles(request):
    fournisseur_id = request.GET.get('fournisseur')
    
    qs = Article.objects.all()
    if fournisseur_id:
        qs = qs.filter(fournisseur_id=fournisseur_id)
        
    # Exclude empty families
    familles = qs.exclude(famille__isnull=True).exclude(famille__exact='').values_list('famille', flat=True).distinct().order_by('famille')
    
    return render(request, 'catalogue/partials/options_famille.html', {'familles': familles})

@login_required
def htmx_load_sous_familles(request):
    famille = request.GET.get('famille')
    fournisseur_id = request.GET.get('fournisseur') # Optional context
    
    qs = Article.objects.all()
    if fournisseur_id:
        qs = qs.filter(fournisseur_id=fournisseur_id)
    
    if famille:
        qs = qs.filter(famille=famille)
        
    sous_familles = qs.exclude(sous_famille__isnull=True).exclude(sous_famille__exact='').values_list('sous_famille', flat=True).distinct().order_by('sous_famille')
    
    return render(request, 'catalogue/partials/options_sous_famille.html', {'sous_familles': sous_familles})

@login_required
def htmx_load_articles_options(request):
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
        )
    
    return render(request, 'catalogue/partials/options_articles.html', {'articles': queryset})

@login_required
def htmx_search_articles(request):
    fournisseur_id = request.GET.get('fournisseur')
    famille = request.GET.get('famille')
    sous_famille = request.GET.get('sous_famille')
    search_query = request.GET.get('search', '')
    affaire_id = request.GET.get('affaire_id')
    
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
        )[:50] 
    else:
        # Even with filters, limit to avoids rendering 2000+ items
        queryset = queryset[:50] 
    
    last_ral = request.session.get('last_ral', '')
    
    return render(request, 'catalogue/partials/article_search_results.html', {
        'articles': queryset,
        'last_ral': last_ral,
        'affaire_id': affaire_id
    })
