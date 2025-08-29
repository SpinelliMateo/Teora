<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { ref } from 'vue';

// Interfaces
interface SectorAcceso {
    id: number;
    sector: string;
    activo: boolean;
    created_at: string;
    updated_at: string;
}

const { success, error } = useToast();
const props = defineProps<{ 
    sectores: SectorAcceso[];
    sectores_disponibles: Record<string, string>;
}>();

// Comentamos las variables para crear sectores - ya no se necesitan
// let modal_create_sector = ref(false);
// let loading_create_sector = ref(false);
// let sector_seleccionado = ref('');
// let codigo_nuevo = ref('');
// let activo_nuevo = ref(true);

let modal_edit_sector = ref(false);
let sector_editando = ref<any>(null);
let codigo_editando = ref('');
let activo_editando = ref(true);
let loading_edit_sector = ref(false);

// Comentamos las variables para eliminar sectores - ya no se necesitan
// let modal_delete_sector = ref(false);
// let sector_eliminando = ref<any>(null);
// let loading_delete_sector = ref(false);

// Comentamos la función de reset crear - ya no se necesita
// const resetCrearSector = () => {
//     sector_seleccionado.value = '';
//     codigo_nuevo.value = '';
//     activo_nuevo.value = true;
// };

const getSectorNombre = (sector: string): string => {
    return props.sectores_disponibles[sector] || sector;
};

// Comentamos la función para obtener sectores disponibles - ya no se necesita
// const getSectoresDisponiblesParaCrear = () => {
//     const sectoresExistentes = props.sectores.map(s => s.sector);
//     return Object.entries(props.sectores_disponibles)
//         .filter(([key]) => !sectoresExistentes.includes(key));
// };

// Comentamos la función guardar sector - ya no se necesita
// const guardarSector = () => {
//     loading_create_sector.value = true;
//     router.post(route('sector-accesos.store'), {
//         sector: sector_seleccionado.value,
//         codigo: codigo_nuevo.value,
//         activo: activo_nuevo.value
//     }, {
//         onSuccess(page) {
//             loading_create_sector.value = false;
//             modal_create_sector.value = false;
//             resetCrearSector();
//             const mensaje = (page.props.flash as any)?.success || 'Código de sector creado correctamente';
//             success(mensaje);
//         },
//         onError(errors) {
//             loading_create_sector.value = false;
//             if (errors.error) {
//                 error(errors.error);
//             } else if (errors.message) {
//                 error(errors.message);
//             } else {
//                 const firstError = Object.values(errors)[0];
//                 if (firstError) {
//                     error(Array.isArray(firstError) ? firstError[0] : firstError);
//                 } else {
//                     error('Error al crear el código de sector');
//                 }
//             }
//         }
//     });
// };

const abrirModalEditar = (sector: any) => {
    sector_editando.value = sector;
    codigo_editando.value = '';
    activo_editando.value = sector.activo;
    modal_edit_sector.value = true;
};

const cerrarModalEditar = () => {
    modal_edit_sector.value = false;
    sector_editando.value = null;
    codigo_editando.value = '';
    activo_editando.value = true;
};

const guardarEdicionSector = () => {
    loading_edit_sector.value = true;
    router.put(route('sector-accesos.update', sector_editando.value.id), {
        codigo: codigo_editando.value,
        activo: activo_editando.value
    }, {
        onSuccess(page) {
            loading_edit_sector.value = false;
            modal_edit_sector.value = false;
            const mensaje = (page.props.flash as any)?.success || 'Código de sector actualizado correctamente';
            success(mensaje);
        },
        onError(errors) {
            loading_edit_sector.value = false;
            if (errors.error) {
                error(errors.error);
            } else if (errors.message) {
                error(errors.message);
            } else {
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    error(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    error('Error al actualizar el código de sector');
                }
            }
        }
    });
};

// Comentamos las funciones de eliminar - ya no se necesitan
// const abrirModalEliminar = (sector: any) => {
//     sector_eliminando.value = sector;
//     modal_delete_sector.value = true;
// };

// const cerrarModalEliminar = () => {
//     modal_delete_sector.value = false;
//     sector_eliminando.value = null;
// };

// const eliminarSector = () => {
//     loading_delete_sector.value = true;
//     router.delete(route('sector-accesos.destroy', sector_eliminando.value.id), {
//         onSuccess(page) {
//             loading_delete_sector.value = false;
//             modal_delete_sector.value = false;
//             const mensaje = (page.props.flash as any)?.success || 'Código de sector eliminado correctamente';
//             success(mensaje);
//         },
//         onError(errors) {
//             loading_delete_sector.value = false;
//             if (errors.error) {
//                 error(errors.error);
//             } else if (errors.message) {
//                 error(errors.message);
//             } else {
//                 const firstError = Object.values(errors)[0];
//                 if (firstError) {
//                     error(Array.isArray(firstError) ? firstError[0] : firstError);
//                 } else {
//                     error('Error al eliminar el código de sector');
//                 }
//             }
//         }
//     });
// };

const toggleActivo = (sector: any) => {
    router.put(route('sector-accesos.toggle-activo', sector.id), {}, {
        onSuccess(page) {
            const mensaje = (page.props.flash as any)?.success || 'Estado actualizado correctamente';
            success(mensaje);
        },
        onError(errors) {
            error('Error al cambiar el estado del sector');
        }
    });
};
</script>

<template>
    <!-- COMENTADO: Modal Crear Código de Sector -->
    <!-- 
    <div v-if="modal_create_sector" @click.self="modal_create_sector = false; resetCrearSector();"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50"
        style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="w-[600px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-black">Nuevo Código de Sector</h2>
            </div>
            
            <div class="mb-6">
                <label class="block font-medium text-lg text-[#5B5B5B] mb-2">Sector</label>
                <select v-model="sector_seleccionado" 
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0D509C] focus:border-transparent">
                    <option value="">Seleccione un sector</option>
                    <option v-for="[key, value] in getSectoresDisponiblesParaCrear()" :key="key" :value="key">
                        {{ value }}
                    </option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block font-medium text-lg text-[#5B5B5B] mb-2">Código de acceso</label>
                <input v-model="codigo_nuevo" type="text" 
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0D509C] focus:border-transparent"
                    placeholder="Ej: PREARM001" 
                    @input="codigo_nuevo = $event.target.value.toUpperCase()" />
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" v-model="activo_nuevo" class="accent-[#0D509C]" />
                    <span class="text-[#5B5B5B]">Sector activo</span>
                </label>
            </div>

            <div class="flex justify-end pt-4 gap-4">
                <button @click="modal_create_sector = false; resetCrearSector();"
                    class="px-6 py-2 border border-[#0D509C] text-[#0D509C] rounded-full hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button @click="guardarSector" :disabled="loading_create_sector || !sector_seleccionado || !codigo_nuevo"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                    style="background-color: #0D509C;">
                    <span v-if="!loading_create_sector">Guardar</span>
                    <span v-else>Guardando...</span>
                </button>
            </div>
        </div>
    </div>
    -->

    <!-- Modal Editar Código -->
    <div v-if="modal_edit_sector" @click.self="cerrarModalEditar"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50"
        style="background-color: rgba(0,0,0,0.5);">
        <div class="w-[600px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-black">
                    Editar Código - {{ getSectorNombre(sector_editando?.sector) }}
                </h2>
            </div>

            <div class="mb-6">
                <label class="block font-medium text-lg text-[#5B5B5B] mb-2">Nuevo código de acceso</label>
                <input v-model="codigo_editando" type="text" 
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#0D509C] focus:border-transparent"
                    placeholder="Ingrese el nuevo código"
                    @input="codigo_editando = $event.target.value.toUpperCase()" />
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="checkbox" v-model="activo_editando" class="accent-[#0D509C]" />
                    <span class="text-[#5B5B5B]">Sector activo</span>
                </label>
            </div>

            <div class="flex justify-end pt-4 gap-4">
                <button @click="cerrarModalEditar"
                    class="px-6 py-2 border border-[#0D509C] text-[#0D509C] rounded-full hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button @click="guardarEdicionSector" :disabled="loading_edit_sector || !codigo_editando"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                    style="background-color: #0D509C;">
                    <span v-if="!loading_edit_sector">Actualizar</span>
                    <span v-else>Actualizando...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- COMENTADO: Modal Eliminar -->
    <!--
    <div v-if="modal_delete_sector" @click.self="cerrarModalEliminar"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50"
        style="background-color: rgba(0,0,0,0.5);">
        <div class="w-[500px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                ¿Estás seguro de eliminar el código del sector 
                <span class="font-bold">{{ getSectorNombre(sector_eliminando?.sector) }}</span>?
            </h2>
            <p class="text-sm text-gray-600 mb-6">
                Los operarios de este sector no podrán acceder hasta que se configure un nuevo código.
            </p>
            <div class="flex justify-between gap-4">
                <button @click="cerrarModalEliminar"
                    class="w-[173px] py-2 bg-gray-200 text-gray-800 rounded-full hover:bg-gray-300 transition-colors">
                    Cancelar
                </button>
                <button v-if="!loading_delete_sector" @click="eliminarSector"
                    class="w-[173px] py-2 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors">
                    Eliminar
                </button>
                <button v-if="loading_delete_sector"
                    class="w-[173px] py-2 bg-red-600 text-white rounded-full flex justify-center">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
                        <radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125" gradientTransform="scale(1.5)">
                            <stop offset="0" stop-color="#FFFFFF"></stop>
                            <stop offset=".3" stop-color="#FFFFFF" stop-opacity=".9"></stop>
                            <stop offset=".6" stop-color="#FFFFFF" stop-opacity=".6"></stop>
                            <stop offset=".8" stop-color="#FFFFFF" stop-opacity=".3"></stop>
                            <stop offset="1" stop-color="#FFFFFF" stop-opacity="0"></stop>
                        </radialGradient>
                        <circle transform-origin="center" fill="none" stroke="url(#a12)" stroke-width="15"
                            stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0" cx="100" cy="100"
                            r="70">
                            <animateTransform type="rotate" attributeName="transform" calcMode="spline" dur="2"
                                values="360;0" keyTimes="0;1" keySplines="0 0 1 1" repeatCount="indefinite">
                            </animateTransform>
                        </circle>
                        <circle transform-origin="center" fill="none" opacity=".2" stroke="#FFFFFF" stroke-width="15"
                            stroke-linecap="round" cx="100" cy="100" r="70"></circle>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    -->

    <Head title="Códigos de Sectores" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 mt-10">
                <button class="cursor-pointer" @click="router.get('/configuracion');">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 20L0 10L10 0L11.7812 1.75L4.78125 8.75H20V11.25H4.78125L11.7812 18.25L10 20Z"
                            fill="#626262" />
                    </svg>
                </button>
                <h1 class="text-[32px] font-bold text-gray-800">Códigos de Acceso por Sector</h1>
            </div>

            <!-- COMENTADO: Botón para añadir código -->
            <!--
            <div class="flex justify-end items-center">
                <button @click="modal_create_sector = true"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer hover:opacity-90 transition-opacity"
                    style="background-color: #0D509C;">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 1V15M1 8H15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Añadir Código
                </button>
            </div>
            -->

            <div class="flex flex-col gap-4">
                <template v-for="sector in props.sectores" :key="sector.id">
                    <div class="flex items-center justify-between bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-3">
                                <h2 class="text-xl font-semibold text-gray-800">
                                    {{ getSectorNombre(sector.sector) }}
                                </h2>
                                <span v-if="sector.activo" 
                                    class="px-3 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                    Activo
                                </span>
                                <span v-else 
                                    class="px-3 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">
                                    Inactivo
                                </span>
                            </div>
                            <div class="text-sm text-gray-600">
                                <span>Código configurado</span>
                                <span class="mx-2">•</span>
                                <span>Actualizado: {{ new Date(sector.updated_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <!-- Toggle activo/inactivo -->
                            <button @click="toggleActivo(sector)"
                                class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                                :title="sector.activo ? 'Desactivar sector' : 'Activar sector'">
                                <svg v-if="sector.activo" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M10 2C14.97 2 19 6.03 19 11C19 15.97 14.97 20 10 20C5.03 20 1 15.97 1 11C1 6.03 5.03 2 10 2ZM10 4C6.13 4 3 7.13 3 11C3 14.87 6.13 18 10 18C13.87 18 17 14.87 17 11C17 7.13 13.87 4 10 4ZM10 6L14 11H11V16H9V11H6L10 6Z" 
                                        fill="#10B981"/>
                                </svg>
                                <svg v-else width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M10 2C14.97 2 19 6.03 19 11C19 15.97 14.97 20 10 20C5.03 20 1 15.97 1 11C1 6.03 5.03 2 10 2ZM10 4C6.13 4 3 7.13 3 11C3 14.87 6.13 18 10 18C13.87 18 17 14.87 17 11C17 7.13 13.87 4 10 4ZM14 10V12H6V10H14Z" 
                                        fill="#EF4444"/>
                                </svg>
                            </button>

                            <!-- Editar -->
                            <button class="cursor-pointer hover:opacity-70 p-2 rounded-lg hover:bg-gray-100 transition-colors" 
                                @click="abrirModalEditar(sector)">
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M17.71 4.0425C18.1 3.6525 18.1 3.0025 17.71 2.6325L15.37 0.2925C15 -0.0975 14.35 -0.0975 13.96 0.2925L12.12 2.1225L15.87 5.8725M0 14.2525V18.0025H3.75L14.81 6.9325L11.06 3.1825L0 14.2525Z"
                                        fill="#6B7280" />
                                </svg>
                            </button>

                            <!-- COMENTADO: Botón Eliminar -->
                            <!--
                            <button class="cursor-pointer hover:opacity-70 p-2 rounded-lg hover:bg-gray-100 transition-colors" 
                                @click="abrirModalEliminar(sector)">
                                <svg width="14" height="18" viewBox="0 0 14 18" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M14 1H10.5L9.5 0H4.5L3.5 1H0V3H14M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16Z"
                                        fill="#EF4444" />
                                </svg>
                            </button>
                            -->
                        </div>
                    </div>
                </template>

                <!-- Estado vacío - Modificado para reflejar que no se pueden crear sectores -->
                <div v-if="props.sectores.length === 0" 
                    class="text-center py-12 bg-white rounded-lg shadow-sm">
                    <div class="text-gray-400 mb-4">
                        <svg width="64" height="64" viewBox="0 0 64 64" fill="currentColor" class="mx-auto">
                            <path d="M32 6C17.64 6 6 17.64 6 32s11.64 26 26 26 26-11.64 26-26S46.36 6 32 6zm0 48c-12.13 0-22-9.87-22-22s9.87-22 22-22 22 9.87 22 22-9.87 22-22 22zm1-37h-2v22l19.4 11.6 1-1.6L33 28V17z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No hay sectores configurados</h3>
                    <p class="text-gray-600 mb-6">Los sectores deben ser configurados desde el sistema.</p>
                    <!-- COMENTADO: Botón para crear primer código -->
                    <!--
                    <button @click="modal_create_sector = true"
                        class="px-6 py-2 text-white rounded-full cursor-pointer hover:opacity-90 transition-opacity"
                        style="background-color: #0D509C;">
                        Crear primer código
                    </button>
                    -->
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.modal-animation {
    animation: modalAppear 0.3s ease-out;
}

@keyframes modalAppear {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>