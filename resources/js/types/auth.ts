export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

/**
 * Hak akses untuk menampilkan menu. Otorisasi sebenarnya tetap di server (Policy).
 */
export type AdminAbilities = {
    articles: boolean;
    categories: boolean;
    pages: boolean;
    agendas: boolean;
    ppid: boolean;
    services: boolean;
    sakip: boolean;
    menus: boolean;
    settings: boolean;
    /** Manajemen akun pegawai: hanya Super Admin. */
    users: boolean;
    /** Halaman Asisten AI: Super Admin dan AGENTIC_ENABLED=true. */
    assistant: boolean;
};

export type Auth = {
    user: User;
    roles: string[];
    can: Partial<AdminAbilities>;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
