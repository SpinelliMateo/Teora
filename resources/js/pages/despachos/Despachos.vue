<script setup lang="ts">

import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import dayjs from 'dayjs';
import { router } from '@inertiajs/vue3';


interface Modelo {
    id: number | string;
    nombre_modelo: string;
}
interface Remito {
    id: number;
    n_remito: string;
    cliente: string;
    modelos: Modelo[];
}
interface Despacho {
    id: number | string;
    numero_despacho: string;
    remitos: Remito[];
    created_at: string;
}
type Cliente = string;

defineProps<{
    despachos: Despacho[],
    clientes: Cliente[],
}>()

const searchTerm = ref('');
const open_filtros = ref(false);
const fecha_desde = ref(null);
const fecha_hasta = ref(null);
const cliente_filter = ref('');

let searchTimer: any = null;


const despacho_modal = ref(false);
const despacho_seleccionado = ref<Despacho | any>(null);

const abrirDespacho = (despacho: Despacho) => {
    despacho_seleccionado.value = despacho;
    despacho_modal.value = true;
}

const handleSearch = () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/historial-despachos', {
            search: searchTerm.value
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 500);
};

const handle_filtro_fechas = () => {
    console.log('Filtrando por fechas:', fecha_desde.value, fecha_hasta.value, cliente_filter.value);

    router.get('/historial-despachos', {
        fecha_desde: fecha_desde.value,
        fecha_hasta: fecha_hasta.value,
        cliente: cliente_filter.value,
        search: searchTerm.value
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const limpiar_filtros = () => {
    fecha_desde.value = null;
    fecha_hasta.value = null;
    cliente_filter.value = '';
    router.get('/historial-despachos', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

</script>

<template>

    <Head title="Historial de despacho" />
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-5 lg:px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 lg:mt-10">
                <h1 class="text-[32px] font-bold text-gray-800">Historial de despachos</h1>
            </div>
            <div class="flex items-center lg:justify-end gap-4">
                <div class="flex flex-col lg:flex-row items-center gap-2 w-full lg:w-auto">
                    <div class="relative w-full">
                        <input type="text" placeholder="Buscar" @input="handleSearch"
                            class="px-10 py-2 border rounded-full focus:outline-none text-black  placeholder-[#0D509C] w-full lg:w-[200px]"
                            style="border-color: #0D509C;" v-model="searchTerm" />
                        <span class="absolute left-3 top-3 text-gray-400">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M6.5 13C4.68333 13 3.146 12.3707 1.888 11.112C0.63 9.85333 0.000667196 8.316 5.29101e-07 6.5C-0.000666138 4.684 0.628667 3.14667 1.888 1.888C3.14733 0.629333 4.68467 0 6.5 0C8.31533 0 9.853 0.629333 11.113 1.888C12.373 3.14667 13.002 4.684 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.81267 10.5627 9.688 9.688C10.5633 8.81333 11.0007 7.75067 11 6.5C10.9993 5.24933 10.562 4.187 9.688 3.313C8.814 2.439 7.75133 2.00133 6.5 2C5.24867 1.99867 4.18633 2.43633 3.313 3.313C2.43967 4.18967 2.002 5.252 2 6.5C1.998 7.748 2.43567 8.81067 3.313 9.688C4.19033 10.5653 5.25267 11.0027 6.5 11Z"
                                    fill="#0D509C" />
                            </svg>
                        </span>
                    </div>
                    <button @click="open_filtros = !open_filtros"
                        class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-full w-full lg:w-auto cursor-pointer"
                        style="background-color: #0D509C;">
                        <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M7.02076 16C6.73743 16 6.49976 15.904 6.30776 15.712C6.11576 15.52 6.02009 15.2827 6.02076 15V9L0.220761 1.6C-0.0292387 1.26667 -0.0669053 0.916667 0.107761 0.55C0.282428 0.183334 0.586761 0 1.02076 0H15.0208C15.4541 0 15.7584 0.183334 15.9338 0.55C16.1091 0.916667 16.0714 1.26667 15.8208 1.6L10.0208 9V15C10.0208 15.2833 9.92476 15.521 9.73276 15.713C9.54076 15.905 9.30343 16.0007 9.02076 16H7.02076Z"
                                fill="white" />
                        </svg>
                        Filtros
                    </button>
                </div>
            </div>
            <transition name="slide-fade" enter-active-class="transition-all duration-300 ease-in-out"
                enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-300 ease-in-out"
                leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                <div v-if="open_filtros" class="text-black flex flex-col rounded-lg bg-white p-5">
                    <h2 class="font-semibold text-xl mb-5">Filtrar por</h2>
                    <div class="flex items-center gap-6">
                        <div class="flex-1 flex items-center gap-6">
                            <div class="flex flex-col w-full">
                                <label for="fecha_desde" class="" style="color: #5B5B5B;">Fecha creación desde</label>
                                <input type="date" id="fecha_desde" v-model="fecha_desde" @change="handle_filtro_fechas"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                            <div class="flex flex-col w-full">
                                <label for="fecha_hasta" class="" style="color: #5B5B5B;">Fecha creación hasta</label>
                                <input type="date" id="fecha_hasta" @change="handle_filtro_fechas" v-model="fecha_hasta"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                        </div>
                        <div class="flex-1 flex flex-col">
                            <label for="cliente_filter" style="color: #5B5B5B;">Cliente</label>
                            <select id="cliente_filter" v-model="cliente_filter" @change="handle_filtro_fechas"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                                <option value="">Todos</option>
                                <option v-for="cliente in clientes">
                                    {{ cliente }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="w-full flex justify-end mt-5">
                        <button @click="limpiar_filtros"
                            class="w-[180px] flex justify-center items-center py-2 bg-blue-600 text-white rounded-full cursor-pointer"
                            style="background-color: #0D509C;">
                            Limpiar filtros
                        </button>
                    </div>
                </div>
            </transition>
            <div class="overflow-x-auto mt-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div v-for="despacho in despachos" :key="despacho.id">
                        <div v-if="despacho" @click="abrirDespacho(despacho)"
                            class="min-h-[128px] h-full w-full border border-[#D9D9D9] px-2 py-3 bg-white flex flex-col gap-2 cursor-pointer">
                            <div class="flex justify-between">
                                <h3 class="font-extrabold text-lg flex items-center gap-2">
                                    Despacho {{ despacho.numero_despacho }}
                                </h3>
                            </div>
                            <div class="text-sm font-bold mb-3">
                                <p>
                                    Fecha: {{ despacho.created_at ? dayjs(despacho.created_at).format('DD/MM/YYYY') :
                                        'N/A' }}
                                </p>
                                <p>
                                    Hora: {{ despacho.created_at ? dayjs(despacho.created_at).format('HH:mm') : 'N/A' }}
                                </p>
                            </div>
                            <div class="grid grid-cols-1 gap-5">
                                <template v-for="remito in despacho.remitos" :key="remito.id">
                                    <div class="flex flex-col gap-2 text-sm">
                                        <span>Remito N°{{ remito.n_remito }}</span>
                                        <span>Cliente: {{ remito.cliente }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div v-if="!despachos.length" class="col-span-5 text-center py-20 text-gray-500">
                        No se encontraron despachos
                    </div>
                </div>
            </div>
            <div v-if="despacho_modal" @click.self="despacho_modal = !despacho_modal;"
                class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50 px-2 lg:px-0"
                style="background-color: rgba(0, 0, 0, 0.5);">
                <div
                    class="bg-white rounded-lg p-6 lg:w-[40vw] modal-animation overflow-y-auto max-h-[90vh] min-h-[400px] flex flex-col justify-between">

                    <div class="flex flex-col gap-6">
                        <div class="flex flex-col justify-between gap-2">
                            <h2 class="text-xl font-bold text-black">
                                Detalle despacho {{ despacho_seleccionado.numero_despacho }}
                            </h2>
                            <div class="text-sm font-bold mb-3">
                                <p>
                                    Fecha: {{ despacho_seleccionado.created_at ?
                                        dayjs(despacho_seleccionado.created_at).format('DD/MM/YYYY') :
                                    'N/A' }}
                                </p>
                                <p>
                                    Hora: {{ despacho_seleccionado.created_at ?
                                        dayjs(despacho_seleccionado.created_at).format('HH:mm') : 'N/A' }}
                                </p>
                            </div>
                            <div class="grid grid-cols-1 gap-5">
                                <template v-for="remito in despacho_seleccionado.remitos" :key="remito.id">
                                    <div class="flex flex-col gap-2 text-sm">
                                        <span>Remito N°{{ remito.n_remito }}</span>
                                        <span>Cliente: {{ remito.cliente }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <hr class="w-full border border-[#D9D9D9]">
                        <div>
                            <template v-for="remito in despacho_seleccionado.remitos" :key="remito.id">
                                <template v-if="remito.control_stock && remito.control_stock.length">
                                    <div v-for="controlStock in remito.control_stock" :key="controlStock.id" class="flex gap-17 py-2">
                                        <p class="w-1/2">N° de serie: {{ controlStock.n_serie }}</p>
                                        <p class="w-1/2">Modelo: {{ controlStock.modelo.nombre_modelo }}</p>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>