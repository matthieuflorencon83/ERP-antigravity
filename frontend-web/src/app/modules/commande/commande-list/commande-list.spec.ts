import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CommandeList } from './commande-list';

describe('CommandeList', () => {
  let component: CommandeList;
  let fixture: ComponentFixture<CommandeList>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CommandeList]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CommandeList);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
