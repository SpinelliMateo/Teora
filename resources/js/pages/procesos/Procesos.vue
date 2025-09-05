<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import 'vue-select/dist/vue-select.css';

const { success, error } = useToast();

const props = defineProps({
    procesos: Array,
    filtro: String,
    search: String,
    prearmadores: Array,
    armadores: Array,
    embaladores: Array,
    can: Object
})

const form = useForm({
    id: '',
    fecha_prearmado: '',
    hora_prearmado: '',
    operario_prearmado: null,
    fecha_inyectado: '',
    hora_inyectado: '',
    fecha_armado: '',
    hora_armado: '',
    operario_armado: null,
    numero_motor: '',
    fecha_embalado: '',
    hora_embalado: '',
    operario_embalado: null,
});

type SerieSeleccionada = {
    control_stock: {
        id: number;
        n_serie: string;
        modelo: {
            modelo: string;
            nombre_modelo: string;
        };
        fecha_prearmado?: string;
        fecha_inyectado?: string;
        fecha_armado?: string;
        fecha_embalado?: string;
        fecha_salida?: string;
        equipo?: string;
    };
    operario_prearmador?: { id: number; nombre: string; apellido: string };
    operario_armador?: { id: number; nombre: string; apellido: string };
    operario_embalador?: { id: number; nombre: string; apellido: string };
};

const modalImpresion = ref(false);
const cargandoImpresion = ref(false);
const loading_proceso = ref(false);
const proceso_modal = ref(false);
const serie_seleccionada = ref<SerieSeleccionada | null>(null);
import type { Ref } from 'vue';
const timer: Ref<number | undefined> = ref();
const searchTerm = ref('');
const tipoImpresion = ref<'prearmado' | 'embalado'>('prearmado');

const handle_filtro = (filtro: string, search = null) => {
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

const toDB = (fecha: string, hora: string) => {
    if (!fecha || !hora) return null;

    const [year, month, day] = fecha.split('-').map(Number);
    const [hour, minute] = hora.split(':').map(Number);

    let d = new Date(year, month - 1, day, hour, minute);

    return d.toISOString().slice(0, 19).replace('T', ' ');
};

const abrirModalProceso = (item: any) => {
    // Resetear primero
    form.reset();
    serie_seleccionada.value = null;

    // Luego asignar (esto forzará el watch)
    setTimeout(() => {
        serie_seleccionada.value = item;
        proceso_modal.value = true;
    }, 0);
};

const update_proceso = () => {
    loading_proceso.value = true;

    form.fecha_prearmado = toDB(form.fecha_prearmado, form.hora_prearmado);
    form.fecha_inyectado = toDB(form.fecha_inyectado, form.hora_inyectado);
    form.fecha_armado = toDB(form.fecha_armado, form.hora_armado);
    form.fecha_embalado = toDB(form.fecha_embalado, form.hora_embalado);


    form.id = (serie_seleccionada.value as any).control_stock.id;
    form.put('/update_proceso', {
        onError(errors) {
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
        const formatFecha = (datetime: string) => {
            if (!datetime) return '';
            const d = new Date(datetime);
            return d.toISOString().split('T')[0]; // YYYY-MM-DD
        };

        const formatHora = (datetime: string) => {
            if (!datetime) return '';
            const d = new Date(datetime);
            return d.toTimeString().slice(0, 5); // HH:mm local
        };


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

        console.log(form.fecha_prearmado, form.hora_prearmado);
    }
});

const abrirModalImpresion = (tipo: 'prearmado' | 'embalado') => {
    console.log(serie_seleccionada.value, 'abriendo');

    if (tipo === 'prearmado') {
        if (!form.fecha_prearmado || !form.hora_prearmado || !form.operario_prearmado) {
            error('Por favor, complete todos los campos antes de imprimir.');
            return;
        }
        tipoImpresion.value = 'prearmado';
    } else {
        if (!form.fecha_embalado || !form.hora_embalado || !form.operario_embalado) {
            error('Por favor, complete todos los campos antes de imprimir.');
            return;
        }
        tipoImpresion.value = 'embalado';
    }

    modalImpresion.value = true;

    proceso_modal.value = false;
};
function generarQRDataURL(productoId: number): string {
    return `/barcode/generate/${productoId}`
}
function generarQRDataURLModelo(controlStockId: number): string {
    return `/barcode/generate/modelo/${controlStockId}`
}

function imprimirEmbalado() {
    cargandoImpresion.value = false;

    const controlId = serie_seleccionada.value?.control_stock.id;

    router.post('/procesos/imprimir-etiqueta',
        {
            control_stock_id: controlId,
            tipo: tipoImpresion.value
        },
        {
            onStart: () => { cargandoImpresion.value = true; },
            onSuccess: (page: any) => {
                success(page.props.flash.message || 'Etiquetas impresas correctamente.');
                modalImpresion.value = false;
                proceso_modal.value = true;
            },
            onError(errors) {
                console.log('Errores de impresion:', errors);
                cargandoImpresion.value = false;

                if (errors.error) {
                    error(errors.error);
                } else if (errors.message) {
                    error(errors.message);
                } else {
                    const firstError = Object.values(errors)[0];
                    if (firstError) {
                        error(Array.isArray(firstError) ? firstError[0] : firstError);
                    } else {
                        error('Error inesperado al imprimir. Por favor, intenta nuevamente.');
                    }
                }
            },
            onFinish: () => { cargandoImpresion.value = false }
        }
    )
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
                            <th v-if="can?.gestionar"
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <!-- todo: poner item.stockMinimo -->
                        <tr v-for="(item, index) in props.procesos?.data" :key="index" class="">
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
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.operario_armador?.nombre }}
                                {{ item.operario_armador?.apellido }}
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
                            <td v-if="can?.gestionar" class="py-3 px-4 text-sm text-center text-gray-800">
                                <button @click="abrirModalProceso(item)"
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
                                    <option v-for="operario in prearmadores" :key="(operario as any).id"
                                        :value="(operario as any).id">
                                        {{ (operario as any).nombre }} {{ (operario as any).apellido }}
                                    </option>
                                </select>
                            </div>
                            <div class="w-1/4"></div>
                        </div>
                        <button class="cursor-pointer hover:opacity-70" @click="abrirModalImpresion('prearmado')">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M15 4H13V0H5V4H3C1.34 4 0 5.34 0 7V12H3V18H15V12H18V7C18 5.34 16.66 4 15 4ZM7 2H11V4H7V2ZM13 16H5V11H13V16ZM15 9C14.45 9 14 8.55 14 8C14 7.45 14.45 7 15 7C15.55 7 16 7.45 16 8C16 8.55 15.55 9 15 9Z"
                                    fill="#0D509C" />
                            </svg>
                        </button>
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
                        <div class="w-4.5"></div>
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
                                <label for="fecha_armado" class="block text-sm text-[#5B5B5B]">Fecha Armado</label>
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
                                    <option v-for="operario in armadores" :key="(operario as any).id"
                                        :value="(operario as any).id">
                                        {{ (operario as any).nombre }} {{ (operario as any).apellido }}
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
                        <div class="w-4.5"></div>
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
                                    <option v-for="operario in embaladores" :key="(operario as any).id"
                                        :value="(operario as any).id">
                                        {{ (operario as any).nombre }} {{ (operario as any).apellido }}
                                    </option>
                                </select>
                            </div>
                            <div class="w-1/4"></div>
                        </div>
                        <button class="cursor-pointer hover:opacity-70" @click="abrirModalImpresion('embalado')">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M15 4H13V0H5V4H3C1.34 4 0 5.34 0 7V12H3V18H15V12H18V7C18 5.34 16.66 4 15 4ZM7 2H11V4H7V2ZM13 16H5V11H13V16ZM15 9C14.45 9 14 8.55 14 8C14 7.45 14.45 7 15 7C15.55 7 16 7.45 16 8C16 8.55 15.55 9 15 9Z"
                                    fill="#0D509C" />
                            </svg>
                        </button>
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
        <div v-if="modalImpresion" class="fixed inset-0 flex items-center justify-center h-full w-full">
            <div class="fixed inset-0 bg-black opacity-50 z-1"></div>
            <div class="bg-white modal-animation rounded-lg shadow-xl max-w-3xl w-full py-3 z-50 max-h-[90vh] relative">
                <div v-if="cargandoImpresion" class="absolute inset-0 bg-gray-200 opacity-70 z-50 ">
                </div>
                <div v-if="cargandoImpresion"
                    class="absolute flex h-full w-full items-center justify-center pb-10 z-50">
                    <div class="flex flex-col gap-10 items-center justify-center h-90 w-80 bg-white rounded-2xl">
                        <div class="animate-spin rounded-full h-30 w-30 border-b-2 border-sky-800"></div>
                        <p class="flex items-center gap-1">
                            Imprimiendo
                            <span class="dot animate-bounce">.</span>
                            <span class="dot animate-bounce delay-200">.</span>
                            <span class="dot animate-bounce delay-400">.</span>
                        </p>

                    </div>
                </div>
                <h2 class="text-lg font-bold text-gray-800 px-6">Imprimir etiqueta de
                    {{ tipoImpresion }}</h2>
                <div class="py-4 space-y-4 overflow-y-auto max-h-[70vh] px-6">
                    <div v-if="tipoImpresion === 'embalado'"
                        class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm text-black">
                        <!-- Header de la tarjeta con título y botón -->
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-700">Datos generales - Etiqueta {{
                                serie_seleccionada?.control_stock.n_serie }}</h3>
                        </div>

                        <!-- Campos en grid de 3 columnas -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <!-- Ingreso a depósito -->
                            <div>
                                <label class="block text-gray-500 mb-2">Ingreso a depósito</label>
                                <div class="relative">
                                    <div
                                        class="flex items-center text-center border border-gray-300 rounded px-3 py-2 h-10.5">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                        </svg>
                                        <span class="text-sm text-gray-900 mt-0.5">
                                            {{ serie_seleccionada?.control_stock?.fecha_embalado
                                                ? new
                                                    Date(serie_seleccionada.control_stock.fecha_embalado).toLocaleDateString('es-ES')
                                                : ''
                                            }}
                                        </span>

                                    </div>
                                </div>
                            </div>

                            <!-- N° de serie -->
                            <div>
                                <label class="block text-gray-500 mb-2">N° de serie</label>
                                <div class="border border-gray-300 rounded bg-white px-3 py-2">
                                    <span class="text-sm font-medium text-gray-900">{{
                                        serie_seleccionada?.control_stock.n_serie
                                    }}</span>
                                </div>
                            </div>

                            <!-- Modelo -->
                            <div>
                                <label class="block text-gray-500 mb-2">Modelo</label>
                                <div class="border border-gray-300 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo.nombre_modelo
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Código de barras -->
                        <div class="bg-gray-50 rounded-lg p-4 flex justify-center">
                            <div>
                                <img :src="generarQRDataURL(serie_seleccionada?.control_stock?.id ?? 0)"
                                    :alt="`Código de barras ${serie_seleccionada?.control_stock.n_serie}`"
                                    class="h-16 w-auto mx-auto block mb-2" />
                                <div class="text-center text-xs font-medium text-gray-700 tracking-wide">
                                    {{ serie_seleccionada?.control_stock.n_serie }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="tipoImpresion === 'prearmado'"
                        class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm text-black">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-700">Datos generales - Etiqueta {{
                                serie_seleccionada?.control_stock.n_serie }}</h3>
                        </div>
                        <div class="flex flex-col gap-4">
                            <div class="flex gap-4 w-full">
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/3 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Fecha</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.fecha_prearmado
                                            ? new
                                                Date(serie_seleccionada.control_stock.fecha_prearmado).toLocaleDateString('es-ES')
                                            : ''
                                    }}</span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/3 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Serie</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.n_serie
                                            ? serie_seleccionada.control_stock.n_serie
                                            : ''
                                    }}</span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/3 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Modelo</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo
                                            ? serie_seleccionada.control_stock.modelo.nombre_modelo
                                            : ''
                                    }}</span>
                                </div>
                            </div>
                            <div class="flex gap-4 w-full">
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/4 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Tension</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo
                                            ? serie_seleccionada.control_stock.modelo.tension
                                            : ''
                                    }}</span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/4 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Frecuencia</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo
                                            ? serie_seleccionada.control_stock.modelo.frecuencia
                                            : ''
                                    }}</span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/4 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Corriente</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo
                                            ? serie_seleccionada.control_stock.modelo.corriente
                                            : ''
                                    }}</span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/4 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Aislacion</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo
                                            ? serie_seleccionada.control_stock.modelo.aislacion
                                            : ''
                                    }}</span>
                                </div>
                            </div>
                            <div class="flex gap-4 w-full">
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/4 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Sistema</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo
                                            ? serie_seleccionada.control_stock.modelo.sistema
                                            : ''
                                    }}</span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/4 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Vol. Bruto</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo
                                            ? serie_seleccionada.control_stock.modelo.volumen
                                            : ''
                                    }}</span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/4 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Ag. Espum.</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo
                                            ? serie_seleccionada.control_stock.modelo.espumante
                                            : ''
                                    }}</span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/4 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Clase Clim.</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo
                                            ? serie_seleccionada.control_stock.modelo.clase
                                            : ''
                                    }}</span>
                                </div>
                            </div>
                            <div class="flex gap-4 w-full">
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/2 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Refrigerante</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo
                                            ? serie_seleccionada.control_stock.modelo.gas
                                            : ''
                                    }}</span>
                                </div>
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/2 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Cantidad</span>
                                    <span class="text-sm text-gray-900">{{
                                        serie_seleccionada?.control_stock.modelo
                                            ? serie_seleccionada.control_stock.modelo.cantidad_gas
                                            : ''
                                    }}</span>
                                </div>
                            </div>
                            <div class="flex gap-4 w-full">
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/2 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Serie</span>
                                    <div>
                                        <img :src="generarQRDataURL(serie_seleccionada?.control_stock?.id ?? 0)"
                                            :alt="`Código de barras ${serie_seleccionada?.control_stock.n_serie}`"
                                            class="h-16 w-auto mx-auto block mb-2" />
                                        <div class="text-center text-xs font-medium text-gray-700 tracking-wide">
                                            {{ serie_seleccionada?.control_stock.n_serie }}
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col gap-1 border border-gray-200 w-1/2 rounded bg-white px-3 py-2">
                                    <span class="text-sm text-gray-500">Modelo</span>
                                    <div>
                                        <img :src="generarQRDataURLModelo(serie_seleccionada?.control_stock?.id ?? 0)"
                                            :alt="`Código de barras ${serie_seleccionada?.control_stock.modelo.modelo}`"
                                            class="h-16 w-auto mx-auto block mb-2" />
                                        <div class="text-center text-xs font-medium text-gray-700 tracking-wide">
                                            {{ serie_seleccionada?.control_stock.modelo.modelo }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 flex justify-end gap-2 ">
                        <button class="mt-2 bg-gray-200 text-gray-800 rounded-lg px-4 py-2"
                            @click="proceso_modal = true; modalImpresion = false">Volver</button>
                        <button class="mt-2 bg-[#0D509C] text-white rounded-lg px-4 py-2"
                            @click="imprimirEmbalado">Imprimir</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
