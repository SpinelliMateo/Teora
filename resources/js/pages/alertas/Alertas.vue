<script setup lang="ts">

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import dayjs from 'dayjs';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const { success, error } = useToast();


// Modal y formulario para agregar/editar alerta
const alerta_modal = ref(false);
const loading_alerta = ref(false);
const formAlerta = ref({
    id: null,
    fecha_alerta: '',
    serie: '',
    modelo_id: '',
    motivo: ''
});
const isEdit = ref(false);

import axios from 'axios';
const modeloNombre = ref('');
const errorAlerta = ref('');
const timerSerie = ref(null);


const abrirModalAlerta = () => {
    alerta_modal.value = true;
    isEdit.value = false;
    formAlerta.value = {
        id: null,
        fecha_alerta: '',
        serie: '',
        modelo_id: '',
        motivo: ''
    };
    modeloNombre.value = '';
    errorAlerta.value = '';
};

const abrirModalEditar = (alerta) => {
    alerta_modal.value = true;
    isEdit.value = true;
    formAlerta.value = {
        id: alerta.id,
        fecha_alerta: alerta.fecha_alerta,
        serie: alerta.serie,
        modelo_id: alerta.modelo?.id || alerta.modelo_id,
        motivo: alerta.motivo
    };
    modeloNombre.value = alerta.modelo?.nombre_modelo || '';
    errorAlerta.value = '';
};

const cerrarModalAlerta = () => {
    alerta_modal.value = false;
    errorAlerta.value = '';
};

const buscarModeloPorSerie = () => {
    modeloNombre.value = '';
    formAlerta.value.modelo_id = '';
    errorAlerta.value = '';
    if (timerSerie.value) clearTimeout(timerSerie.value);
    if (!formAlerta.value.serie) return;
    loading_alerta.value = true;
    timerSerie.value = setTimeout(async () => {
        try {
            const response = await axios.get('/get_modelo_by_serie', { params: { n_serie: formAlerta.value.serie } });
            if (response.data && response.data.modelo) {
                modeloNombre.value = response.data.modelo.nombre_modelo || '';
                formAlerta.value.modelo_id = response.data.modelo.id;
            } else {
                modeloNombre.value = '';
                formAlerta.value.modelo_id = '';
                errorAlerta.value = 'No se encontró el número de serie.';
            }
        } catch (e) {
            modeloNombre.value = '';
            formAlerta.value.modelo_id = '';
            errorAlerta.value = 'No se encontró el número de serie.';
        } finally {
            loading_alerta.value = false;
        }
    }, 500);
};


const guardarAlerta = () => {
    errorAlerta.value = '';
    if (!formAlerta.value.fecha_alerta || !formAlerta.value.serie || !formAlerta.value.modelo_id || !formAlerta.value.motivo) {
        errorAlerta.value = 'Todos los campos son obligatorios';
        return;
    }
    loading_alerta.value = true;
    if (isEdit.value && formAlerta.value.id) {
        // Editar
        router.put(`/alertas/update/${formAlerta.value.id}`, {
            fecha_alerta: formAlerta.value.fecha_alerta,
            serie: formAlerta.value.serie,
            modelo_id: formAlerta.value.modelo_id,
            motivo: formAlerta.value.motivo,
            user_id: props.user_id
        }, {
            onError(errors) {
                loading_alerta.value = false;
                let mensaje = '';
                if (errors.error) {
                    mensaje = errors.error;
                } else if (errors.message) {
                    mensaje = errors.message;
                } else {
                    const errorMessages = [];
                    Object.keys(errors).forEach(key => {
                        if (Array.isArray(errors[key])) {
                            errorMessages.push(...errors[key]);
                        } else {
                            errorMessages.push(errors[key]);
                        }
                    });
                    if (errorMessages.length > 0) {
                        mensaje = errorMessages[0];
                    } else {
                        mensaje = 'Error inesperado al actualizar la alerta. Verifica los datos e intenta nuevamente.';
                    }
                }
                errorAlerta.value = mensaje;
                error(mensaje);
            },
            onSuccess(page) {
                loading_alerta.value = false;
                alerta_modal.value = false;
                const mensaje = (page.props.flash as any)?.success || 'Alerta actualizada correctamente.';
                success(mensaje);
                router.get('/alertas', {}, {
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        });
    } else {
        // Crear
        router.post('/alertas/create', {
            fecha_alerta: formAlerta.value.fecha_alerta,
            serie: formAlerta.value.serie,
            modelo_id: formAlerta.value.modelo_id,
            motivo: formAlerta.value.motivo,
            user_id: props.user_id
        }, {
            onError(errors) {
                loading_alerta.value = false;
                let mensaje = '';
                if (errors.error) {
                    mensaje = errors.error;
                } else if (errors.message) {
                    mensaje = errors.message;
                } else {
                    const errorMessages = [];
                    Object.keys(errors).forEach(key => {
                        if (Array.isArray(errors[key])) {
                            errorMessages.push(...errors[key]);
                        } else {
                            errorMessages.push(errors[key]);
                        }
                    });
                    if (errorMessages.length > 0) {
                        mensaje = errorMessages[0];
                    } else {
                        mensaje = 'Error inesperado al crear la alerta. Verifica que todos los campos estén completos.';
                    }
                }
                errorAlerta.value = mensaje;
                error(mensaje);
            },
            onSuccess(page) {
                loading_alerta.value = false;
                alerta_modal.value = false;
                const mensaje = (page.props.flash as any)?.success || 'Alerta creada correctamente.';
                success(mensaje);
                // Recargar las alertas
                router.get('/alertas', {}, {
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        });
    }
};

interface Usuario {
    id: number | string;
    name: string;
    apellido: string;
}
interface Modelo {
    id: number | string;
    nombre_modelo: string;
}
interface Alerta {
    id: number | string;
    created_at: string;
    user?: Usuario;
    fecha_alerta: string;
    serie: string;
    modelo?: Modelo;
    motivo: string;
    solucionado: boolean;
}
const props = defineProps<{
    alertas: Alerta[],
    usuarios: Usuario[],
    modelos: Modelo[],
    user_id?: number | string
}>()

const searchTerm = ref('');
const open_filtros = ref(false);
const fecha_desde = ref(null);
const fecha_hasta = ref(null);
const fecha_alerta_desde = ref(null);
const fecha_alerta_hasta = ref(null);
const usuario_filter = ref('');
const modelo_filter = ref('');

let searchTimer: any = null;

const handleSearch = () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/alertas', {
            search: searchTerm.value
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 500);
};

const handle_filtro_fechas = () => {
    router.get('/alertas', {
        fecha_desde: fecha_desde.value,
        fecha_hasta: fecha_hasta.value,
        fecha_alerta_desde: fecha_alerta_desde.value,
        fecha_alerta_hasta: fecha_alerta_hasta.value,
        usuario: usuario_filter.value,
        modelo: modelo_filter.value,
        search: searchTerm.value
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const limpiar_filtros = () => {
    fecha_desde.value = null;
    fecha_hasta.value = null;
    fecha_alerta_desde.value = null;
    fecha_alerta_hasta.value = null;
    usuario_filter.value = '';
    modelo_filter.value = '';
    router.get('/alertas', {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
const handle_change_solucionado = (alerta, solucionado) => {
    router.put('/alertas/toggleSolucionado', {
        id: alerta.id,
        solucionado: solucionado
    }, {
        onError(errors) {
            console.log('Errores:', errors);
            // Manejar diferentes tipos de errores
            if (errors.error) {
                error(errors.error);
            } else {
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    error(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    error('Error inesperado al cambiar el estado del remito. Por favor, intenta nuevamente.');
                }
            }
        },
        onSuccess(page) {

            const mensaje = (page.props.flash as any)?.success || 'Estado de la alerta actualizado correctamente.';
            success(mensaje);
        }
    });
};
const confirmandoEliminacion = ref(false);

const eliminarAlerta = () => {
    if (!formAlerta.value.id) return;
    loading_alerta.value = true;
    confirmandoEliminacion.value = false;
    router.delete(`/alertas/delete/${formAlerta.value.id}`, {
        onError(errors) {
            loading_alerta.value = false;
            confirmandoEliminacion.value = false;
            let mensaje = '';
            if (errors.error) {
                mensaje = errors.error;
            } else if (errors.message) {
                mensaje = errors.message;
            } else {
                const errorMessages = [];
                Object.keys(errors).forEach(key => {
                    if (Array.isArray(errors[key])) {
                        errorMessages.push(...errors[key]);
                    } else {
                        errorMessages.push(errors[key]);
                    }
                });
                if (errorMessages.length > 0) {
                    mensaje = errorMessages[0];
                } else {
                    mensaje = 'Error inesperado al eliminar la alerta. Por favor, intenta nuevamente.';
                }
            }
            error(mensaje);
        },
        onSuccess(page) {
            loading_alerta.value = false;
            alerta_modal.value = false;
            isEdit.value = false;
            formAlerta.value = {
                id: null,
                fecha_alerta: '',
                serie: '',
                modelo_id: '',
                motivo: ''
            };
            const mensaje = (page.props.flash as any)?.success || '🗑️ Alerta eliminada correctamente.';
            success(mensaje);
        }
    });
};
</script>

<template>

    <Head title="Alertas" />
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-5 lg:px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 lg:mt-10">
                <h1 class="text-[32px] font-bold text-gray-800">Registro de alertas</h1>
            </div>
            <div class="flex items-center justify-end gap-4">
                <div class="flex flex-col lg:flex-row items-center gap-2 w-full lg:w-auto">
                    <div class="relative w-full lg:w-auto">
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
                        class="flex items-center justify-center lg:justify-normal gap-2 px-6 py-2 bg-blue-600 text-white rounded-full cursor-pointer w-full lg:w-auto"
                        style="background-color: #0D509C;">
                        <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M7.02076 16C6.73743 16 6.49976 15.904 6.30776 15.712C6.11576 15.52 6.02009 15.2827 6.02076 15V9L0.220761 1.6C-0.0292387 1.26667 -0.0669053 0.916667 0.107761 0.55C0.282428 0.183334 0.586761 0 1.02076 0H15.0208C15.4541 0 15.7584 0.183334 15.9338 0.55C16.1091 0.916667 16.0714 1.26667 15.8208 1.6L10.0208 9V15C10.0208 15.2833 9.92476 15.521 9.73276 15.713C9.54076 15.905 9.30343 16.0007 9.02076 16H7.02076Z"
                                fill="white" />
                        </svg>
                        Filtros
                    </button>
                    <button @click="abrirModalAlerta"
                        class="flex w-full lg:w-[173px] justify-center items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-full cursor-pointer"
                        style="background-color: #0D509C;">
                        Añadir Alerta
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
                        <div class="flex-1 flex items-center gap-6">
                            <div class="flex flex-col w-full">
                                <label for="fecha_alerta_desde" class="" style="color: #5B5B5B;">Fecha alerta
                                    desde</label>
                                <input type="date" id="fecha_alerta_desde" v-model="fecha_alerta_desde"
                                    @change="handle_filtro_fechas"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                            <div class="flex flex-col w-full">
                                <label for="fecha_alerta_hasta" class="" style="color: #5B5B5B;">Fecha alerta
                                    hasta</label>
                                <input type="date" id="fecha_alerta_hasta" @change="handle_filtro_fechas"
                                    v-model="fecha_alerta_hasta"
                                    class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                            </div>
                        </div>
                        <div class="flex-1 flex flex-col">
                            <label for="usuario_filter" style="color: #5B5B5B;">Usuario</label>
                            <select id="usuario_filter" v-model="usuario_filter" @change="handle_filtro_fechas"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                                <option value="">Todos</option>
                                <option v-for="usuario in usuarios" :key="usuario.id" :value="usuario.id">
                                    {{ usuario.name }} {{ usuario.apellido }}
                                </option>
                            </select>
                        </div>
                        <div class="flex-1 flex flex-col">
                            <label for="modelo_filter" style="color: #5B5B5B;">Modelo</label>
                            <select id="modelo_filter" v-model="modelo_filter" @change="handle_filtro_fechas"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                                <option value="">Todos</option>
                                <option v-for="modelo in modelos" :key="modelo.id" :value="modelo.id">
                                    {{ modelo.nombre_modelo }}
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
                <table class="w-full bg-white overflow-hidden">
                    <thead class="bg-[#E1E5E9]">
                        <tr>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Fecha creación</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Usuario</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Fecha alerta</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                N° de serie</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Modelo</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Motivo</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider w-20">
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 ">
                        <tr v-for="alerta in alertas" :key="alerta.id">
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{
                                dayjs(alerta.created_at).format('DD/MM/YYYY') }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ alerta.user?.name }} {{
                                alerta.user?.apellido }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{
                                dayjs(alerta.fecha_alerta).format('DD/MM/YYYY') }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ alerta.serie }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ alerta.modelo?.nombre_modelo }}
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ alerta.motivo }}</td>
                            <td class="py-3 px-4 flex text-center justify-end items-center gap-6 w-20">
                                <div>
                                    <button @click="handle_change_solucionado(alerta, true)" v-if="!alerta.solucionado"
                                        class="cursor-pointer flex justify-center items-center w-full h-full">
                                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M16 0H2C1.46957 0 0.960859 0.210714 0.585786 0.585786C0.210714 0.960859 0 1.46957 0 2V16C0 16.5304 0.210714 17.0391 0.585786 17.4142C0.960859 17.7893 1.46957 18 2 18H16C16.5304 18 17.0391 17.7893 17.4142 17.4142C17.7893 17.0391 18 16.5304 18 16V2C18 1.46957 17.7893 0.960859 17.4142 0.585786C17.0391 0.210714 16.5304 0 16 0ZM16 2V16H2V2H16Z"
                                                fill="#D9D9D9" />
                                        </svg>
                                    </button>
                                    <button @click="handle_change_solucionado(alerta, false)" v-if="alerta.solucionado"
                                        class="cursor-pointer flex justify-center items-center w-full h-full">
                                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M16 0H2C1.46957 0 0.960859 0.210714 0.585786 0.585786C0.210714 0.960859 0 1.46957 0 2V16C0 16.5304 0.210714 17.0391 0.585786 17.4142C0.960859 17.7893 1.46957 18 2 18H16C16.5304 18 17.0391 17.7893 17.4142 17.4142C17.7893 17.0391 18 16.5304 18 16V2C18 1.46957 17.7893 0.960859 17.4142 0.585786C17.0391 0.210714 16.5304 0 16 0ZM16 2V16H2V2H16ZM7 14L3 10L4.41 8.58L7 11.17L13.59 4.58L15 6"
                                                fill="#0D509C" />
                                        </svg>
                                    </button>
                                </div>

                                <button @click="abrirModalEditar(alerta)" title="Editar"
                                    class="cursor-pointer flex justify-center items-center w-full h-full">
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
            <div v-if="alerta_modal" @click.self="cerrarModalAlerta"
                class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50"
                style="background-color: rgba(0, 0, 0, 0.5);">
                <div
                    class="bg-white rounded-lg p-6 w-[40vw] modal-animation overflow-y-auto max-h-[90vh] min-h-[350px] flex flex-col justify-between">
                    <div class="flex flex-col gap-1.5 mb-6">
                        <div class="flex justify-between items-center mb-4 h-10">
                            <h2 class="text-xl font-bold text-black">{{ isEdit ? 'Editar Alerta' : 'Nueva Alerta' }}
                            </h2>
                            <div v-if="isEdit" class="flex items-center gap-2">
                                <Transition name="delete-confirm" mode="out-in">
                                    <button v-if="!confirmandoEliminacion" @click="confirmandoEliminacion = true"
                                        type="button"
                                        class="flex items-center gap-2 text-red-700 hover:text-red-500 transition-colors cursor-pointer">
                                        <svg width="18" height="20" viewBox="0 0 14 18" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16ZM3 6H11V16H3V6ZM10.5 1L9.5 0H4.5L3.5 1H0V3H14V1H10.5Z"
                                                fill="currentColor" />
                                        </svg>
                                        <span class="text-lg font-medium">Eliminar</span>
                                    </button>
                                    <div v-else class="flex items-center gap-2">
                                        <button @click="eliminarAlerta" type="button"
                                            class="flex items-center gap-2 bg-red-700 hover:bg-white text-white hover:text-red-700 border border-red-700 px-4 py-2 rounded-md transition-colors cursor-pointer">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12.5 3.5L3.5 12.5M3.5 3.5L12.5 12.5" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <span class="font-medium">¿Seguro?</span>
                                        </button>
                                        <button @click="confirmandoEliminacion = false" type="button"
                                            class="border border-gray-700 hover:bg-gray-500 text-gray-700 hover:text-white px-4 py-2 rounded-md transition-colors cursor-pointer">
                                            <span class="font-medium">Cancelar</span>
                                        </button>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                        <div class="flex gap-8 w-full justify-between">
                            <div class="flex flex-col gap-1 w-full">
                                <label for="fecha_alerta" class="text-[#5B5B5B]">Fecha alerta</label>
                                <input type="date" id="fecha_alerta" v-model="formAlerta.fecha_alerta"
                                    class="border border-gray-300 p-2 rounded-md" />
                            </div>
                            <div class="flex flex-col gap-1 w-full">
                                <label for="serie" class="text-[#5B5B5B]">N° de serie</label>
                                <input type="text" id="serie" v-model="formAlerta.serie" @input="buscarModeloPorSerie"
                                    class="border border-gray-300 p-2 rounded-md" placeholder="Ingrese N° de serie" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 w-full mt-2">
                            <div class="flex items-center gap-4">
                                <label for="modelo" class="text-[#5B5B5B]">Modelo</label>
                                <div v-if="loading_alerta" class="text-gray-500 text-xs">Buscando modelo...</div>
                            </div>
                            <input type="text" id="modelo" :value="modeloNombre"
                                class="border border-gray-300 p-2 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed"
                                :class="{ 'border-red-500': errorAlerta && !modeloNombre }" readonly />
                        </div>
                        <div class="flex flex-col gap-1 w-full mt-2">
                            <label for="motivo" class="text-[#5B5B5B]">Motivo</label>
                            <textarea id="motivo" v-model="formAlerta.motivo"
                                class="border border-gray-300 p-2 rounded-md" rows="2"
                                placeholder="Motivo de la alerta"></textarea>
                        </div>
                        <div v-if="errorAlerta" class="text-red-500 text-sm mt-2">{{ errorAlerta }}</div>
                    </div>
                    <div class="flex items-center justify-between gap-3 mt-4 w-full">
                        <div class="flex justify-end space-x-2">
                            <button @click="cerrarModalAlerta"
                                class="w-[120px] py-2 bg-white text-[#0D509C] rounded-full hover:shadow-lg duration-300 cursor-pointer flex justify-center border border-[#0D509C]">Volver</button>
                        </div>
                        <div v-if="!loading_alerta" class="flex justify-end space-x-2">
                            <button @click="guardarAlerta"
                                class="w-[120px] py-2 bg-[#0D509C] text-white rounded-full hover:shadow-lg duration-300 cursor-pointer flex justify-center">
                                {{ isEdit ? 'Actualizar' : 'Guardar' }}
                            </button>
                        </div>
                        <button v-if="loading_alerta"
                            class="w-[120px] py-2 bg-[#0D509C] text-white rounded-full disabled:opacity-50 hover:shadow-lg duration-300 disabled:cursor-not-allowed cursor-pointer flex justify-center">
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
<style scoped>
.delete-confirm-enter-active,
.delete-confirm-leave-active {
    transition: all 0.3s ease;
}

.delete-confirm-enter-from {
    opacity: 0;
    transform: translateX(10px);
}

.delete-confirm-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}

.delete-confirm-enter-to,
.delete-confirm-leave-from {
    opacity: 1;
    transform: translateX(0);
}
</style>