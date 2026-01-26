/**
 * Shared interfaces for ERP Arts alu
 * Adheres to "Règle du Contrat d'Interface"
 */

export interface Product {
    id: number;           // SQL Primary Key
    sku: string;         // SQL
    price: number;       // SQL (HT)
    stock: number;       // SQL
    family: string;      // SQL
    
    // Technical specifications using JSON for variability
    metadata: {
        ral?: string;
        technical_sheet_url?: string;
        dimensions?: string;
        [key: string]: any; 
    };
}

export interface CalculationRequest {
    type: 'calepinage' | 'structure' | 'generic';
    data: any;
}

export interface CalculationResponse {
    success: boolean;
    result: any;
    error?: string;
}
