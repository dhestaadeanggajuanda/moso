<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: logika buka-tutup menu dan aksesibilitas
    keyboard (Esc, fokus kembali ke tombol) ada di sana.
-->
<script setup lang="ts">
import {
    ChevronDown,
    Landmark,
    Mail,
    Megaphone,
    Menu,
    Phone,
    Search,
    X,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { MenuLink, SiteContext } from '@/types/site';
import { pathOf } from './format';
import SiteBrand from './SiteBrand.vue';
import SiteLink from './SiteLink.vue';

const props = defineProps<{
    site: SiteContext;
    currentPath?: string | null;
}>();

const mobileOpen = ref(false);
const openIndex = ref<number | null>(null);
const desktopNav = ref<HTMLElement | null>(null);
const menuButton = ref<HTMLButtonElement | null>(null);

const currentPathname = computed(() =>
    pathOf(props.currentPath, props.site.links.home),
);
const phoneHref = computed(() =>
    props.site.contact.phone
        ? `tel:${props.site.contact.phone.replace(/[^\d+]/g, '')}`
        : null,
);

function isCurrent(link: MenuLink): boolean {
    const path = pathOf(link.href, props.site.links.home);

    return path !== null && path === currentPathname.value;
}

function containsCurrent(link: MenuLink): boolean {
    return link.children.some(
        (child) => isCurrent(child) || containsCurrent(child),
    );
}

function toggleDropdown(index: number): void {
    openIndex.value = openIndex.value === index ? null : index;
}

/** Tutup dropdown saat fokus keyboard keluar dari item menu tersebut. */
function onItemFocusOut(event: FocusEvent, index: number): void {
    const item = event.currentTarget as HTMLElement;

    if (
        openIndex.value === index &&
        !item.contains(event.relatedTarget as Node | null)
    ) {
        openIndex.value = null;
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key !== 'Escape') {
        return;
    }

    if (openIndex.value !== null) {
        const trigger = desktopNav.value?.querySelector<HTMLElement>(
            `[data-menu-trigger="${openIndex.value}"]`,
        );
        openIndex.value = null;
        trigger?.focus();
    } else if (mobileOpen.value) {
        mobileOpen.value = false;
        menuButton.value?.focus();
    }
}

function onDocumentClick(event: MouseEvent): void {
    if (
        openIndex.value !== null &&
        !desktopNav.value?.contains(event.target as Node)
    ) {
        openIndex.value = null;
    }
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    document.removeEventListener('click', onDocumentClick);
});
</script>

<template>
    <header class="relative z-30">
        <!-- Strip situs resmi -->
        <div class="bg-ink text-ground">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-1.5 text-xs sm:px-6 sm:text-sm lg:px-8"
            >
                <p class="flex items-center gap-2">
                    <Landmark class="size-4 flex-none" aria-hidden="true" />
                    Situs resmi Pemerintah Kota Singkawang
                </p>
                <div class="hidden items-center gap-5 md:flex">
                    <a
                        v-if="phoneHref"
                        :href="phoneHref"
                        class="inline-flex items-center gap-1.5 hover:underline"
                    >
                        <Phone class="size-4" aria-hidden="true" />
                        <span class="tabular-nums">{{
                            site.contact.phone
                        }}</span>
                    </a>
                    <a
                        v-if="site.contact.email"
                        :href="`mailto:${site.contact.email}`"
                        class="inline-flex items-center gap-1.5 hover:underline"
                    >
                        <Mail class="size-4" aria-hidden="true" />
                        {{ site.contact.email }}
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-ground">
            <div
                class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:gap-4 lg:px-8 lg:py-4"
            >
                <SiteBrand :site="site" class="mr-auto" />

                <!-- Menu desktop -->
                <nav
                    ref="desktopNav"
                    aria-label="Menu utama"
                    class="hidden lg:block"
                >
                    <ul class="flex items-center gap-1">
                        <li
                            v-for="(link, index) in site.menus.header"
                            :key="`${index}-${link.label}`"
                            class="relative"
                            @focusout="onItemFocusOut($event, index)"
                        >
                            <template v-if="link.children.length">
                                <button
                                    type="button"
                                    :data-menu-trigger="index"
                                    :aria-expanded="openIndex === index"
                                    :aria-controls="`submenu-${index}`"
                                    class="press text-ink hover:bg-ground-deep inline-flex min-h-11 items-center gap-1 rounded-full px-4 text-[0.95rem] font-bold"
                                    :class="
                                        containsCurrent(link)
                                            ? 'text-action shadow-chip bg-card'
                                            : ''
                                    "
                                    @click="toggleDropdown(index)"
                                >
                                    {{ link.label }}
                                    <ChevronDown
                                        class="size-4 transition-transform duration-200"
                                        :class="
                                            openIndex === index
                                                ? 'rotate-180'
                                                : ''
                                        "
                                        aria-hidden="true"
                                    />
                                </button>

                                <div
                                    v-show="openIndex === index"
                                    :id="`submenu-${index}`"
                                    class="rounded-card text-ink shadow-lift absolute top-full left-0 mt-2 w-72 bg-card p-2"
                                >
                                    <ul>
                                        <li v-if="link.href">
                                            <SiteLink
                                                :href="link.href"
                                                :new-tab="link.open_in_new_tab"
                                                :current="isCurrent(link)"
                                                class="text-ink hover:bg-action-50 hover:text-action aria-[current=page]:bg-action-50 aria-[current=page]:text-action rounded-icon block px-3 py-2.5 font-extrabold"
                                            >
                                                {{ link.label }}
                                            </SiteLink>
                                        </li>
                                        <li
                                            v-for="(
                                                child, childIndex
                                            ) in link.children"
                                            :key="`${childIndex}-${child.label}`"
                                        >
                                            <SiteLink
                                                v-if="child.href"
                                                :href="child.href"
                                                :new-tab="child.open_in_new_tab"
                                                :current="isCurrent(child)"
                                                class="text-ink hover:bg-action-50 hover:text-action aria-[current=page]:bg-action-50 aria-[current=page]:text-action rounded-icon block px-3 py-2.5 font-semibold aria-[current=page]:font-bold"
                                            >
                                                {{ child.label }}
                                            </SiteLink>
                                            <span
                                                v-else
                                                class="text-ink-muted block px-3 pt-3 pb-1 text-sm font-bold"
                                            >
                                                {{ child.label }}
                                            </span>
                                            <ul
                                                v-if="child.children.length"
                                                class="border-line mb-1 ml-4 border-l-2 pl-2"
                                            >
                                                <li
                                                    v-for="(
                                                        grandchild, grandIndex
                                                    ) in child.children"
                                                    :key="`${grandIndex}-${grandchild.label}`"
                                                >
                                                    <SiteLink
                                                        v-if="grandchild.href"
                                                        :href="grandchild.href"
                                                        :new-tab="
                                                            grandchild.open_in_new_tab
                                                        "
                                                        :current="
                                                            isCurrent(
                                                                grandchild,
                                                            )
                                                        "
                                                        class="text-ink hover:bg-action-50 hover:text-action aria-[current=page]:text-action rounded-icon block px-3 py-2 text-[0.95rem] aria-[current=page]:font-bold"
                                                    >
                                                        {{ grandchild.label }}
                                                    </SiteLink>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                            </template>

                            <SiteLink
                                v-else-if="link.href"
                                :href="link.href"
                                :new-tab="link.open_in_new_tab"
                                :current="isCurrent(link)"
                                class="press text-ink hover:bg-ground-deep aria-[current=page]:text-action aria-[current=page]:shadow-chip inline-flex min-h-11 items-center rounded-full px-4 text-[0.95rem] font-bold aria-[current=page]:bg-card"
                            >
                                {{ link.label }}
                            </SiteLink>
                        </li>
                    </ul>
                </nav>

                <a
                    :href="site.links.services"
                    class="btn-quiet press hidden h-12 flex-none lg:inline-flex"
                >
                    <Search class="size-4" aria-hidden="true" />
                    Cari layanan
                </a>

                <button
                    ref="menuButton"
                    type="button"
                    class="press text-action shadow-chip inline-flex h-11 flex-none items-center gap-2 rounded-full bg-card pr-4 pl-3 font-extrabold hover:shadow-card lg:hidden"
                    :aria-expanded="mobileOpen"
                    aria-controls="menu-ponsel"
                    @click="mobileOpen = !mobileOpen"
                >
                    <X v-if="mobileOpen" class="size-5" aria-hidden="true" />
                    <Menu v-else class="size-5" aria-hidden="true" />
                    Menu
                </button>
            </div>

            <!-- Menu ponsel -->
            <div v-show="mobileOpen" id="menu-ponsel" class="lg:hidden">
                <nav
                    aria-label="Menu utama"
                    class="mx-auto max-w-7xl px-4 pb-4 sm:px-6"
                >
                    <div class="rounded-card bg-card p-4 shadow-card">
                        <a
                            :href="site.links.services"
                            class="btn-action press w-full"
                        >
                            <Search class="size-5" aria-hidden="true" />
                            Cari layanan
                        </a>

                        <ul class="divide-line mt-3 divide-y">
                            <li
                                v-for="(link, index) in site.menus.header"
                                :key="`${index}-${link.label}`"
                            >
                                <details
                                    v-if="link.children.length"
                                    class="group"
                                >
                                    <summary
                                        class="text-ink flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 py-2 text-base font-bold [&::-webkit-details-marker]:hidden"
                                    >
                                        {{ link.label }}
                                        <ChevronDown
                                            class="text-ink-muted size-5 flex-none transition-transform duration-200 group-open:rotate-180"
                                            aria-hidden="true"
                                        />
                                    </summary>
                                    <ul class="pb-3">
                                        <li v-if="link.href">
                                            <SiteLink
                                                :href="link.href"
                                                :new-tab="link.open_in_new_tab"
                                                :current="isCurrent(link)"
                                                class="text-ink hover:bg-action-50 aria-[current=page]:bg-action-50 aria-[current=page]:text-action rounded-icon flex min-h-11 items-center px-3 font-extrabold"
                                            >
                                                {{ link.label }}
                                            </SiteLink>
                                        </li>
                                        <li
                                            v-for="(
                                                child, childIndex
                                            ) in link.children"
                                            :key="`${childIndex}-${child.label}`"
                                        >
                                            <SiteLink
                                                v-if="child.href"
                                                :href="child.href"
                                                :new-tab="child.open_in_new_tab"
                                                :current="isCurrent(child)"
                                                class="text-ink hover:bg-action-50 aria-[current=page]:bg-action-50 aria-[current=page]:text-action rounded-icon flex min-h-11 items-center px-3 aria-[current=page]:font-bold"
                                            >
                                                {{ child.label }}
                                            </SiteLink>
                                            <span
                                                v-else
                                                class="text-ink-muted block px-3 pt-3 pb-1 text-sm font-bold"
                                            >
                                                {{ child.label }}
                                            </span>
                                            <ul
                                                v-if="child.children.length"
                                                class="border-line ml-4 border-l-2 pl-2"
                                            >
                                                <li
                                                    v-for="(
                                                        grandchild, grandIndex
                                                    ) in child.children"
                                                    :key="`${grandIndex}-${grandchild.label}`"
                                                >
                                                    <SiteLink
                                                        v-if="grandchild.href"
                                                        :href="grandchild.href"
                                                        :new-tab="
                                                            grandchild.open_in_new_tab
                                                        "
                                                        :current="
                                                            isCurrent(
                                                                grandchild,
                                                            )
                                                        "
                                                        class="text-ink hover:bg-action-50 aria-[current=page]:text-action rounded-icon flex min-h-11 items-center px-3 text-[0.95rem] aria-[current=page]:font-bold"
                                                    >
                                                        {{ grandchild.label }}
                                                    </SiteLink>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </details>

                                <SiteLink
                                    v-else-if="link.href"
                                    :href="link.href"
                                    :new-tab="link.open_in_new_tab"
                                    :current="isCurrent(link)"
                                    class="text-ink aria-[current=page]:text-action flex min-h-12 items-center py-2 text-base font-bold"
                                >
                                    {{ link.label }}
                                </SiteLink>
                            </li>
                        </ul>

                        <div
                            class="border-line text-ink mt-3 grid gap-1 border-t pt-3 text-[0.95rem]"
                        >
                            <a
                                v-if="phoneHref"
                                :href="phoneHref"
                                class="hover:text-action flex min-h-11 items-center gap-3"
                            >
                                <Phone
                                    class="text-ink-muted size-5"
                                    aria-hidden="true"
                                />
                                <span class="tabular-nums">{{
                                    site.contact.phone
                                }}</span>
                            </a>
                            <a
                                v-if="site.contact.email"
                                :href="`mailto:${site.contact.email}`"
                                class="hover:text-action flex min-h-11 items-center gap-3 break-all"
                            >
                                <Mail
                                    class="text-ink-muted size-5 flex-none"
                                    aria-hidden="true"
                                />
                                {{ site.contact.email }}
                            </a>
                            <SiteLink
                                v-if="site.contact.complaint_url"
                                :href="site.contact.complaint_url"
                                new-tab
                                class="text-action flex min-h-11 items-center gap-3 font-extrabold hover:underline"
                            >
                                <Megaphone
                                    class="size-5 flex-none"
                                    aria-hidden="true"
                                />
                                Sampaikan pengaduan
                            </SiteLink>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>
</template>
