import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ApiResponse } from '../../shared/interfaces';


@Injectable({
    providedIn: 'root'
})
export class ApiService {
    // Assuming backend-node acts as the primary API gateway. 
    // Adjust base URL if needed (e.g., via environment files).
    // For now hardcoding to localhost:3000 based on standard setup or proxy.
    private baseUrl = 'http://localhost:3000';

    constructor(private http: HttpClient) { }

    get<T>(path: string, params: any = {}): Observable<T> {
        let httpParams = new HttpParams();
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                httpParams = httpParams.set(key, params[key]);
            }
        });
        return this.http.get<T>(`${this.baseUrl}${path}`, { params: httpParams });
    }

    post<T>(path: string, body: any): Observable<T> {
        return this.http.post<T>(`${this.baseUrl}${path}`, body);
    }

    put<T>(path: string, body: any): Observable<T> {
        return this.http.put<T>(`${this.baseUrl}${path}`, body);
    }

    delete<T>(path: string): Observable<T> {
        return this.http.delete<T>(`${this.baseUrl}${path}`);
    }
}
