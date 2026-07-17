// @vitest-environment jsdom

import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const readSource = (path: string) => {
    const absolutePath = resolve(__dirname, path);

    return existsSync(absolutePath) ? readFileSync(absolutePath, 'utf8') : '';
};

const welcomeSource = readSource('Welcome.vue');
const flowSource = readSource('../components/public-intake/PublicIntakeFlow.vue');
const successSource = readSource('../components/public-intake/PublicIntakeSuccess.vue');
const layoutSource = readSource('../layouts/PublicWorkspaceLayout.vue');

describe('global customer intake', () => {
    it('uses one stable public workspace composition', () => {
        expect(welcomeSource).toContain('PublicWorkspaceLayout');
        expect(welcomeSource).not.toContain('PublicHeader');
        expect(welcomeSource).not.toContain('PublicTrustPanel');
        expect(welcomeSource).not.toContain('intakeExpanded');
        expect(welcomeSource).not.toContain('lg:grid-cols-[minmax(0,1fr)_18rem]');
    });

    it('keeps customer and staff actions in the workspace navigation', () => {
        expect(layoutSource).toContain('My requests');
        expect(layoutSource).toContain('Staff login');
        expect(layoutSource).toContain('For workshops');
        expect(layoutSource).toContain(`route('customer-portal.index')`);
        expect(layoutSource).toContain('adminLoginUrl');
        expect(layoutSource).toContain('adminRegisterUrl');
    });

    it('uses the shared public shell and truthful intake controls', () => {
        expect(welcomeSource).toContain('PublicWorkspaceLayout');
        expect(flowSource).toContain('public-button-primary');
        expect(flowSource).toContain('border-[#0e7c86]');
        expect(flowSource).toContain('bg-[#e9f3f2]');
        expect(flowSource).toContain('sm:grid-cols-2');
        expect(flowSource).not.toMatch(/rating|available today|reply within/i);
    });

    it('keeps the intake in one responsive public card', () => {
        expect(flowSource).toContain('data-testid="intake-chat"');
        expect(flowSource).toContain('data-testid="intake-starter"');
        expect(flowSource).toContain('data-testid="intake-workspace"');
        expect(flowSource).toContain('data-testid="intake-transcript"');
        expect(flowSource).toContain("'intake-composer'");
        expect(flowSource).not.toMatch(/Step \d of \d/);
        expect(flowSource).not.toContain('Request progress');
        expect(flowSource).not.toContain('router.visit');
        expect(flowSource).not.toContain('window.location');
    });

    it('shows the workshop-specific success state and follow-up actions', () => {
        expect(welcomeSource).toContain('intakeWorkshopName?: string');
        expect(successSource).toContain('Request sent');
        expect(successSource).toContain('workshopName');
        expect(successSource).toContain('will contact you');
        expect(successSource).toContain('My requests');
        expect(successSource).toContain('Create another request');
    });
});
