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

    it('directs workshop customers to their workshop-provided booking link', () => {
        expect(source).toContain('Customer of a workshop?');
        expect(source).toContain('Use the booking link provided by your workshop.');
    });
});
