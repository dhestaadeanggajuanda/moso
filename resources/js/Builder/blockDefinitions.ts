import type { LucideIcon } from '@lucide/vue';
import { CalendarDays, Image, Newspaper, Type } from '@lucide/vue';
import type { AnyPageBlock, BlockType } from '@/types/blocks';

/**
 * Metadata tampilan blok di editor. Nilai bawaan dan aturan validasi tidak
 * didefinisikan di sini: sumbernya BlockRegistry di server.
 */
export const blockIcons: Record<BlockType, LucideIcon> = {
    HeroBanner: Image,
    NewsGridHybrid: Newspaper,
    AgendaKegiatan: CalendarDays,
    RichText: Type,
};

const modeLabels = {
    tag: 'tag',
    category: 'kategori',
    search: 'pencarian',
} as const;

/**
 * Ringkasan satu baris isi blok untuk kanvas editor.
 */
export function blockSummary(block: AnyPageBlock): string {
    switch (block.type) {
        case 'HeroBanner':
            return block.props.heading;
        case 'NewsGridHybrid': {
            const parts = [
                `${block.props.limit} berita`,
                `${block.props.columns} kolom`,
            ];

            if (block.props.local_category) {
                parts.push(`kategori lokal: ${block.props.local_category}`);
            }

            if (block.props.fetch_media_center) {
                parts.push(
                    `Media Center: ${block.props.media_center_keyword ?? '?'} (${modeLabels[block.props.media_center_mode]})`,
                );
            }

            return parts.join(' · ');
        }
        case 'AgendaKegiatan':
            return `${block.props.limit} agenda terdekat`;
        case 'RichText': {
            const text = block.props.html.replace(/<[^>]*>/g, ' ').trim();

            return text ? text.slice(0, 80) : 'Teks kosong';
        }
    }
}
