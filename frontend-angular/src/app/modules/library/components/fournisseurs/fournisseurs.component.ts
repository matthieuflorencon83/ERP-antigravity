import { Component, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LibraryStore } from '../../services/library.store';
import { ReactiveFormsModule, FormControl, FormsModule } from '@angular/forms';
import { debounceTime } from 'rxjs/operators';

import { NgxSkeletonLoaderModule } from 'ngx-skeleton-loader';
import { LucideAngularModule, X, Info, Settings2, Truck, Trash2, User, Contact } from 'lucide-angular';
import { Fournisseur } from '../../../../shared/interfaces';

@Component({
    selector: 'app-library-fournisseurs',
    standalone: true,
    imports: [
        CommonModule,
        ReactiveFormsModule,
        FormsModule,
        NgxSkeletonLoaderModule,

        LucideAngularModule
    ],
    templateUrl: './fournisseurs.component.html',
    styleUrls: ['./fournisseurs.component.scss']
})
export class FournisseursComponent {
    store = inject(LibraryStore);

    // UI State
    selectedFournisseur = signal<Fournisseur | null>(null);
    loading = computed(() => this.store.loading());

    // Filters
    codeFilter = signal('');
    nameFilter = signal('');

    // Form Controls
    codeControl = new FormControl('');
    nameControl = new FormControl('');

    // Filter Logic
    filteredFournisseurs = computed(() => {
        const code = this.codeFilter().toLowerCase();
        const name = this.nameFilter().toLowerCase();
        const list = this.store.fournisseurs();

        return list.filter(f => {
            const matchCode = !code || f.code_fou.toLowerCase().includes(code);
            const rawName = f.nom_client || f.nom_court || '';
            const matchName = !name || rawName.toLowerCase().includes(name);

            return matchCode && matchName;
        });
    });

    constructor() {
        if (this.store.fournisseurs().length === 0) {
            this.store.loadFournisseurs();
        }

        this.codeControl.valueChanges.subscribe(val => {
            this.codeFilter.set(val || '');
        });

        this.nameControl.valueChanges
            .pipe(debounceTime(300))
            .subscribe(val => {
                this.nameFilter.set(val || '');
            });
    }

    selectFournisseur(fou: Fournisseur) {
        // Create new reference for signal change detection
        this.selectedFournisseur.set({ ...fou });
    }

    closeDetails() {
        this.selectedFournisseur.set(null);
    }

    isSelected(fou: Fournisseur): boolean {
        const selected = this.selectedFournisseur();
        return selected?.code_fou === fou.code_fou;
    }

    deleteFournisseur(fou: Fournisseur) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur ?')) {
            console.log('Suppression de', fou.code_fou);
            // Implement delete logic via store if available
        }
    }
}
