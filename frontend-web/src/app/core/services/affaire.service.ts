import { Injectable, inject } from '@angular/core';
import { ApiService } from './api.service';
import { Observable } from 'rxjs';
import { Affaire } from '../../shared/models/interfaces';

@Injectable({
    providedIn: 'root'
})
export class AffaireService {
    private api = inject(ApiService);
    private readonly endpoint = 'affaires';

    getAll(): Observable<Affaire[]> {
        return this.api.get<Affaire[]>(this.endpoint);
    }

    getById(id: string): Observable<Affaire> {
        return this.api.get<Affaire>(`${this.endpoint}/${id}`);
    }

    create(data: Partial<Affaire>): Observable<Affaire> {
        return this.api.post<Affaire>(this.endpoint, data);
    }

    update(id: string, data: Partial<Affaire>): Observable<Affaire> {
        return this.api.put<Affaire>(`${this.endpoint}/${id}`, data);
    }
}
