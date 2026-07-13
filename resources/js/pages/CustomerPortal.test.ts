import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const source = (path: string): string => {
    const absolutePath = resolve(__dirname, path);

    return existsSync(absolutePath) ? readFileSync(absolutePath, 'utf8') : '';
};

const layout = source('../layouts/CustomerPortalLayout.vue');
const requestAccess = source('CustomerPortal/RequestAccess.vue');
const verifyCode = source('CustomerPortal/VerifyCode.vue');
const index = source('CustomerPortal/Index.vue');

describe('Customer Portal public pages', () => {
    it('uses the established compact public design shell', () => {
        expect(layout).toContain('bg-[#f7f3ea]');
        expect(layout).toContain('bg-[#2f6471]');
        expect(layout).toContain('max-w-md');
        expect(layout).toContain('border-slate-200');
        expect(layout).toContain('AutoService home');
    });

    it('requests only a phone number without account or record-existence language', () => {
        expect(requestAccess).toContain('Access your service requests');
        expect(requestAccess).toContain('Phone number');
        expect(requestAccess).toContain('autocomplete="tel"');
        expect(requestAccess).toContain('aria-describedby="phone-help phone-error"');
        expect(requestAccess).toContain(':aria-invalid="form.errors.phone ? true : undefined"');
        expect(requestAccess).toContain("route('customer-portal.access.store')");
        expect(requestAccess).toContain('sessionExpired');
        expect(requestAccess).toContain('aria-live="polite"');
        expect(requestAccess).not.toMatch(/customer account|create account|password|no requests|not found/i);
    });

    it('provides an accessible one-time-code step with neutral messaging', () => {
        expect(verifyCode).toContain('Check your phone');
        expect(verifyCode).toContain('maskedPhone');
        expect(verifyCode).toContain('Verification code');
        expect(verifyCode).toContain('autocomplete="one-time-code"');
        expect(verifyCode).toContain('inputmode="numeric"');
        expect(verifyCode).toContain('aria-describedby="code-help code-error"');
        expect(verifyCode).toContain(':aria-invalid="form.errors.code ? true : undefined"');
        expect(verifyCode).toContain('aria-live="polite"');
        expect(verifyCode).toContain("route('customer-portal.verify.store')");
        expect(verifyCode).toContain("route('customer-portal.access.create')");
        expect(verifyCode).not.toMatch(/customer account|password|phone.*not found|no records/i);
    });

    it('shows a privacy-safe verified placeholder without request records', () => {
        expect(index).toContain('Phone verified');
        expect(index).toContain('Your requests');
        expect(index).toContain('Request history is not available yet');
        expect(index).not.toMatch(/bookingRequests|repairOrders|estimates|documents/i);
    });
});
