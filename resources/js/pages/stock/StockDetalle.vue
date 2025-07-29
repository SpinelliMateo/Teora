<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const { success, error, warning, info } = useToast();

const props = defineProps({
    stock_detalle: Array,
    modelo: Object,
    filtro: String,
    fecha: String,
})

const form = useForm({
    id: null,
    oculto: null,
});

const ocultar_stock_detalle = (stock_detalle, value) => {
    form.id = stock_detalle.id;
    form.oculto = value;
    form.put('/ocultar_stock_detalle', {
        onError(errors) {
            const firstError = Object.values(errors)[0];
            if (firstError) {
                error(firstError); // tu función toast de error
            }
        },
        onSuccess() {
            success('Stock oculto correctamente.');
        }
    });
}

const handle_filtro = (filtro) => {
    router.get('/stock-detalle', { filtro: filtro, nombre_modelo: props.modelo.nombre_modelo, fecha: props.fecha }, {
        preserveState: true, // opcional, mantiene el estado actual (útil para scroll o inputs)
        preserveScroll: true, // opcional, mantiene la posición del scroll
    });
}

</script>


<template>
    <Head title="Stock Detalle" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 mt-10">
                <button class="cursor-pointer" @click="router.get('/stock');">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 20L0 10L10 0L11.7812 1.75L4.78125 8.75H20V11.25H4.78125L11.7812 18.25L10 20Z" fill="#626262"/>
                    </svg>
                </button>
                <h1 class="text-[32px] font-bold text-gray-800">Detalle modelo {{ modelo.modelo }}</h1>
            </div>

            <div class="flex items-center  gap-5 ml-1">
                <div class="flex flex-col items-center">
                    <button @click="handle_filtro('TODOS')" class="text-lg cursor-pointer" :class="filtro == 'TODOS' ? 'text-[#0D509C] font-bold' : 'text-[#5B5B5B]'">TODOS</button>
                    <div class="h-[2px] w-[110%] mt-1" :class="filtro == 'TODOS' ? 'bg-[#0D509C]' : 'bg-[#5B5B5B]'"></div>
                </div>
                <div class="flex flex-col items-center">
                    <button @click="handle_filtro('NO OCULTOS')" class="text-lg  cursor-pointer" :class="filtro == 'NO OCULTOS' ? 'text-[#0D509C] font-bold' : 'text-[#5B5B5B]'">NO OCULTOS</button>
                    <div class="h-[2px]  w-[110%] mt-1" :class="filtro == 'NO OCULTOS' ? 'bg-[#0D509C]' : 'bg-[#5B5B5B]'"></div>
                </div>
                <div class="flex flex-col items-center">
                    <button @click="handle_filtro('OCULTOS')" class="text-lg cursor-pointer" :class="filtro == 'OCULTOS' ? 'text-[#0D509C] font-bold' : 'text-[#5B5B5B]'">OCULTOS</button>
                    <div class="h-[2px]  w-[110%] mt-1" :class="filtro == 'OCULTOS' ? 'bg-[#0D509C]' : 'bg-[#5B5B5B]'"></div>
                </div>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="w-full bg-white overflow-hidden">
                    <thead class="bg-[#E1E5E9]">
                        <tr>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">N° DE SERIE</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">PREARMADOS</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">INYECTADOS</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">ARMADOS</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">EMBALADOS</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">OCULTAR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="(stock, index) in props.stock_detalle" :key="index">
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ stock.n_serie }}</td>
                            <td class="py-3 px-4 text-sm text-gray-800">
                                <div v-if="stock.fecha_prearmado" class="flex flex-col items-center">
                                    <span class="font-medium">{{ new Date(stock.fecha_prearmado).toLocaleDateString('es-ES') }}</span>
                                    <span class="text-[#B1B1B1]">{{ new Date(stock.fecha_prearmado).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                </div>
                            </td>
                            <td  class="py-3 px-4 text-sm text-gray-800">
                                <div v-if="stock.fecha_inyectado" class="flex flex-col items-center">
                                    <span class="font-medium">{{ new Date(stock.fecha_inyectado).toLocaleDateString('es-ES') }}</span>
                                    <span class="text-[#B1B1B1]">{{ new Date(stock.fecha_inyectado).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                </div>
                            </td>
                            <td  class="py-3 px-4 text-sm text-gray-800">
                                <div v-if="stock.fecha_armado" class="flex flex-col items-center">
                                    <span class="font-medium">{{ new Date(stock.fecha_armado).toLocaleDateString('es-ES') }}</span>
                                    <span class="text-[#B1B1B1]">{{ new Date(stock.fecha_armado).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                </div>
                            </td>
                            <td  class="py-3 px-4 text-sm text-gray-800">
                                <div v-if="stock.fecha_embalado" class="flex flex-col items-center">
                                    <span class="font-medium">{{ new Date(stock.fecha_embalado).toLocaleDateString('es-ES') }}</span>
                                    <span class="text-[#B1B1B1]">{{ new Date(stock.fecha_embalado).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button @click="ocultar_stock_detalle(stock, true)" v-if="!stock.oculto" class="cursor-pointer flex justify-center items-center w-full h-full">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16 0H2C1.46957 0 0.960859 0.210714 0.585786 0.585786C0.210714 0.960859 0 1.46957 0 2V16C0 16.5304 0.210714 17.0391 0.585786 17.4142C0.960859 17.7893 1.46957 18 2 18H16C16.5304 18 17.0391 17.7893 17.4142 17.4142C17.7893 17.0391 18 16.5304 18 16V2C18 1.46957 17.7893 0.960859 17.4142 0.585786C17.0391 0.210714 16.5304 0 16 0ZM16 2V16H2V2H16Z" fill="#D9D9D9"/>
                                    </svg>
                                </button>
                                <button @click="ocultar_stock_detalle(stock, false)" v-if="stock.oculto" class="cursor-pointer flex justify-center items-center w-full h-full">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16 0H2C1.46957 0 0.960859 0.210714 0.585786 0.585786C0.210714 0.960859 0 1.46957 0 2V16C0 16.5304 0.210714 17.0391 0.585786 17.4142C0.960859 17.7893 1.46957 18 2 18H16C16.5304 18 17.0391 17.7893 17.4142 17.4142C17.7893 17.0391 18 16.5304 18 16V2C18 1.46957 17.7893 0.960859 17.4142 0.585786C17.0391 0.210714 16.5304 0 16 0ZM16 2V16H2V2H16ZM7 14L3 10L4.41 8.58L7 11.17L13.59 4.58L15 6" fill="#0D509C"/>
                                    </svg>  
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>