import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { ClientsComponent } from './clients.component';
import { LibraryStore } from '../../services/library.store';
import { signal } from '@angular/core';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

describe('ClientsComponent', () => {
    let component: ClientsComponent;
    let fixture: ComponentFixture<ClientsComponent>;
    let mockStore: any;

    beforeEach(async () => {
        mockStore = {
            clients: signal([
                { code_cli: 'C1', nom_client: 'Client One' },
                { code_cli: 'C2', nom_client: 'Client Two' }
            ]),
            loading: signal(false),
            loadClients: vi.fn()
        };

        await TestBed.configureTestingModule({
            imports: [ClientsComponent, BrowserAnimationsModule],
            providers: [
                { provide: LibraryStore, useValue: mockStore }
            ]
        })
            .compileComponents();

        fixture = TestBed.createComponent(ClientsComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });

    it('should display all clients initially', () => {
        expect(component.filteredClients().length).toBe(2);
    });

    it('should filter by code', () => {
        component.codeControl.setValue('C1');
        expect(component.filteredClients().length).toBe(1);
        expect(component.filteredClients()[0].code_cli).toBe('C1');
    });

    it('should filter by name with debounce', fakeAsync(() => {
        component.nameControl.setValue('Two');
        tick(300);
        expect(component.filteredClients().length).toBe(1);
        expect(component.filteredClients()[0].nom_client).toBe('Client Two');
    }));

    it('should select a client', () => {
        const cli = mockStore.clients()[0];
        component.selectClient(cli);
        expect(component.selectedClient()).toBe(cli);
    });
});
