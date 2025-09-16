<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted  } from 'vue';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';


const props = defineProps({
    stock: Object,
    fecha: String,
})
const form = useForm({
    nombre_modelo: '',
    fecha: '',
});
const searchTerm = ref('');
const open_filtros = ref(false);
const modeloSeleccionado = ref('');
const nombreSeleccionado = ref('');

const fecha = ref(props.fecha ?? null);
const timer = ref(null);
const modelosUnicos = [...new Set(props.stock.data.map(item => item.modelo.modelo))];
const nombresUnicos = [...new Set(props.stock.data.map(item => item.modelo.nombre_modelo))];

const handle_fecha = () =>{
    if (fecha.value) {
        router.get('/stock', { fecha: fecha.value }, {
            preserveState: true, // opcional, mantiene el estado actual (útil para scroll o inputs)
            preserveScroll: true, // opcional, mantiene la posición del scroll
        });
    }
    else
    {
        router.get('/stock');
    }
}

const view_detail = (nombre_modelo) => {
    form.nombre_modelo = nombre_modelo;
    form.fecha = fecha;
    form.get('/stock-detalle', {
        onError(errors) {
            console.log(errors.message);
        },
    });
}

// const filteredData = computed(() => {
//   return props.stock.data.filter(item => {
//     const matchesSearch = item.modelo.nombre_modelo.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
//                           item.modelo.modelo.includes(searchTerm.value);
//     const matchesModelo = modeloSeleccionado.value === '' || item.modelo.modelo === modeloSeleccionado.value;
//     const matchesNombre = nombreSeleccionado.value === '' || item.modelo.nombre_modelo === nombreSeleccionado.value;

//     return matchesSearch && matchesModelo && matchesNombre;
//   });
// });


const handleSearch = () => {
    clearTimeout(timer.value);
    timer.value = setTimeout(() => {
        // console.log(searchTerm.value);
        router.get('/stock', {
            search: searchTerm.value || modeloSeleccionado.value || nombreSeleccionado.value,
            fecha: fecha.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 500);
}

</script>


<template>
    <Head title="Stock" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-5 lg:px-20" style="background-color: #F4F4F4;">
            <h1 class="text-[32px] font-bold text-gray-800 lg:mt-8">Stock</h1>
            <div class="flex justify-end items-center lg:mb-8">
                <div class="flex flex-col lg:flex-row items-center gap-2 w-full lg:w-auto ">
                    <div class="w-full lg:w-auto">
                        <input type="datetime-local" name="fecha_hora" v-model="fecha" @change="handle_fecha" class="border border-[#0D509C] text-[#0D509C] bg-transparent cursor-pointer duration-300 rounded-full py-2 px-4 w-full lg:w-[200px]">
                    </div>
                    <div class="relative w-full lg:w-auto">
                        <input
                            type="text"
                            placeholder="Buscar"
                            @input="handleSearch"
                            class="px-10 py-2 border rounded-full focus:outline-none text-black  placeholder-[#0D509C] w-full lg:w-[200px]" style="border-color: #0D509C;"
                            v-model="searchTerm"
                        />
                        <span class="absolute left-3 top-3 text-gray-400">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.5 13C4.68333 13 3.146 12.3707 1.888 11.112C0.63 9.85333 0.000667196 8.316 5.29101e-07 6.5C-0.000666138 4.684 0.628667 3.14667 1.888 1.888C3.14733 0.629333 4.68467 0 6.5 0C8.31533 0 9.853 0.629333 11.113 1.888C12.373 3.14667 13.002 4.684 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.81267 10.5627 9.688 9.688C10.5633 8.81333 11.0007 7.75067 11 6.5C10.9993 5.24933 10.562 4.187 9.688 3.313C8.814 2.439 7.75133 2.00133 6.5 2C5.24867 1.99867 4.18633 2.43633 3.313 3.313C2.43967 4.18967 2.002 5.252 2 6.5C1.998 7.748 2.43567 8.81067 3.313 9.688C4.19033 10.5653 5.25267 11.0027 6.5 11Z" fill="#0D509C"/>
                            </svg>
                        </span>
                    </div>
                    <button @click="open_filtros = !open_filtros" class="flex items-center justify-center lg:justify-normal gap-2 px-6 py-2 bg-blue-600 text-white rounded-full cursor-pointer w-full lg:w-auto" style="background-color: #0D509C;">
                        <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.02076 16C6.73743 16 6.49976 15.904 6.30776 15.712C6.11576 15.52 6.02009 15.2827 6.02076 15V9L0.220761 1.6C-0.0292387 1.26667 -0.0669053 0.916667 0.107761 0.55C0.282428 0.183334 0.586761 0 1.02076 0H15.0208C15.4541 0 15.7584 0.183334 15.9338 0.55C16.1091 0.916667 16.0714 1.26667 15.8208 1.6L10.0208 9V15C10.0208 15.2833 9.92476 15.521 9.73276 15.713C9.54076 15.905 9.30343 16.0007 9.02076 16H7.02076Z" fill="white"/>
                        </svg>
                        Filtros
                    </button>
                </div>
            </div>

            <transition
            name="slide-fade"
            enter-active-class="transition-all duration-300 ease-in-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-300 ease-in-out"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="open_filtros" class="text-black flex flex-col rounded-lg bg-white p-5 mb-10">
                    <h2 class="font-semibold text-xl mb-5">Filtrar por</h2>
                    <div class="w-full flex items-center gap-10">
                        <div class="flex flex-col w-[280px] gap-2">
                            <label for="modelos" class="" style="color: #5B5B5B;">Modelo</label>
                            <select  v-model="modeloSeleccionado" @input="handleSearch" name="modelos" id="" class="border border-neutral-200 rounded-md py-2">
                                <option value="" selected>TODOS</option>
                                <option v-for="modelo in modelosUnicos" :key="modelo" :value="modelo">{{ modelo }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col w-[280px] gap-2">
                            <label for="modelos" class="" style="color: #5B5B5B;">Nombre</label>
                            <select v-model="nombreSeleccionado" @input="handleSearch" name="modelos" id="" class="border border-neutral-200 rounded-md py-2">
                                <option value="" selected>TODOS</option>
                                <option v-for="nombre in nombresUnicos" :key="nombre" :value="nombre">{{ nombre }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </transition>

            <div class="overflow-x-auto">
                <table class="w-full bg-white overflow-hidden">
                    <thead class="bg-[#E1E5E9]">
                        <tr>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Modelo</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Nombre</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Prearmados</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Inyectados</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Armados</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Embalados</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Stock Mínimo</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <!-- todo: poner item.stockMinimo -->
                        <tr v-for="(item, index) in props.stock.data" :key="index" :style="item.conteo_embalado < item.modelo.stock_minimo.stock_minimo ? 'background-color: #FAE8E8;' : ''"  class="">
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.modelo.modelo }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.modelo.nombre_modelo }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.conteo_prearmado  }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.conteo_inyectado }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.conteo_armado }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.conteo_embalado }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.modelo.stock_minimo.stock_minimo }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                <button @click="view_detail(item.modelo.nombre_modelo)" class="hover:bg-neutral-200 py-1 px-2 rounded-full duration-300 cursor-pointer">
                                    <svg width="11" height="18" viewBox="0 0 11 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 1L9 9L1 17" stroke="#0C0C0C" stroke-width="2"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-center items-center mt-4">
                <button
                    :disabled="!props.stock.prev_page_url"
                    @click="$inertia.get(props.stock.prev_page_url, { fecha: fecha })"
                    class="px-2 py-1 cursor-pointer hover:bg-[#0D509C] hover:text-white text-black duration-300  rounded disabled:opacity-50"
                >
                    < 
                </button>

                <span class="text-gray-700 px-2">{{ props.stock.current_page }} de {{ props.stock.last_page }}</span>

                <button
                    :disabled="!props.stock.next_page_url"
                    @click="$inertia.get(props.stock.next_page_url, { fecha: fecha })"
                    class="px-2 py-1 cursor-pointer hover:bg-[#0D509C] hover:text-white text-black duration-300  rounded disabled:opacity-50"
                >
                    >
                </button>
            </div>
        </div>
    </AppLayout>
</template>