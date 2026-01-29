import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';

@Component({
    selector: 'app-main-layout',
    standalone: true,
    imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive],
    templateUrl: './main-layout.component.html',
    styleUrls: ['./main-layout.component.scss']
})
export class MainLayoutComponent {
    isToggled = false;

    toggleSidebar() {
        this.isToggled = !this.isToggled;
        const wrapper = document.getElementById('wrapper');
        if (wrapper) {
            wrapper.classList.toggle('toggled');
        }
    }
}
