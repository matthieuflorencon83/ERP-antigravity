import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { CommandeService } from '../../../core/services/commande.service';
import { Commande } from '../../../shared/models/interfaces';

@Component({
    selector: 'app-commande-list',
    standalone: true,
    imports: [CommonModule, RouterModule],
    templateUrl: './commande-list.component.html',
    styles: []
})
export class CommandeListComponent implements OnInit {
    private commandeService = inject(CommandeService);

    commandes: Commande[] = [];
    loading = false;

    ngOnInit() {
        this.loadCommandes();
    }

    loadCommandes() {
        this.loading = true;
        this.commandeService.getAll().subscribe({
            next: (data) => {
                this.commandes = data;
                this.loading = false;
            },
            error: (e) => {
                console.error(e);
                this.loading = false;
            }
        });
    }

    getStatusClass(status: string): string {
        switch (status) {
            case 'BROUILLON': return 'bg-secondary';
            case 'ENVOYEE': return 'bg-primary';
            case 'CONFIRMEE': return 'bg-warning text-dark';
            case 'LIVREE': return 'bg-success';
            default: return 'bg-light text-dark';
        }
    }
}
