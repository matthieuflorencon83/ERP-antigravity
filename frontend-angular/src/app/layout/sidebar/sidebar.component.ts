import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { ThemeService } from '../../core/services/theme.service';
import { LucideAngularModule, LayoutDashboard, Library, Settings, LogOut, Sun, Moon, Menu, ChevronLeft, ChevronRight, ChevronDown } from 'lucide-angular';
import { GlobalSearchComponent } from '../../shared/components/global-search/global-search.component';

@Component({
  selector: 'app-sidebar',
  standalone: true,
  imports: [CommonModule, RouterModule, LucideAngularModule, GlobalSearchComponent],
  template: `
    <div class="sidebar d-flex flex-column flex-shrink-0 p-3" [class.collapsed]="collapsed">
      <div class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none branding">
        <span class="fs-4 fw-bold logo-text" *ngIf="!collapsed">Antigravity</span>
        <button class="btn btn-sm btn-link toggle-btn ms-auto" (click)="toggleCollapse()">
            <lucide-icon [name]="collapsed ? 'chevron-right' : 'chevron-left'" [size]="20"></lucide-icon>
        </button>
      </div>
      
      <!-- Global Search -->
      <div class="mb-3 px-1" *ngIf="!collapsed">
        <app-global-search></app-global-search>
      </div>

      <hr>
      <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
          <a routerLink="/dashboard" routerLinkActive="active" class="nav-link d-flex align-items-center" aria-current="page" title="Tableau de bord">
            <lucide-icon name="layout-dashboard" [size]="24" strokeWidth="2"></lucide-icon>
            <span class="ms-2" *ngIf="!collapsed">Tableau de bord</span>
          </a>
        </li>
        <li>
          <a class="nav-link d-flex align-items-center justify-content-between cursor-pointer" 
             (click)="toggleLibrary()" 
             [class.active]="rla.isActive"
             routerLinkActive #rla="routerLinkActive"
             [routerLinkActiveOptions]="{paths: 'subset', matrixParams: 'ignored', queryParams: 'ignored', fragment: 'ignored'}"
             title="Bibliothèque">
            <div class="d-flex align-items-center">
                <lucide-icon name="library" [size]="24" strokeWidth="2"></lucide-icon>
                <span class="ms-2" *ngIf="!collapsed">Bibliothèque</span>
            </div>
            <lucide-icon *ngIf="!collapsed" [name]="isLibraryExpanded ? 'chevron-down' : 'chevron-right'" [size]="16"></lucide-icon>
          </a>
          
          <!-- Sub-menu -->
          <ul class="nav flex-column ms-3 mt-1" *ngIf="!collapsed && (isLibraryExpanded || rla.isActive)">
            <li class="nav-item">
                <a routerLink="/library/articles" routerLinkActive="active" class="nav-link sub-link d-flex align-items-center">
                    <span class="bullet me-2"></span> Articles
                </a>
            </li>
            <li class="nav-item">
                <a routerLink="/library/clients" routerLinkActive="active" class="nav-link sub-link d-flex align-items-center">
                    <span class="bullet me-2"></span> Clients
                </a>
            </li>
             <li class="nav-item">
                <a routerLink="/library/fournisseurs" routerLinkActive="active" class="nav-link sub-link d-flex align-items-center">
                    <span class="bullet me-2"></span> Fournisseurs
                </a>
            </li>
          </ul>
        </li>
      </ul>
      <hr>
      <div class="dropdown">
        <div class="d-flex align-items-center justify-content-between profile-section">
            <div class="d-flex align-items-center" *ngIf="!collapsed">
                <div class="avatar me-2 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">U</div>
                <strong>Utilisateur</strong>
            </div>
            <button class="btn btn-link p-0 theme-toggle" (click)="themeService.toggleTheme()" title="Toggle Theme">
                <lucide-icon [name]="themeService.isDarkMode() ? 'sun' : 'moon'" [size]="20"></lucide-icon>
            </button>
        </div>
      </div>
    </div>
  `,
  styles: [`
    :host {
        display: block;
        height: 100vh;
        position: sticky;
        top: 0;
        z-index: 100;
        border-right: 1px solid var(--border-color);
        background-color: var(--sidebar-bg);
        color: var(--sidebar-color);
        transition: width 0.3s;
    }

    .sidebar {
        width: 240px;
        height: 100%;
        transition: width 0.3s;
        &.collapsed {
            width: 80px;
            .logo-text, .ms-2, .profile-text {
                display: none;
            }
            .nav-link {
                justify-content: center;
            }
        }
    }

    .nav-link {
        color: var(--sidebar-color);
        opacity: 0.8;
        margin-bottom: 0.5rem;
        transition: all 0.2s;
        
        &:hover, &.active {
            background-color: var(--primary-color);
            color: white;
            opacity: 1;
        }
    }

    .branding {
        height: 40px;
        color: var(--sidebar-color);
    }

    .theme-toggle {
        color: var(--sidebar-color);
    }
    
    .cursor-pointer {
        cursor: pointer;
    }

    .sub-link {
        font-size: 0.9em;
        opacity: 0.7;
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
        
        &:hover, &.active {
            opacity: 1;
            background: none;
            color: var(--primary-color);
        }

        &.active .bullet {
            background-color: var(--primary-color);
        }
    }

    .bullet {
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background-color: var(--sidebar-color);
        opacity: 0.5;
    }
  `]
})
export class SidebarComponent {
  themeService = inject(ThemeService);
  collapsed = false;

  // Expose icons to template
  readonly icons = { LayoutDashboard, Library, Settings, LogOut, Sun, Moon, Menu, ChevronLeft, ChevronRight, ChevronDown };

  isLibraryExpanded = false;

  toggleCollapse() {
    this.collapsed = !this.collapsed;
  }

  toggleLibrary() {
    if (!this.collapsed) {
      this.isLibraryExpanded = !this.isLibraryExpanded;
    }
  }
}
