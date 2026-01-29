import { CommandeStatus, AffaireStatus } from './enums';

export interface Unite {
    code: string;
    unite_1?: string;
    unite_2?: string;
    coeff_conv?: number;
    commentaire?: string;
}

export interface Client {
    code_cli: string;
    nom_client: string;
    adresse?: string;
    tel?: string;
    mail?: string;
    type?: string;
}

export interface Fournisseur {
    code_fou: string;
    nom_client?: string; // Nom juridique/complet
    nom_court?: string;
    adresse?: string;
    tel?: string;
    mail?: string;
    type?: string;
    remise?: string;
}

export interface Image {
    id: number;
    chemin: string;
    date_creation: Date;
}

export interface Article {
    code_art: string;
    designation: string;
    desi_courte?: string;
    type?: string;
    famille?: string;
    ssfamille?: string;
    Fabricant?: string;
    tenu_en_stock: boolean; // converted from TINYINT
    Conditionnement?: string;
    unite?: string; // FK -> Unite.code
    poid?: number;
    dimension?: string;
    id_image?: number; // FK -> Image.id

    // Virtual/Joined properties
    image_url?: string;
    prix_unitaire?: number; // Added to fix frontend type errors
    fournisseur?: string; // Added to fix frontend type errors
}

export interface Affaire {
    num_cde_vente: string; // Primary Key
    code_cli: string;
    Statut: AffaireStatus;
    date_creation: string; // ISO Date
    date_cde?: string;
    date_liv?: string;
    montant_ht?: number;
    nom_client?: string; // Joined field
}

export interface Commande {
    num_oa: string; // Primary Key
    num_cde_fou?: string;
    num_cde_vente?: string;
    code_fou: string;
    designation: string;
    Statut: CommandeStatus;
    date_a_cde: string;
    date_conf?: string;
    date_liv?: string;
    montant_ht?: number;
    nom_fournisseur?: string; // Joined field
}

// Responses
export interface ApiResponse<T> {
    success: boolean;
    data: T;
    error?: string;
}

