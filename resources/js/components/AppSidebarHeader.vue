<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';

withDefaults(defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>(), {
    breadcrumbs: () => []
});

const user = usePage().props.auth.user
const rol = usePage().props.auth.user.roles[0].name.charAt(0).toUpperCase() + usePage().props.auth.user.roles[0].name.slice(1);
const permissions = usePage().props.auth.user.roles[0].permissions.map(permission => permission.name);


const open_cerrar_sesion = ref(false);

const handleLogout = () => {
    router.flushAll();
};

</script>
<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-20"
        style="background-color: #272727;">
        <div class="w-full flex items-center justify-between">
            <div class="flex items-center gap-2 text-white">
                <SidebarTrigger v-if="permissions.length > 1" class="-ml-1" />
                <p class="hidden lg:block">
                    Sistema de producción - Teora <span style="color: #757575;">/ {{ rol }}</span></p>
            </div>
            <div @click="open_cerrar_sesion = !open_cerrar_sesion"
                class="flex items-center justify-center gap-2 cursor-pointer relative">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M9.688 2.06545e-07C7.7719 2.06545e-07 5.89882 0.568191 4.30564 1.63272C2.71246 2.69725 1.47072 4.21031 0.737459 5.98056C0.00419766 7.75081 -0.187657 9.69875 0.186157 11.578C0.55997 13.4573 1.48266 15.1836 2.83755 16.5385C4.19244 17.8933 5.91868 18.816 7.79797 19.1898C9.67726 19.5637 11.6252 19.3718 13.3954 18.6385C15.1657 17.9053 16.6788 16.6635 17.7433 15.0704C18.8078 13.4772 19.376 11.6041 19.376 9.688C19.3763 8.41568 19.1259 7.15577 18.6391 5.98025C18.1523 4.80473 17.4387 3.73663 16.539 2.83696C15.6394 1.9373 14.5713 1.22369 13.3958 0.73692C12.2202 0.250146 10.9603 -0.000262534 9.688 2.06545e-07ZM9.688 3.75C10.368 3.75 11.0328 3.95166 11.5982 4.32948C12.1636 4.70729 12.6042 5.2443 12.8644 5.87257C13.1246 6.50084 13.1926 7.19215 13.0599 7.85909C12.9271 8.52602 12.5996 9.13861 12.1187 9.61939C11.6378 10.1002 11.0251 10.4275 10.3581 10.5601C9.69114 10.6926 8.99984 10.6244 8.37165 10.364C7.74345 10.1036 7.20658 9.66283 6.82893 9.09732C6.45127 8.53182 6.24981 7.86701 6.25 7.187C6.25027 6.27536 6.6126 5.40115 7.25732 4.75661C7.90205 4.11208 8.77636 3.75 9.688 3.75ZM9.688 17.188C8.59762 17.1875 7.5205 16.9489 6.53197 16.4887C5.54344 16.0286 4.66736 15.358 3.965 14.524C4.33266 13.8209 4.88542 13.2316 5.56359 12.8197C6.24176 12.4078 7.01957 12.189 7.813 12.187C7.90695 12.1876 8.00029 12.2021 8.09 12.23C8.60581 12.4036 9.14579 12.4947 9.69 12.5C10.2343 12.4951 10.7743 12.4039 11.29 12.23C11.3797 12.2021 11.4731 12.1876 11.567 12.187C12.3603 12.189 13.1381 12.4076 13.8162 12.8193C14.4944 13.231 15.0472 13.8201 15.415 14.523C14.7123 15.3577 13.8356 16.0288 12.8464 16.4891C11.8571 16.9495 10.7791 17.188 9.688 17.188Z"
                        fill="white" />
                </svg>
                <span class="text-white">
                    {{ user.name }}
                </span>

                <transition enter-active-class="transition duration-300 ease-out"
                    enter-from-class="transform -translate-y-4 opacity-0"
                    enter-to-class="transform translate-y-0 opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="transform translate-y-0 opacity-100"
                    leave-to-class="transform -translate-y-4 opacity-0">
                    <div v-if="open_cerrar_sesion"
                        class="absolute top-8 right-0 bg-white py-2 px-4 w-40 rounded-lg shadow-lg z-50 overflow-hidden">
                        <Link method="post" :href="route('logout')" @click="handleLogout"
                            class="flex items-center gap-2 text-gray-700 hover:bg-neutral-100 duration-300 rounded-lg p-2 text-sm font-medium cursor-pointer w-full">
                        <LogOut class="h-4 w-4" />
                        Cerrar sesión
                        </Link>
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>