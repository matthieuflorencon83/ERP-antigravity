import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CardComponent } from '../../shared/components/ui/card/card.component';
import { LucideAngularModule } from 'lucide-angular';
import { DashboardService } from './services/dashboard.service';

@Component({
    selector: 'app-dashboard',
    standalone: true,
    imports: [CommonModule, CardComponent, LucideAngularModule],
    template: `
    <div class="row g-4 mb-4">
      <div class="col-12">
        <h1 class="h3 mb-0">Tableau de bord</h1>
        <p class="text-muted">Vue d'ensemble de l'activité.</p>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <app-card>
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary bg-opacity-10 p-3 rounded-3 text-primary me-3">
                    <lucide-icon name="library" [size]="24"></lucide-icon>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1">Total Articles</h6>
                    <h2 class="card-title mb-0" *ngIf="!service.loading(); else skeleton">{{ service.totalArticles() }}</h2>
                </div>
            </div>
            <div class="text-muted small">
                <span class="text-success fw-medium"><lucide-icon name="plus" [size]="14" class="align-text-bottom"></lucide-icon> Actifs</span>
                dans la base
            </div>
        </app-card>
      </div>

      <div class="col-md-4">
        <app-card>
            <div class="d-flex align-items-center mb-3">
                <div class="bg-success bg-opacity-10 p-3 rounded-3 text-success me-3">
                    <lucide-icon name="layout-dashboard" [size]="24"></lucide-icon>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1">Dernières Commandes</h6>
                    <h2 class="card-title mb-0" *ngIf="!service.loading(); else skeleton">{{ service.latestOrders().length }}</h2>
                </div>
            </div>
            <div class="text-muted small">
                 Affichées ci-dessous
            </div>
        </app-card>
      </div>

      <div class="col-md-4">
        <app-card>
            <div class="d-flex align-items-center mb-3">
                <div class="bg-warning bg-opacity-10 p-3 rounded-3 text-warning me-3">
                    <lucide-icon name="search-x" [size]="24"></lucide-icon>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted mb-1">Alertes Chutes</h6>
                    <h2 class="card-title mb-0" *ngIf="!service.loading(); else skeleton">{{ service.scrapAlerts() }}</h2>
                </div>
            </div>
            <div class="text-muted small">
                À optimiser
            </div>
        </app-card>
      </div>
    </div>

    <!-- Recent Orders & Activity -->
    <div class="row g-4">
        <!-- Recent Orders Table -->
        <div class="col-lg-8">
            <app-card title="Activité Récente (Commandes)">
                <div class="table-responsive" *ngIf="!service.loading(); else listSkeleton">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>N° OA</th>
                                <th>Fournisseur</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th class="text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr *ngFor="let order of service.latestOrders()">
                                <td class="fw-medium text-primary">#{{ order.num_oa }}</td>
                                <td>{{ order.code_fou }}</td>
                                <td>{{ order.date_cre | date:'shortDate' }}</td>
                                <td>
                                    <span class="badge rounded-pill" 
                                        [class.bg-secondary]="order.Statut === 'BROUILLON'"
                                        [class.bg-info]="order.Statut === 'ENVOYEE'"
                                        [class.bg-success]="order.Statut === 'CONFIRMEE'">
                                        {{ order.Statut }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold">{{ order.montant_ht ? (order.montant_ht + ' €') : '-' }}</td>
                            </tr>
                             <tr *ngIf="service.latestOrders().length === 0">
                                <td colspan="5" class="text-center text-muted py-4">Aucune commande récente.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </app-card>
        </div>
        
        <!-- Activity Timeline / Quick Actions (Placeholder) -->
        <div class="col-lg-4">
            <app-card title="Actions Rapides">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2">
                        <lucide-icon name="plus" [size]="18"></lucide-icon> Nouvelle Commande
                    </button>
                    <button class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2">
                        <lucide-icon name="pencil" [size]="18"></lucide-icon> Gérer stocks
                    </button>
                </div>
            </app-card>
        </div>
    </div>

    <!-- Templates -->
    <ng-template #skeleton>
        <div class="placeholder-glow">
            <span class="placeholder col-6"></span>
        </div>
    </ng-template>

    <ng-template #listSkeleton>
         <div class="placeholder-glow p-3">
            <span class="placeholder col-12 mb-2"></span>
            <span class="placeholder col-12 mb-2"></span>
            <span class="placeholder col-12 mb-2"></span>
        </div>
    </ng-template>
  `
})
export class DashboardComponent {
    service = inject(DashboardService);
}
