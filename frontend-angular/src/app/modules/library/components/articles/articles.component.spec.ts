import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { ArticlesComponent } from './articles.component';
import { LibraryStore } from '../../services/library.store';
import { signal } from '@angular/core';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

describe('ArticlesComponent', () => {
    let component: ArticlesComponent;
    let fixture: ComponentFixture<ArticlesComponent>;
    let mockStore: any;

    beforeEach(async () => {
        mockStore = {
            articles: signal([
                { code_art: 'A1', designation: 'Article One', fournisseur: 'FOU1', famille: 'FAM1' },
                { code_art: 'A2', designation: 'Article Two', fournisseur: 'FOU2', famille: 'FAM2' },
                { code_art: 'A3', designation: 'Article Three', fournisseur: 'FOU1', famille: 'FAM2' }
            ]),
            loading: signal(false),
            loadArticles: vi.fn()
        };

        await TestBed.configureTestingModule({
            imports: [ArticlesComponent, BrowserAnimationsModule],
            providers: [
                { provide: LibraryStore, useValue: mockStore }
            ]
        })
            .compileComponents();

        fixture = TestBed.createComponent(ArticlesComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });

    it('should filter by supplier', () => {
        component.supplierFilter.set('FOU1');
        expect(component.filteredArticles().length).toBe(2);
        expect(component.filteredArticles().every(a => a.fournisseur === 'FOU1')).toBe(true);
    });

    it('should filter by family', () => {
        component.familyFilter.set('FAM2');
        expect(component.filteredArticles().length).toBe(2);
        expect(component.filteredArticles().every(a => a.famille === 'FAM2')).toBe(true);
    });

    it('should filter by ref', () => {
        component.refControl.setValue('A1');
        expect(component.filteredArticles().length).toBe(1);
        expect(component.filteredArticles()[0].code_art).toBe('A1');
    });

    it('should filter by designation', fakeAsync(() => {
        component.designationControl.setValue('Two');
        tick(300);
        expect(component.filteredArticles().length).toBe(1);
        expect(component.filteredArticles()[0].designation).toBe('Article Two');
    }));

    it('should update families list based on supplier selection', () => {
        // Before selection, families should contain FAM1 and FAM2
        expect(component.families()).toContain('FAM1');
        expect(component.families()).toContain('FAM2');

        // Select FOU2 (only has FAM2)
        component.supplierFilter.set('FOU2');
        fixture.detectChanges(); // Trigger effect if any (effect might need tick in testing)

        // Check computed logic
        expect(component.families()).toEqual(['FAM2']);
    });
});
