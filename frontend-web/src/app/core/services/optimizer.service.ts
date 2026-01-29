import { Injectable, inject } from '@angular/core';
import { ApiService } from './api.service';
import { Observable, map } from 'rxjs';

export interface StockOption {
    ref: string;
    len_mm: number;
}

export interface OptimizationResult {
    bins: Bin[];
    oversized_cuts: number[];
}

export interface Bin {
    ref: string;
    len_mm: number;
    free: number;
    cuts: number[];
}

@Injectable({
    providedIn: 'root'
})
export class OptimizerService {
    private api = inject(ApiService);

    constructor() { }

    /**
     * 1D Bin Packing via Backend API (Python Engine)
     */
    solveMulti(stockOptions: StockOption[], cutsMm: number[], sawKerf: number = 4, scrapEnd: number = 0): Observable<OptimizationResult> {
        const payload = {
            stock_options: stockOptions,
            cuts_mm: cutsMm,
            saw_kerf: sawKerf,
            scrap_end: scrapEnd
        };

        return this.api.post<OptimizationResult>('optimize', payload);
    }
}
