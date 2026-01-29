import { Component, inject, signal, computed, effect } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LibraryStore } from '../../services/library.store';
import { ReactiveFormsModule, FormControl, FormsModule } from '@angular/forms';
import { debounceTime } from 'rxjs/operators';
import { NgSelectModule } from '@ng-select/ng-select';
import { NgxSkeletonLoaderModule } from 'ngx-skeleton-loader';
import { LucideAngularModule, Search, X, Box, Settings2, Truck, Trash2, MousePointer2 } from 'lucide-angular';

@Component({
    selector: 'app-library-articles',
    standalone: true,
    imports: [
        CommonModule,
        ReactiveFormsModule,
        FormsModule,
        NgSelectModule,
        NgxSkeletonLoaderModule,
        LucideAngularModule
    ],
    templateUrl: './articles.component.html',
    styleUrls: ['./articles.component.scss']
})
export class ArticlesComponent {
    store = inject(LibraryStore);

    // UI State
    selectedArticle = signal<any>(null);
    loading = computed(() => this.store.loading());
    // Filters
    supplierFilter = signal<string | null>(null);
    familyFilter = signal<string | null>(null);
    refFilter = signal('');
    designationFilter = signal('');

    // Form Controls for inputs
    refControl = new FormControl('');
    designationControl = new FormControl('');

    // Computed Options
    suppliers = computed(() => {
        const arts = this.store.articles();
        const s = new Set(arts.map(a => a.fournisseur).filter(Boolean));
        return Array.from(s).sort();
    });

    families = computed(() => {
        const arts = this.store.articles();
        const sup = this.supplierFilter();

        // Filter articles by supplier first if one is selected
        const relevantArticles = sup
            ? arts.filter(a => a.fournisseur === sup)
            : arts;

        const f = new Set(relevantArticles.map(a => a.famille).filter(Boolean));
        return Array.from(f).sort();
    });

    // Filter Logic
    filteredArticles = computed(() => {
        const sup = this.supplierFilter();
        const fam = this.familyFilter();
        const ref = this.refFilter().toLowerCase();
        const des = this.designationFilter().toLowerCase();

        return this.store.articles().filter(a => {
            const matchSup = !sup || a.fournisseur === sup;
            const matchFam = !fam || a.famille === fam;
            const matchRef = !ref || a.code_art?.toLowerCase().includes(ref);
            const matchDes = !des || a.designation?.toLowerCase().includes(des);

            return matchSup && matchFam && matchRef && matchDes;
        });
    });

    constructor() {
        if (this.store.articles().length === 0) {
            this.store.loadArticles();
        }

        this.refControl.valueChanges.subscribe(val => {
            this.refFilter.set(val || '');
        });

        this.designationControl.valueChanges
            .pipe(debounceTime(300))  // Wait 300ms after user stops typing
            .subscribe(val => {
                this.designationFilter.set(val || '');
            });

        // Reset family filter when supplier changes
        effect(() => {
            const sup = this.supplierFilter();
            // When supplier changes, reset family if it's no longer valid
            const currentFamily = this.familyFilter();
            if (currentFamily && sup) {
                const validFamilies = this.families();
                if (!validFamilies.includes(currentFamily)) {
                    this.familyFilter.set(null);
                }
            }
        });
    }



    /**
     * Select an article and display its details
     * CRITICAL: Create a new object reference to ensure signal triggers change detection
     * See: https://angular.dev/guide/signals#mutability
     */
    selectArticle(article: any) {
        // Spread operator creates a new reference, ensuring signal detects the change
        this.selectedArticle.set({ ...article });
    }

    closeDetails() {
        this.selectedArticle.set(null);
    }

    /**
     * Check if article is currently selected (optimized for performance)
     */
    isSelected(article: any): boolean {
        const selected = this.selectedArticle();
        return selected?.code_art === article.code_art;
    }

    /**
     * Transform image path to API URL
     * @param imagePath - Relative path from database (e.g., "installux_arcelor/102.png")
     * @returns Full API URL or placeholder
     */
    getImageUrl(imagePath?: string): string {
        if (!imagePath) {
            return 'assets/placeholder-image.png'; // Fallback placeholder
        }

        // Extract filename from path (e.g., "installux_arcelor/102.png" -> "102.png")
        const filename = imagePath.split('/').pop();
        return `http://localhost:3000/api/images/${filename}`;
    }

    /**
     * Handle image load errors
     * @param event - Error event
     */
    onImageError(event: Event): void {
        const img = event.target as HTMLImageElement;
        img.src = 'assets/placeholder-image.png'; // Fallback on error
    }
}
