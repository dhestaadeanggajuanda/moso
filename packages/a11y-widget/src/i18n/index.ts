import { en } from './en';
import { id } from './id';
import type { Messages } from './id';

export type Language = 'id' | 'en';
export type { Messages };

const MESSAGES: Record<Language, Messages> = { id, en };

/** Bahasa Indonesia sebagai bawaan; kode lain yang tidak dikenal juga jatuh ke id. */
export function messagesFor(language: string | null | undefined): {
    language: Language;
    messages: Messages;
} {
    const normalized: Language = language?.toLowerCase().startsWith('en')
        ? 'en'
        : 'id';

    return { language: normalized, messages: MESSAGES[normalized] };
}
