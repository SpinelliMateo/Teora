<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import 'vue-select/dist/vue-select.css';

const { success, error, warning, info } = useToast();

const props = defineProps({
    procesos: Array,
    filtro: String,
    search: String,
    operarios: Array,
    can: Object
})

console.log(props.procesos);

const form = useForm({
    id: '',
    fecha_prearmado: '',
    hora_prearmado: '',
    operario_prearmado: '',
    fecha_inyectado: '',
    hora_inyectado: '',
    fecha_armado: '',
    hora_armado: '',
    operario_armado: '',
    numero_motor: '',
    fecha_embalado: '',
    hora_embalado: '',
    operario_embalado: '',
});



const open_filtros = ref(false);


const loading_proceso = ref(false);
const proceso_modal = ref(false);
const serie_seleccionada = ref(null);
const update_proceso = () => {
    loading_proceso.value = true;
    form.id = (serie_seleccionada.value as any).control_stock.id;
    form.put('/update_proceso', {
        onError(errors) {
            console.log(errors.message);
            const firstError = Object.values(errors)[0];
            if (firstError) {
                error(firstError);
            }
        },
        onSuccess() {
            success('Proceso actualizado correctamente.');
        }
    });

    loading_proceso.value = false;
    proceso_modal.value = !proceso_modal.value;
    serie_seleccionada.value = null;
    form.reset();
}

watch(serie_seleccionada, (nuevoValor: any) => {
    if (nuevoValor) {
        console.log(nuevoValor);
        const formatFecha = (datetime) => datetime ? datetime.split(' ')[0] : '';
        const formatHora = (datetime) => datetime ? datetime.split(' ')[1]?.slice(0, 5) : '';


        form.id = nuevoValor.control_stock.id;
        form.fecha_prearmado = formatFecha(nuevoValor.control_stock.fecha_prearmado);
        form.hora_prearmado = formatHora(nuevoValor.control_stock.fecha_prearmado);
        form.operario_prearmado = nuevoValor.operario_prearmador?.id || '';
        form.fecha_inyectado = formatFecha(nuevoValor.control_stock.fecha_inyectado);
        form.hora_inyectado = formatHora(nuevoValor.control_stock.fecha_inyectado);
        form.fecha_armado = formatFecha(nuevoValor.control_stock.fecha_armado);
        form.hora_armado = formatHora(nuevoValor.control_stock.fecha_armado);
        form.operario_armado = nuevoValor.operario_armador?.id || '';
        form.numero_motor = nuevoValor.control_stock.equipo || '';
        form.fecha_embalado = formatFecha(nuevoValor.control_stock.fecha_embalado);
        form.hora_embalado = formatHora(nuevoValor.control_stock.fecha_embalado);
        form.operario_embalado = nuevoValor.operario_embalador?.id || '';
    }
});



const timer = ref(null);
const searchTerm = ref('');
const handle_filtro = (filtro, search = null) => {
    searchTerm.value = '';
    router.get('/seguimiento-por-proceso', { filtro: filtro ?? props.filtro, search: search }, {
        preserveState: true, // opcional, mantiene el estado actual (útil para scroll o inputs)
        preserveScroll: true, // opcional, mantiene la posición del scroll
    });

}

const handleSearch = () => {
    clearTimeout(timer.value);
    timer.value = setTimeout(() => {
        router.get('/seguimiento-por-proceso', {
            search: searchTerm.value,
            filtro: props.filtro
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 500);
}
</script>


<template>

    <Head title="Seguimiento por proceso" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 mt-10">
                <h1 class="text-[32px] font-bold text-gray-800">Seguimiento por proceso</h1>
            </div>

            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-5 ml-1">
                    <div class="flex flex-col items-center">
                        <button @click="handle_filtro('EN PROCESO')" class="text-lg  cursor-pointer"
                            :class="filtro == 'EN PROCESO' ? 'text-[#0D509C] font-bold' : 'text-[#5B5B5B]'">EN
                            PROCESO</button>
                        <div class="h-[2px] w-[110%] mt-1"
                            :class="filtro == 'EN PROCESO' ? 'bg-[#0D509C]' : 'bg-[#5B5B5B]'"></div>
                    </div>
                    <div class="flex flex-col items-center">
                        <button @click="handle_filtro('FINALIZADOS')" class="text-lg cursor-pointer"
                            :class="filtro == 'FINALIZADOS' ? 'text-[#0D509C] font-bold' : 'text-[#5B5B5B]'">FINALIZADOS</button>
                        <div class="h-[2px]  w-[110%] mt-1"
                            :class="filtro == 'FINALIZADOS' ? 'bg-[#0D509C]' : 'bg-[#5B5B5B]'"></div>
                    </div>
                </div>
                <div class="relative">
                    <input type="text" placeholder="Buscar" @input="handleSearch"
                        class="px-10 py-2 border rounded-full focus:outline-none text-black  placeholder-[#0D509C] w-[200px]"
                        style="border-color: #0D509C;" v-model="searchTerm" />
                    <span class="absolute left-3 top-3 text-gray-400">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M6.5 13C4.68333 13 3.146 12.3707 1.888 11.112C0.63 9.85333 0.000667196 8.316 5.29101e-07 6.5C-0.000666138 4.684 0.628667 3.14667 1.888 1.888C3.14733 0.629333 4.68467 0 6.5 0C8.31533 0 9.853 0.629333 11.113 1.888C12.373 3.14667 13.002 4.684 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.81267 10.5627 9.688 9.688C10.5633 8.81333 11.0007 7.75067 11 6.5C10.9993 5.24933 10.562 4.187 9.688 3.313C8.814 2.439 7.75133 2.00133 6.5 2C5.24867 1.99867 4.18633 2.43633 3.313 3.313C2.43967 4.18967 2.002 5.252 2 6.5C1.998 7.748 2.43567 8.81067 3.313 9.688C4.19033 10.5653 5.25267 11.0027 6.5 11Z"
                                fill="#0D509C" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto mt-4">
                <table class="w-full bg-white overflow-hidden">
                    <thead class="bg-[#E1E5E9]">
                        <tr>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                SERIE</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                MODELO</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                PREARMADO
                            </th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                OP</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                INYECTADO
                            </th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                ARMADO</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                OP</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                EQUIPO</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                EMBALADO
                            </th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                OP</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                SALIDA</th>
                            <th
                                v-if="can.gestionar" class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <!-- todo: poner item.stockMinimo -->
                        <tr v-for="(item, index) in props.procesos.data" :key="index" class="">
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.control_stock.n_serie }}
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.control_stock.modelo.modelo
                            }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                <div class="flex flex-col items-center">
                                    <span class="font-medium">{{ new
                                        Date(item.control_stock.fecha_prearmado).toLocaleDateString('es-ES') }}</span>
                                    <span class="text-[#B1B1B1]">{{ new
                                        Date(item.control_stock.fecha_prearmado).toLocaleTimeString('es-ES', {
                                            hour:
                                                '2-digit',
                                            minute: '2-digit', second: '2-digit'
                                        }) }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.operario_prearmador?.nombre
                            }} {{ item.operario_prearmador?.apellido }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                <div v-if="item.control_stock.fecha_inyectado" class="flex flex-col items-center">
                                    <span class="font-medium">{{ new
                                        Date(item.control_stock.fecha_inyectado).toLocaleDateString('es-ES') }}</span>
                                    <span class="text-[#B1B1B1]">{{ new
                                        Date(item.control_stock.fecha_inyectado).toLocaleTimeString('es-ES', {
                                            hour:
                                                '2-digit',
                                            minute: '2-digit', second: '2-digit'
                                        }) }}</span>
                                </div>
                                <div v-else>
                                    --/--/----
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                <div v-if="item.control_stock.fecha_armado" class="flex flex-col items-center">
                                    <span class="font-medium">{{ new
                                        Date(item.control_stock.fecha_armado).toLocaleDateString('es-ES') }}</span>
                                    <span class="text-[#B1B1B1]">{{ new
                                        Date(item.control_stock.fecha_armado).toLocaleTimeString('es-ES', {
                                            hour:
                                                '2-digit', minute:
                                                '2-digit', second: '2-digit'
                                        }) }}</span>
                                </div>
                                <div v-else>
                                    --/--/----
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.operario_armador?.nombre }} {{ item.operario_armador?.apellido }}
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.control_stock.equipo != '0'
                                ?
                                item.control_stock.equipo : '' }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                <div v-if="item.control_stock.fecha_embalado" class="flex flex-col items-center">
                                    <span class="font-medium">{{ new
                                        Date(item.control_stock.fecha_embalado).toLocaleDateString('es-ES') }}</span>
                                    <span class="text-[#B1B1B1]">{{ new
                                        Date(item.control_stock.fecha_embalado).toLocaleTimeString('es-ES', {
                                            hour:
                                                '2-digit',
                                            minute: '2-digit', second: '2-digit'
                                        }) }}</span>
                                </div>
                                <div v-else>
                                    --/--/----
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.operario_embalador?.nombre
                            }} {{ item.operario_embalador?.apellido }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                <div v-if="item.control_stock.fecha_salida" class="flex flex-col items-center">
                                    <span class="font-medium">{{ new
                                        Date(item.control_stock.fecha_salida).toLocaleDateString('es-ES') }}</span>
                                    <span class="text-[#B1B1B1]">{{ new
                                        Date(item.control_stock.fecha_salida).toLocaleTimeString('es-ES', {
                                            hour:
                                                '2-digit', minute:
                                                '2-digit', second: '2-digit'
                                        }) }}</span>
                                </div>
                                <div v-else>
                                    --/--/----
                                </div>
                            </td>
                            <td v-if="can.gestionar" class="py-3 px-4 text-sm text-center text-gray-800">
                                <button @click="proceso_modal = !proceso_modal; serie_seleccionada = item"
                                    class="cursor-pointer hover:bg-neutral-100 duration-300 p-2 rounded-full">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M17.71 4.0425C18.1 3.6525 18.1 3.0025 17.71 2.6325L15.37 0.2925C15 -0.0975 14.35 -0.0975 13.96 0.2925L12.12 2.1225L15.87 5.8725M0 14.2525V18.0025H3.75L14.81 6.9325L11.06 3.1825L0 14.2525Z"
                                            fill="#D9D9D9" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-center items-center mt-4">
                <button :disabled="!props.procesos.prev_page_url"
                    @click="$inertia.get(props.procesos.prev_page_url, { filtro: filtro ?? props.filtro, search: props.search })"
                    class="px-2 py-1 cursor-pointer hover:bg-[#0D509C] hover:text-white text-black duration-300  rounded disabled:opacity-50">
                    < </button>

                        <span class="text-gray-700 px-2">{{ props.procesos.current_page }} de {{
                            props.procesos.last_page }}</span>

                        <button :disabled="!props.procesos.next_page_url"
                            @click="$inertia.get(props.procesos.next_page_url, { filtro: filtro ?? props.filtro, search: props.search })"
                            class="px-2 py-1 cursor-pointer hover:bg-[#0D509C] hover:text-white text-black duration-300  rounded disabled:opacity-50">
                            >
                        </button>
            </div>
        </div>

        <!-- Modal proceso -->
        <div v-if="proceso_modal" @click.self="proceso_modal = !proceso_modal;"
            class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="bg-white rounded-lg p-6 w-[80vw] modal-animation overflow-y-auto max-h-[90vh] min-h-[442px]">
                <h2 class="text-xl font-semibold mb-4 text-black">N°{{ (serie_seleccionada as
                    any)?.control_stock?.n_serie }}
                </h2>

                <div class="w-full">
                    <div class="flex items-center gap-5">
                        <div class="w-full flex gap-5 mb-4">
                            <div class="w-1/4">
                                <label for="fecha_prearmado" class="block text-sm text-[#5B5B5B]">Fecha
                                    Prearmado</label>
                                <input type="date" id="fecha_prearmado" v-model="form.fecha_prearmado"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                            <div class="w-1/4">
                                <label for="hora_prearmado" class="block text-sm text-[#5B5B5B]">Hora Prearmado</label>
                                <input type="time" id="hora_prearmado" v-model="form.hora_prearmado"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                            <div class="w-1/4">
                                <label for="operario_prearmado" class="block text-sm text-[#5B5B5B]">Operario
                                    Prearmado</label>
                                <select id="operario_prearmado" v-model="form.operario_prearmado"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                                    <option value="">{{ (serie_seleccionada as any)?.operario_prearmador?.nombre || '-'
                                        }}
                                    </option>
                                    <option v-for="operario in operarios" :key="(operario as any).id"
                                        :value="(operario as any).id">
                                        {{ (operario as any).nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="w-1/4"></div>
                        </div>
                        <button class="cursor-pointer"
                            @click="form.fecha_prearmado = ''; form.hora_prearmado = ''; form.operario_prearmado = null;">
                            <svg width="14" height="18" viewBox="0 0 14 18" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16ZM3 6H11V16H3V6ZM10.5 1L9.5 0H4.5L3.5 1H0V3H14V1H10.5Z"
                                    fill="#0D509C" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-5">
                        <div class="w-full flex gap-5 mb-4">
                            <div class="w-1/4">
                                <label for="fecha_inyectado" class="block text-sm text-[#5B5B5B]">Fecha
                                    Inyectado</label>
                                <input type="date" id="fecha_inyectado" v-model="form.fecha_inyectado"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                            <div class="w-1/4">
                                <label for="hora_inyectado" class="block text-sm text-[#5B5B5B]">Hora Inyectado</label>
                                <input type="time" id="hora_inyectado" v-model="form.hora_inyectado"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                            <div class="w-1/4"></div>
                            <div class="w-1/4"></div>
                        </div>
                        <button class="cursor-pointer" @click="form.fecha_inyectado = ''; form.hora_inyectado = '';">
                            <svg width="14" height="18" viewBox="0 0 14 18" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16ZM3 6H11V16H3V6ZM10.5 1L9.5 0H4.5L3.5 1H0V3H14V1H10.5Z"
                                    fill="#0D509C" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-5">
                        <div class="w-full flex gap-5 mb-4">
                            <div class="w-1/4">
                                <label for="fecha_armado" class="block text-sm text-[#5B5B5B]">Fecha armado</label>
                                <input type="date" id="fecha_armado" v-model="form.fecha_armado"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                            <div class="w-1/4">
                                <label for="hora_armado" class="block text-sm text-[#5B5B5B]">Hora Armado</label>
                                <input type="time" id="hora_armado" v-model="form.hora_armado"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                            <div class="w-1/4">
                                <label for="operario_armado" class="block text-sm text-[#5B5B5B]">Operario
                                    Armado</label>
                                <select id="operario_armado" v-model="form.operario_armado"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                                    <option value="">{{ (serie_seleccionada as any)?.operario_armador?.nombre || '-' }}
                                    </option>
                                    <option v-for="operario in operarios" :key="(operario as any).id"
                                        :value="(operario as any).id">
                                        {{ (operario as any).nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="w-1/4">
                                <label for="numero_motor" class="block text-sm text-[#5B5B5B]">N° motor</label>
                                <input type="text" id="numero_motor" v-model="form.numero_motor"
                                    :placeholder="(serie_seleccionada as any)?.control_stock?.equipo && (serie_seleccionada as any).control_stock.equipo !== '0' ? (serie_seleccionada as any).control_stock.equipo : '-'"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                        </div>
                        <button class="cursor-pointer"
                            @click="form.fecha_armado = ''; form.hora_armado = ''; form.operario_armado = null; form.numero_motor = '';">
                            <svg width="14" height="18" viewBox="0 0 14 18" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16ZM3 6H11V16H3V6ZM10.5 1L9.5 0H4.5L3.5 1H0V3H14V1H10.5Z"
                                    fill="#0D509C" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-5">
                        <div class="w-full flex gap-5 mb-4">
                            <div class="w-1/4">
                                <label for="fecha_embalado" class="block text-sm text-[#5B5B5B]">Fecha Embalado</label>
                                <input type="date" id="fecha_embalado" v-model="form.fecha_embalado"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                            <div class="w-1/4">
                                <label for="hora_embalado" class="block text-sm text-[#5B5B5B]">Hora Embalado</label>
                                <input type="time" id="hora_embalado" v-model="form.hora_embalado"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                            <div class="w-1/4">
                                <label for="operario_embalado" class="block text-sm text-[#5B5B5B]">Operario
                                    Embalado</label>
                                <select id="operario_embalado" v-model="form.operario_embalado"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                                    <option value="">{{ (serie_seleccionada as any)?.operario_embalador?.nombre || '-'
                                    }}
                                    </option>
                                    <option v-for="operario in operarios" :key="(operario as any).id"
                                        :value="(operario as any).id">
                                        {{ (operario as any).nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="w-1/4"></div>
                        </div>
                        <button class="cursor-pointer"
                            @click="form.fecha_embalado = ''; form.hora_embalado = ''; form.operario_embalado = null;">
                            <svg width="14" height="18" viewBox="0 0 14 18" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16ZM3 6H11V16H3V6ZM10.5 1L9.5 0H4.5L3.5 1H0V3H14V1H10.5Z"
                                    fill="#0D509C" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center justify-between gap-3 mt-4 mr-9">
                        <div class="flex justify-end space-x-2">
                            <button @click="proceso_modal = !proceso_modal;"
                                class="w-[150px] py-2 bg-white text-[#0D509C] rounded-full hover:shadow-lg duration-300 cursor-pointer flex justify-center border border-[#0D509C]">Volver</button>
                        </div>
                        <div v-if="!loading_proceso" class="flex justify-end space-x-2">
                            <button @click="update_proceso"
                                class="w-[150px] py-2 bg-[#0D509C] text-white rounded-full hover:shadow-lg duration-300 cursor-pointer flex justify-center">Guardar</button>
                        </div>
                        <button v-if="loading_proceso"
                            class="w-[150px] py-2 bg-[#0D509C] text-white rounded-full disabled:opacity-50 hover:shadow-lg duration-300 disabled:cursor-not-allowed cursor-pointer flex justify-center">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
                                <radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125"
                                    gradientTransform="scale(1.5)">
                                    <stop offset="0" stop-color="#FFFFFF"></stop>
                                    <stop offset=".3" stop-color="#FFFFFF" stop-opacity=".9"></stop>
                                    <stop offset=".6" stop-color="#FFFFFF" stop-opacity=".6"></stop>
                                    <stop offset=".8" stop-color="#FFFFFF" stop-opacity=".3"></stop>
                                    <stop offset="1" stop-color="#FFFFFF" stop-opacity="0"></stop>
                                </radialGradient>
                                <circle transform-origin="center" fill="none" stroke="url(#a12)" stroke-width="15"
                                    stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0" cx="100"
                                    cy="100" r="70">
                                    <animateTransform type="rotate" attributeName="transform" calcMode="spline" dur="2"
                                        values="360;0" keyTimes="0;1" keySplines="0 0 1 1" repeatCount="indefinite">
                                    </animateTransform>
                                </circle>
                                <circle transform-origin="center" fill="none" opacity=".2" stroke="#FFFFFF"
                                    stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
