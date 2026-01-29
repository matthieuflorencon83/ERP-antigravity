import { Routes } from '@angular/router';
import { MainLayoutComponent } from './layout/main-layout/main-layout.component';

export const routes: Routes = [
    {
        path: '',
        component: MainLayoutComponent,
        children: [
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
            {
                path: 'dashboard',
                loadComponent: () => import('./modules/dashboard/dashboard.component').then(m => m.DashboardComponent)
            },
            {
                path: 'library',
                loadComponent: () => import('./modules/library/library.component').then(m => m.LibraryComponent),
                children: [
                    { path: '', redirectTo: 'articles', pathMatch: 'full' },
                    { path: 'articles', loadComponent: () => import('./modules/library/components/articles/articles.component').then(m => m.ArticlesComponent) },
                    { path: 'clients', loadComponent: () => import('./modules/library/components/clients/clients.component').then(m => m.ClientsComponent) },
                    { path: 'fournisseurs', loadComponent: () => import('./modules/library/components/fournisseurs/fournisseurs.component').then(m => m.FournisseursComponent) }
                ]
            }
        ]
    }
];
