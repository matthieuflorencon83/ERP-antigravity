import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../core/services/api.service';
import { Article } from '../shared/models/interfaces';
import { OptimizerService, StockOption, OptimizationResult } from '../core/services/optimizer.service';

interface CartItem extends Article {
  besoin: number; // In meters or units
  stock: number;
  commande: number;
  total_ht: number;
  ral?: string;
  is_gasket?: boolean;
  is_profile?: boolean;
  optimization_result?: OptimizationResult;
}

@Component({
  selector: 'app-besoin',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './besoin.html',
  styleUrls: ['./besoin.scss']
})
export class BesoinComponent implements OnInit {
  private api = inject(ApiService);
  private optimizer = inject(OptimizerService);

  // Data
  library: Article[] = [];
  filteredLibrary: Article[] = [];
  cart: CartItem[] = [];

  // State
  loadingLib = true;
  searchTerm = '';
  totalCost = 0;

  // Selection
  selectedCartIndex: number | null = null;
  selectedItem: CartItem | null = null;

  ngOnInit() {
    this.loadLibrary();
  }

  loadLibrary() {
    this.loadingLib = true;
    this.api.get<Article[]>('bibliotheque/articles').subscribe({
      next: (data) => {
        this.library = data;
        this.filteredLibrary = data;
        this.loadingLib = false;
      },
      error: (err) => {
        console.error('Error loading library', err);
        this.loadingLib = false;
      }
    });
  }

  filterLibrary() {
    const term = this.searchTerm.toLowerCase();
    this.filteredLibrary = this.library.filter(a =>
      a.code_art.toLowerCase().includes(term) ||
      a.designation.toLowerCase().includes(term)
    ).slice(0, 100);
  }

  addToCart(article: Article) {
    const existing = this.cart.find(i => i.code_art === article.code_art);
    if (existing) return;

    const isGasket = this.checkIfGasket(article);
    const isProfile = this.checkIfProfile(article);

    const newItem: CartItem = {
      ...article,
      besoin: 0,
      stock: article.tenu_en_stock ? 0 : 0,
      commande: 0,
      total_ht: 0,
      ral: '-',
      is_gasket: isGasket,
      is_profile: isProfile
    };

    this.cart.push(newItem);
    this.recalcItem(newItem);
  }

  removeFromCart(index: number) {
    this.cart.splice(index, 1);
    this.updateTotal();
    this.selectedCartIndex = null;
    this.selectedItem = null;
  }

  selectItem(index: number) {
    this.selectedCartIndex = index;
    this.selectedItem = this.cart[index];
  }

  clearCart() {
    if (confirm('Vider toute la liste de besoins ?')) {
      this.cart = [];
      this.updateTotal();
      this.selectedItem = null;
    }
  }

  checkIfGasket(article: Article): boolean {
    const des = article.designation.toUpperCase();
    const cond = (article.Conditionnement || '').toUpperCase();
    return des.includes('JOINT') || cond.includes('ML') || cond.includes('METTRE');
  }

  checkIfProfile(article: Article): boolean {
    const des = article.designation.toUpperCase();
    const cond = (article.Conditionnement || '').toUpperCase();
    return des.includes('PROFIL') || cond.includes('BARRE') || cond.includes('LG');
  }

  recalcItem(item: CartItem) {
    let condFactor = 1;
    if (item.is_gasket && item.Conditionnement) {
      const matches = item.Conditionnement.match(/(\d+)/);
      if (matches) condFactor = parseInt(matches[0], 10);
    }

    if (item.is_gasket) {
      const stockMeters = item.stock * condFactor;
      const remainingNeed = Math.max(0, item.besoin - stockMeters);
      item.commande = Math.ceil(remainingNeed / condFactor);
    } else {
      item.commande = Math.max(0, item.besoin - item.stock);
    }

    item.total_ht = item.commande * (item.prix_unitaire || 0);
    this.updateTotal();
  }

  updateTotal() {
    this.totalCost = this.cart.reduce((acc, i) => acc + i.total_ht, 0);
  }

  runOptimization() {
    if (!this.selectedItem || !this.selectedItem.is_profile) return;

    // Simulate prompt for cuts
    const cutsInput = prompt("Entrez les coupes en mm séparées par des virgules (ex: 2500, 1200, 800) :", "2500, 1200, 1200, 800");
    if (!cutsInput) return;

    const cuts = cutsInput.split(',').map(s => parseInt(s.trim(), 10)).filter(n => !isNaN(n));
    if (cuts.length === 0) return;

    // Define standard stock lengths (should be dynamic)
    const stockOptions: StockOption[] = [
      { ref: '6.5m', len_mm: 6500 },
      { ref: '4.5m', len_mm: 4500 }
    ];

    this.optimizer.solveMulti(stockOptions, cuts).subscribe({
      next: (result) => {
        this.selectedItem!.optimization_result = result;
        this.selectedItem!.besoin = Math.ceil(result.bins.length);
        this.selectedItem!.commande = result.bins.length;

        this.recalcItem(this.selectedItem!);

        alert(`Optimisation terminée !\nBarres nécessaires : ${result.bins.length}\nChutes : ${result.oversized_cuts.length > 0 ? result.oversized_cuts.length + ' hors cotes' : 'Aucune'}`);
      },
      error: (err) => {
        console.error('Optimization failed', err);
        alert('Erreur lors de l\'optimisation. Vérifiez le backend.');
      }
    });
  }

  getSearchPlaceholder() { return "Rechercher un article..."; }
}
