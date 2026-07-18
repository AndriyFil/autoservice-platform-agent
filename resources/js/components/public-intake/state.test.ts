import { describe, expect, it, vi } from 'vitest';
import type { PublicIntakePayload } from './types';

const state = await import('./state').catch(() => null);

const payload = (): PublicIntakePayload => ({
    message: 'Brakes make noise when stopping',
    phone: '+380 67 123 45 67',
    customer_name: 'Olena',
    vehicle: {
        brand: 'Opel',
        model: 'Insignia',
        year: 2017,
        license_plate: 'AA 1234 BB',
    },
    workshop_id: 42,
    website: '',
});

describe('public intake conversation state', () => {
    it('advances through the required conversational questions', () => {
        const entered = payload();

        expect(state?.advanceConversation('problem', entered, true)).toEqual({ state: 'phone' });
        expect(state?.advanceConversation('phone', entered, true)).toEqual({ state: 'optional-details' });
        expect(state?.advanceConversation('workshop', entered, true)).toEqual({ state: 'confirmation' });
    });

    it('returns each optional editor to the optional menu before continuing', () => {
        expect(state?.beginEdit('name', 'optional-details')).toEqual({ answer: 'name', returnState: 'optional-details' });
        expect(state?.beginEdit('vehicle', 'optional-details')).toEqual({ answer: 'vehicle', returnState: 'optional-details' });
        expect(state?.finishOptionalDetails()).toBe('workshop');
    });

    it('keeps the active response control matched to the conversation state', () => {
        expect(state?.responseControlFor('problem')).toBe('problem-composer');
        expect(state?.responseControlFor('phone')).toBe('phone-input');
        expect(state?.responseControlFor('optional-details')).toBe('optional-actions');
        expect(state?.responseControlFor('name')).toBe('name-input');
        expect(state?.responseControlFor('vehicle')).toBe('vehicle-inputs');
        expect(state?.responseControlFor('workshop')).toBe('workshop-cards');
        expect(state?.responseControlFor('confirmation')).toBe('send-action');
        expect(state?.responseControlFor('success')).toBe('none');
    });

    it('validates required answers without mutating preserved form values', () => {
        const entered = payload();
        const snapshot = structuredClone(entered);

        expect(state?.advanceConversation('problem', { ...entered, message: '' }, true)).toEqual({
            state: 'problem',
            error: { field: 'message', message: 'Tell us what is happening with your car.' },
        });
        expect(state?.advanceConversation('phone', { ...entered, phone: '' }, true)).toEqual({
            state: 'phone',
            error: { field: 'phone', message: 'Add a phone number so the workshop can contact you.' },
        });
        expect(state?.advanceConversation('workshop', { ...entered, workshop_id: null }, true)).toEqual({
            state: 'workshop',
            error: { field: 'workshop_id', message: 'Choose a workshop before continuing.' },
        });
        expect(entered).toEqual(snapshot);
    });

    it('submits only from confirmation and only while idle', () => {
        const post = vi.fn();

        expect(state?.submitOnConfirmation('workshop', false, post)).toBe(false);
        expect(state?.submitOnConfirmation('confirmation', true, post)).toBe(false);
        expect(state?.submitOnConfirmation('confirmation', false, post)).toBe(true);
        expect(post).toHaveBeenCalledTimes(1);
    });

    it('routes server errors to the matching conversational control', () => {
        expect(state?.stateForServerErrors({ message: 'Required' })).toBe('problem');
        expect(state?.stateForServerErrors({ phone: 'Invalid' })).toBe('phone');
        expect(state?.stateForServerErrors({ customer_name: 'Invalid' })).toBe('name');
        expect(state?.stateForServerErrors({ 'vehicle.year': 'Invalid' })).toBe('vehicle');
        expect(state?.stateForServerErrors({ workshop_id: 'Required' })).toBe('workshop');
    });

    it('opens the matching editor for every answer without changing the payload', () => {
        const entered = payload();
        const snapshot = structuredClone(entered);

        expect(state?.editorStateFor('problem')).toBe('problem');
        expect(state?.editorStateFor('phone')).toBe('phone');
        expect(state?.editorStateFor('name')).toBe('name');
        expect(state?.editorStateFor('vehicle')).toBe('vehicle');
        expect(state?.editorStateFor('optional-details')).toBe('optional-details');
        expect(state?.editorStateFor('workshop')).toBe('workshop');
        expect(entered).toEqual(snapshot);
    });

    it('keeps the state that was active when an earlier answer is edited', () => {
        const edit = state?.beginEdit('problem', 'phone');

        expect(edit).toEqual({ answer: 'problem', returnState: 'phone' });
        expect(state?.activeStateFor('phone', edit ?? null)).toBe('problem');
        expect(state?.returnStateFor(edit!)).toBe('phone');
    });

    it('returns edits opened from confirmation to confirmation', () => {
        const edit = state?.beginEdit('phone', 'confirmation');

        expect(state?.activeStateFor('confirmation', edit ?? null)).toBe('phone');
        expect(state?.returnStateFor(edit!)).toBe('confirmation');
    });

    it('does not use the active editor to reveal future exchanges', () => {
        expect(state?.completedConversationFor('phone')).toEqual({
            problem: true,
            phone: false,
            optionalDetails: false,
            workshop: false,
            confirmation: false,
        });
    });
});
