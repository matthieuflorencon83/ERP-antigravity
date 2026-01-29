import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { forkJoin } from 'rxjs';
import { ApiService } from '../../core/services/api.service';
import { Article, Client, Fournisseur } from '../../shared/models/interfaces';

type TabType = 'articles' | 'fournisseurs' | 'clients';

@Component({
    selector: 'app-bibliotheque',
    standalone: true,
    imports: [CommonModule, FormsModule],
    template: `
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="text-primary fw-bold">Bibliothèque</h2>
                <p class="text-muted">Gestion intégrale de la base de données</p>
            </div>
            <button class="btn btn-primary" (click)="onNewItem()">
                <i class="bi bi-plus-lg me-2"></i> Nouveau
            </button>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" [class.active]="activeTab === 'articles'" (click)="setActiveTab('articles')" style="cursor: pointer;">
                    <i class="bi bi-box-seam me-2"></i> Articles
                    <span class="badge rounded-pill bg-light text-dark ms-2" *ngIf="articles.length">{{ articles.length }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" [class.active]="activeTab === 'fournisseurs'" (click)="setActiveTab('fournisseurs')" style="cursor: pointer;">
                    <i class="bi bi-truck me-2"></i> Fournisseurs
                    <span class="badge rounded-pill bg-light text-dark ms-2" *ngIf="fournisseurs.length">{{ fournisseurs.length }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" [class.active]="activeTab === 'clients'" (click)="setActiveTab('clients')" style="cursor: pointer;">
                    <i class="bi bi-people me-2"></i> Clients
                    <span class="badge rounded-pill bg-light text-dark ms-2" *ngIf="clients.length">{{ clients.length }}</span>
                </a>
            </li>
        </ul>

        <!-- Search Bar -->
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body p-2">
                <div class="input-group">
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-0" [placeholder]="getSearchPlaceholder()" [(ngModel)]="searchTerm" (input)="filterData()">
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div *ngIf="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Content Area -->
        <div *ngIf="!loading">
            
            <!-- TAB: ARTICLES -->
            <ng-container *ngIf="activeTab === 'articles'">
                <!-- Desktop Table -->
                <div class="card shadow-sm d-none d-lg-block border-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Code / Désignation</th>
                                    <th>Famille</th>
                                    <th>Fournisseur</th>
                                    <th>Stock</th>
                                    <th class="text-end pe-4">Prix HT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr *ngFor="let item of filteredArticles">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3 bg-light rounded text-center d-flex align-items-center justify-content-center text-primary fw-bold">
                                                {{ item.code_art.substring(0, 2) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark">{{ item.code_art }}</h6>
                                                <small class="text-muted">{{ item.designation }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-soft-primary text-primary">{{ item.famille || 'N/A' }}</span></td>
                                    <td>{{ item.fournisseur || '-' }}</td>
                                    <td>
                                        <span *ngIf="item.tenu_en_stock" class="badge bg-success-subtle text-success">Stock</span>
                                        <span *ngIf="!item.tenu_en_stock" class="badge bg-light text-muted border">Commande</span>
                                    </td>
                                    <td class="text-end pe-4 fw-bold">{{ $any(item).prix_unitaire | currency:'EUR' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Mobile Cards -->
                <div class="d-lg-none row g-3">
                    <div class="col-12" *ngFor="let item of filteredArticles">
                        <div class="card shadow-sm border-start border-4 border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="fw-bold mb-1">{{ item.code_art }}</h6>
                                        <small class="text-muted">{{ item.designation }}</small>
                                    </div>
                                    <span class="fw-bold text-primary">{{ $any(item).prix_unitaire | currency:'EUR' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </ng-container>

            <!-- TAB: FOURNISSEURS -->
            <ng-container *ngIf="activeTab === 'fournisseurs'">
                <div class="card shadow-sm d-none d-lg-block border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Code / Raison Sociale</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th class="text-end pe-4">Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr *ngFor="let item of filteredFournisseurs">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3 bg-warning-subtle text-warning rounded d-flex align-items-center justify-content-center fw-bold">
                                                {{ item.code_fou.substring(0, 1) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ item.nom_client || item.nom_court }}</h6>
                                                <small class="text-muted">{{ item.code_fou }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ item.tel || '-' }}</td>
                                    <td>{{ item.mail || '-' }}</td>
                                    <td class="text-end pe-4">
                                        <span class="badge bg-light text-dark border">{{ item.type || 'Fournisseur' }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                 <!-- Mobile Cards Fournisseurs -->
                 <div class="d-lg-none row g-3">
                    <div class="col-12" *ngFor="let item of filteredFournisseurs">
                        <div class="card shadow-sm border-start border-4 border-warning">
                            <div class="card-body">
                                <h6 class="fw-bold">{{ item.nom_client }}</h6>
                                <p class="mb-1 text-muted small"><i class="bi bi-upc me-1"></i> {{ item.code_fou }}</p>
                                <div class="d-flex justify-content-between mt-2">
                                     <small>{{ item.tel }}</small>
                                     <small>{{ item.mail }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </ng-container>

            <!-- TAB: CLIENTS -->
            <ng-container *ngIf="activeTab === 'clients'">
                 <div class="card shadow-sm d-none d-lg-block border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Code / Nom</th>
                                    <th>Adresse</th>
                                    <th>Email / Tel</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr *ngFor="let item of filteredClients">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3 bg-info-subtle text-info rounded d-flex align-items-center justify-content-center fw-bold">
                                                {{ item.code_cli.substring(0, 1) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ item.nom_client }}</h6>
                                                <small class="text-muted">{{ item.code_cli }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ item.adresse || '-' }}</td>
                                    <td>
                                        <div>{{ item.mail }}</div>
                                        <small class="text-muted">{{ item.tel }}</small>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                 <!-- Mobile Cards Clients -->
                 <div class="d-lg-none row g-3">
                    <div class="col-12" *ngFor="let item of filteredClients">
                        <div class="card shadow-sm border-start border-4 border-info">
                            <div class="card-body">
                                <h6 class="fw-bold">{{ item.nom_client }}</h6>
                                <p class="mb-1 text-muted small">{{ item.code_cli }}</p>
                                <p class="mb-0 small" *ngIf="item.adresse"><i class="bi bi-geo-alt me-1"></i> {{ item.adresse }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </ng-container>

        </div>
    </div>
  `,
    styles: [`
    .avatar-sm { width: 40px; height: 40px; min-width: 40px; }
    .bg-soft-primary { background-color: rgba(var(--bs-primary-rgb), 0.1); }
    .nav-link { color: var(--bs-secondary); }
    .nav-link.active { color: var(--bs-primary); font-weight: bold; border-bottom: 2px solid var(--bs-primary); }
    .table-nowrap td, .table-nowrap th { white-space: nowrap; }
  `]
})
export class BibliothequeComponent implements OnInit {
    private api = inject(ApiService);

    activeTab: TabType = 'articles';
    loading = false;
    searchTerm = '';

    // Data
    articles: Article[] = [];
    fournisseurs: Fournisseur[] = [];
    clients: Client[] = [];

    // Filtered Data
    filteredArticles: Article[] = [];
    filteredFournisseurs: Fournisseur[] = [];
    filteredClients: Client[] = [];

    ngOnInit() {
        this.loadData('articles');
        // Preload others lightly or load on click? Let's load all for fluidity for now (small DB)
        this.loadData('fournisseurs');
        this.loadData('clients');
    }

    setActiveTab(tab: TabType) {
        this.activeTab = tab;
        this.searchTerm = '';
        this.filterData();
    }

    onNewItem() {
        console.log('New item for', this.activeTab);
        // TODO: Open Modal based on activeTab
    }

    private loadData(type: TabType) {
        this.loading = true; // Global loading, maybe refine to per-tab if needed
        let endpoint = '';

        if (type === 'articles') endpoint = 'bibliotheque/articles';
        if (type === 'fournisseurs') endpoint = 'bibliotheque/tiers/fournisseurs';
        if (type === 'clients') endpoint = 'bibliotheque/tiers/clients';

        this.api.get<any[]>(endpoint).subscribe({
            next: (data) => {
                if (type === 'articles') {
                    this.articles = data;
                    this.filteredArticles = data;
                } else if (type === 'fournisseurs') {
                    this.fournisseurs = data;
                    this.filteredFournisseurs = data;
                } else if (type === 'clients') {
                    this.clients = data;
                    this.filteredClients = data;
                }
                this.loading = false;
            },
            error: (err) => {
                console.error(`Failed to load ${type}`, err);
                this.loading = false;
            }
        });
    }

    filterData() {
        const term = this.searchTerm.toLowerCase();

        if (this.activeTab === 'articles') {
            this.filteredArticles = this.articles.filter(a =>
                (a.code_art && a.code_art.toLowerCase().includes(term)) ||
                (a.designation && a.designation.toLowerCase().includes(term))
            );
        } else if (this.activeTab === 'fournisseurs') {
            this.filteredFournisseurs = this.fournisseurs.filter(f =>
                (f.nom_client && f.nom_client.toLowerCase().includes(term)) ||
                (f.code_fou && f.code_fou.toLowerCase().includes(term))
            );
        } else if (this.activeTab === 'clients') {
            this.filteredClients = this.clients.filter(c =>
                (c.nom_client && c.nom_client.toLowerCase().includes(term)) ||
                (c.code_cli && c.code_cli.toLowerCase().includes(term))
            );
        }
    }

    getSearchPlaceholder(): string {
        if (this.activeTab === 'articles') return 'Rechercher un article...';
        if (this.activeTab === 'fournisseurs') return 'Rechercher un fournisseur...';
        return 'Rechercher un client...';
    }
}
