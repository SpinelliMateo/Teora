<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import axios from 'axios';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

interface Role {
    id: number;
    name: string;
}

interface Usuario {
    id: number;
    name: string;
    apellido: string;
    username: string;
    email: string;
    roles: Role[];
}

const props = defineProps<{ usuarios: Usuario[]; roles: Role[] }>();
import { Ref } from 'vue';
const timer: Ref<number | null> = ref(null);
const searchTerm = ref('');

const handleSearch = () => {
    clearTimeout(timer.value as unknown as number);
    timer.value = setTimeout(() => {
        router.get('/usuarios', {
            search: searchTerm.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 500);
}

const { success, error } = useToast();


let modal_create_usuario = ref(false);
let loading_create_usuario = ref(false);
let usuario_name = ref('');
let usuario_apellido = ref('');
let usuario_username = ref('');
let usuario_email = ref('');
let usuario_password = ref('');
let usuario_password_confirmation = ref('');
let usuario_rol = ref('');

let modal_delete_usuario = ref(false);
let usuario_eliminando = ref<Usuario | null>(null);
let loading_delete_usuario = ref(false);

let modal_edit_usuario = ref(false);
let loading_edit_usuario = ref(false);
let usuario_edit = ref<Usuario | null>(null);
let usuario_edit_name = ref('');
let usuario_edit_apellido = ref('');
let usuario_edit_username = ref('');
let usuario_edit_email = ref('');
let usuario_edit_password = ref('');
let usuario_edit_password_confirmation = ref('');
let usuario_edit_rol = ref('');

const formUsuario = useForm({
    usuario_name: '',
    usuario_apellido: '',
    usuario_username: '',
    usuario_email: '',
    usuario_password: '',
    usuario_password_confirmation: '',
    usuario_rol: '',
});

const formEditUsuario = useForm({
    usuario_name: '',
    usuario_apellido: '',
    usuario_username: '',
    usuario_email: '',
    usuario_password: '',
    usuario_password_confirmation: '',
    usuario_rol: '',
});

const resetCrearUsuario = () => {
    usuario_name.value = '';
    usuario_apellido.value = '';
    usuario_username.value = '';
    usuario_email.value = '';
    usuario_password.value = '';
    usuario_password_confirmation.value = '';
    usuario_rol.value = '';
};

const crearUsuario = () => {
    loading_create_usuario.value = true;
    formUsuario.usuario_name = usuario_name.value;
    formUsuario.usuario_apellido = usuario_apellido.value;
    formUsuario.usuario_username = usuario_username.value;
    formUsuario.usuario_email = usuario_email.value;
    formUsuario.usuario_password = usuario_password.value;
    formUsuario.usuario_password_confirmation = usuario_password_confirmation.value;
    formUsuario.usuario_rol = usuario_rol.value;
    formUsuario.post(route('usuarios.store'), {
        onError(errors) {
            loading_create_usuario.value = false;
            if (errors.error) {
                error(errors.error);
            } else if (errors.message) {
                error(errors.message);
            } else {
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    error(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    error('Error inesperado al crear el usuario. Verifica que todos los campos estén completos.');
                }
            }
        },
        onSuccess(page) {
            loading_create_usuario.value = false;
            modal_create_usuario.value = false;
            resetCrearUsuario();
            formUsuario.reset();
            const mensaje = (page.props.flash as any)?.success || 'Usuario creado correctamente';
            success(mensaje);
        }
    });
};

const abrirModalEliminar = (usuario: any) => {
    usuario_eliminando.value = usuario;
    modal_delete_usuario.value = true;
};

const cerrarModalEliminar = () => {
    modal_delete_usuario.value = false;
    usuario_eliminando.value = null;
};

const eliminarUsuario = () => {
    if (!usuario_eliminando.value) {
        error('No hay usuario seleccionado para eliminar');
        return;
    }
    loading_delete_usuario.value = true;
    router.delete(route('usuarios.destroy', usuario_eliminando.value.id), {
        onSuccess: () => {
            loading_delete_usuario.value = false;
            modal_delete_usuario.value = false;
            success('Usuario eliminado correctamente');
        },
        onError: () => {
            loading_delete_usuario.value = false;
            error('Error al eliminar el usuario');
        }
    });
};

const abrirModalEditar = (usuario: Usuario) => {
    usuario_edit.value = usuario;
    usuario_edit_name.value = usuario.name;
    usuario_edit_apellido.value = usuario.apellido;
    usuario_edit_username.value = usuario.username;
    usuario_edit_email.value = usuario.email;
    usuario_edit_rol.value = usuario.roles.length > 0 ? String(usuario.roles[0].id) : '';
    usuario_edit_password.value = '';
    usuario_edit_password_confirmation.value = '';
    modal_edit_usuario.value = true;
};

const cerrarModalEditar = () => {
    modal_edit_usuario.value = false;
    usuario_edit.value = null;
    usuario_edit_name.value = '';
    usuario_edit_apellido.value = '';
    usuario_edit_username.value = '';
    usuario_edit_email.value = '';
    usuario_edit_rol.value = '';
    usuario_edit_password.value = '';
    usuario_edit_password_confirmation.value = '';
};

const editarUsuario = () => {
    if (!usuario_edit.value) return;
    loading_edit_usuario.value = true;
    formEditUsuario.usuario_name = usuario_edit_name.value;
    formEditUsuario.usuario_apellido = usuario_edit_apellido.value;
    formEditUsuario.usuario_username = usuario_edit_username.value;
    formEditUsuario.usuario_email = usuario_edit_email.value;
    formEditUsuario.usuario_password = usuario_edit_password.value;
    formEditUsuario.usuario_password_confirmation = usuario_edit_password_confirmation.value;
    formEditUsuario.usuario_rol = usuario_edit_rol.value;
    formEditUsuario.put(route('usuarios.update', usuario_edit.value.id), {
        onError(errors) {
            loading_edit_usuario.value = false;
            if (errors.error) {
                error(errors.error);
            } else if (errors.message) {
                error(errors.message);
            } else {
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    error(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    error('Error inesperado al editar el usuario. Verifica que todos los campos estén completos.');
                }
            }
        },
        onSuccess(page) {
            loading_edit_usuario.value = false;
            modal_edit_usuario.value = false;
            cerrarModalEditar();
            formEditUsuario.reset();
            const mensaje = (page.props.flash as any)?.success || 'Usuario editado correctamente';
            success(mensaje);
        }
    });
};
</script>


<template>

    <Head title="Usuarios" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-5 lg:px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 lg:mt-10">
                <button class="cursor-pointer" @click="router.get('/configuracion');">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 20L0 10L10 0L11.7812 1.75L4.78125 8.75H20V11.25H4.78125L11.7812 18.25L10 20Z"
                            fill="#626262" />
                    </svg>
                </button>
                <h1 class="text-[32px] font-bold text-gray-800">Usuarios</h1>
            </div>

            <div class="flex flex-col lg:flex-row justify-end items-center gap-2">
                <div class="relative w-full">
                    <input type="text" placeholder="Buscar" @input="handleSearch"
                        class="px-10 py-2 border rounded-full focus:outline-none text-black  placeholder-[#0D509C] w-full lg:w-[200px]"
                        style="border-color: #0D509C;" v-model="searchTerm" />
                    <span class="absolute left-3 top-3 text-gray-400">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M6.5 13C4.68333 13 3.146 12.3707 1.888 11.112C0.63 9.85333 0.000667196 8.316 5.29101e-07 6.5C-0.000666138 4.684 0.628667 3.14667 1.888 1.888C3.14733 0.629333 4.68467 0 6.5 0C8.31533 0 9.853 0.629333 11.113 1.888C12.373 3.14667 13.002 4.684 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.81267 10.5627 9.688 9.688C10.5633 8.81333 11.0007 7.75067 11 6.5C10.9993 5.24933 10.562 4.187 9.688 3.313C8.814 2.439 7.75133 2.00133 6.5 2C5.24867 1.99867 4.18633 2.43633 3.313 3.313C2.43967 4.18967 2.002 5.252 2 6.5C1.998 7.748 2.43567 8.81067 3.313 9.688C4.19033 10.5653 5.25267 11.0027 6.5 11Z"
                                fill="#0D509C" />
                        </svg>
                    </span>
                </div>
                <button @click="modal_create_usuario = true"
                    class="flex items-center text-center justify-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer w-full lg:w-[180px]"
                    style="background-color: #0D509C;">
                    Añadir Usuario
                </button>
            </div>
            <div class="w-full overflow-x-auto">
                <table class="w-full bg-white overflow-hidden">
                    <thead class="bg-[#E1E5E9]">
                        <tr>
                            <th
                                class="py-3 px-24 text-center text-sm font-medium text-gray-600 uppercase tracking-wider w-40">
                                USUARIO</th>
                            <th
                                class="py-3 px-4 text-start text-sm font-medium text-gray-600 uppercase tracking-wider ">
                                ROL</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider w-30">
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="(user, index) in props.usuarios" :key="index">
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ user.name.charAt(0).toUpperCase()
                                + user.name.slice(1) }} {{ user.apellido ? user.apellido.charAt(0).toUpperCase() +
                                    user.apellido.slice(1) : '' }}</td>
                            <td class="py-3 px-4 text-sm text-start text-gray-800">
                                {{ user.roles && user.roles.length > 0 ? user.roles[0].name.charAt(0).toUpperCase() +
                                    user.roles[0].name.slice(1) : '' }}
                            </td>
                            <td class="py-3 px-4 text-right flex items-center gap-4">
                                <button class="cursor-pointer hover:opacity-70" @click="abrirModalEditar(user)">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M17.71 4.0425C18.1 3.6525 18.1 3.0025 17.71 2.6325L15.37 0.2925C15 -0.0975 14.35 -0.0975 13.96 0.2925L12.12 2.1225L15.87 5.8725M0 14.2525V18.0025H3.75L14.81 6.9325L11.06 3.1825L0 14.2525Z"
                                            fill="#D9D9D9" />
                                    </svg>
                                </button>
                                <button class="cursor-pointer hover:opacity-70" @click="abrirModalEliminar(user)">
                                    <svg width="14" height="18" viewBox="0 0 14 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M14 1H10.5L9.5 0H4.5L3.5 1H0V3H14M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16Z"
                                            fill="#D9D9D9" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
    <!-- Modal Crear Usuario -->
    <div v-if="modal_create_usuario" @click.self="modal_create_usuario = false; resetCrearUsuario();"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50 px-2 lg:px-0"
        style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="w-[900px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-black">Nuevo Usuario</h2>
            </div>
            <div class="flex gap-6">
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Nombre</label>
                    <input v-model="usuario_name" type="text" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Nombre" />
                </div>
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Apellido</label>
                    <input v-model="usuario_apellido" type="text" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Apellido" />
                </div>
            </div>
            <div class="flex gap-6">
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Username</label>
                    <input v-model="usuario_username" type="text" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Username" />
                </div>
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Email</label>
                    <input v-model="usuario_email" type="email" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Email" pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$" required />
                </div>
            </div>
            <div class="flex gap-6">
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Contraseña</label>
                    <input v-model="usuario_password" type="password" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Contraseña" />
                </div>
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Confirmar Contraseña</label>
                    <input v-model="usuario_password_confirmation" type="password"
                        class="w-full border rounded px-3 py-2 mt-2" placeholder="Confirmar Contraseña" />
                </div>
            </div>
            <div class="mb-6">
                <label class="block font-medium text-lg text-[#5B5B5B]">Rol</label>
                <select v-model="usuario_rol" class="w-full border rounded px-3 py-2 mt-2">
                    <option v-for="rol in props.roles" :key="rol.id" :value="rol.id">
                        {{ rol.name.charAt(0).toUpperCase() + rol.name.slice(1) }}
                    </option>
                </select>

            </div>
            <div class="flex justify-end pt-4">
                <button @click="crearUsuario" :disabled="loading_create_usuario"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer"
                    style="background-color: #0D509C;">
                    <span v-if="!loading_create_usuario">Crear usuario</span>
                    <span v-else>Creando...</span>
                </button>
            </div>
        </div>
    </div>
    <!-- Modal Eliminar Usuario -->
    <div v-if="modal_delete_usuario" @click.self="cerrarModalEliminar"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50 px-2 lg:px-0"
        style="background-color: rgba(0,0,0,0.5);">
        <div class="w-[500px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
            <h2 class="text-lg text-center font-semibold text-gray-800 mb-4">¿Estás seguro de eliminar el usuario <span
                    class="font-bold">{{ usuario_eliminando?.name }} {{ usuario_eliminando?.apellido }}</span>?</h2>
            <div class="flex justify-center gap-4">
                <button @click="cerrarModalEliminar"
                    class="w-[173px] py-2 bg-gray-200 text-gray-800 rounded-full cursor-pointer hover:shadow-md duration-300">Cancelar</button>
                <button v-if="!loading_delete_usuario" @click="eliminarUsuario"
                    class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full disabled:opacity-50 hover:shadow-lg duration-300 disabled:cursor-not-allowed cursor-pointer">Eliminar</button>
                <button v-if="loading_delete_usuario"
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
    <!-- Modal Editar Usuario -->
    <div v-if="modal_edit_usuario" @click.self="cerrarModalEditar"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50 px-2 lg:px-0"
        style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="w-[900px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-black">Editar Usuario</h2>
            </div>
            <div class="flex gap-6">
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Nombre</label>
                    <input v-model="usuario_edit_name" type="text" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Nombre" />
                </div>
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Apellido</label>
                    <input v-model="usuario_edit_apellido" type="text" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Apellido" />
                </div>
            </div>
            <div class="flex gap-6">
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Username</label>
                    <input v-model="usuario_edit_username" type="text" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Username" />
                </div>
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Email</label>
                    <input v-model="usuario_edit_email" type="mail" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Email" />
                </div>
            </div>
            <div class="flex gap-6">
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Contraseña</label>
                    <input v-model="usuario_edit_password" type="password" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Dejar en blanco si no desea cambiar" />
                </div>
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Confirmar Contraseña</label>
                    <input v-model="usuario_edit_password_confirmation" type="password"
                        class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Dejar en blanco si no desea cambiar" />
                </div>
            </div>
            <div class="mb-6">
                <label class="block font-medium text-lg text-[#5B5B5B]">Rol</label>
                <select v-model="usuario_edit_rol" class="w-full border rounded px-3 py-2 mt-2">
                    <option v-for="rol in props.roles" :key="rol.id" :value="rol.id">
                        {{ rol.name.charAt(0).toUpperCase() + rol.name.slice(1) }}
                    </option>
                </select>

            </div>
            <div class="flex justify-end pt-4">
                <button @click="editarUsuario" :disabled="loading_edit_usuario"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer"
                    style="background-color: #0D509C;">
                    <span v-if="!loading_edit_usuario">Guardar cambios</span>
                    <span v-else>Guardando...</span>
                </button>
            </div>
        </div>
    </div>
</template>
