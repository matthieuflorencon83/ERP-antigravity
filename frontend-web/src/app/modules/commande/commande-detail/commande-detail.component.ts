import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { CommandeService } from '../../../core/services/commande.service';
import { Commande } from '../../../shared/models/interfaces';
import { FormsModule } from '@angular/forms';

@Component({
    selector: 'app-commande-detail',
    standalone: true,
    imports: [CommonModule, FormsModule],
    templateUrl: './commande-detail.component.html',
    styles: [`
    .step-item { flex: 1; text-align: center; position: relative; }
    .step-item:not(:last-child)::after {
        content: ''; position: absolute; top: 15px; left: 50%; width: 100%; height: 2px;
        background: #e9ecef; z-index: -1;
    }
    .step-item.active .step-circle { background-color: var(--bs-primary); border-color: var(--bs-primary); color: white; }
    .step-item.completed .step-circle { background-color: var(--bs-success); border-color: var(--bs-success); color: white; }
    .step-circle {
        width: 30px; height: 30px; border-radius: 50%; background: white; border: 2px solid #dee2e6;
        display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold;
    }
  `]
})
export class CommandeDetailComponent implements OnInit {
    private route = inject(ActivatedRoute);
    private commandeService = inject(CommandeService);

    commande: Commande | null = null;
    loading = false;

    // Form Inputs for transitions
    arcRef = '';
    arcDate = new Date().toISOString().split('T')[0];
    livDate = new Date().toISOString().split('T')[0];

    ngOnInit() {
        const id = this.route.snapshot.paramMap.get('id');
        if (id) this.loadCommande(id);
    }

    loadCommande(id: string) {
        this.loading = true;
        this.commandeService.getById(id).subscribe({
            next: (c) => {
                this.commande = c;
                this.loading = false;
            },
            error: (e) => console.error(e)
        });
    }

    isStepCompleted(step: string): boolean {
        if (!this.commande) return false;
        const s = this.commande.Statut;
        if (step === 'BDC') return ['ENVOYEE', 'CONFIRMEE', 'LIVREE'].includes(s);
        if (step === 'ARC') return ['CONFIRMEE', 'LIVREE'].includes(s);
        if (step === 'LIV') return ['LIVREE'].includes(s);
        return false;
    }

    isActive(step: string): boolean {
        if (!this.commande) return false;
        // Current step logic
        const s = this.commande.Statut;
        if (step === 'BDC' && s === 'BROUILLON') return true;
        if (step === 'ARC' && s === 'ENVOYEE') return true;
        if (step === 'LIV' && s === 'CONFIRMEE') return true;
        return false;
    }

    // Transitions
    setEnvoyee() {
        if (!this.commande) return;
        this.updateStatus('ENVOYEE');
    }

    setConfirmee() {
        if (!this.commande) return;
        this.commandeService.updateStatus(this.commande.num_oa, 'CONFIRMEE', { num_cde_fou: this.arcRef, date_conf: this.arcDate })
            .subscribe(c => this.commande = c);
    }

    setLivree() {
        if (!this.commande) return;
        this.commandeService.updateStatus(this.commande.num_oa, 'LIVREE', { date_liv: this.livDate })
            .subscribe(c => this.commande = c);
    }

    private updateStatus(status: string) {
        if (!this.commande) return;
        this.commandeService.updateStatus(this.commande.num_oa, status)
            .subscribe(c => this.commande = c);
    }
}
