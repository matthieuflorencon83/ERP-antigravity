import { Component, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LibraryStore } from '../../services/library.store';
import { ReactiveFormsModule, FormControl, FormsModule } from '@angular/forms';
import { debounceTime } from 'rxjs/operators';

import { NgxSkeletonLoaderModule } from 'ngx-skeleton-loader';
import { LucideAngularModule, X, Info, Settings2, Users, Trash2, Contact } from 'lucide-angular';
import { Client } from '../../../../shared/interfaces';

@Component({
    selector: 'app-library-clients',
    standalone: true,
    imports: [
        CommonModule,
        ReactiveFormsModule,
        FormsModule,
        NgxSkeletonLoaderModule,

        LucideAngularModule
    ],
    templateUrl: './clients.component.html',
    styleUrls: ['./clients.component.scss']
})
export class ClientsComponent {
    store = inject(LibraryStore);

    // UI State
    selectedClient = signal<Client | null>(null);
    loading = computed(() => this.store.loading());

    // Filters
    codeFilter = signal('');
    nameFilter = signal('');

    // Form Controls
    codeControl = new FormControl('');
    nameControl = new FormControl('');

    // Filter Logic
    filteredClients = computed(() => {
        const code = this.codeFilter().toLowerCase();
        const name = this.nameFilter().toLowerCase();
        const list = this.store.clients();

        return list.filter(c => {
            const matchCode = !code || c.code_cli.toLowerCase().includes(code);
            const matchName = !name || c.nom_client.toLowerCase().includes(name);

            return matchCode && matchName;
        });
    });

    constructor() {
        if (this.store.clients().length === 0) {
            this.store.loadClients();
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

    selectClient(cli: Client) {
        // Create new reference for signal change detection
        this.selectedClient.set({ ...cli });
    }

    closeDetails() {
        this.selectedClient.set(null);
    }

    isSelected(cli: Client): boolean {
        const selected = this.selectedClient();
        return selected?.code_cli === cli.code_cli;
    }

    deleteClient(cli: Client) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce client ?')) {
            console.log('Suppression de', cli.code_cli);
            // Implement delete logic via store if available
        }
    }
}
