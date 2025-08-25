<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { ref, reactive } from 'vue';

const { success, error } = useToast();

interface Sector {
    id: number;
    nombre: string;
    descripcion: string;
    activo: boolean;
}

interface Operario {
    id: number;
    nombre: string;
    apellido: string;
    n_legajo: string;
    codigo_qr: string;
    activo: boolean;
    sectores?: Sector[];
}

const props = defineProps<{
    operarios: Operario[];
    sectores: Sector[];
    legajo_sugerido?: string;
}>();

const timer = ref<number | null>(null);
const searchTerm = ref('');

const handleSearch = () => {
    if (timer.value) clearTimeout(timer.value);
    timer.value = setTimeout(() => {
        router.get('/operarios', {
            search: searchTerm.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 500);
}


// Estados para modal crear
const modalCreate = reactive({
    show: false,
    loading: false,
    data: {
        nombre: '',
        apellido: '',
        legajo: '',
        activo: true,
        sectores: [] as number[]
    }
});

// Estados para modal eliminar
const modalDelete = reactive({
    show: false,
    loading: false,
    operario: null as Operario | null
});

// Estados para modal editar
const modalEdit = reactive({
    show: false,
    loading: false,
    operario: null as Operario | null,
    data: {
        nombre: '',
        apellido: '',
        legajo: '',
        activo: true,
        sectores: [] as number[]
    }
});

// Estados para modal imprimir
const modalPrint = reactive({
    show: false,
    operario: null as Operario | null
});

const resetModalCreate = () => {
    modalCreate.data = {
        nombre: '',
        apellido: '',
        legajo: props.legajo_sugerido || '',
        activo: true,
        sectores: []
    };
};

const abrirModalCrear = () => {
    resetModalCreate();
    modalCreate.show = true;
};

const crearOperario = () => {
    modalCreate.loading = true;

    const formData = {
        operario_nombre: modalCreate.data.nombre,
        operario_apellido: modalCreate.data.apellido,
        operario_legajo: modalCreate.data.legajo,
        operario_activo: modalCreate.data.activo,
        sectores: modalCreate.data.sectores
    };

    router.post(route('operarios.store'), formData, {
        onError: (errors) => {
            modalCreate.loading = false;
            console.error('Errores de validación:', errors);

            if (errors.error) {
                error(errors.error);
            } else {
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    error(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    error('Error al crear el operario. Verifica que todos los campos estén completos.');
                }
            }
        },
        onSuccess: (page) => {
            modalCreate.loading = false;
            modalCreate.show = false;
            resetModalCreate();
            const mensaje = (page.props.flash as any)?.success || 'Operario creado correctamente';
            success(mensaje);
        }
    });
};

const abrirModalEliminar = (operario: Operario) => {
    modalDelete.operario = operario;
    modalDelete.show = true;
};

const cerrarModalEliminar = () => {
    modalDelete.show = false;
    modalDelete.operario = null;
};

const eliminarOperario = () => {
    if (!modalDelete.operario) {
        error('No hay operario seleccionado para eliminar');
        return;
    }

    modalDelete.loading = true;

    router.delete(route('operarios.destroy', modalDelete.operario.id), {
        onSuccess: () => {
            modalDelete.loading = false;
            modalDelete.show = false;
            modalDelete.operario = null;
            success('Operario eliminado correctamente');
        },
        onError: () => {
            modalDelete.loading = false;
            error('Error al eliminar el operario');
        }
    });
};

const abrirModalEditar = (operario: Operario) => {
    modalEdit.operario = operario;
    modalEdit.data = {
        nombre: operario.nombre,
        apellido: operario.apellido,
        legajo: operario.n_legajo,
        activo: operario.activo,
        sectores: operario.sectores?.map(s => s.id) || []
    };
    modalEdit.show = true;
};

const cerrarModalEditar = () => {
    modalEdit.show = false;
    modalEdit.operario = null;
    modalEdit.data = {
        nombre: '',
        apellido: '',
        legajo: '',
        activo: true,
        sectores: []
    };
};

const editarOperario = () => {
    if (!modalEdit.operario) return;

    modalEdit.loading = true;

    const formData = {
        operario_nombre: modalEdit.data.nombre,
        operario_apellido: modalEdit.data.apellido,
        operario_legajo: modalEdit.data.legajo,
        operario_activo: modalEdit.data.activo,
        sectores: modalEdit.data.sectores
    };

    router.put(route('operarios.update', modalEdit.operario.id), formData, {
        onError: (errors) => {
            modalEdit.loading = false;
            console.error('Errores de validación:', errors);

            if (errors.error) {
                error(errors.error);
            } else {
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    error(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    error('Error al editar el operario.');
                }
            }
        },
        onSuccess: (page) => {
            modalEdit.loading = false;
            modalEdit.show = false;
            cerrarModalEditar();
            const mensaje = (page.props.flash as any)?.success || 'Operario editado correctamente';
            success(mensaje);
        }
    });
};

const abrirModalImprimir = (operario: Operario) => {
    modalPrint.operario = operario;
    modalPrint.show = true;
};

const cerrarModalImprimir = () => {
    modalPrint.show = false;
    modalPrint.operario = null;
};


const imprimirEtiqueta = () => {
    if (!modalPrint.operario) return;

    router.post(route('operarios.imprimir', modalPrint.operario), {}, {
        onSuccess: (page: any) => {
            cerrarModalImprimir();
            const mensaje = (page.props.flash as any)?.message || 'Etiqueta impresa correctamente';
            success(mensaje);
        },
        onError: (errors) => {
            console.error("Error:", errors);
        }
    });

};

const getSectorNombres = (sectores: Sector[]) => {
    return sectores?.map(s => s.nombre).join(', ') || 'Sin sectores';
};

const toggleSector = (sectorId: number, sectoresArray: number[]) => {
    const index = sectoresArray.indexOf(sectorId);
    if (index > -1) {
        sectoresArray.splice(index, 1);
    } else {
        sectoresArray.push(sectorId);
    }
};
</script>

<template>

    <Head title="Operarios" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 lg:px-20 md:px-8 sm:px-4" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 mt-10">
                <button class="cursor-pointer" @click="router.get('/configuracion');">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 20L0 10L10 0L11.7812 1.75L4.78125 8.75H20V11.25H4.78125L11.7812 18.25L10 20Z"
                            fill="#626262" />
                    </svg>
                </button>
                <h1 class="text-[32px] font-bold text-gray-800">Operarios</h1>
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
                <button @click="abrirModalCrear"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer"
                    style="background-color: #0D509C;">
                    Añadir Operario
                </button>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto">
                <table class="w-full bg-white overflow-hidden">
                    <thead class="bg-[#E1E5E9]">
                        <tr>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Legajo</th>
                            <th class="py-3 px-4 text-start text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Nombre</th>
                            <th class="py-3 px-4 text-start text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Sectores</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Estado</th>
                            <th
                                class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="(operario, index) in props.operarios" :key="index">
                            <td class="py-3 px-4 text-sm text-center text-gray-800 font-mono">
                                {{ operario.n_legajo }}
                            </td>
                            <td class="py-3 px-4 text-sm text-start text-gray-800">
                                {{ operario.nombre }} {{ operario.apellido }}
                            </td>
                            <td class="py-3 px-4 text-sm text-start text-gray-800">
                                {{ getSectorNombres(operario.sectores || []) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span v-if="operario.activo"
                                    class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                    Activo
                                </span>
                                <span v-else class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">
                                    Inactivo
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center flex items-center justify-center gap-4">
                                <!-- Botón Imprimir -->
                                <button class="cursor-pointer hover:opacity-70" @click="abrirModalImprimir(operario)">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M15 4H13V0H5V4H3C1.34 4 0 5.34 0 7V12H3V18H15V12H18V7C18 5.34 16.66 4 15 4ZM7 2H11V4H7V2ZM13 16H5V11H13V16ZM15 9C14.45 9 14 8.55 14 8C14 7.45 14.45 7 15 7C15.55 7 16 7.45 16 8C16 8.55 15.55 9 15 9Z"
                                            fill="#0D509C" />
                                    </svg>
                                </button>
                                <!-- Botón Editar -->
                                <button class="cursor-pointer hover:opacity-70" @click="abrirModalEditar(operario)">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M17.71 4.0425C18.1 3.6525 18.1 3.0025 17.71 2.6325L15.37 0.2925C15 -0.0975 14.35 -0.0975 13.96 0.2925L12.12 2.1225L15.87 5.8725M0 14.2525V18.0025H3.75L14.81 6.9325L11.06 3.1825L0 14.2525Z"
                                            fill="#D9D9D9" />
                                    </svg>
                                </button>
                                <!-- Botón Eliminar -->
                                <button class="cursor-pointer hover:opacity-70" @click="abrirModalEliminar(operario)">
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

    <!-- Modal Crear Operario -->
    <div v-if="modalCreate.show" @click.self="modalCreate.show = false; resetModalCreate();"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50 p-4"
        style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="w-full max-w-[700px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-black">Nuevo Operario</h2>
            </div>

            <div class="flex gap-6">
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Nombre *</label>
                    <input v-model="modalCreate.data.nombre" type="text" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Nombre" />
                </div>
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Apellido</label>
                    <input v-model="modalCreate.data.apellido" type="text" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Apellido" />
                </div>
            </div>

            <div class="mb-6">
                <label class="block font-medium text-lg text-[#5B5B5B]">Legajo *</label>
                <input v-model="modalCreate.data.legajo" type="text" class="w-full border rounded px-3 py-2 mt-2"
                    placeholder="Legajo del operario" />
                <p class="text-sm text-gray-500 mt-1">El legajo debe ser único para cada operario</p>
            </div>

            <div class="mb-6">
                <label class="block font-medium text-lg text-[#5B5B5B] mb-3">Sectores</label>
                <div class="grid grid-cols-2 gap-3">
                    <label v-for="sector in props.sectores" :key="sector.id" class="flex items-center gap-2">
                        <input type="checkbox" :value="sector.id"
                            :checked="modalCreate.data.sectores.includes(sector.id)"
                            @change="toggleSector(sector.id, modalCreate.data.sectores)" class="w-4 h-4" />
                        <span class="text-sm text-gray-700">{{ sector.nombre }}</span>
                    </label>
                </div>
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-3">
                    <input v-model="modalCreate.data.activo" type="checkbox" class="w-4 h-4" />
                    <span class="font-medium text-lg text-[#5B5B5B]">Activo</span>
                </label>
            </div>

            <div class="flex justify-end pt-4">
                <button @click="crearOperario" :disabled="modalCreate.loading"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer"
                    style="background-color: #0D509C;">
                    <span v-if="!modalCreate.loading">Crear operario</span>
                    <span v-else>Creando...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Operario -->
    <div v-if="modalDelete.show" @click.self="cerrarModalEliminar"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50 p-4"
        style="background-color: rgba(0,0,0,0.5);">
        <div class="w-full max-w-[500px] bg-white rounded-lg p-6">
            <h2 class="text-lg text-center font-semibold text-gray-800 mb-4">
                ¿Estás seguro de eliminar el operario
                <span class="font-bold">{{ modalDelete.operario?.nombre }} {{ modalDelete.operario?.apellido }}</span>?
            </h2>
            <div class="flex justify-center gap-4">
                <button @click="cerrarModalEliminar"
                    class="w-[173px] py-2 bg-gray-200 text-gray-800 rounded-full cursor-pointer">
                    Cancelar
                </button>
                <button @click="eliminarOperario" :disabled="modalDelete.loading"
                    class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full cursor-pointer">
                    <span v-if="!modalDelete.loading">Eliminar</span>
                    <span v-else>Eliminando...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Editar Operario -->
    <div v-if="modalEdit.show" @click.self="cerrarModalEditar"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50 p-4"
        style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="w-full max-w-[700px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-black">Editar Operario</h2>
            </div>

            <div class="flex gap-6">
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Nombre *</label>
                    <input v-model="modalEdit.data.nombre" type="text" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Nombre" />
                </div>
                <div class="mb-6 w-full">
                    <label class="block font-medium text-lg text-[#5B5B5B]">Apellido</label>
                    <input v-model="modalEdit.data.apellido" type="text" class="w-full border rounded px-3 py-2 mt-2"
                        placeholder="Apellido" />
                </div>
            </div>

            <div class="mb-6">
                <label class="block font-medium text-lg text-[#5B5B5B]">Legajo *</label>
                <input v-model="modalEdit.data.legajo" type="text" class="w-full border rounded px-3 py-2 mt-2"
                    placeholder="Legajo del operario" />
                <p class="text-sm text-gray-500 mt-1">El legajo debe ser único para cada operario</p>
            </div>

            <div class="mb-6">
                <label class="block font-medium text-lg text-[#5B5B5B] mb-3">Sectores</label>
                <div class="grid grid-cols-2 gap-3">
                    <label v-for="sector in props.sectores" :key="sector.id" class="flex items-center gap-2">
                        <input type="checkbox" :value="sector.id" :checked="modalEdit.data.sectores.includes(sector.id)"
                            @change="toggleSector(sector.id, modalEdit.data.sectores)" class="w-4 h-4" />
                        <span class="text-sm text-gray-700">{{ sector.nombre }}</span>
                    </label>
                </div>
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-3">
                    <input v-model="modalEdit.data.activo" type="checkbox" class="w-4 h-4" />
                    <span class="font-medium text-lg text-[#5B5B5B]">Activo</span>
                </label>
            </div>

            <div class="flex justify-end pt-4">
                <button @click="editarOperario" :disabled="modalEdit.loading"
                    class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer"
                    style="background-color: #0D509C;">
                    <span v-if="!modalEdit.loading">Guardar cambios</span>
                    <span v-else>Guardando...</span>
                </button>
            </div>
        </div>
    </div>

    <div v-if="modalPrint.show" @click.self="cerrarModalImprimir"
        class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50 p-4"
        style="background-color: rgba(0,0,0,0.5);">
        <div class="w-full max-w-[500px] bg-white rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-black">Imprimir Etiqueta</h2>
            </div>

            <div class="text-center mb-6">
                <div class="bg-gray-50 p-6 rounded-lg mb-4">
                    <h3 class="text-lg font-semibold mb-2">{{ modalPrint.operario?.nombre }} {{
                        modalPrint.operario?.apellido }}</h3>
                    <p class="text-gray-600 mb-4">Legajo: {{ modalPrint.operario?.n_legajo }}</p>

                    <!-- Código de barras SVG real -->
                    <div class="flex justify-center mb-2">
                        <div class="bg-white p-4 border rounded">
                            <img v-if="modalPrint.operario" :src="`/operarios/barcode/${modalPrint.operario.id}`"
                                :alt="'Código de barras: ' + modalPrint.operario.codigo_qr"
                                class="mx-auto max-w-full h-auto mb-2" style="max-height: 60px;" />
                            <p class="text-xs font-mono">{{ modalPrint.operario?.codigo_qr }}</p>
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-600 mb-4">
                    Se abrirá una nueva ventana con la etiqueta lista para imprimir
                </p>
            </div>

            <div class="flex justify-center gap-4">
                <button @click="cerrarModalImprimir"
                    class="w-[173px] py-2 bg-gray-200 text-gray-800 rounded-full cursor-pointer">
                    Cancelar
                </button>
                <button @click="imprimirEtiqueta"
                    class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full cursor-pointer">
                    Imprimir
                </button>
            </div>
        </div>
    </div>
</template>