<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import vSelect from 'vue-select'
import 'vue-select/dist/vue-select.css'
import axios from 'axios'

const { success, error, warning, info } = useToast();

const props = defineProps({
    servicios: Array,
    filtro: String,
    modelos: Array,
    usuarios: Array,
    direcciones: Array,
    problemas: Array,
    can: Object
})

console.log(props.problemas);

const form = useForm({
    modelo_id: null,
    factura: '',
    user_id: null,
    serie: '',
    fecha_salida: '',
    estado: '',
    pagado: '',
    razon_social: '',
    dni_cuit: '',
    cliente_distribuidor: '',
    vendedor: '',
    direccion: '',
    contacto: '',
    localidad: '',
    provincia: '',
    telefono: '',
    email: '',
    horarios: '',
    problema_id: '',
    subproblema_id: '',
    interno_externo: '',
    reinc: '',
});


const modeloSeleccionado = ref(null)
const loadingModelo = ref(false)
const timerSerie = ref(null);
const open_filtros = ref(false);
const direccion_filter = ref('');
const tecnico_filter = ref('');

watch(() => form.serie, async (serie) => {
    loadingModelo.value = true
    clearTimeout(timerSerie.value);
    timerSerie.value = setTimeout(async () => {
        try {
            const response = await axios.get(route('stock_by_n_serie', { n_serie: serie }))

            if (response.data.modelo) {
                console.log('entra');
                form.modelo_id = response.data.modelo.id
                modeloSeleccionado.value = response.data.modelo.nombre_modelo
                form.fecha_salida = response.data.fecha_salida.substring(0, 10);
            }
            else {
                console.log();
                error('No se encontro el numero de serie.')
                modeloSeleccionado.value = null;
                form.fecha_salida = null;
                form.modelo_id = null;
            }
        } catch (e) {
            console.error(e)
            modeloSeleccionado.value = null;
            form.fecha_salida = null;
            form.modelo_id = null;
        } finally {
            loadingModelo.value = false
        }
    }, 500);
})



const form_estado = useForm({
    id: null,
    estado: '',
});

const handle_change_estado = ((servicio) => {
    form_estado.id = servicio.id;
    form_estado.estado = servicio.estado;
    form_estado.put('/update_servicio_estado', {
        onError(errors) {
            const firstError = Object.values(errors)[0];
            if (firstError) {
                error(firstError); // tu función toast de error
            }
        },
        onSuccess() {
            success('Estado actualizado correctamente.');
        }
    });
});

const form_pagado = useForm({
    id: null,
    pagado: null,
});

const handle_change_pagado = ((servicio, pagado) => {
    form_pagado.id = servicio.id;
    form_pagado.pagado = pagado;
    form_pagado.put('/update_servicio_pagado', {
        onError(errors) {
            const firstError = Object.values(errors)[0];
            if (firstError) {
                error(firstError); // tu función toast de error
            }
        },
        onSuccess() {
            success('Estado del pago actualizado correctamente.');
        }
    });
});

const provincias = [
    { nombre: 'Buenos Aires' },
    { nombre: 'Catamarca' },
    { nombre: 'Chaco' },
    { nombre: 'Chubut' },
    { nombre: 'Córdoba' },
    { nombre: 'Corrientes' },
    { nombre: 'Entre Ríos' },
    { nombre: 'Formosa' },
    { nombre: 'Jujuy' },
    { nombre: 'La Pampa' },
    { nombre: 'La Rioja' },
    { nombre: 'Mendoza' },
    { nombre: 'Misiones' },
    { nombre: 'Neuquén' },
    { nombre: 'Río Negro' },
    { nombre: 'Salta' },
    { nombre: 'San Juan' },
    { nombre: 'San Luis' },
    { nombre: 'Santa Cruz' },
    { nombre: 'Santa Fe' },
    { nombre: 'Santiago del Estero' },
    { nombre: 'Tierra del Fuego' },
    { nombre: 'Tucumán' },
    { nombre: 'Ciudad Autónoma de Buenos Aires' },
];

const modal_servicio = ref(false);
const create_servicio_tecnico = () => {
    form.post('/create_servicio_tecnico', {
        onError(errors) {
            console.log(errors.message);
            const firstError = Object.values(errors)[0];
            if (firstError) {
                error(firstError); // tu función toast de error
            }
            form.reset()
        },
        onSuccess() {
            success('Servicio técnico creado correctamente.');
            form.reset()
        }
    });

    modal_servicio.value = !modal_servicio.value
}

const handle_filtro = (filtro, search = null) => {
    router.get('/servicio-tecnico', { filtro: filtro ?? props.filtro, search: search }, {
        preserveState: true, // opcional, mantiene el estado actual (útil para scroll o inputs)
        preserveScroll: true, // opcional, mantiene la posición del scroll
    });

}


const timer = ref(null);
const searchTerm = ref('');

const handleSearch = () => {
    clearTimeout(timer.value);
    timer.value = setTimeout(() => {
        router.get('/servicio-tecnico', {
            search: searchTerm.value,
            filtro: props.filtro
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 500);
}

const servicio_tecnico_detalle = (id) => {
    router.get('/servicio-tecnico-detalle', { id: id }, {
        preserveState: true, // opcional, mantiene el estado actual (útil para scroll o inputs)
        preserveScroll: true, // opcional, mantiene la posición del scroll
    });
}

const fecha_desde = ref(null);
const fecha_hasta = ref(null);
const handle_filtro_fechas = () => {
    if (!fecha_desde.value) {
        error('Debe elegir la fecha de inicio.');
        return
    }
    if (!fecha_hasta.value) {
        return
    }

    router.get('/servicio-tecnico', { fecha_desde: fecha_desde.value, fecha_hasta: fecha_hasta.value }, {
        preserveState: true, // opcional, mantiene el estado actual (útil para scroll o inputs)
        preserveScroll: true, // opcional, mantiene la posición del scroll
    });
}

const limpiar_filtros = () => {
    fecha_desde.value = null;
    fecha_hasta.value = null;
    direccion_filter.value = null;
    tecnico_filter.value = null;

    router.get('/servicio-tecnico', { fecha_desde: fecha_desde.value, fecha_hasta: fecha_hasta.value, search: null }, {
        preserveState: true, // opcional, mantiene el estado actual (útil para scroll o inputs)
        preserveScroll: true, // opcional, mantiene la posición del scroll
    });
}

let loading_subproblemas = ref(false);
let subproblemas = ref([]);
const get_subproblemas_by_id = async () => {
    try {
        loading_subproblemas.value = true;

        const response = await axios.get(route('get_subproblemas_by_id', { id: form.problema_id }))
        if (response.data.subproblemas) {
            subproblemas.value = response.data.subproblemas;
        }
    } catch (e) {
        console.error(e)
        error(e.response.data.message);

    } finally {
        loading_subproblemas.value = false;
    }
}
</script>


<template>

    <Head title="Servicio Técnico" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 mt-10">
                <h1 class="text-[32px] font-bold text-gray-800">Servicio técnico</h1>
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
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-4 mr-4">
                        <div class="flex items-center gap-1">
                            <span class="inline-block w-4 h-4 rounded-full bg-orange-500" title="Reincidente"></span>
                            <span>Reincidente</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="inline-block w-4 h-4 rounded-full bg-green-600" title="No reincidente"></span>
                            <span>No reincidente</span>
                        </div>
                    </div>
                    <div class="relative">
                        <input type="text" placeholder="Buscar" @input="handleSearch"
                            class="px-10 py-2 border rounded-full focus:outline-none text-black  placeholder-[#0D509C] w-[200px]"
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
                        class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-full cursor-pointer"
                        style="background-color: #0D509C;">
                        <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M7.02076 16C6.73743 16 6.49976 15.904 6.30776 15.712C6.11576 15.52 6.02009 15.2827 6.02076 15V9L0.220761 1.6C-0.0292387 1.26667 -0.0669053 0.916667 0.107761 0.55C0.282428 0.183334 0.586761 0 1.02076 0H15.0208C15.4541 0 15.7584 0.183334 15.9338 0.55C16.1091 0.916667 16.0714 1.26667 15.8208 1.6L10.0208 9V15C10.0208 15.2833 9.92476 15.521 9.73276 15.713C9.54076 15.905 9.30343 16.0007 9.02076 16H7.02076Z"
                                fill="white" />
                        </svg>
                        Filtros
                    </button>
                    <button v-if="can.gestionar" @click="modal_servicio = !modal_servicio"
                        class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-full cursor-pointer"
                        style="background-color: #0D509C;">
                        Añadir Servicio
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
                                <label for="fecha_desde" class="" style="color: #5B5B5B;">Desde</label>
                                <input type="date" id="fecha_desde" v-model="fecha_desde" @change="handle_filtro_fechas"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                            <div class="flex flex-col w-full">
                                <label for="fecha_hasta" class="" style="color: #5B5B5B;">Hasta</label>
                                <input type="date" id="fecha_hasta" @change="handle_filtro_fechas" v-model="fecha_hasta"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                        </div>
                        <div v-if="can.gestionar" class="flex-1 flex flex-col">
                            <label for="tecnico_filter" style="color: #5B5B5B;">Técnico</label>
                            <select id="tecnico_filter" v-model="tecnico_filter"
                                @change="handle_filtro(null, tecnico_filter)"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                                <option value="">Todos</option>
                                <option v-for="tecnico in usuarios" :key="tecnico.id" :value="tecnico.id">
                                    {{ tecnico.name.charAt(0).toUpperCase() + tecnico.name.slice(1) }} {{
                                        tecnico.apellido.charAt(0).toUpperCase() + tecnico.apellido.slice(1) }}
                                </option>
                            </select>
                        </div>
                        <div class="flex-1 flex flex-col w-full">
                            <label for="direcciones" class="" style="color: #5B5B5B;">Dirección</label>
                            <v-select :options="direcciones" v-model="direccion_filter" class="mt-1 custom-v-select"
                                @option:selected="handle_filtro(null, direccion_filter)"
                                @option:deselected="console.log('asd')">
                                <template #no-options>
                                    <span>No se encontraron opciones</span>
                                </template>
                            </v-select>
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
                <table class="w-full bg-white overflow-hidden">
                    <thead class="bg-[#E1E5E9]">
                        <tr>
                            <th class=" text-center text-sm font-medium text-gray-600 uppercase tracking-wider"></th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                FECHA</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                N°</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                TÉCNICO
                            </th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                SERIE</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                FACTURA
                            </th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                MODELO</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                SALIDA</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                PROBLEMA
                            </th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                DIRECCIÓN
                            </th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                ESTADO</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                PAGADO</th>
                            <th class=" text-center text-sm font-medium text-gray-600 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="(servicio, index) in props.servicios" :key="index">
                            <td class="px-1 text-sm text-center">
                                <span v-if="servicio.reinc == 'Si'"
                                    class="inline-block w-4 h-4 rounded-full bg-orange-500" title="Reincidente"></span>
                                <span v-else class="inline-block w-4 h-4 rounded-full bg-green-600"
                                    title="No reincidente"></span>
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ servicio.created_at }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ servicio.id }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                {{ servicio.user?.name ? servicio.user.name.charAt(0).toUpperCase() +
                                    servicio.user.name.slice(1) :
                                    '' }}
                                {{ servicio.user?.apellido ? servicio.user.apellido.charAt(0).toUpperCase() +
                                    servicio.user.apellido.slice(1) : '' }}
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ servicio.serie }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ servicio.factura }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ servicio.modelo.nombre_modelo }}
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ servicio.fecha_salida }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ servicio.problema.nombre }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ servicio.direccion }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                <div class="border rounded-2xl flex items-center justify-center py-1 px-2">
                                    <span class="inline-block w-3 h-3 rounded-full" :class="{
                                        'bg-yellow-300': servicio.estado === 'Pendiente',
                                        'bg-green-500': servicio.estado === 'Finalizado',
                                        'bg-red-500': servicio.estado === 'Urgente',
                                    }"></span>

                                    <select v-model="servicio.estado" @change="handle_change_estado(servicio)"
                                        class="text-sm bg-transparent focus:outline-none">
                                        <option value="Pendiente">Pendiente</option>
                                        <option value="Finalizado">Finalizado</option>
                                        <option value="Urgente">Urgente</option>
                                    </select>
                                </div>
                            </td>

                            <td class="text-center">
                                <button @click="handle_change_pagado(servicio, true)" v-if="!servicio.pagado"
                                    class="cursor-pointer flex justify-center items-center w-full h-full">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M16 0H2C1.46957 0 0.960859 0.210714 0.585786 0.585786C0.210714 0.960859 0 1.46957 0 2V16C0 16.5304 0.210714 17.0391 0.585786 17.4142C0.960859 17.7893 1.46957 18 2 18H16C16.5304 18 17.0391 17.7893 17.4142 17.4142C17.7893 17.0391 18 16.5304 18 16V2C18 1.46957 17.7893 0.960859 17.4142 0.585786C17.0391 0.210714 16.5304 0 16 0ZM16 2V16H2V2H16Z"
                                            fill="#D9D9D9" />
                                    </svg>
                                </button>
                                <button @click="handle_change_pagado(servicio, false)" v-if="servicio.pagado"
                                    class="cursor-pointer flex justify-center items-center w-full h-full">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M16 0H2C1.46957 0 0.960859 0.210714 0.585786 0.585786C0.210714 0.960859 0 1.46957 0 2V16C0 16.5304 0.210714 17.0391 0.585786 17.4142C0.960859 17.7893 1.46957 18 2 18H16C16.5304 18 17.0391 17.7893 17.4142 17.4142C17.7893 17.0391 18 16.5304 18 16V2C18 1.46957 17.7893 0.960859 17.4142 0.585786C17.0391 0.210714 16.5304 0 16 0ZM16 2V16H2V2H16ZM7 14L3 10L4.41 8.58L7 11.17L13.59 4.58L15 6"
                                            fill="#0D509C" />
                                    </svg>
                                </button>
                            </td>
                            <td class="pr-4 text-sm text-center text-gray-800">
                                <button @click="servicio_tecnico_detalle(servicio.id)"
                                    class="hover:bg-neutral-200 py-1 px-2 rounded-full duration-300 cursor-pointer">
                                    <svg width="11" height="18" viewBox="0 0 11 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 1L9 9L1 17" stroke="#0C0C0C" stroke-width="2" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal servicio -->
        <div v-if="modal_servicio" @click.self="modal_servicio = !modal_servicio;"
            class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="bg-white rounded-lg p-6 w-[645px] modal-animation overflow-y-auto max-h-[90vh]">
                <h2 class="text-xl font-semibold mb-4 text-black">Nuevo Servicio técnico</h2>

                <div class="w-full">
                    <div class="w-full flex gap-5 mb-4">
                        <div class="w-1/2">
                            <label for="serie" class="block text-sm text-[#5B5B5B]">Serie</label>
                            <input type="text" id="serie" v-model="form.serie"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                        <div class="w-1/2">
                            <div class="flex items-center gap-2">
                                <label for="modeloSeleccionado" class="block text-sm text-[#5B5B5B]">Modelo</label>
                                <div v-if="loadingModelo"
                                    class="text-sm text-blue-600 font-medium flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 200 200">
                                        <radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125"
                                            gradientTransform="scale(1.5)">
                                            <stop offset="0" stop-color="#0D509C"></stop>
                                            <stop offset=".3" stop-color="#0D509C" stop-opacity=".9"></stop>
                                            <stop offset=".6" stop-color="#0D509C" stop-opacity=".6"></stop>
                                            <stop offset=".8" stop-color="#0D509C" stop-opacity=".3"></stop>
                                            <stop offset="1" stop-color="#0D509C" stop-opacity="0"></stop>
                                        </radialGradient>
                                        <circle transform-origin="center" fill="none" stroke="url(#a12)"
                                            stroke-width="15" stroke-linecap="round" stroke-dasharray="200 1000"
                                            stroke-dashoffset="0" cx="100" cy="100" r="70">
                                            <animateTransform type="rotate" attributeName="transform" calcMode="spline"
                                                dur="2" values="360;0" keyTimes="0;1" keySplines="0 0 1 1"
                                                repeatCount="indefinite">
                                            </animateTransform>
                                        </circle>
                                        <circle transform-origin="center" fill="none" opacity=".2" stroke="#0D509C"
                                            stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle>
                                    </svg>
                                </div>
                            </div>
                            <input type="text" id="modeloSeleccionado" v-model="modeloSeleccionado" disabled
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="w-full flex gap-5 mb-4">
                        <div class="w-1/2">
                            <label for="factura" class="block text-sm text-[#5B5B5B]">Factura</label>
                            <input type="text" id="factura" v-model="form.factura"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                        <div class="w-1/2">
                            <div class="flex items-center gap-2">
                                <label for="fecha_salida" class="block text-sm text-[#5B5B5B]">Fecha de salida</label>
                                <div v-if="loadingModelo"
                                    class="text-sm text-blue-600 font-medium flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 200 200">
                                        <radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125"
                                            gradientTransform="scale(1.5)">
                                            <stop offset="0" stop-color="#0D509C"></stop>
                                            <stop offset=".3" stop-color="#0D509C" stop-opacity=".9"></stop>
                                            <stop offset=".6" stop-color="#0D509C" stop-opacity=".6"></stop>
                                            <stop offset=".8" stop-color="#0D509C" stop-opacity=".3"></stop>
                                            <stop offset="1" stop-color="#0D509C" stop-opacity="0"></stop>
                                        </radialGradient>
                                        <circle transform-origin="center" fill="none" stroke="url(#a12)"
                                            stroke-width="15" stroke-linecap="round" stroke-dasharray="200 1000"
                                            stroke-dashoffset="0" cx="100" cy="100" r="70">
                                            <animateTransform type="rotate" attributeName="transform" calcMode="spline"
                                                dur="2" values="360;0" keyTimes="0;1" keySplines="0 0 1 1"
                                                repeatCount="indefinite">
                                            </animateTransform>
                                        </circle>
                                        <circle transform-origin="center" fill="none" opacity=".2" stroke="#0D509C"
                                            stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle>
                                    </svg>
                                </div>
                            </div>
                            <input type="date" id="fecha_salida" v-model="form.fecha_salida" disabled
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="w-full flex gap-5 mb-4">
                        <div class="w-1/2">
                            <label for="contacto" class="block text-sm text-[#5B5B5B]">Contacto</label>
                            <input type="text" id="contacto" v-model="form.contacto"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                        <div class="w-1/2">
                            <label for="razon_social" class="block text-sm text-[#5B5B5B]">Razón social</label>
                            <input type="text" id="razon_social" v-model="form.razon_social"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="w-full flex gap-5 mb-4">
                        <div class="w-1/2">
                            <label for="dni_cuit" class="block text-sm text-[#5B5B5B]">DNI/CUIT</label>
                            <input type="text" id="dni_cuit" v-model="form.dni_cuit"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                        <div class="w-1/2">
                            <label for="cliente_distribuidor"
                                class="block text-sm text-[#5B5B5B]">Cliente/Distribuidor</label>
                            <input type="text" id="cliente_distribuidor" v-model="form.cliente_distribuidor"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="w-full flex gap-5 mb-4">
                        <div class="w-1/2">
                            <label for="vendedor" class="block text-sm text-[#5B5B5B]">Vendedor</label>
                            <input type="text" id="vendedor" v-model="form.vendedor"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                        <div class="w-1/2">
                            <label for="direccion" class="block text-sm text-[#5B5B5B]">Dirección</label>
                            <input type="text" id="direccion" v-model="form.direccion"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="w-full flex gap-5 mb-4">
                        <div class="w-1/2">
                            <label for="localidad" class="block text-sm text-[#5B5B5B]">Localidad</label>
                            <input type="text" id="localidad" v-model="form.localidad"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                        <div class="w-1/2">
                            <label for="provincia" class="block text-sm text-[#5B5B5B]">Provincia</label>
                            <v-select :options="provincias" label="nombre" v-model="form.provincia"
                                :reduce="provincia => provincia.nombre" class="mt-1 custom-v-select">
                                <template #no-options>
                                    <span>No se encontraron opciones</span>
                                </template>
                            </v-select>
                        </div>
                    </div>
                    <div class="w-full flex gap-5 mb-4">
                        <div class="w-1/2">
                            <label for="telefono" class="block text-sm text-[#5B5B5B]">Teléfono</label>
                            <input type="text" id="telefono" v-model="form.telefono"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                        <div class="w-1/2">
                            <label for="email" class="block text-sm text-[#5B5B5B]">Email</label>
                            <input type="text" id="email" v-model="form.email"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="w-full flex gap-5 mb-4">
                        <div class="w-1/2">
                            <label for="horarios" class="block text-sm text-[#5B5B5B]">Horarios</label>
                            <input type="text" id="horarios" v-model="form.horarios"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                        <div class="w-1/2">
                            <label for="problema" class="block text-sm text-[#5B5B5B]">Problema</label>
                            <select v-model="form.problema_id" @change="get_subproblemas_by_id" name="problema"
                                id="problema_id" class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                                <option v-for="(problema, index) in problemas" :key="index" :value="problema.id">{{
                                    problema.nombre }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="w-full flex gap-5 mb-4">
                        <div class="w-1/2">
                            <div class="flex items-center gap-2">
                                <label for="sub_problema" class="block text-sm text-[#5B5B5B]">Sub problema</label>
                                <div v-if="loading_subproblemas"
                                    class="text-sm text-blue-600 font-medium flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 200 200">
                                        <radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125"
                                            gradientTransform="scale(1.5)">
                                            <stop offset="0" stop-color="#0D509C"></stop>
                                            <stop offset=".3" stop-color="#0D509C" stop-opacity=".9"></stop>
                                            <stop offset=".6" stop-color="#0D509C" stop-opacity=".6"></stop>
                                            <stop offset=".8" stop-color="#0D509C" stop-opacity=".3"></stop>
                                            <stop offset="1" stop-color="#0D509C" stop-opacity="0"></stop>
                                        </radialGradient>
                                        <circle transform-origin="center" fill="none" stroke="url(#a12)"
                                            stroke-width="15" stroke-linecap="round" stroke-dasharray="200 1000"
                                            stroke-dashoffset="0" cx="100" cy="100" r="70">
                                            <animateTransform type="rotate" attributeName="transform" calcMode="spline"
                                                dur="2" values="360;0" keyTimes="0;1" keySplines="0 0 1 1"
                                                repeatCount="indefinite">
                                            </animateTransform>
                                        </circle>
                                        <circle transform-origin="center" fill="none" opacity=".2" stroke="#0D509C"
                                            stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle>
                                    </svg>
                                </div>
                            </div>
                            <select v-model="form.subproblema_id" @change="get_subproblemas_by_id" name="problema"
                                id="problema_id" class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                                <option v-for="(sub, index) in subproblemas" :key="index" :value="sub.id">{{ sub.nombre
                                    }}
                                </option>
                            </select>
                        </div>
                        <div class="w-1/2">
                            <label for="tecnico" class="block text-sm text-[#5B5B5B]">Técnico</label>
                            <select v-model="form.user_id" id="tecnico"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                                <option value="">Seleccionar técnico</option>
                                <option v-for="tecnico in usuarios" :key="tecnico.id" :value="tecnico.id">
                                    {{ tecnico.name.charAt(0) + tecnico.name.slice(1) }} {{
                                        tecnico.apellido.charAt(0) + tecnico.apellido.slice(1) }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="w-full flex gap-5 mb-4">
                        <div class="w-1/2">
                            <label for="interno_externo" class="block text-sm text-[#5B5B5B]">Interno / externo</label>
                            <select v-model="form.interno_externo"
                                class="mt-1 h-10 p-2 w-full border border-gray-300 rounded-md outline-none"
                                name="interno_externo" id="interno_externo">
                                <option value="Interno">Interno</option>
                                <option value="Externo">Externo</option>
                            </select>
                        </div>
                        <div class="w-1/2">

                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <div class="flex justify-end space-x-2">
                            <button @click="modal_servicio = !modal_servicio;"
                                class="px-6 py-2 bg-white text-[#0D509C] border border-[#0D509C] hover:shadow-md rounded-full duration-300 cursor-pointer">Cancelar</button>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button @click="create_servicio_tecnico"
                                class="px-6 py-2 bg-[#0D509C] text-white rounded-full hover:shadow-md duration-300 cursor-pointer">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.custom-v-select .vs__dropdown-toggle {
    padding-top: 0.35rem;
    padding-bottom: 0.35rem;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    cursor: pointer;
}
</style>