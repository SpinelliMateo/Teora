<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import 'vue-select/dist/vue-select.css';

const { success, error } = useToast();

const props = defineProps({
    remitos: Object,
    modelos: Array,
    filtro: String,
    search: String,
    proximo_numero: String,
    can: Object
})

console.log('Remitos:', props.remitos);

const form = useForm({
    id: '',
    n_remito: props.proximo_numero || '00000001',
    cliente: '',
    modelos: [{ modelo_id: '', cantidad: 1 }]
});
const formDespachado = useForm({
    id: '',
    estado: '',
});

const loading_remito = ref(false);
const remito_modal = ref(false);
const editando = ref(false);
const remito_seleccionado = ref(null);
const confirmandoEliminacion = ref(false);

const agregarModelo = () => {
    form.modelos.push({ modelo_id: '', cantidad: 1 });
};

const eliminarModelo = (index: number) => {
    if (form.modelos.length > 1) {
        form.modelos.splice(index, 1);
    }
};

// Función para verificar si un modelo ya está seleccionado
const isModeloSeleccionado = (modeloId: string, currentIndex: number) => {
    return form.modelos.some((modelo, index) =>
        modelo.modelo_id === modeloId && index !== currentIndex && modeloId !== ''
    );
};

// Función para obtener modelos disponibles para un índice específico
const getModelosDisponibles = (currentIndex: number) => {
    const modelosSeleccionados = form.modelos
        .map((modelo, index) => index !== currentIndex ? modelo.modelo_id : null)
        .filter(id => id !== null && id !== '');

    return (props.modelos as any[]).filter(modelo =>
        !modelosSeleccionados.includes(modelo.id.toString())
    );
};

// Función para validar antes de guardar
const validarModelosDuplicados = () => {
    const modelosSeleccionados = form.modelos
        .map(modelo => modelo.modelo_id)
        .filter(id => id !== '');

    const duplicados = modelosSeleccionados.filter((item, index) =>
        modelosSeleccionados.indexOf(item) !== index
    );

    return duplicados.length === 0;
};

const abrirModal = () => {
    editando.value = false;
    remito_seleccionado.value = null;
    confirmandoEliminacion.value = false;
    remito_modal.value = true;
    // Resetear el formulario y establecer el número correcto
    form.reset();
    form.n_remito = props.proximo_numero || '00000001';
    form.modelos = [{ modelo_id: '', cantidad: 1 }];
};

const abrirModalEdicion = (remito: any) => {
    editando.value = true;
    remito_seleccionado.value = remito;
    confirmandoEliminacion.value = false;
    remito_modal.value = true;

    // Cargar los datos del remito en el formulario
    form.reset();
    form.id = remito.id;
    form.n_remito = remito.n_remito;
    form.cliente = remito.cliente;

    // Cargar los modelos del remito
    form.modelos = remito.modelos.map((modelo: any) => ({
        modelo_id: modelo.id,
        cantidad: modelo.pivot.cantidad
    }));
};

const eliminarRemito = () => {
    if (remito_seleccionado.value) {
        loading_remito.value = true;
        const remito = remito_seleccionado.value as any;

        form.delete(`/remitos/${remito.id}`, {
            onError(errors) {
                console.log('Errores de eliminación:', errors);
                loading_remito.value = false;
                confirmandoEliminacion.value = false;

                if (errors.error) {
                    error(errors.error);
                } else if (errors.message) {
                    error(errors.message);
                } else {
                    const firstError = Object.values(errors)[0];
                    if (firstError) {
                        error(Array.isArray(firstError) ? firstError[0] : firstError);
                    } else {
                        error('Error inesperado al eliminar el remito. Por favor, intenta nuevamente.');
                    }
                }
            },
            onSuccess(page: any) {
                loading_remito.value = false;
                remito_modal.value = false;
                editando.value = false;
                remito_seleccionado.value = null;
                confirmandoEliminacion.value = false;
                form.reset();

                const mensaje = (page.props.flash as any)?.message || '🗑️ Remito eliminado correctamente.';
                success(mensaje);
            }
        });
    }
};

const create_remito = () => {
    // Validar modelos duplicados antes de enviar
    if (!validarModelosDuplicados()) {
        error('❌ No se pueden seleccionar modelos duplicados. Por favor, revisa tu selección.');
        return;
    }

    // Validar que todos los modelos tengan un ID seleccionado
    const modelosVacios = form.modelos.some(modelo => modelo.modelo_id === '');
    if (modelosVacios) {
        error('❌ Todos los modelos deben estar seleccionados. Por favor, completa la información.');
        return;
    }

    loading_remito.value = true;

    if (editando.value && remito_seleccionado.value) {
        // Actualizar remito existente
        form.put(`/remitos/update/${(remito_seleccionado.value as any).id}`, {
            onError(errors) {
                console.log('Errores de actualización:', errors);
                loading_remito.value = false;

                if (errors.error) {
                    error(errors.error);
                } else if (errors.message) {
                    error(errors.message);
                } else {
                    // Manejar errores de validación
                    const errorMessages: any[] = [];
                    Object.keys(errors).forEach(key => {
                        if (Array.isArray(errors[key])) {
                            errorMessages.push(...errors[key]);
                        } else {
                            errorMessages.push(errors[key]);
                        }
                    });

                    if (errorMessages.length > 0) {
                        error('❌ Error al actualizar: ' + errorMessages[0]);
                    } else {
                        error('Error inesperado al actualizar el remito. Verifica los datos e intenta nuevamente.');
                    }
                }
            },
            onSuccess(page: any) {
                loading_remito.value = false;
                remito_modal.value = false;
                editando.value = false;
                remito_seleccionado.value = null;
                form.reset();

                const mensaje = (page.props.flash as any)?.message || 'Remito actualizado correctamente.';
                success(mensaje);
            }
        });
    } else {
        // Crear nuevo remito
        form.post('/remitos/create', {
            onError(errors) {
                console.log('Errores de creación:', errors);
                loading_remito.value = false;

                if (errors.error) {
                    error(errors.error);
                } else if (errors.message) {
                    error(errors.message);
                } else {
                    // Manejar errores de validación
                    const errorMessages: any[] = [];
                    Object.keys(errors).forEach(key => {
                        if (Array.isArray(errors[key])) {
                            errorMessages.push(...errors[key]);
                        } else {
                            errorMessages.push(errors[key]);
                        }
                    });

                    if (errorMessages.length > 0) {
                        error('❌ Error al crear remito: ' + errorMessages[0]);
                    } else {
                        error('Error inesperado al crear el remito. Verifica que todos los campos estén completos.');
                    }
                }
            },
            onSuccess(page: any) {
                loading_remito.value = false;
                remito_modal.value = false;
                form.reset();

                // Usar el próximo número del backend si está disponible, sino usar el default
                const nuevoNumero = page.props.flash?.proximo_numero || props.proximo_numero || '00000001';
                form.n_remito = nuevoNumero;
                form.modelos = [{ modelo_id: '', cantidad: 1 }];

                const mensaje = (page.props.flash as any)?.message || '🎉 Remito creado exitosamente.';
                success(mensaje);
            }
        });
    }
}


const timer = ref(null);
const searchTerm = ref('');

const handle_filtro = (filtro: string | null, search = null) => {
    searchTerm.value = '';
    router.get('/remitos', { filtro: filtro ?? props.filtro, search: search }, {
        preserveState: true, // opcional, mantiene el estado actual (útil para scroll o inputs)
        preserveScroll: true, // opcional, mantiene la posición del scroll
    });

}

const handleSearch = () => {
    clearTimeout(timer.value as any);
    timer.value = setTimeout(() => {
        router.get('/remitos', {
            search: searchTerm.value,
            filtro: props.filtro
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 500);
}

const handle_change_estado = ((remito, estado: string) => {
    formDespachado.id = remito.id;
    formDespachado.estado = estado;
    formDespachado.put('/remitos/despachar/' + remito.id, {
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
            // Acceder al mensaje desde la respuesta del servidor
            const mensaje = (page.props.flash as any)?.message || 'Estado del remito actualizado correctamente.';
            success(mensaje);
        }
    });
});
</script>


<template>

    <Head title="Remitos" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 mt-10">
                <h1 class="text-[32px] font-bold text-gray-800">Remitos</h1>
            </div>

            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-5 ml-1">
                    <div class="flex flex-col items-center">
                        <button @click="handle_filtro('EN PROCESO')" class="text-lg  cursor-pointer"
                            :class="filtro == 'EN PROCESO' ? 'text-[#0D509C] font-bold' : 'text-[#5B5B5B]'">EN
                            PROCESO</button>
                        <div class="h-[2px] w-[110%] mt-1"
                            :class="filtro == 'EN PROCESO' ? 'bg-[#0D509C]' : 'bg-[#5B5B5B]'"></div>
                    </div>
                    <div class="flex flex-col items-center">
                        <button @click="handle_filtro('DESPACHO')" class="text-lg cursor-pointer"
                            :class="filtro == 'DESPACHO' ? 'text-[#0D509C] font-bold' : 'text-[#5B5B5B]'">EN
                            DESPACHO</button>
                        <div class="h-[2px]  w-[110%] mt-1"
                            :class="filtro == 'DESPACHO' ? 'bg-[#0D509C]' : 'bg-[#5B5B5B]'"></div>
                    </div>
                    <div class="flex flex-col items-center">
                        <button @click="handle_filtro('FINALIZADOS')" class="text-lg cursor-pointer"
                            :class="filtro == 'FINALIZADOS' ? 'text-[#0D509C] font-bold' : 'text-[#5B5B5B]'">FINALIZADOS</button>
                        <div class="h-[2px]  w-[110%] mt-1"
                            :class="filtro == 'FINALIZADOS' ? 'bg-[#0D509C]' : 'bg-[#5B5B5B]'"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-3">
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
                    <button v-if="can?.gestionar" @click="abrirModal"
                        class="bg-[#0D509C] text-white px-4 py-2 rounded-full w-[173px] cursor-pointer">Añadir
                        remito</button>
                </div>
            </div>
            <div class="grid grid-cols-5 gap-6 mt-2">
                <template v-for="remito in (remitos as any)?.data" :key="remito?.id">
                    <div v-if="remito"
                        @click="can?.gestionar && remito.estado !== 'finalizado' ? abrirModalEdicion(remito) : null"
                        :class="[
                            'min-h-[128px] w-full border border-gray-200 px-2 py-3 flex flex-col gap-3 transition-shadow',
                            remito.estado === 'finalizado'
                                ? 'bg-gray-100 cursor-not-allowed opacity-75'
                                : can?.gestionar ? 'bg-white cursor-pointer hover:shadow-md' : 'bg-white cursor-default'
                        ]">
                        <div class="flex justify-between">
                            <h3 class="font-extrabold text-lg flex items-center gap-2">
                                Remito N°{{ remito.n_remito }}
                                <span v-if="remito.estado === 'finalizado'"
                                    class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-medium">
                                    FINALIZADO
                                </span>
                            </h3>
                            <div @click.stop v-if="can?.gestionar">
                                <button @click="handle_change_estado(remito, true)" v-if="remito.estado === 'procesado'"
                                    class="cursor-pointer flex justify-center items-center w-full h-full">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M16 0H2C1.46957 0 0.960859 0.210714 0.585786 0.585786C0.210714 0.960859 0 1.46957 0 2V16C0 16.5304 0.210714 17.0391 0.585786 17.4142C0.960859 17.7893 1.46957 18 2 18H16C16.5304 18 17.0391 17.7893 17.4142 17.4142C17.7893 17.0391 18 16.5304 18 16V2C18 1.46957 17.7893 0.960859 17.4142 0.585786C17.0391 0.210714 16.5304 0 16 0ZM16 2V16H2V2H16Z"
                                            fill="#D9D9D9" />
                                    </svg>
                                </button>
                                <button @click="handle_change_estado(remito, false)"
                                    v-if="remito.estado === 'despachado'"
                                    class="cursor-pointer flex justify-center items-center w-full h-full">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M16 0H2C1.46957 0 0.960859 0.210714 0.585786 0.585786C0.210714 0.960859 0 1.46957 0 2V16C0 16.5304 0.210714 17.0391 0.585786 17.4142C0.960859 17.7893 1.46957 18 2 18H16C16.5304 18 17.0391 17.7893 17.4142 17.4142C17.7893 17.0391 18 16.5304 18 16V2C18 1.46957 17.7893 0.960859 17.4142 0.585786C17.0391 0.210714 16.5304 0 16 0ZM16 2V16H2V2H16ZM7 14L3 10L4.41 8.58L7 11.17L13.59 4.58L15 6"
                                            fill="#0D509C" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm">
                                Cliente: {{ remito.cliente }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 gap-1">
                            <template v-for="modelo in remito.modelos" :key="modelo.id">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-700">{{ modelo.nombre_modelo }}</span>
                                    <span class="text-gray-600 font-medium">{{ modelo.pivot.cantidad }}</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
                <div v-if="!(remitos as any)?.data?.length" class="col-span-5 text-center py-20 text-gray-500">
                    No se encontraron remitos
                </div>
            </div>
            <div v-if="(remitos as any)?.data?.length" class="flex justify-center items-center mt-4">
                <button :disabled="!(remitos as any)?.prev_page_url"
                    @click="router.get((remitos as any).prev_page_url, { filtro: filtro ?? props.filtro, search: props.search })"
                    class="px-2 py-1 cursor-pointer hover:bg-[#0D509C] hover:text-white text-black duration-300  rounded disabled:opacity-50">
                    < </button>
                        <span class="text-gray-700 px-2">{{ (remitos as any)?.current_page }} de {{ (remitos as
                            any)?.last_page
                            }}</span>
                        <button :disabled="!(remitos as any)?.next_page_url"
                            @click="router.get((remitos as any).next_page_url, { filtro: filtro ?? props.filtro, search: props.search })"
                            class="px-2 py-1 cursor-pointer hover:bg-[#0D509C] hover:text-white text-black duration-300  rounded disabled:opacity-50">
                            >
                        </button>
            </div>
            <div v-if="remito_modal" @click.self="remito_modal = !remito_modal; confirmandoEliminacion = false;"
                class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50"
                style="background-color: rgba(0, 0, 0, 0.5);">
                <div
                    class="bg-white rounded-lg p-6 w-[40vw] modal-animation overflow-y-auto max-h-[90vh] min-h-[400px] flex flex-col justify-between">

                    <div class="flex flex-col gap-1.5 mb-6">
                        <div class="flex justify-between items-center mb-4 h-10">
                            <h2 class="text-xl font-bold text-black">
                                {{ editando ? `Editar Remito N°${(remito_seleccionado as any)?.n_remito}`
                                    : 'Nuevo Remito' }}
                            </h2>
                            <div v-if="editando" class="flex items-center gap-2">
                                <Transition name="delete-confirm" mode="out-in">
                                    <button v-if="!confirmandoEliminacion" @click="confirmandoEliminacion = true"
                                        type="button"
                                        class="flex items-center gap-2 text-red-700 hover:text-red-500 transition-colors cursor-pointer">
                                        <svg width="18" height="22" viewBox="0 0 14 18" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16ZM3 6H11V16H3V6ZM10.5 1L9.5 0H4.5L3.5 1H0V3H14V1H10.5Z"
                                                fill="currentColor" />
                                        </svg>
                                        <span class="text-lg font-medium">Eliminar</span>
                                    </button>
                                    <div v-else class="flex items-center gap-2">
                                        <button @click="eliminarRemito" type="button"
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
                                <label for="n_remito" class="text-[#5B5B5B]">N° de remito</label>
                                <input type="text" id="n_remito" v-model="form.n_remito"
                                    class="border border-gray-300 p-2 rounded-md" />
                            </div>
                            <div class="flex flex-col gap-1 w-full">
                                <label for="cliente" class="text-[#5B5B5B]">Cliente</label>
                                <input type="text" id="cliente" v-model="form.cliente"
                                    class="border border-gray-300 p-2 rounded-md" />
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <div v-for="(modelo, index) in form.modelos" :key="index"
                                class="flex gap-8 w-full justify-between">
                                <div class="flex flex-col gap-1 w-full">
                                    <label :for="'modelo_' + index" class="text-[#5B5B5B]">Modelo</label>
                                    <select :id="'modelo_' + index" v-model="modelo.modelo_id" :class="[
                                        'border p-2 rounded-md',
                                        isModeloSeleccionado(modelo.modelo_id, index) ? 'border-red-500 bg-red-50' : 'border-gray-300'
                                    ]">
                                        <option value="">Selecciona un modelo</option>
                                        <option v-for="modeloOption in getModelosDisponibles(index)"
                                            :key="modeloOption.id" :value="modeloOption.id">
                                            {{ modeloOption.modelo }} - {{ modeloOption.nombre_modelo }}
                                        </option>
                                        <!-- Mostrar el modelo seleccionado aunque esté duplicado -->
                                        <option v-if="modelo.modelo_id && isModeloSeleccionado(modelo.modelo_id, index)"
                                            :value="modelo.modelo_id" class="text-red-600 font-bold">
                                            {{(modelos as any[]).find(m => m.id == modelo.modelo_id)?.modelo}} -
                                            {{(modelos as any[]).find(m => m.id == modelo.modelo_id)?.nombre_modelo}}
                                            (DUPLICADO)
                                        </option>
                                    </select>
                                    <div v-if="isModeloSeleccionado(modelo.modelo_id, index)"
                                        class="text-red-500 text-sm mt-1">
                                        ⚠️ Este modelo ya está seleccionado en otra fila
                                    </div>
                                </div>
                                <div class="w-full flex">
                                    <div class="flex flex-col gap-1 w-full">
                                        <label :for="'cantidad_' + index" class="text-[#5B5B5B]">Cantidad</label>
                                        <input type="number" :id="'cantidad_' + index" v-model="modelo.cantidad" min="1"
                                            class="border border-gray-300 p-2 rounded-md" />
                                    </div>
                                    <div class="flex items-end">
                                        <button v-if="index > 0" @click="eliminarModelo(index)" type="button"
                                            class=" text-[#0D509C] px-3 pb-3 hover:underline cursor-pointer">
                                            <svg width="14" height="18" viewBox="0 0 14 18" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16ZM3 6H11V16H3V6ZM10.5 1L9.5 0H4.5L3.5 1H0V3H14V1H10.5Z"
                                                    fill="#0D509C" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button @click="agregarModelo" type="button"
                                class="text-[#0D509C] hover:underline cursor-pointer">
                                + Agregar Modelo
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 mt-4 w-full">
                        <div class="flex justify-end space-x-2">
                            <button @click="remito_modal = !remito_modal;"
                                class="w-[150px] py-2 bg-white text-[#0D509C] rounded-full hover:shadow-lg duration-300 cursor-pointer flex justify-center border border-[#0D509C]">Volver</button>
                        </div>
                        <div v-if="!loading_remito" class="flex justify-end space-x-2">
                            <button @click="create_remito"
                                class="w-[150px] py-2 bg-[#0D509C] text-white rounded-full hover:shadow-lg duration-300 cursor-pointer flex justify-center">
                                {{ editando ? 'Actualizar' : 'Guardar' }}
                            </button>
                        </div>
                        <button v-if="loading_remito"
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

<style scoped>
.modal-animation {
    animation: modal-appear 0.3s ease-out;
}

@keyframes modal-appear {
    from {
        opacity: 0;
        transform: scale(0.9);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

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
