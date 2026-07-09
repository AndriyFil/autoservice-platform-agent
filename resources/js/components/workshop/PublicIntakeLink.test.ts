import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

const source = readFileSync(resolve(__dirname, 'PublicIntakeLink.vue'), 'utf8');

describe('Public intake link copy', () => {
    it('describes the URL as customer-facing', () => {
        expect(source).toContain('Public customer intake link');
        expect(source).toContain('Share this URL with customers');
    });
});
