import 'zone.js';
import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { App } from './app/app';

console.log('Main.ts: Bootstrapping application...');
bootstrapApplication(App, appConfig)
  .catch((err) => console.error('Bootstrap Error:', err));
