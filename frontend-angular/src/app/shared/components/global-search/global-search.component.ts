import { Component, HostListener, ViewChild, ElementRef, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { LucideAngularModule } from 'lucide-angular';
import { ApiService } from '../../../core/services/api.service';
import { Subject, debounceTime, switchMap, distinctUntilChanged, of, catchError } from 'rxjs';
import { Router } from '@angular/router';

@Component({
    selector: 'app-global-search',
    standalone: true,
    imports: [CommonModule, FormsModule, LucideAngularModule],
    template: `
    <div class="position-relative w-100">
      <!-- Search Input -->
      <div class="input-group">
        <span class="input-group-text bg-body-tertiary border-end-0 ps-3">
            <lucide-icon name="search" [size]="16" class="text-muted opacity-75"></lucide-icon>
        </span>
        <input 
            #searchInput
            type="text" 
            class="form-control border-start-0 ps-2 shadow-none bg-body-tertiary small" 
            [class.rounded-bottom-0]="showResults()"
            placeholder="Rechercher..." 
            [(ngModel)]="query" 
            (ngModelChange)="onSearch($event)"
            (focus)="onFocus()"
            (blur)="onBlur()"
            style="font-size: 0.9rem;"
        >
      </div>

      <!-- Results Dropdown -->
      <div class="dropdown-menu w-100 show p-0 shadow-lg border-top-0 rounded-bottom" *ngIf="showResults()">
        <div class="list-group list-group-flush">
            
            <div *ngIf="loading()" class="list-group-item text-center py-3 text-muted">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Recherche en cours...
            </div>

            <ng-container *ngIf="!loading()">
                <button 
                    *ngFor="let result of results()" 
                    class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2"
                    (click)="selectResult(result)">
                    
                    <div class="icon-box rounded p-2" [ngClass]="getIconClass(result.type)">
                        <lucide-icon [name]="getIconName(result.type)" [size]="20"></lucide-icon>
                    </div>
                    
                    <div class="flex-grow-1">
                        <div class="fw-medium">{{ result.label }}</div>
                        <small class="text-muted text-uppercase" style="font-size: 0.7rem;">{{ result.type }} • #{{ result.id }}</small>
                    </div>

                    <lucide-icon name="chevron-right" [size]="16" class="text-muted opacity-50"></lucide-icon>
                </button>

                <div *ngIf="results().length === 0 && query.length >= 2" class="list-group-item text-center py-3 text-muted">
                    <lucide-icon name="search-x" [size]="24" class="mb-2 opacity-50"></lucide-icon>
                    <p class="mb-0 small">Aucun résultat trouvé.</p>
                </div>
            </ng-container>

            <div class="list-group-item bg-light text-muted small py-1 text-end fst-italic">
                Antigravity Search
            </div>
        </div>
      </div>
    </div>
    
    <!-- Overlay for mobile focus (optional) -->
    <div class="search-backdrop" *ngIf="showResults()" (click)="closeSearch()"></div>
  `,
    styles: [`
    :host {
        display: block;
        width: 100%;
        display: flex;
        justify-content: center;
    }
    
    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1050;
        max-height: 400px;
        overflow-y: auto;
    }

    .icon-box {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon-article { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; }
    .icon-client { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
    .icon-order { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
    
    kbd {
        font-family: var(--bs-font-monospace);
        font-size: 0.7em;
    }

    /* Backdrop to handle clicks outside more robustly than (blur) */
    .search-backdrop {
        position: fixed;
        top: 0; 
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 1040;
        background: rgba(0,0,0,0.05);
    }
    
    /* Ensure input is above backdrop */
    .input-group {
        position: relative;
        z-index: 1060;
    }
  `]
})
export class GlobalSearchComponent {
    @ViewChild('searchInput') searchInput!: ElementRef;

    api = inject(ApiService);
    router = inject(Router);

    query = '';
    results = signal<any[]>([]);
    loading = signal(false);
    showResults = signal(false);

    private searchSubject = new Subject<string>();

    constructor() {
        this.searchSubject.pipe(
            debounceTime(300),
            distinctUntilChanged(),
            switchMap(term => {
                if (!term || term.length < 2) {
                    return of([]);
                }
                this.loading.set(true);
                // Using generic 'any' here, best to define interface in real project
                return this.api.get<{ success: boolean, results: any[] }>('/search', { q: term })
                    .pipe(
                        catchError(() => of({ success: false, results: [] })) // Handle error gracefully
                    );
            })
        ).subscribe(response => { // response is { success, results } or []
            this.loading.set(false);
            if (Array.isArray(response)) {
                this.results.set([]);
            } else if (response.success) {
                this.results.set(response.results);
            } else {
                this.results.set([]);
            }
            this.showResults.set(true);
        });
    }

    onSearch(value: string) {
        this.searchSubject.next(value);
        if (!value) {
            this.showResults.set(false);
        }
    }

    onFocus() {
        if (this.query.length >= 2) {
            this.showResults.set(true);
        }
    }

    closeSearch() {
        this.showResults.set(false);
    }

    // Blur handled by backdrop click mostly, but keep simple blur for tab-away
    onBlur() {
        // Delay to allow click event on result to fire
        setTimeout(() => {
            // this.showResults.set(false); 
        }, 200);
    }

    @HostListener('window:keydown.control.k', ['$event'])
    @HostListener('window:keydown.meta.k', ['$event']) // Mac Command+K
    focusSearch(event: any) { // Using any to avoid strict KeyboardEvent mismatch though it is a KeyboardEvent
        event.preventDefault();
        this.searchInput.nativeElement.focus();
    }

    @HostListener('window:keydown.escape')
    escapeSearch() {
        this.searchInput.nativeElement.blur();
        this.showResults.set(false);
    }

    getIconName(type: string): string {
        switch (type) {
            case 'article': return 'library'; // Using 'library'
            case 'client': return 'users'; // Using 'users' provided in app config
            case 'order': return 'layout-dashboard'; // Fallback
            default: return 'search';
        }
    }

    getIconClass(type: string): string {
        return `icon-${type}`;
    }

    selectResult(result: any) {
        this.showResults.set(false);
        this.query = '';

        // Navigation logic
        switch (result.type) {
            case 'article':
                console.log('Navigate to Article', result.id);
                // this.router.navigate(['/library/article', result.id]);
                break;
            case 'client':
                console.log('Navigate to Client', result.id);
                break;
            case 'order':
                console.log('Navigate to Order', result.id);
                break;
        }
    }
}
