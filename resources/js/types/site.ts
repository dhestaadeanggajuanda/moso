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
        /** Indeks berita dinas (/berita). */
        news: string;
        services: string;
        /** Daftar Informasi Publik (/ppid). */
        ppid: string;
        /** Dokumen E-SAKIP (/sakip). */
        sakip: string;
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

/** Filter kategori di indeks berita (Site\ArticleController@index). */
export type NewsCategoryFilter = {
    name: string;
    slug: string;
    url: string;
};

export type PpidCategoryValue = 'berkala' | 'serta_merta' | 'setiap_saat';

/** Dokumen PPID terbit (PublicDocument::toPublicSummary). Tanpa path storage. */
export type PublicDocumentItem = {
    slug: string;
    title: string;
    description: string | null;
    category: { value: PpidCategoryValue; label: string };
    year: number | null;
    /** Ukuran berkas dalam byte. */
    file_size: number;
    published_at: string | null;
    download_url: string;
};

/** Tab kategori PPID beserta jumlah dokumen terbitnya. */
export type PpidCategoryTab = {
    value: PpidCategoryValue;
    label: string;
    description: string;
    count: number;
    url: string;
};

/** Dokumen E-SAKIP terbit (ESakip::toPublicSummary). Tanpa path storage. */
export type SakipDocumentItem = {
    id: number;
    title: string;
    type: { value: string; label: string };
    year: number;
    /** Ukuran berkas dalam byte. */
    file_size: number;
    published_at: string | null;
    download_url: string;
};

export type SakipYearGroup = {
    year: number;
    documents: SakipDocumentItem[];
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
