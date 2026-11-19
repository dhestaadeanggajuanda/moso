<!--
    PERINGATAN UNTUK TIM MAGANG
    Hanya <template> dan class Tailwind yang boleh diubah.
    <script setup> DILARANG disentuh: props dan logikanya adalah kontrak tema.
-->
<script setup lang="ts">
import {
    Clock,
    ExternalLink,
    Mail,
    MapPin,
    Megaphone,
    Phone,
} from '@lucide/vue';
import { computed } from 'vue';
import type { SiteContext } from '@/types/site';
import SiteBrand from './SiteBrand.vue';
import SiteLink from './SiteLink.vue';

const props = defineProps<{
    site: SiteContext;
}>();

const socialLabels = {
    facebook: 'Facebook',
    instagram: 'Instagram',
    youtube: 'YouTube',
} as const;

const socialLinks = computed(() =>
    (Object.keys(socialLabels) as (keyof typeof socialLabels)[])
        .filter((key) => props.site.social[key])
        .map((key) => ({
            label: socialLabels[key],
            href: props.site.social[key] as string,
        })),
);

const phoneHref = computed(() =>
    props.site.contact.phone
        ? `tel:${props.site.contact.phone.replace(/[^\d+]/g, '')}`
        : null,
);

const hasContact = computed(
    () =>
        !!(
            props.site.contact.phone ||
            props.site.contact.email ||
            props.site.contact.hours
        ),
);

const year = new Date().getFullYear();
</script>

<template>
    <!-- Ruang bawah ekstra di ponsel supaya bilah navigasi tidak menutup isi. -->
    <footer
        id="kontak"
        class="bg-ground-deep text-ink mt-14 rounded-t-[2rem] pb-24 sm:mt-20 lg:pb-0"
    >
        <div
            class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-12 lg:gap-12 lg:px-8 lg:py-16"
        >
            <div class="lg:col-span-5">
                <SiteBrand :site="site" size="lg" />
                <p
                    v-if="site.description"
                    class="text-ink-muted mt-5 max-w-md leading-relaxed"
                >
                    {{ site.description }}
                </p>
                <p
                    v-if="site.contact.address"
                    class="mt-5 flex max-w-md gap-3 leading-relaxed"
                >
                    <MapPin
                        class="text-ink-muted mt-0.5 size-5 flex-none"
                        aria-hidden="true"
                    />
                    <span>{{ site.contact.address }}</span>
                </p>
            </div>

            <div class="lg:col-span-3">
                <h2 v-if="hasContact" class="text-ink text-base font-extrabold">
                    Hubungi kami
                </h2>
                <ul v-if="hasContact" class="mt-4 mb-8 grid gap-1">
                    <li v-if="phoneHref">
                        <a
                            :href="phoneHref"
                            class="hover:text-action flex min-h-11 items-center gap-3 font-semibold hover:underline"
                        >
                            <Phone
                                class="text-ink-muted size-5"
                                aria-hidden="true"
                            />
                            <span class="tabular-nums">{{
                                site.contact.phone
                            }}</span>
                        </a>
                    </li>
                    <li v-if="site.contact.email">
                        <a
                            :href="`mailto:${site.contact.email}`"
                            class="hover:text-action flex min-h-11 items-center gap-3 font-semibold break-all hover:underline"
                        >
                            <Mail
                                class="text-ink-muted size-5 flex-none"
                                aria-hidden="true"
                            />
                            {{ site.contact.email }}
                        </a>
                    </li>
                    <li
                        v-if="site.contact.hours"
                        class="flex min-h-11 items-center gap-3"
                    >
                        <Clock
                            class="text-ink-muted size-5 flex-none"
                            aria-hidden="true"
                        />
                        <span>
                            <span class="sr-only">Jam layanan: </span>
                            {{ site.contact.hours }}
                        </span>
                    </li>
                </ul>

                <template v-if="site.menus.footer.length">
                    <h2 class="text-ink text-base font-extrabold">Tautan</h2>
                    <ul class="mt-4 grid gap-1">
                        <li
                            v-for="(link, index) in site.menus.footer"
                            :key="`${index}-${link.label}`"
                        >
                            <SiteLink
                                v-if="link.href"
                                :href="link.href"
                                :new-tab="link.open_in_new_tab"
                                class="hover:text-action inline-flex min-h-11 items-center font-semibold hover:underline"
                            >
                                {{ link.label }}
                            </SiteLink>
                        </li>
                    </ul>
                </template>
            </div>

            <div class="md:col-span-2 lg:col-span-4">
                <div v-if="site.contact.complaint_url">
                    <span
                        class="text-ink-muted rounded-icon grid size-11 place-items-center bg-card"
                        aria-hidden="true"
                    >
                        <Megaphone class="size-6" />
                    </span>
                    <h2 class="text-ink mt-4 text-lg font-extrabold">
                        Punya keluhan atau saran?
                    </h2>
                    <p class="text-ink-muted mt-2 leading-relaxed">
                        Sampaikan pengaduan atau saran tentang pelayanan kami
                        melalui kanal pengaduan resmi.
                    </p>
                    <SiteLink
                        :href="site.contact.complaint_url"
                        new-tab
                        class="btn-action press mt-5"
                    >
                        Sampaikan pengaduan
                        <ExternalLink class="size-4" aria-hidden="true" />
                    </SiteLink>
                </div>

                <template v-if="socialLinks.length">
                    <h2 class="text-ink mt-8 text-base font-extrabold">
                        Media sosial
                    </h2>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        <li v-for="social in socialLinks" :key="social.label">
                            <SiteLink
                                :href="social.href"
                                new-tab
                                class="btn-quiet press"
                            >
                                {{ social.label }}
                            </SiteLink>
                        </li>
                    </ul>
                </template>
            </div>
        </div>

        <div class="border-line border-t">
            <div
                class="text-ink-muted mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-sm sm:flex-row sm:justify-between sm:px-6 lg:px-8"
            >
                <p>© {{ year }} {{ site.name }}</p>
                <p>Pemerintah Kota Singkawang</p>
            </div>
        </div>
    </footer>
</template>
