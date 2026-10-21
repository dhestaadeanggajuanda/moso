export type Option<T = string> = {
    value: T;
    label: string;
};

/** Peran akun (RoleName::options()); label sama dengan nilai. */
export type RoleOption = Option & {
    description: string;
};

/** Identitas SSO yang tertaut ke akun pegawai. */
export type SsoIdentityRow = {
    id: number;
    provider: string;
    last_login_at: string | null;
    created_at: string | null;
};

export type StatusValue = {
    value: string;
    label: string;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/**
 * Bentuk JSON LengthAwarePaginator Laravel.
 */
export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type CategoryOption = {
    id: number;
    name: string;
};

export type MenuNode = {
    id: number;
    label: string;
    href: string | null;
    is_active: boolean;
    open_in_new_tab: boolean;
    sort_order: number;
    children: MenuNode[];
};

/** Aksi alur persetujuan yang boleh dipakai pengguna (dihitung ArticlePolicy di server). */
export type WorkflowAction = {
    value:
        | 'submit'
        | 'approve'
        | 'request_revision'
        | 'publish'
        | 'archive'
        | 'unarchive';
    label: string;
    requires_note: boolean;
    allows_schedule: boolean;
};

/** Entri riwayat artikel: komentar atau perubahan status. Teks polos. */
export type ArticleHistoryEntry = {
    id: number;
    type: 'comment' | 'status_change';
    from: StatusValue | null;
    to: StatusValue | null;
    body: string | null;
    user: string;
    created_at: string | null;
};

/** Pengaturan tombol "Tulis dengan AI" di form artikel. */
export type AiDraftOptions = {
    enabled: boolean;
    kinds: Option[];
};

/** Hasil draf asisten AI. `body_html` sudah disanitasi server (HtmlSanitizer); teks lain teks polos. */
export type AiDraftResult = {
    title_suggestion: string | null;
    excerpt: string | null;
    body_html: string;
    image_alt: string | null;
    needs_confirmation: { item: string; question: string }[];
    category_ids: number[];
    word_count: number;
};

export type AiDraftStatus = {
    id: string;
    status: 'queued' | 'processing' | 'done' | 'failed';
    stage: 'observing' | 'recalling' | 'writing' | 'reviewing' | null;
    error?: string | null;
    result?: AiDraftResult | null;
};

/** Profil dinas di asisten AI (moso-agentic). */
export type AssistantProfile = {
    official_name: string;
    short_name: string;
    duties: string;
    officials: { name: string; position: string }[];
    programs: string[];
    address: string;
    contact: string;
    style_guide: string;
    updated_at: string | null;
};

/** Aturan gaya tulisan hasil belajar asisten AI. */
export type AssistantStyleNotes = {
    notes: string[];
    locked: boolean;
    sample_count: number;
    updated_at: string | null;
};

export type AssistantStats = {
    indexed_articles: number;
    drafts_30d: number;
    last_draft_at: string | null;
};

/** Isian yang dipakai form artikel dari draf AI. Field tanpa nilai berarti tidak diubah. */
export type AiDraftApplication = {
    draftId: string;
    body: string;
    excerpt?: string;
    imageAlt?: string;
    title?: string;
    categoryIds: number[];
};
