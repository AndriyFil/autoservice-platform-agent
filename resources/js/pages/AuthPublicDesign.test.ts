import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const source = (path: string): string => {
    const absolutePath = resolve(__dirname, path);

    return existsSync(absolutePath) ? readFileSync(absolutePath, 'utf8') : '';
};

const layout = source('../layouts/auth/AuthSimpleLayout.vue');
const login = source('auth/Login.vue');
const register = source('auth/Register.vue');
const forgot = source('auth/ForgotPassword.vue');
const reset = source('auth/ResetPassword.vue');
const verify = source('auth/VerifyEmail.vue');
const confirm = source('auth/ConfirmPassword.vue');
const textLink = source('../components/TextLink.vue');
const authPages = [login, register, forgot, reset, verify, confirm];

describe('public staff authentication design', () => {
    it('uses the same calm public shell as the customer journey', () => {
        expect(layout).toContain('PublicBrand');
        expect(layout).toContain('public-page');
        expect(layout).toContain('public-card');
        expect(layout).toContain('Workshop access');
        expect(layout).toContain('rounded-[1.75rem]');
    });

    it('shares premium fields and actions across every reachable auth page', () => {
        for (const page of authPages) {
            expect(page).toContain('public-button-primary');
        }

        for (const page of [login, register, forgot, reset, confirm]) {
            expect(page).toContain('public-field');
        }

        expect(login).toContain('Workshop staff');
        expect(register).toContain('Workshop account');
    });

    it('preserves the existing authentication routes and form contracts', () => {
        expect(login).toContain("route('login')");
        expect(login).toContain("route('password.request')");
        expect(login).toContain('remember');
        expect(register).toContain("route('register')");
        expect(register).toContain('password_confirmation');
        expect(forgot).toContain("route('password.email')");
        expect(reset).toContain("route('password.store')");
        expect(verify).toContain("route('verification.send')");
        expect(confirm).toContain("route('password.confirm')");
    });

    it('keeps authentication links readable on the light public card', () => {
        expect(textLink).toContain('text-[#0e7c86]');
        expect(textLink).toContain('hover:text-[#0b1f33]');
        expect(textLink).not.toContain('text-foreground');
    });
});
