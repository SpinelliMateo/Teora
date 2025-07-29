<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
// import axios from 'axios';
import { ref } from 'vue';

// Interfaces para tipar los roles y permisos
interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

const { success, error } = useToast();
const props = defineProps<{ roles: Role[]; permisos: Permission[] }>();

let modal_create_rol = ref(false);
let loading_create_rol = ref(false);
let rol_nombre = ref('');
let permisos_seleccionados = ref<string[]>([]);
let subpermisos_seleccionados = ref<Record<string, string[]>>({});

let modal_edit_rol = ref(false);
let modal_delete_rol = ref(false);
let rol_editando = ref<any>(null);
let rol_eliminando = ref<any>(null);
let loading_edit_rol = ref(false);
let loading_delete_rol = ref(false);

const secciones = [
    {
        label: 'Inicio',
        permisos: ['inicio'],
        subdivide: false
    },
    {
        label: 'Órdenes de fabricación',
        permisos: ['ver ordenes', 'gestionar ordenes'],
        subdivide: true
    },
    {
        label: 'Stock',
        permisos: ['stock'],
        subdivide: false
    },
    {
        label: 'Servicio técnico',
        permisos: ['ver servicio tecnico', 'gestionar servicio tecnico'],
        subdivide: true
    },
    {
        label: 'Seguimiento por proceso',
        permisos: ['ver servicio proceso', 'gestionar servicio proceso'],
        subdivide: true
    },
    {
        label: 'Registro de alertas',
        permisos: ['alertas'],
        subdivide: false
    },
    {
        label: 'Reportes',
        permisos: ['reportes'],
        subdivide: false
    },
    {
        label: 'Remitos',
        permisos: ['ver remitos', 'gestionar remitos'],
        subdivide: true
    },
    {
        label: 'Configuración',
        permisos: ['configuracion'],
        subdivide: false
    },
];

const resetCrearRol = () => {
    rol_nombre.value = '';
    permisos_seleccionados.value = [];
    subpermisos_seleccionados.value = {};
};

const handlePermisoChange = (permiso: string, subdivide: boolean) => {
    if (!subdivide) {
        if (permisos_seleccionados.value.includes(permiso)) {
            permisos_seleccionados.value = permisos_seleccionados.value.filter((p: string) => p !== permiso);
        } else {
            permisos_seleccionados.value.push(permiso);
        }
    }
};

const handleSeccionChange = (seccion: any) => {
    if (seccion.subdivide) {
        if (permisos_seleccionados.value.includes(seccion.label)) {
            permisos_seleccionados.value = permisos_seleccionados.value.filter((p: string) => p !== seccion.label);
            subpermisos_seleccionados.value[seccion.label] = [];
        } else {
            permisos_seleccionados.value.push(seccion.label);
            subpermisos_seleccionados.value[seccion.label] = [];
        }
    } else {
        handlePermisoChange(seccion.permisos[0], false);
    }
};

const handleSubpermisoChange = (seccionLabel: string, permiso: string) => {
    if (!subpermisos_seleccionados.value[seccionLabel]) {
        subpermisos_seleccionados.value[seccionLabel] = [];
    }
    if (subpermisos_seleccionados.value[seccionLabel].includes(permiso)) {
        subpermisos_seleccionados.value[seccionLabel] = subpermisos_seleccionados.value[seccionLabel].filter((p: string) => p !== permiso);
    } else {
        subpermisos_seleccionados.value[seccionLabel].push(permiso);
    }
};

const guardarRol = () => {
    loading_create_rol.value = true;
    let permisosFinal: string[] = [];
    secciones.forEach(seccion => {
        if (seccion.subdivide) {
            if (permisos_seleccionados.value.includes(seccion.label)) {
                permisosFinal = permisosFinal.concat(subpermisos_seleccionados.value[seccion.label] || []);
            }
        } else {
            if (permisos_seleccionados.value.includes(seccion.permisos[0])) {
                permisosFinal.push(seccion.permisos[0]);
            }
        }
    });
    router.post(route('roles.store'), {
        name: rol_nombre.value,
        permissions: permisosFinal
    }, {
        onSuccess(page) {
            loading_create_rol.value = false;
            modal_create_rol.value = false;
            resetCrearRol();
            const mensaje = (page.props.flash as any)?.success || 'Rol creado correctamente';
            success(mensaje);
        },
        onError(errors) {
            loading_create_rol.value = false;
            if (errors.error) {
                error(errors.error);
            } else if (errors.message) {
                error(errors.message);
            } else {
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    error(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    error('Error al crear el rol');
                }
            }
        }
    });
};

const abrirModalEditar = (rol: any) => {
    rol_editando.value = rol;
    rol_nombre.value = rol.name;
    permisos_seleccionados.value = [];
    subpermisos_seleccionados.value = {};
    secciones.forEach(seccion => {
        if (seccion.subdivide) {
            const seleccionados = (rol.permissions || []).filter((p: any) => seccion.permisos.includes(p.name)).map((p: any) => p.name);
            if (seleccionados.length) {
                permisos_seleccionados.value.push(seccion.label);
                subpermisos_seleccionados.value[seccion.label] = seleccionados;
            }
        } else {
            if ((rol.permissions || []).find((p: any) => p.name === seccion.permisos[0])) {
                permisos_seleccionados.value.push(seccion.permisos[0]);
            }
        }
    });
    modal_edit_rol.value = true;
};

const cerrarModalEditar = () => {
    modal_edit_rol.value = false;
    rol_editando.value = null;
    resetCrearRol();
};

const guardarEdicionRol = () => {
    loading_edit_rol.value = true;
    let permisosFinal: string[] = [];
    secciones.forEach(seccion => {
        if (seccion.subdivide) {
            if (permisos_seleccionados.value.includes(seccion.label)) {
                permisosFinal = permisosFinal.concat(subpermisos_seleccionados.value[seccion.label] || []);
            }
        } else {
            if (permisos_seleccionados.value.includes(seccion.permisos[0])) {
                permisosFinal.push(seccion.permisos[0]);
            }
        }
    });
    router.put(route('roles.update', rol_editando.value.id), {
        name: rol_nombre.value,
        permissions: permisosFinal
    }, {
        onSuccess(page) {
            loading_edit_rol.value = false;
            modal_edit_rol.value = false;
            resetCrearRol();
            const mensaje = (page.props.flash as any)?.success || 'Rol actualizado correctamente';
            success(mensaje);
        },
        onError(errors) {
            loading_edit_rol.value = false;
            if (errors.error) {
                error(errors.error);
            } else if (errors.message) {
                error(errors.message);
            } else {
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    error(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    error('Error al actualizar el rol');
                }
            }
        }
    });
};

const abrirModalEliminar = (rol: any) => {
    rol_eliminando.value = rol;
    modal_delete_rol.value = true;
};

const cerrarModalEliminar = () => {
    modal_delete_rol.value = false;
    rol_eliminando.value = null;
};

const eliminarRol = () => {
    loading_delete_rol.value = true;
    router.delete(route('roles.destroy', rol_eliminando.value.id), {
        onSuccess(page) {
            loading_delete_rol.value = false;
            modal_delete_rol.value = false;
            const mensaje = (page.props.flash as any)?.success || 'Rol eliminado correctamente';
            success(mensaje);
        },
        onError(errors) {
            loading_delete_rol.value = false;
            if (errors.error) {
                error(errors.error);
            } else if (errors.message) {
                error(errors.message);
            } else {
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    error(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    error('Error al eliminar el rol');
                }
            }
        }
    });
};
</script>


<template>
    <!-- Modal Crear Rol -->
    <div v-if="modal_create_rol" @click.self="modal_create_rol = false; resetCrearRol();"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50"
        style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="w-[900px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-black">Nuevo Rol</h2>
            </div>
            <div class="mb-6">
                <label class="block font-medium text-lg text-[#5B5B5B]">Nombre del rol</label>
                <input v-model="rol_nombre" type="text" class="w-full border rounded px-3 py-2 mt-2"
                    placeholder="Nombre" />
            </div>
            <div class="mb-6">
                <label class="block font-medium text-[#5B5B5B]">Permisos</label>
                <div class="grid grid-cols-2 gap-4 mt-2">
                    <div v-for="seccion in secciones" :key="seccion.label" class="border-b pb-4 mb-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox"
                                :checked="seccion.subdivide ? permisos_seleccionados.includes(seccion.label) : permisos_seleccionados.includes(seccion.permisos[0])"
                                @change="handleSeccionChange(seccion)" :id="'seccion-' + seccion.label"
                                class="accent-[#0D509C]" />
                            <label :for="'seccion-' + seccion.label" class="text-lg">{{ seccion.label }}</label>
                        </div>
                        <transition name="fade">
                            <div v-if="seccion.subdivide && permisos_seleccionados.includes(seccion.label)"
                                class="ml-6 mt-2 flex gap-4">
                                <div v-for="permiso in seccion.permisos" :key="permiso" class="flex items-center gap-2">
                                    <input type="checkbox"
                                        :checked="subpermisos_seleccionados[seccion.label]?.includes(permiso)"
                                        @change="handleSubpermisoChange(seccion.label, permiso)"
                                        :id="'subpermiso-' + seccion.label + '-' + permiso"
                                        class="rounded-full accent-[#0D509C]" />
                                    <label :for="'subpermiso-' + seccion.label + '-' + permiso">{{
                                        permiso.charAt(0).toUpperCase() + permiso.slice(1) }}</label>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>
            <div class="flex justify-end pt-4">
                <button @click="guardarRol" :disabled="loading_create_rol"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer"
                    style="background-color: #0D509C;">
                    <span v-if="!loading_create_rol">Guardar rol</span>
                    <span v-else>Guardando...</span>
                </button>
            </div>
        </div>
    </div>
    <!-- Modal Editar Rol -->
    <div v-if="modal_edit_rol" @click.self="cerrarModalEditar"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50"
        style="background-color: rgba(0,0,0,0.5);">
        <div class="w-[900px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-black">Editar Rol</h2>
            </div>
            <div class="mb-6">
                <label class="block font-medium text-lg text-[#5B5B5B]">Nombre del rol</label>
                <input v-model="rol_nombre" type="text" class="w-full border rounded px-3 py-2 mt-2"
                    placeholder="Nombre" />
            </div>
            <div class="mb-6">
                <label class="block font-medium text-[#5B5B5B]">Permisos</label>
                <div class="grid grid-cols-2 gap-4 mt-2">
                    <div v-for="seccion in secciones" :key="seccion.label" class="border-b pb-4 mb-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox"
                                :checked="seccion.subdivide ? permisos_seleccionados.includes(seccion.label) : permisos_seleccionados.includes(seccion.permisos[0])"
                                @change="handleSeccionChange(seccion)" :id="'edit-seccion-' + seccion.label"
                                class="accent-[#0D509C]" />
                            <label :for="'edit-seccion-' + seccion.label" class="text-lg">{{ seccion.label }}</label>
                        </div>
                        <transition name="fade-height">
                            <div v-if="seccion.subdivide && permisos_seleccionados.includes(seccion.label)"
                                class="ml-6 mt-2 flex gap-4 overflow-hidden">
                                <div v-for="permiso in seccion.permisos" :key="permiso" class="flex items-center gap-2">
                                    <input type="checkbox"
                                        :checked="subpermisos_seleccionados[seccion.label]?.includes(permiso)"
                                        @change="handleSubpermisoChange(seccion.label, permiso)"
                                        :id="'edit-subpermiso-' + seccion.label + '-' + permiso"
                                        class="rounded-full accent-[#0D509C]" />
                                    <label :for="'edit-subpermiso-' + seccion.label + '-' + permiso">{{
                                        permiso.charAt(0).toUpperCase() + permiso.slice(1) }}</label>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>
            <div class="flex justify-end pt-4 gap-4">
                <button @click="cerrarModalEditar"
                    class="px-6 py-2 border border-[#0D509C] text-[#0D509C] rounded-full cursor-pointer">Cancelar</button>
                <button @click="guardarEdicionRol" :disabled="loading_edit_rol"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer"
                    style="background-color: #0D509C;">
                    <span v-if="!loading_edit_rol">Guardar</span>
                    <span v-else>Guardando...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Rol -->
    <div v-if="modal_delete_rol" @click.self="cerrarModalEliminar"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50"
        style="background-color: rgba(0,0,0,0.5);">
        <div class="w-[400px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">¿Estás seguro de eliminar el rol <span
                    class="font-bold">{{ rol_eliminando?.name }}</span>?</h2>
            <div class="flex justify-between gap-4">
                <button @click="cerrarModalEliminar"
                    class="w-[173px] py-2 bg-gray-200 text-gray-800 rounded-full cursor-pointer hover:shadow-md duration-300">Cancelar</button>
                <button v-if="!loading_delete_rol" @click="eliminarRol"
                    class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full disabled:opacity-50 hover:shadow-lg duration-300 disabled:cursor-not-allowed cursor-pointer">Eliminar</button>
                <button v-if="loading_delete_rol"
                    class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full flex justify-center">
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

    <Head title="Roles" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 mt-10">
                <button class="cursor-pointer" @click="router.get('/configuracion');">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 20L0 10L10 0L11.7812 1.75L4.78125 8.75H20V11.25H4.78125L11.7812 18.25L10 20Z"
                            fill="#626262" />
                    </svg>
                </button>
                <h1 class="text-[32px] font-bold text-gray-800">Roles/Grupos</h1>
            </div>

            <div class="flex justify-end items-center ">
                <button @click="modal_create_rol = true"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer"
                    style="background-color: #0D509C;">
                    Añadir Rol
                </button>
            </div>

            <div class="flex flex-col gap-6">
                <template v-for="rol in props.roles">
                    <div class="flex items-center justify-between bg-white p-4 rounded-lg shadow-md">
                        <div class="flex flex-col gap-1">
                            <h2>{{ rol.name.charAt(0).toUpperCase() + rol.name.slice(1) }}</h2>
                            <div class="flex items-center gap-1 flex-wrap">
                                <span v-for="(permiso, index) in rol.permissions" :key="permiso.id"
                                    class="text-gray-600 text-left whitespace-nowrap">
                                    {{ permiso.name.charAt(0).toUpperCase() + permiso.name.slice(1) }}<span
                                        v-if="index < rol.permissions.length - 1">, </span>
                                </span>
                            </div>
                        </div>
                        <div>
                            <td class="py-3 px-4 text-right flex items-center gap-4">
                                <button class="cursor-pointer hover:opacity-70" @click="abrirModalEditar(rol)">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M17.71 4.0425C18.1 3.6525 18.1 3.0025 17.71 2.6325L15.37 0.2925C15 -0.0975 14.35 -0.0975 13.96 0.2925L12.12 2.1225L15.87 5.8725M0 14.2525V18.0025H3.75L14.81 6.9325L11.06 3.1825L0 14.2525Z"
                                            fill="#D9D9D9" />
                                    </svg>
                                </button>
                                <button class="cursor-pointer hover:opacity-70" @click="abrirModalEliminar(rol)">
                                    <svg width="14" height="18" viewBox="0 0 14 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M14 1H10.5L9.5 0H4.5L3.5 1H0V3H14M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16Z"
                                            fill="#D9D9D9" />
                                    </svg>
                                </button>
                            </td>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>

</template>
<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s, transform 0.3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.fade-enter-to,
.fade-leave-from {
    opacity: 1;
    transform: translateY(0);
}
</style>
