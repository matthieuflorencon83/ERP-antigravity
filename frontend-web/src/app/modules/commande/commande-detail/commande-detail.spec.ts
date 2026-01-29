import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CommandeDetail } from './commande-detail';

describe('CommandeDetail', () => {
  let component: CommandeDetail;
  let fixture: ComponentFixture<CommandeDetail>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CommandeDetail]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CommandeDetail);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
