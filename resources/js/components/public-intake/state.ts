import type { PublicIntakePayload } from './types';

export type ConversationState = 'problem' | 'phone' | 'optional-details' | 'name' | 'vehicle' | 'workshop' | 'confirmation' | 'success';
export type EditableAnswer = Exclude<ConversationState, 'confirmation' | 'success'>;
export type EditContext = {
    answer: EditableAnswer;
    returnState: ConversationState;
};
export type CompletedConversation = {
    problem: boolean;
    phone: boolean;
    optionalDetails: boolean;
    workshop: boolean;
    confirmation: boolean;
};

export type IntakeErrors = Record<string, string>;
export type RequiredField = 'message' | 'phone' | 'workshop_id';
export type ConversationError = { field: RequiredField; message: string };
export type ResponseControl =
    | 'problem-composer'
    | 'phone-input'
    | 'optional-actions'
    | 'name-input'
    | 'vehicle-inputs'
    | 'workshop-cards'
    | 'send-action'
    | 'none';

const conversationRanks: Record<ConversationState, number> = {
    problem: 0,
    phone: 1,
    'optional-details': 2,
    name: 3,
    vehicle: 3,
    workshop: 4,
    confirmation: 5,
    success: 6,
};

export const advanceConversation = (
    state: ConversationState,
    payload: PublicIntakePayload,
    hasAvailableWorkshops: boolean,
): { state: ConversationState; error?: ConversationError } => {
    if (state === 'problem') {
        return payload.message.trim() === ''
            ? { state, error: { field: 'message', message: 'Tell us what is happening with your car.' } }
            : { state: 'phone' };
    }

    if (state === 'phone') {
        return payload.phone.trim() === ''
            ? { state, error: { field: 'phone', message: 'Add a phone number so the workshop can contact you.' } }
            : { state: 'optional-details' };
    }

    if (state === 'workshop') {
        return !hasAvailableWorkshops || payload.workshop_id === null
            ? { state, error: { field: 'workshop_id', message: 'Choose a workshop before continuing.' } }
            : { state: 'confirmation' };
    }

    return { state };
};

export const editorStateFor = (answer: EditableAnswer): ConversationState => answer;

export const beginEdit = (answer: EditableAnswer, returnState: ConversationState): EditContext => ({ answer, returnState });

export const activeStateFor = (state: ConversationState, edit: EditContext | null): ConversationState => edit?.answer ?? state;

export const returnStateFor = (edit: EditContext): ConversationState => edit.returnState;

export const completedConversationFor = (state: ConversationState): CompletedConversation => {
    const rank = conversationRanks[state];

    return {
        problem: rank >= conversationRanks.phone,
        phone: rank >= conversationRanks['optional-details'],
        optionalDetails: rank >= conversationRanks.workshop,
        workshop: rank >= conversationRanks.confirmation,
        confirmation: state === 'confirmation' || state === 'success',
    };
};

export const finishOptionalDetails = (): ConversationState => 'workshop';

export const responseControlFor = (state: ConversationState): ResponseControl =>
    ({
        problem: 'problem-composer',
        phone: 'phone-input',
        'optional-details': 'optional-actions',
        name: 'name-input',
        vehicle: 'vehicle-inputs',
        workshop: 'workshop-cards',
        confirmation: 'send-action',
        success: 'none',
    })[state] as ResponseControl;

export const stateForServerErrors = (errors: IntakeErrors): ConversationState | null => {
    if (errors.message) return 'problem';
    if (errors.phone) return 'phone';
    if (errors.customer_name) return 'name';
    if (Object.keys(errors).some((field) => field.startsWith('vehicle.'))) return 'vehicle';
    if (errors.workshop_id) return 'workshop';

    return null;
};

export const submitOnConfirmation = (state: ConversationState, processing: boolean, submit: () => void): boolean => {
    if (state !== 'confirmation' || processing) return false;

    submit();

    return true;
};
