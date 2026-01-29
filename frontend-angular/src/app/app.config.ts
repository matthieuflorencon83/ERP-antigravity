import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { routes } from './app.routes';
import { provideHttpClient, withFetch } from '@angular/common/http';
import { LucideAngularModule, LayoutDashboard, Library, Settings, LogOut, Sun, Moon, Menu, ChevronLeft, ChevronRight, Plus, Pencil, Trash2, SearchX, Calendar, Users, Search, ChevronDown, Download, Image as LucideImage, MousePointer2, ArrowLeft } from 'lucide-angular';
import { importProvidersFrom } from '@angular/core';
import { providePrimeNG } from 'primeng/config';
import Aura from '@primeuix/themes/aura';

export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),
    provideRouter(routes),
    provideHttpClient(withFetch()),
    importProvidersFrom(LucideAngularModule.pick({ LayoutDashboard, Library, Settings, LogOut, Sun, Moon, Menu, ChevronLeft, ChevronRight, Plus, Pencil, Trash2, SearchX, Calendar, Users, Search, ChevronDown, Download, Image: LucideImage, MousePointer2, ArrowLeft })),
    // PrimeNG Configuration with Aura Theme (Tailwind compatible)
    providePrimeNG({
      theme: {
        preset: Aura,
        options: {
          darkModeSelector: '.dark', // Sync with Tailwind dark mode
          cssLayer: {
            name: 'primeng',
            order: 'tailwind, primeng'
          }
        }
      },
      ripple: true
    })
  ]
};
