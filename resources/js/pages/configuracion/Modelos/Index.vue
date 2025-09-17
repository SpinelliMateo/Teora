<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { ref, reactive } from 'vue';

const { success, error } = useToast();

interface Modelo {
    id: number;
    modelo: string;
    nombre_modelo: string;
    tension: string;
    frecuencia: string;
    corriente: string;
    potencia: string;
    aislacion: string;
    sistema: string;
    volumen: string;
    espumante: string;
    clase: string;
    gas: string;
    cantidad_gas: string;
}

const props = defineProps<{
    modelos: Modelo[];
}>();

const timer = ref<number | null>(null);
const searchTerm = ref('');

const handleSearch = () => {
    if (timer.value) clearTimeout(timer.value);
    timer.value = setTimeout(() => {
        router.get('/modelos', {
            search: searchTerm.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 500);
}

// Estados para modal eliminar
const modalDelete = reactive({
    show: false,
    loading: false,
    modelo: null as Modelo | null
});

const abrirModalEliminar = (modelo: Modelo) => {
    modalDelete.modelo = modelo;
    modalDelete.show = true;
};

const cerrarModalEliminar = () => {
    modalDelete.show = false;
    modalDelete.modelo = null;
};

const eliminarModelo = () => {
    if (!modalDelete.modelo) {
        error('No hay modelo seleccionado para eliminar');
        return;
    }

    modalDelete.loading = true;

    router.delete(route('modelos.destroy', modalDelete.modelo.id), {
        onSuccess: () => {
            modalDelete.loading = false;
            modalDelete.show = false;
            modalDelete.modelo = null;
            success('Modelo eliminado correctamente');
        },
        onError: () => {
            modalDelete.loading = false;
            error('Error al eliminar el modelo');
        }
    });
};

const irACrear = () => {
    router.get(route('modelos.create'));
};

const irAEditar = (modelo: Modelo) => {
    router.get(route('modelos.edit', modelo.id));
};
</script>

<template>
    <Head title="Modelos" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 lg:px-20 md:px-8 sm:px-4" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 lg:mt-10">
                <button class="cursor-pointer" @click="router.get('/configuracion');">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 20L0 10L10 0L11.7812 1.75L4.78125 8.75H20V11.25H4.78125L11.7812 18.25L10 20Z"
                            fill="#626262" />
                    </svg>
                </button>
                <h1 class="text-[32px] font-bold text-gray-800">Modelos</h1>
            </div>

            <div class="flex justify-end items-center gap-4">
                <div class="relative">
                    <input type="text" placeholder="Buscar" @input="handleSearch"
                        class="px-10 py-2 border rounded-full focus:outline-none text-black placeholder-[#0D509C] w-[200px]"
                        style="border-color: #0D509C;" v-model="searchTerm" />
                    <span class="absolute left-3 top-3 text-gray-400">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M6.5 13C4.68333 13 3.146 12.3707 1.888 11.112C0.63 9.85333 0.000667196 8.316 5.29101e-07 6.5C-0.000666138 4.684 0.628667 3.14667 1.888 1.888C3.14733 0.629333 4.68467 0 6.5 0C8.31533 0 9.853 0.629333 11.113 1.888C12.373 3.14667 13.002 4.684 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.81267 10.5627 9.688 9.688C10.5633 8.81333 11.0007 7.75067 11 6.5C10.9993 5.24933 10.562 4.187 9.688 3.313C8.814 2.439 7.75133 2.00133 6.5 2C5.24867 1.99867 4.18633 2.43633 3.313 3.313C2.43967 4.18967 2.002 5.252 2 6.5C1.998 7.748 2.43567 8.81067 3.313 9.688C4.19033 10.5653 5.25267 11.0027 6.5 11Z"
                                fill="#0D509C" />
                        </svg>
                    </span>
                </div>
                <button @click="irACrear"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer"
                    style="background-color: #0D509C;">
                    Añadir Modelo
                </button>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto">
                <table class="w-full bg-white overflow-hidden">
                    <thead class="bg-[#E1E5E9]">
                        <tr>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                N° Modelo</th>
                            <th class="py-3 px-4 text-start text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Nombre</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Tensión</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Sistema</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Gas</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="(modelo, index) in props.modelos" :key="index">
                            <td class="py-3 px-4 text-sm text-center text-gray-800 font-mono">
                                {{ modelo.modelo }}
                            </td>
                            <td class="py-3 px-4 text-sm text-start text-gray-800">
                                {{ modelo.nombre_modelo }}
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                {{ modelo.tension }}
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                {{ modelo.sistema }}
                            </td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                {{ modelo.gas }}
                            </td>
                            <td class="py-3 px-4 text-center flex items-center justify-center gap-4">
                                <!-- Botón Editar -->
                                <button class="cursor-pointer hover:opacity-70" @click="irAEditar(modelo)">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M17.71 4.0425C18.1 3.6525 18.1 3.0025 17.71 2.6325L15.37 0.2925C15 -0.0975 14.35 -0.0975 13.96 0.2925L12.12 2.1225L15.87 5.8725M0 14.2525V18.0025H3.75L14.81 6.9325L11.06 3.1825L0 14.2525Z"
                                            fill="#D9D9D9" />
                                    </svg>
                                </button>
                                <!-- Botón Eliminar -->
                                <button class="cursor-pointer hover:opacity-70" @click="abrirModalEliminar(modelo)">
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

    <!-- Modal Eliminar Modelo -->
    <div v-if="modalDelete.show" @click.self="cerrarModalEliminar"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50 p-4"
        style="background-color: rgba(0,0,0,0.5);">
        <div class="w-full max-w-[500px] bg-white rounded-lg p-6">
            <h2 class="text-lg text-center font-semibold text-gray-800 mb-4">
                ¿Estás seguro de eliminar el modelo
                <span class="font-bold">{{ modalDelete.modelo?.nombre_modelo }}</span>?
            </h2>
            <div class="flex justify-center gap-4">
                <button @click="cerrarModalEliminar"
                    class="w-[173px] py-2 bg-gray-200 text-gray-800 rounded-full cursor-pointer">
                    Cancelar
                </button>
                <button @click="eliminarModelo" :disabled="modalDelete.loading"
                    class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full cursor-pointer">
                    <span v-if="!modalDelete.loading">Eliminar</span>
                    <span v-else>Eliminando...</span>
                </button>
            </div>
        </div>
    </div>
</template>