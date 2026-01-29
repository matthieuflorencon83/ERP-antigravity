import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { ApiService } from '../../core/services/api.service';

@Component({
    selector: 'app-dashboard',
    standalone: true,
    imports: [CommonModule, RouterModule],
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.scss']
})
export class DashboardComponent implements OnInit {
    stats = {
        commandes: 12,
        ca: '45k €',
        besoins: 5,
        bibliotheque: 0
    };

    private api = inject(ApiService);

    ngOnInit() {
        // Fetch real data from Bibliothèque
        this.api.get<any[]>('bibliotheque/articles').subscribe({
            next: (articles) => {
                this.stats.bibliotheque = articles.length;
            },
            error: (err) => console.error('Failed to fetch stats', err)
        });
    }
}
