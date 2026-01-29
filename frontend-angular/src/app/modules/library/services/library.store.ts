import { Injectable, signal, computed, inject } from '@angular/core';
import { ApiService } from '../../../core/services/api.service';
import { Article } from '../../../shared/interfaces';
import { finalize } from 'rxjs';

@Injectable({
    providedIn: 'root'
})
export class LibraryStore {
    private api = inject(ApiService);

    // State Signals
    readonly articles = signal<Article[]>([]);
    readonly loading = signal<boolean>(false);
    readonly error = signal<string | null>(null);

    // Filter Signals
    readonly filterCategory = signal<string | null>(null);
    readonly filterSupplier = signal<string | null>(null);
    readonly filterType = signal<string | null>(null);

    // Computed: Derived state (filtered articles)
    // Note: ideally filtering happens on server side for large datasets, 
    // but for "Smart Table" demo we can do it client side or pass params to API.
    // Given requirement "Filtres... dans les colonnes", client-side is often smoother for < 10k items 
    // if virtual scrolling is used, or server-side if paginated.
    // I will implement client-side filtering logic for the demo on loaded data.
    readonly filteredArticles = computed(() => {
        const all = this.articles();
        const cat = this.filterCategory();
        const sup = this.filterSupplier();
        const type = this.filterType();

        return all.filter(a => {
            const matchCat = cat ? a.famille === cat : true;
            const matchSup = sup ? a.fournisseur === sup : true; // Using virtual field
            const matchType = type ? a.type === type : true;
            return matchCat && matchSup && matchType;
        });
    });

    // Computed: Unique values for select options
    readonly categories = computed(() => [...new Set(this.articles().map(a => a.famille).filter(Boolean))]);
    readonly suppliers = computed(() => [...new Set(this.articles().map(a => a.fournisseur).filter(Boolean))]);
    readonly types = computed(() => [...new Set(this.articles().map(a => a.type).filter(Boolean))]);

    constructor() {
        this.loadArticles();
    }

    loadArticles() {
        this.loading.set(true);
        this.api.get<{ success: boolean, data: Article[] }>('/api/bibliotheque/articles')
            .pipe(finalize(() => this.loading.set(false)))
            .subscribe({
                next: (res) => this.articles.set(res.data),
                error: (err) => {
                    console.error(err);
                    this.error.set('Erreur lors du chargement des articles');
                }
            });
    }

    // Clients
    readonly clients = signal<any[]>([]);

    loadClients() {
        this.loading.set(true);
        this.api.get<{ success: boolean, data: any[] }>('/api/bibliotheque/clients')
            .pipe(finalize(() => this.loading.set(false)))
            .subscribe({
                next: (res) => this.clients.set(res.data),
                error: (err) => {
                    console.error(err);
                    this.error.set('Erreur lors du chargement des clients');
                }
            });
    }

    // Suppliers
    readonly fournisseurs = signal<any[]>([]);

    loadFournisseurs() {
        this.loading.set(true);
        this.api.get<{ success: boolean, data: any[] }>('/api/bibliotheque/fournisseurs')
            .pipe(finalize(() => this.loading.set(false)))
            .subscribe({
                next: (res) => this.fournisseurs.set(res.data),
                error: (err) => {
                    console.error(err);
                    this.error.set('Erreur lors du chargement des fournisseurs');
                }
            });
    }
}
