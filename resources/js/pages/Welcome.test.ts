import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const source = readFileSync(resolve(__dirname, 'Welcome.vue'), 'utf8');

describe('Welcome page copy', () => {
    it('presents admin access as workshop/staff actions', () => {
        expect(source).toContain('Create workshop account');
        expect(source).toContain('Staff login');
        expect(source).toContain('adminLoginUrl');
        expect(source).toContain('adminRegisterUrl');
        expect(source).not.toContain('>Login<');
        expect(source).not.toContain('>Register<');
    });

    it('keeps customer portal access available independently of staff login', () => {
        const primaryNavigation = source.indexOf('<nav aria-label="Primary"');
        const customerPortalLink = source.indexOf(`:href="route('customer-portal.index')"`);
        const conditionalStaffLinks = source.indexOf('<template v-if="canLogin">');

        expect(primaryNavigation).toBeGreaterThan(-1);
        expect(customerPortalLink).toBeGreaterThan(-1);
        expect(primaryNavigation).toBeLessThan(customerPortalLink);
        expect(customerPortalLink).toBeLessThan(conditionalStaffLinks);
        expect(source).not.toContain('<nav v-if="canLogin"');
        expect(source).toContain('My requests');
    });

    it('presents phone verification as a limited customer access preview', () => {
        expect(source).toContain('aria-labelledby="customer-access-preview-title"');
        expect(source).toContain('<h2 id="customer-access-preview-title"');
        expect(source).toContain('Customer access preview</h2>');
        expect(source).toContain('Verify your phone to securely access customer services. No account or password required.');
        expect(source).toContain('Request history is not available yet');
        expect(source).toContain('Verify phone access');
    });

    it('distinguishes a new workshop request from customer portal access', () => {
        expect(source).toContain('Need to send a new request? Use the workshop-specific link provided by your workshop.');
        expect(source).not.toContain('Customer of a workshop?');
    });
});
