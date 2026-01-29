/**
 * ArticlesTable Demo Component
 * Demonstrates PrimeNG p-table with Tailwind CSS styling
 * Uses Angular Signals for state management
 */
import { Component, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TableModule } from 'primeng/table';
import { InputTextModule } from 'primeng/inputtext';
import { ButtonModule } from 'primeng/button';
import { LibraryStore } from '../../store/library.store';

interface ArticleDisplay {
    code_art: string;
    designation: string;
    famille: string;
    fournisseur: string;
    prix_unitaire: number;
}

@Component({
    selector: 'app-articles-table-demo',
    standalone: true,
    imports: [CommonModule, TableModule, InputTextModule, ButtonModule],
    template: `
        <div class="card">
            <!-- Header with search -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">
                    Articles Catalogue
                </h2>
                <div class="relative">
                    <input 
                        type="text" 
                        class="input pl-10 w-64"
                        placeholder="Rechercher..."
                        (input)="onSearch($event)">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                        🔍
                    </span>
                </div>
            </div>

            <!-- PrimeNG Table with Tailwind Passthrough styling -->
            <p-table 
                [value]="filteredArticles()" 
                [paginator]="true" 
                [rows]="10"
                [rowsPerPageOptions]="[10, 25, 50]"
                [globalFilterFields]="['code_art', 'designation', 'fournisseur']"
                [pt]="{
                    root: { class: 'w-full' },
                    table: { class: 'w-full border-collapse' },
                    thead: { class: 'bg-slate-100 dark:bg-slate-800' },
                    headerRow: { class: 'border-b border-slate-200 dark:border-slate-700' },
                    headerCell: { class: 'px-4 py-3 text-left text-sm font-semibold text-slate-700 dark:text-slate-200' },
                    tbody: { class: 'divide-y divide-slate-100 dark:divide-slate-700' },
                    bodyRow: { class: 'hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer' },
                    bodyCell: { class: 'px-4 py-3 text-sm text-slate-600 dark:text-slate-300' }
                }">
                
                <ng-template pTemplate="header">
                    <tr>
                        <th class="w-32">Code</th>
                        <th>Désignation</th>
                        <th class="w-40">Famille</th>
                        <th class="w-36">Fournisseur</th>
                        <th class="w-28 text-right">Prix HT</th>
                    </tr>
                </ng-template>
                
                <ng-template pTemplate="body" let-article>
                    <tr>
                        <td>
                            <code class="text-xs font-mono bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded">
                                {{ article.code_art }}
                            </code>
                        </td>
                        <td class="font-medium text-slate-800 dark:text-slate-100">
                            {{ article.designation }}
                        </td>
                        <td>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                                {{ article.famille }}
                            </span>
                        </td>
                        <td class="text-slate-500 dark:text-slate-400">
                            {{ article.fournisseur }}
                        </td>
                        <td class="text-right font-semibold text-accent-600 dark:text-accent-400">
                            {{ article.prix_unitaire | currency:'EUR':'symbol':'1.2-2' }}
                        </td>
                    </tr>
                </ng-template>
                
                <ng-template pTemplate="emptymessage">
                    <tr>
                        <td colspan="5" class="text-center py-8 text-slate-400">
                            Aucun article trouvé
                        </td>
                    </tr>
                </ng-template>
            </p-table>
        </div>
    `,
    styles: [`
        :host {
            display: block;
        }
    `]
})
export class ArticlesTableDemoComponent {
    private store = inject(LibraryStore);

    // Signal for search filter
    private searchTerm = signal('');

    // Computed signal: filtered articles
    filteredArticles = computed(() => {
        const term = this.searchTerm().toLowerCase();
        const articles = this.store.articles();

        if (!term) return articles;

        return articles.filter(a =>
            a.code_art?.toLowerCase().includes(term) ||
            a.designation?.toLowerCase().includes(term) ||
            a.fournisseur?.toLowerCase().includes(term)
        );
    });

    onSearch(event: Event) {
        const input = event.target as HTMLInputElement;
        this.searchTerm.set(input.value);
    }
}
