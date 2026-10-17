<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    ChartColumn,
    ClipboardList,
    ExternalLink,
    FileText,
    FolderOpen,
    LayoutGrid,
    ListTree,
    Newspaper,
    Settings2,
    Tags,
} from '@lucide/vue';
import { computed } from 'vue';
import AgendaController from '@/actions/App/Http/Controllers/Admin/AgendaController';
import ArticleController from '@/actions/App/Http/Controllers/Admin/ArticleController';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import MenuController from '@/actions/App/Http/Controllers/Admin/MenuController';
import PageController from '@/actions/App/Http/Controllers/Admin/PageController';
import PublicDocumentController from '@/actions/App/Http/Controllers/Admin/PublicDocumentController';
import SakipController from '@/actions/App/Http/Controllers/Admin/SakipController';
import ServiceController from '@/actions/App/Http/Controllers/Admin/ServiceController';
import SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();

// Menu disaring sesuai hak akses dari server; aksesnya sendiri tetap dicek oleh Policy.
const mainNavItems = computed<NavItem[]>(() => {
    const can = page.props.auth.can;

    const entries: [boolean | undefined, NavItem][] = [
        [true, { title: 'Dashboard', href: dashboard(), icon: LayoutGrid }],
        [
            can.articles,
            {
                title: 'Artikel',
                href: ArticleController.index(),
                icon: Newspaper,
            },
        ],
        [
            can.categories,
            { title: 'Kategori', href: CategoryController.index(), icon: Tags },
        ],
        [
            can.pages,
            { title: 'Halaman', href: PageController.index(), icon: FileText },
        ],
        [
            can.agendas,
            {
                title: 'Agenda',
                href: AgendaController.index(),
                icon: CalendarDays,
            },
        ],
        [
            can.ppid,
            {
                title: 'Dokumen PPID',
                href: PublicDocumentController.index(),
                icon: FolderOpen,
            },
        ],
        [
            can.services,
            {
                title: 'Layanan',
                href: ServiceController.index(),
                icon: ClipboardList,
            },
        ],
        [
            can.sakip,
            {
                title: 'E-SAKIP',
                href: SakipController.index(),
                icon: ChartColumn,
            },
        ],
        [
            can.menus,
            { title: 'Menu', href: MenuController.index(), icon: ListTree },
        ],
        [
            can.settings,
            {
                title: 'Pengaturan situs',
                href: SettingController.index(),
                icon: Settings2,
            },
        ],
    ];

    return entries.filter(([visible]) => visible).map(([, item]) => item);
});

const footerNavItems: NavItem[] = [
    { title: 'Lihat situs', href: home(), icon: ExternalLink },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
