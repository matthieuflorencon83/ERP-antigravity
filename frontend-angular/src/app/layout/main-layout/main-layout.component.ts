import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { SidebarComponent } from '../sidebar/sidebar.component';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [CommonModule, RouterOutlet, SidebarComponent],
  template: `
    <div class="d-flex h-100 w-100 overflow-hidden" id="wrapper">
      <!-- Sidebar -->
      <app-sidebar class="h-100"></app-sidebar>
      
      <!-- Page Content -->
      <div id="page-content-wrapper" class="d-flex flex-column h-100 w-100 overflow-hidden position-relative">
        <!-- Top bar removed as search moved to sidebar -->
        <!-- <div class="top-bar p-3 d-flex justify-content-center custom-border flex-shrink-0">
            <app-global-search></app-global-search>
        </div> -->


        <div class="content-container flex-grow-1 overflow-hidden">
             <router-outlet></router-outlet>
        </div>
      </div>
    </div>
  `,
  styles: [`
    :host {
        display: block;
        height: 100vh;
        overflow: hidden;
    }
    #wrapper {
        min-height: 100%;
    }
    .content-container {
        position: relative;
        width: 100%;
        /* Padding removed to allow components to manage their own spacing/full-bleed */
    }
    .custom-border {
        border-bottom: 1px solid var(--primary-color) !important;
        background-color: var(--sidebar-bg) !important;
    }
  `]
})
export class MainLayoutComponent {
  constructor() {
    console.log('MainLayoutComponent: Initialized');
  }
}
