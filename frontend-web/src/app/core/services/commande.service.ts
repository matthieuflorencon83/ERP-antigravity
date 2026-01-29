import { Injectable, inject } from '@angular/core';
import { ApiService } from './api.service';
import { Observable } from 'rxjs';
import { Commande } from '../../shared/models/interfaces';

@Injectable({
    providedIn: 'root'
})
export class CommandeService {
    private api = inject(ApiService);
    private readonly endpoint = 'commandes';

    getAll(): Observable<Commande[]> {
        return this.api.get<Commande[]>(this.endpoint);
    }

    getById(id: string): Observable<Commande> {
        return this.api.get<Commande>(`${this.endpoint}/${id}`);
    }

    create(data: Partial<Commande>): Observable<Commande> {
        return this.api.post<Commande>(this.endpoint, data);
    }

    update(id: string, data: Partial<Commande>): Observable<Commande> {
        return this.api.put<Commande>(`${this.endpoint}/${id}`, data);
    }

    updateStatus(id: string, statut: string, extraData: any = {}): Observable<Commande> {
        return this.api.put<Commande>(`${this.endpoint}/${id}/status`, { statut, ...extraData });
    }
}
