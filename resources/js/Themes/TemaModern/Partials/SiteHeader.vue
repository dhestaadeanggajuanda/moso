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
        <div class="bg-brand-950 text-brand-100">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2 text-xs sm:px-6 sm:text-sm lg:px-8"
            >
                <p class="flex items-center gap-2">
                    <Landmark
                        class="text-gold-300 size-4 flex-none"
                        aria-hidden="true"
                    />
                    Situs resmi Pemerintah Kota Singkawang
                </p>
                <div class="hidden items-center gap-5 md:flex">
                    <a
                        v-if="phoneHref"
                        :href="phoneHref"
                        class="inline-flex items-center gap-1.5 hover:text-white"
                    >
                        <Phone class="size-4" aria-hidden="true" />
                        <span class="tabular-nums">{{
                            site.contact.phone
                        }}</span>
                    </a>
                    <a
                        v-if="site.contact.email"
                        :href="`mailto:${site.contact.email}`"
                        class="inline-flex items-center gap-1.5 hover:text-white"
                    >
                        <Mail class="size-4" aria-hidden="true" />
                        {{ site.contact.email }}
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-brand-900 text-white">
            <div
                class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8 lg:py-4"
            >
                <SiteBrand :site="site" class="mr-auto" />

                <!-- Menu desktop -->
                <nav
                    ref="desktopNav"
                    aria-label="Menu utama"
                    class="hidden lg:block"
                >
                    <ul class="flex items-center gap-0.5">
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
                                    class="text-brand-50 inline-flex min-h-11 items-center gap-1 rounded-lg px-3 text-[0.95rem] font-semibold transition-colors hover:bg-white/10 hover:text-white"
                                    :class="
                                        containsCurrent(link)
                                            ? 'decoration-gold-400 text-white underline decoration-2 underline-offset-8'
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
                                    class="rounded-card bg-canvas text-ink shadow-raised ring-line absolute top-full left-0 mt-2 w-72 p-2 ring-1"
                                >
                                    <ul>
                                        <li v-if="link.href">
                                            <SiteLink
                                                :href="link.href"
                                                :new-tab="link.open_in_new_tab"
                                                :current="isCurrent(link)"
                                                class="text-brand-900 hover:bg-brand-50 aria-[current=page]:bg-brand-50 block rounded-lg px-3 py-2.5 font-bold"
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
                                                class="text-ink hover:bg-brand-50 hover:text-brand-800 aria-[current=page]:bg-brand-50 aria-[current=page]:text-brand-800 block rounded-lg px-3 py-2.5 font-semibold"
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
                                                class="border-line mb-1 ml-4 border-l pl-2"
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
                                                        class="text-ink hover:bg-brand-50 hover:text-brand-800 aria-[current=page]:text-brand-800 block rounded-lg px-3 py-2 text-[0.95rem] aria-[current=page]:font-semibold"
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
                                class="text-brand-50 aria-[current=page]:decoration-gold-400 inline-flex min-h-11 items-center rounded-lg px-3 text-[0.95rem] font-semibold transition-colors hover:bg-white/10 hover:text-white aria-[current=page]:text-white aria-[current=page]:underline aria-[current=page]:decoration-2 aria-[current=page]:underline-offset-8"
                            >
                                {{ link.label }}
                            </SiteLink>
                        </li>
                    </ul>
                </nav>

                <a
                    :href="site.links.services"
                    class="bg-gold-400 text-brand-950 hover:bg-gold-300 hidden h-11 flex-none items-center gap-2 rounded-full px-5 font-bold transition-colors lg:inline-flex"
                >
                    <Search class="size-4" aria-hidden="true" />
                    Cari layanan
                </a>

                <button
                    ref="menuButton"
                    type="button"
                    class="inline-flex size-11 flex-none items-center justify-center rounded-xl bg-white/10 text-white ring-1 ring-white/15 transition-colors hover:bg-white/15 lg:hidden"
                    :aria-expanded="mobileOpen"
                    aria-controls="menu-ponsel"
                    @click="mobileOpen = !mobileOpen"
                >
                    <X v-if="mobileOpen" class="size-6" aria-hidden="true" />
                    <Menu v-else class="size-6" aria-hidden="true" />
                    <span class="sr-only">Menu</span>
                </button>
            </div>

            <!-- Menu ponsel -->
            <div
                v-show="mobileOpen"
                id="menu-ponsel"
                class="border-t border-white/10 lg:hidden"
            >
                <nav
                    aria-label="Menu utama"
                    class="mx-auto max-w-7xl px-4 pt-4 pb-6 sm:px-6"
                >
                    <a
                        :href="site.links.services"
                        class="bg-gold-400 text-brand-950 hover:bg-gold-300 flex h-12 w-full items-center justify-center gap-2 rounded-full font-bold transition-colors"
                    >
                        <Search class="size-5" aria-hidden="true" />
                        Cari layanan
                    </a>

                    <ul class="mt-3 divide-y divide-white/10">
                        <li
                            v-for="(link, index) in site.menus.header"
                            :key="`${index}-${link.label}`"
                        >
                            <details v-if="link.children.length" class="group">
                                <summary
                                    class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 py-2 text-base font-semibold [&::-webkit-details-marker]:hidden"
                                >
                                    {{ link.label }}
                                    <ChevronDown
                                        class="text-brand-200 size-5 flex-none transition-transform duration-200 group-open:rotate-180"
                                        aria-hidden="true"
                                    />
                                </summary>
                                <ul class="pb-3">
                                    <li v-if="link.href">
                                        <SiteLink
                                            :href="link.href"
                                            :new-tab="link.open_in_new_tab"
                                            :current="isCurrent(link)"
                                            class="text-brand-50 flex min-h-11 items-center rounded-lg px-3 font-semibold hover:bg-white/10 aria-[current=page]:bg-white/10"
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
                                            class="text-brand-100 flex min-h-11 items-center rounded-lg px-3 hover:bg-white/10 hover:text-white aria-[current=page]:bg-white/10 aria-[current=page]:font-semibold aria-[current=page]:text-white"
                                        >
                                            {{ child.label }}
                                        </SiteLink>
                                        <span
                                            v-else
                                            class="text-brand-200 block px-3 pt-3 pb-1 text-sm font-bold"
                                        >
                                            {{ child.label }}
                                        </span>
                                        <ul
                                            v-if="child.children.length"
                                            class="ml-4 border-l border-white/15 pl-2"
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
                                                        isCurrent(grandchild)
                                                    "
                                                    class="text-brand-100 flex min-h-11 items-center rounded-lg px-3 text-[0.95rem] hover:bg-white/10 hover:text-white aria-[current=page]:font-semibold aria-[current=page]:text-white"
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
                                class="aria-[current=page]:text-gold-300 flex min-h-12 items-center py-2 text-base font-semibold"
                            >
                                {{ link.label }}
                            </SiteLink>
                        </li>
                    </ul>

                    <div
                        class="text-brand-100 mt-3 grid gap-1 border-t border-white/10 pt-4 text-[0.95rem]"
                    >
                        <a
                            v-if="phoneHref"
                            :href="phoneHref"
                            class="flex min-h-11 items-center gap-3 hover:text-white"
                        >
                            <Phone
                                class="text-brand-300 size-5"
                                aria-hidden="true"
                            />
                            <span class="tabular-nums">{{
                                site.contact.phone
                            }}</span>
                        </a>
                        <a
                            v-if="site.contact.email"
                            :href="`mailto:${site.contact.email}`"
                            class="flex min-h-11 items-center gap-3 break-all hover:text-white"
                        >
                            <Mail
                                class="text-brand-300 size-5 flex-none"
                                aria-hidden="true"
                            />
                            {{ site.contact.email }}
                        </a>
                        <SiteLink
                            v-if="site.contact.complaint_url"
                            :href="site.contact.complaint_url"
                            new-tab
                            class="flex min-h-11 items-center gap-3 font-semibold text-white hover:underline"
                        >
                            <Megaphone
                                class="text-brand-300 size-5"
                                aria-hidden="true"
                            />
                            Sampaikan pengaduan
                        </SiteLink>
                    </div>
                </nav>
            </div>
        </div>
    </header>
</template>
