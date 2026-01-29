import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { FournisseursComponent } from './fournisseurs.component';
import { LibraryStore } from '../../services/library.store';
import { signal } from '@angular/core';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

describe('FournisseursComponent', () => {
    let component: FournisseursComponent;
    let fixture: ComponentFixture<FournisseursComponent>;
    let mockStore: any;

    beforeEach(async () => {
        mockStore = {
            fournisseurs: signal([
                { code_fou: 'F1', nom_client: 'Supplier One', code_pc: 'CP1' },
                { code_fou: 'F2', nom_client: 'Supplier Two', code_pc: 'CP2' }
            ]),
            loading: signal(false),
            loadFournisseurs: vi.fn()
        };

        await TestBed.configureTestingModule({
            imports: [FournisseursComponent, BrowserAnimationsModule],
            providers: [
                { provide: LibraryStore, useValue: mockStore }
            ]
        })
            .compileComponents();

        fixture = TestBed.createComponent(FournisseursComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });

    it('should display all suppliers initially', () => {
        expect(component.filteredFournisseurs().length).toBe(2);
    });

    it('should filter by code', () => {
        component.codeControl.setValue('F1');
        // The subscription invalidates the signal immediately for codeControl (no debounce)
        expect(component.filteredFournisseurs().length).toBe(1);
        expect(component.filteredFournisseurs()[0].code_fou).toBe('F1');
    });

    it('should filter by name with debounce', fakeAsync(() => {
        component.nameControl.setValue('Two');
        expect(component.filteredFournisseurs().length).toBe(2); // Not yet filtered
        tick(300); // Wait for debounce
        expect(component.filteredFournisseurs().length).toBe(1);
        expect(component.filteredFournisseurs()[0].nom_client).toBe('Supplier Two');
    }));

    it('should select a supplier', () => {
        const fou = mockStore.fournisseurs()[0];
        component.selectFournisseur(fou);
        expect(component.selectedFournisseur()).toBe(fou);
    });

    it('should close details', () => {
        const fou = mockStore.fournisseurs()[0];
        component.selectFournisseur(fou);
        expect(component.selectedFournisseur()).not.toBeNull();
        component.closeDetails();
        expect(component.selectedFournisseur()).toBeNull();
    });
});
