import { Injectable, signal, effect, PLATFORM_ID, Inject } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';

@Injectable({
    providedIn: 'root'
})
export class ThemeService {
    isDarkMode = signal<boolean>(false);

    constructor(@Inject(PLATFORM_ID) private platformId: Object) {
        if (isPlatformBrowser(this.platformId)) {
            // Initialize from localStorage or system preference
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                this.isDarkMode.set(savedTheme === 'dark');
            } else {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                this.isDarkMode.set(prefersDark);
            }
        }

        // Effect to apply class to body
        effect(() => {
            if (isPlatformBrowser(this.platformId)) {
                const isDark = this.isDarkMode();
                document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', isDark ? 'dark' : 'light'); // Activation Bootstrap 5.3 Mode
                if (isDark) {
                    document.body.classList.add('dark-mode');
                } else {
                    document.body.classList.remove('dark-mode');
                }
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            }
        });
    }

    toggleTheme() {
        this.isDarkMode.update(dark => !dark);
    }
}
