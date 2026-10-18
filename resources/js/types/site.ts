import type { ServiceSummary } from '@/types/blocks';

/**
 * Data situs publik yang dikirim ke tema (App\Support\SiteContext).
 */
export type MenuLink = {
    label: string;
    href: string | null;
    open_in_new_tab: boolean;
    children: MenuLink[];
};

export type SiteContext = {
    name: string;
    tagline: string | null;
    description: string | null;
    /** URL logo; null bila belum diatur di Pengaturan. */
    logos: {
        pemkot: string | null;
        dinas: string | null;
    };
    contact: {
        address: string | null;
        phone: string | null;
        email: string | null;
        /** Jam layanan, mis. "Senin–Jumat 07.30–16.00". */
        hours: string | null;
        /** Kanal pengaduan (sudah dicek skemanya di server). */
        complaint_url: string | null;
    };
    social: Partial<Record<'facebook' | 'instagram' | 'youtube', string>>;
    links: {
        home: string;
        services: string;
    };
    menus: {
        header: MenuLink[];
        footer: MenuLink[];
    };
};

export type PublicPage = {
    title: string;
    slug: string;
    meta_description: string | null;
};

export type PublicArticle = {
    title: string;
    slug: string;
    excerpt: string | null;
    /** HTML yang sudah disanitasi server. */
    body: string;
    image_url: string | null;
    image_alt: string | null;
    published_at: string | null;
    author: string;
    categories: { name: string; slug: string }[];
};

/** Detail Standar Pelayanan (Site\ServiceController@show). */
export type PublicService = ServiceSummary & {
    requirements: string[];
    procedure: string[];
    product: string | null;
    complaint_channel: string | null;
    updated_at: string | null;
};

/** Paginator Laravel (LengthAwarePaginator) seperti yang diserialisasi Inertia. */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};
