/**
 * Skema JSON blok Page Builder, dipakai bersama oleh editor (Builder) dan
 * komponen tema (Themes/*). Harus selaras dengan App\Services\PageBuilder\Blocks\*.
 */

export type MediaCenterMode = 'tag' | 'category' | 'search';

export type NewsGridHybridProps = {
    title: string | null;
    limit: number;
    columns: 2 | 3 | 4;
    local_category: string | null;
    fetch_media_center: boolean;
    media_center_keyword: string | null;
    media_center_mode: MediaCenterMode;
};

export type HeroBannerProps = {
    heading: string;
    subheading: string | null;
    cta_label: string | null;
    cta_url: string | null;
    variant: 'primary' | 'muted';
};

export type AgendaKegiatanProps = {
    title: string | null;
    limit: number;
};

export type RichTextProps = {
    /** HTML yang sudah disanitasi server. */
    html: string;
};

export type BlockPropsMap = {
    HeroBanner: HeroBannerProps;
    NewsGridHybrid: NewsGridHybridProps;
    AgendaKegiatan: AgendaKegiatanProps;
    RichText: RichTextProps;
};

export type BlockType = keyof BlockPropsMap;

export type PageBlock<T extends BlockType = BlockType> = {
    id: string;
    type: T;
    props: BlockPropsMap[T];
};

/** Union terdiskriminasi atas `type`. */
export type AnyPageBlock = { [K in BlockType]: PageBlock<K> }[BlockType];

export type BlockCatalogEntry = {
    type: BlockType;
    label: string;
    description: string;
    defaults: BlockPropsMap[BlockType];
};

/** Berita ternormalisasi (App\Services\News\NewsItem). Semua teks polos. */
export type NewsItem = {
    id: string;
    source: 'local' | 'media_center';
    title: string;
    excerpt: string | null;
    url: string;
    image_url: string | null;
    image_alt: string | null;
    published_at: string | null;
};

export type AgendaItem = {
    id: number;
    title: string;
    location: string | null;
    organizer: string | null;
    start_at: string;
    end_at: string | null;
};

/** Data yang disuntikkan BlockHydrator saat render, per tipe blok. */
export type BlockDataMap = {
    HeroBanner: null;
    NewsGridHybrid: { items: NewsItem[] } | null;
    AgendaKegiatan: { items: AgendaItem[] } | null;
    RichText: null;
};

export type HydratedBlock<T extends BlockType = BlockType> = PageBlock<T> & {
    data: BlockDataMap[T];
};
