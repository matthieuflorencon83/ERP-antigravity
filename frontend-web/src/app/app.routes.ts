import { Routes } from '@angular/router';
import { MainLayoutComponent } from './layout/main-layout/main-layout.component';
import { DashboardComponent } from './modules/dashboard/dashboard.component';

export const routes: Routes = [
    {
        path: '',
        component: MainLayoutComponent,
        children: [
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
            { path: 'dashboard', component: DashboardComponent },
            {
                path: 'bibliotheque',
                loadComponent: () => import('./modules/bibliotheque/bibliotheque.component').then(m => m.BibliothequeComponent)
            },
            // Future routes will be lazy loaded or added here
            {
                path: 'besoin',
                loadComponent: () => import('./modules/besoin').then(m => m.BesoinComponent)
            },
            {
                path: 'commande',
                loadComponent: () => import('./modules/commande/commande-list/commande-list.component').then(m => m.CommandeListComponent)
            },
            {
                path: 'commandes', // Plural alias
                redirectTo: 'commande',
                pathMatch: 'full'
            },
            {
                path: 'commandes/:id',
                loadComponent: () => import('./modules/commande/commande-detail/commande-detail.component').then(m => m.CommandeDetailComponent)
            },
            // Placeholders for new modules to prevent 404
            { path: 'affaires', component: DashboardComponent },
            { path: 'tiers', component: DashboardComponent },
        ]
    },
    { path: '**', redirectTo: '' }
];
