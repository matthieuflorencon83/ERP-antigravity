/**
 * NgRx SignalStore Example - Article Store
 * Pattern conforme aux règles d'architecture v2.0
 * 
 * Ce fichier démontre le pattern NgRx SignalStore recommandé.
 * À utiliser pour les nouveaux stores et migration progressive.
 */
import { computed, inject } from '@angular/core';
import {
    signalStore,
    withState,
    withComputed,
    withMethods,
    patchState
} from '@ngrx/signals';
import { rxMethod } from '@ngrx/signals/rxjs-interop';
import { pipe, switchMap, tap } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import { Article, Client, Fournisseur } from '../../../shared/interfaces';

// State interface - Typage strict (pas de any!)
interface LibraryState {
    articles: Article[];
    clients: Client[];
    fournisseurs: Fournisseur[];
    loading: boolean;
    error: string | null;
    // Filters
    filterCategory: string | null;
    filterSupplier: string | null;
    filterType: string | null;
}

// Initial state
const initialState: LibraryState = {
    articles: [],
    clients: [],
    fournisseurs: [],
    loading: false,
    error: null,
    filterCategory: null,
    filterSupplier: null,
    filterType: null
};

/**
 * Library SignalStore (NgRx Pattern)
 * 
 * Avantages sur le store manuel:
 * - Structure standardisée et prévisible
 * - DevTools support natif
 * - Immutabilité garantie via patchState
 * - Meilleure testabilité
 */
export const LibrarySignalStore = signalStore(
    { providedIn: 'root' },

    // State
    withState(initialState),

    // Computed (derived state)
    withComputed((state) => ({
        // Filtered articles
        filteredArticles: computed(() => {
            const all = state.articles();
            const cat = state.filterCategory();
            const sup = state.filterSupplier();
            const type = state.filterType();

            return all.filter(a => {
                const matchCat = cat ? a.famille === cat : true;
                const matchSup = sup ? a.fournisseur === sup : true;
                const matchType = type ? a.type === type : true;
                return matchCat && matchSup && matchType;
            });
        }),

        // Unique filter options
        categories: computed(() =>
            [...new Set(state.articles().map(a => a.famille).filter(Boolean))]
        ),
        suppliers: computed(() =>
            [...new Set(state.articles().map(a => a.fournisseur).filter(Boolean))]
        ),
        types: computed(() =>
            [...new Set(state.articles().map(a => a.type).filter(Boolean))]
        ),

        // Stats
        totalArticles: computed(() => state.articles().length),
        totalClients: computed(() => state.clients().length),
        totalFournisseurs: computed(() => state.fournisseurs().length)
    })),

    // Methods (actions)
    withMethods((store) => {
        const api = inject(ApiService);

        return {
            // Filter setters
            setFilterCategory(category: string | null) {
                patchState(store, { filterCategory: category });
            },
            setFilterSupplier(supplier: string | null) {
                patchState(store, { filterSupplier: supplier });
            },
            setFilterType(type: string | null) {
                patchState(store, { filterType: type });
            },
            clearFilters() {
                patchState(store, {
                    filterCategory: null,
                    filterSupplier: null,
                    filterType: null
                });
            },

            // Async data loading with rxMethod
            loadArticles: rxMethod<void>(
                pipe(
                    tap(() => patchState(store, { loading: true, error: null })),
                    switchMap(() =>
                        api.get<{ success: boolean; data: Article[] }>('/api/bibliotheque/articles')
                    ),
                    tap({
                        next: (res) => patchState(store, {
                            articles: res.data,
                            loading: false
                        }),
                        error: (err) => {
                            console.error('Error loading articles:', err);
                            patchState(store, {
                                loading: false,
                                error: 'Erreur lors du chargement des articles'
                            });
                        }
                    })
                )
            ),

            loadClients: rxMethod<void>(
                pipe(
                    tap(() => patchState(store, { loading: true, error: null })),
                    switchMap(() =>
                        api.get<{ success: boolean; data: Client[] }>('/api/bibliotheque/clients')
                    ),
                    tap({
                        next: (res) => patchState(store, {
                            clients: res.data,
                            loading: false
                        }),
                        error: (err) => {
                            console.error('Error loading clients:', err);
                            patchState(store, {
                                loading: false,
                                error: 'Erreur lors du chargement des clients'
                            });
                        }
                    })
                )
            ),

            loadFournisseurs: rxMethod<void>(
                pipe(
                    tap(() => patchState(store, { loading: true, error: null })),
                    switchMap(() =>
                        api.get<{ success: boolean; data: Fournisseur[] }>('/api/bibliotheque/fournisseurs')
                    ),
                    tap({
                        next: (res) => patchState(store, {
                            fournisseurs: res.data,
                            loading: false
                        }),
                        error: (err) => {
                            console.error('Error loading fournisseurs:', err);
                            patchState(store, {
                                loading: false,
                                error: 'Erreur lors du chargement des fournisseurs'
                            });
                        }
                    })
                )
            ),

            // Utility method to load everything
            loadAll() {
                this.loadArticles();
                this.loadClients();
                this.loadFournisseurs();
            }
        };
    })
);

// Type export for dependency injection
export type LibrarySignalStoreType = InstanceType<typeof LibrarySignalStore>;
