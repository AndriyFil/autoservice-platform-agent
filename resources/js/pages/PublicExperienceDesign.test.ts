import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const source = (path: string): string => {
    const absolutePath = resolve(__dirname, path);

    return existsSync(absolutePath) ? readFileSync(absolutePath, 'utf8') : '';
};

const css = source('../../css/app.css');
const brand = source('../components/public/PublicBrand.vue');
const workspace = source('../layouts/PublicWorkspaceLayout.vue');

describe('shared public experience design', () => {
    it('defines an isolated premium public color and component system', () => {
        expect(css).toContain('--public-ivory: #f7f4ed');
        expect(css).toContain('--public-surface: #ffffff');
        expect(css).toContain('--public-navy: #0b1f33');
        expect(css).toContain('--public-brand: #0e7c86');
        expect(css).toContain('.public-page');
        expect(css).toContain('.public-card');
        expect(css).toContain('.public-field');
        expect(css).toContain('.public-button-primary');
        expect(css).toContain('.public-button-secondary');
        expect(css).not.toContain('linear-gradient');
    });

    it('keeps public fields light when generic dark-theme input utilities are present', () => {
        expect(css).toContain('.public-page .public-field {');
        expect(css).toContain('.public-page .public-field::placeholder');
        expect(css).toContain('.public-page .public-field:focus');
    });

    it('uses one recognizable AutoService identity across public surfaces', () => {
        expect(brand).toContain('AutoService');
        expect(brand).toContain('Wrench');
        expect(brand).toContain('bg-[#0e7c86]');
        expect(brand).toContain('Your car. Our care.');
    });

    it('keeps all public destinations accessible in a responsive workspace', () => {
        expect(workspace).toContain('PublicBrand');
        expect(workspace).toContain('New request');
        expect(workspace).toContain('My requests');
        expect(workspace).toContain('Staff login');
        expect(workspace).toContain('For workshops');
        expect(workspace).toContain("route('customer-portal.index')");
        expect(workspace).toContain('adminLoginUrl');
        expect(workspace).toContain('adminRegisterUrl');
        expect(workspace).toContain('data-testid="desktop-sidebar"');
        expect(workspace).toContain('aria-label="Open navigation"');
    });
});
