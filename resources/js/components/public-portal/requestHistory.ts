export type CustomerRequestGroup = 'Today' | 'Last 7 days' | 'Earlier';

export const requestGroup = (submittedAt: string, now = new Date()): CustomerRequestGroup => {
    const submitted = new Date(submittedAt);
    const startToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const startSubmitted = new Date(submitted.getFullYear(), submitted.getMonth(), submitted.getDate());
    const ageDays = Math.floor((startToday.getTime() - startSubmitted.getTime()) / 86_400_000);

    if (ageDays <= 0) {
        return 'Today';
    }

    if (ageDays <= 6) {
        return 'Last 7 days';
    }

    return 'Earlier';
};
