import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

@Component({
    selector: 'app-library',
    standalone: true,
    imports: [RouterOutlet],
    template: `<router-outlet></router-outlet>`
})
export class LibraryComponent { }
