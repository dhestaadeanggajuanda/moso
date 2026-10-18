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
    <footer id="kontak" class="bg-brand-950 text-brand-100">
        <div
            class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-12 lg:gap-12 lg:px-8 lg:py-16"
        >
            <div class="lg:col-span-5">
                <SiteBrand :site="site" size="lg" />
                <p
                    v-if="site.description"
                    class="text-brand-200 mt-5 max-w-md leading-relaxed"
                >
                    {{ site.description }}
                </p>
                <p
                    v-if="site.contact.address"
                    class="mt-5 flex max-w-md gap-3 leading-relaxed"
                >
                    <MapPin
                        class="text-brand-300 mt-0.5 size-5 flex-none"
                        aria-hidden="true"
                    />
                    <span>{{ site.contact.address }}</span>
                </p>
            </div>

            <div class="lg:col-span-3">
                <h2 v-if="hasContact" class="text-base font-bold text-white">
                    Hubungi kami
                </h2>
                <ul v-if="hasContact" class="mt-4 mb-8 grid gap-1">
                    <li v-if="phoneHref">
                        <a
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
                    </li>
                    <li v-if="site.contact.email">
                        <a
                            :href="`mailto:${site.contact.email}`"
                            class="flex min-h-11 items-center gap-3 break-all hover:text-white"
                        >
                            <Mail
                                class="text-brand-300 size-5 flex-none"
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
                            class="text-brand-300 size-5 flex-none"
                            aria-hidden="true"
                        />
                        <span>
                            <span class="sr-only">Jam layanan: </span>
                            {{ site.contact.hours }}
                        </span>
                    </li>
                </ul>

                <template v-if="site.menus.footer.length">
                    <h2 class="text-base font-bold text-white">Tautan</h2>
                    <ul class="mt-4 grid gap-1">
                        <li
                            v-for="(link, index) in site.menus.footer"
                            :key="`${index}-${link.label}`"
                        >
                            <SiteLink
                                v-if="link.href"
                                :href="link.href"
                                :new-tab="link.open_in_new_tab"
                                class="inline-flex min-h-10 items-center hover:text-white hover:underline"
                            >
                                {{ link.label }}
                            </SiteLink>
                        </li>
                    </ul>
                </template>
            </div>

            <div class="md:col-span-2 lg:col-span-4">
                <div
                    v-if="site.contact.complaint_url"
                    class="rounded-card bg-white/5 p-6 ring-1 ring-white/10"
                >
                    <Megaphone
                        class="text-brand-300 size-7"
                        aria-hidden="true"
                    />
                    <h2 class="mt-4 text-lg font-bold text-white">
                        Punya keluhan atau saran?
                    </h2>
                    <p class="text-brand-200 mt-2 leading-relaxed">
                        Sampaikan pengaduan atau saran tentang pelayanan kami
                        melalui kanal pengaduan resmi.
                    </p>
                    <SiteLink
                        :href="site.contact.complaint_url"
                        new-tab
                        class="bg-gold-400 text-brand-950 hover:bg-gold-300 mt-5 inline-flex h-11 items-center gap-2 rounded-full px-5 font-bold transition-colors"
                    >
                        Sampaikan pengaduan
                        <ExternalLink class="size-4" aria-hidden="true" />
                    </SiteLink>
                </div>

                <template v-if="socialLinks.length">
                    <h2 class="mt-8 text-base font-bold text-white">
                        Media sosial
                    </h2>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        <li v-for="social in socialLinks" :key="social.label">
                            <SiteLink
                                :href="social.href"
                                new-tab
                                class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 ring-1 ring-white/20 transition-colors hover:bg-white/10 hover:text-white"
                            >
                                {{ social.label }}
                            </SiteLink>
                        </li>
                    </ul>
                </template>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div
                class="text-brand-200 mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-sm sm:flex-row sm:justify-between sm:px-6 lg:px-8"
            >
                <p>© {{ year }} {{ site.name }}</p>
                <p>Pemerintah Kota Singkawang</p>
            </div>
        </div>
    </footer>
</template>
