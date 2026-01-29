import { Injectable, inject, signal, computed } from '@angular/core';
import { ApiService } from '../../../core/services/api.service';
import { Article, Commande } from '../../../shared/interfaces';
import { map, catchError, of, forkJoin } from 'rxjs';

@Injectable({
    providedIn: 'root'
})
export class DashboardService {
    private api = inject(ApiService);

    // Stats Signals
    loading = signal(true);
    error = signal<string | null>(null);

    // Widget Data Signals
    totalArticles = signal(0);
    latestOrders = signal<Commande[]>([]);
    scrapAlerts = signal(0); // Mocked for now

    constructor() {
        this.refreshData();
    }

    refreshData() {
        this.loading.set(true);
        this.error.set(null);

        // Parallel Data Fetching
        forkJoin({
            articles: this.api.get<{ success: boolean, data: Article[] }>('/bibliotheque/articles').pipe(
                map(res => res.data),
                catchError(err => {
                    console.error('Failed to fetch articles', err);
                    return of([] as Article[]);
                })
            ),
            orders: this.api.get<Commande[]>('/commandes').pipe(
                catchError(err => {
                    console.error('Failed to fetch orders', err);
                    return of([] as Commande[]);
                })
            )
        }).subscribe({
            next: (res) => {
                // 1. Total Articles
                this.totalArticles.set(res.articles.length);

                // 2. Latest Orders (Sort DESC by date and take 5)
                // Assuming default sort is not guaranteed by backend
                const sortedOrders = [...res.orders].sort((a, b) => {
                    // Determine logic: descending ID or Date?
                    // Using ID as proxy for recentness if string/number, or Date if available.
                    // Assuming newer orders have higher ID/num strings roughly for now or date logic
                    if (a.date_cre && b.date_cre) {
                        return new Date(b.date_cre).getTime() - new Date(a.date_cre).getTime();
                    }
                    // Fallback to simple generic sort if no date
                    return 0;
                }).slice(0, 5);

                this.latestOrders.set(sortedOrders);

                // 3. Scrap Alerts (Stubbed)
                this.scrapAlerts.set(0);

                this.loading.set(false);
            },
            error: (err) => {
                this.error.set('Impossible de charger les données du tableau de bord.');
                this.loading.set(false);
            }
        });
    }
}
