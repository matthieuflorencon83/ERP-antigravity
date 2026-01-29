import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
    selector: 'app-card',
    standalone: true,
    imports: [CommonModule],
    template: `
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h6 class="card-subtitle mb-2 text-muted text-uppercase small" *ngIf="subtitle">{{ subtitle }}</h6>
        <h5 class="card-title fw-bold" *ngIf="title">{{ title }}</h5>
        <div class="card-text mt-3">
          <ng-content></ng-content>
        </div>
      </div>
      <div class="card-footer bg-transparent border-top-0 small text-muted" *ngIf="footer">
        {{ footer }}
      </div>
    </div>
  `,
    styles: [`
    .card {
        background-color: var(--card-bg);
        border-color: var(--border-color);
        transition: transform 0.2s;
        
        &:hover {
            transform: translateY(-2px);
        }
    }
  `]
})
export class CardComponent {
    @Input() title = '';
    @Input() subtitle = '';
    @Input() footer = '';
}
