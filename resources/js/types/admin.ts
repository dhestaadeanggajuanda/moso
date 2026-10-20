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
