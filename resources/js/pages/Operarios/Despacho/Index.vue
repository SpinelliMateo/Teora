<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch, nextTick } from 'vue';
import axios from 'axios';

interface Modelo {
    id: number;
    nombre_modelo: string;
    modelo: string;
    pivot: {
        cantidad: number;
    };
}

interface Remito {
    id: number;
    n_remito: string;
    cliente: string;
    estado: string;
    modelos: Modelo[];
}

interface ControlStockItem {
    id: number;
    n_serie: string;
    fecha_embalado: string;
    modelo_id: number;
    modelo_nombre: string;
}

interface ModeloResumen {
    modelo_id: number;
    nombre_modelo: string;
    cantidad_total: number;
    cantidad_cargada: number;
    cantidad_restante: number;
}

interface Props {
    remitos: Remito[];
    urls: {
      modelosRemitos: string;
      buscarControlStock: string;
      procesarDespacho: string;
    };
}

const props = defineProps<Props>();

const remitosSeleccionados = ref<number[]>([]);
const controlStockItems = ref<ControlStockItem[]>([]);
const modelosResumen = ref<ModeloResumen[]>([]);
const numeroSerie = ref('');
const isLoadingModelos = ref(false);
const isLoadingBusqueda = ref(false);
const isLoadingDespacho = ref(false);
const mensaje = ref('');
const tipoMensaje = ref<'success' | 'error' | ''>('');

const remitosParaDespacho = computed((): Remito[] => {
    return props.remitos.filter(remito => remito.estado === 'despachado');
});

const hasRemitos = computed((): boolean => {
    return remitosParaDespacho.value.length > 0;
});

const hasRemitosSeleccionados = computed((): boolean => {
    return remitosSeleccionados.value.length > 0;
});

const hasControlStockItems = computed((): boolean => {
    return controlStockItems.value.length > 0;
});

const puedeDespachar = computed((): boolean => {
    if (!hasRemitosSeleccionados.value || !hasControlStockItems.value || modelosResumen.value.length === 0) {
        return false;
    }
    
    // Verificar que todas las cantidades estén completas (cantidad_restante === 0)
    return modelosResumen.value.every(modelo => modelo.cantidad_restante === 0);
});

watch(remitosSeleccionados, async (newIds) => {
    if (newIds.length > 0) {
        await cargarModelosRemitos();
    } else {
        modelosResumen.value = [];
    }
}, { deep: true });

const toggleRemitoSeleccion = (remitoId: number) => {
    const index = remitosSeleccionados.value.indexOf(remitoId);
    if (index > -1) {
        remitosSeleccionados.value.splice(index, 1);
    } else {
        remitosSeleccionados.value.push(remitoId);
    }
};

const isRemitoSeleccionado = (remitoId: number): boolean => {
    return remitosSeleccionados.value.includes(remitoId);
};

const cargarModelosRemitos = async () => {
    try {
        isLoadingModelos.value = true;
        const response = await axios.post(
          props.urls.modelosRemitos,
          { remito_ids: remitosSeleccionados.value }
        );

        if (response.data.success) {
            modelosResumen.value = response.data.data;
            recalcularCantidades();
        }
    } catch (error) {
        console.error('Error al cargar modelos:', error);
        mostrarMensaje('Error al cargar los modelos de los remitos', 'error');
    } finally {
        isLoadingModelos.value = false;
    }
};

const buscarControlStock = async () => {
    if (!numeroSerie.value.trim()) {
        mostrarMensaje('Debe ingresar un número de serie', 'error');
        return;
    }

    try {
        isLoadingBusqueda.value = true;
        const response = await axios.post(
            props.urls.buscarControlStock, {
            numero_serie: numeroSerie.value.trim()
        });

        if (response.data.success) {
            const existe = controlStockItems.value.some(item => item.id === response.data.data.id);
            if (existe) {
                mostrarMensaje('Este número de serie ya fue agregado', 'error');
                return;
            }

            await agregarControlStockItem(response.data.data);
            actualizarCantidadModelo(response.data.data.modelo_id, 1);
            numeroSerie.value = '';
            mostrarMensaje(response.data.message, 'success');
        } else {
            mostrarMensaje(response.data.message, 'error');
        }
    } catch (error) {
        console.error('Error al buscar control stock:', error);
        mostrarMensaje('Error al buscar el número de serie', 'error');
    } finally {
        isLoadingBusqueda.value = false;
    }
};

const agregarControlStockItem = async (item: ControlStockItem) => {
    controlStockItems.value.push(item);
    await nextTick();
};

const eliminarControlStock = async (item: ControlStockItem) => {
    const index = controlStockItems.value.findIndex(cs => cs.id === item.id);
    if (index > -1) {
    
        const elemento = document.querySelector(`[data-item-id="${item.id}"]`);
        if (elemento) {
            elemento.classList.add('slide-out');
            await new Promise(resolve => setTimeout(resolve, 300));
        }
        
        controlStockItems.value.splice(index, 1);
        actualizarCantidadModelo(item.modelo_id, -1);
        mostrarMensaje('Registro eliminado correctamente', 'success');
    }
};

const actualizarCantidadModelo = (modeloId: number, incremento: number) => {
    const modelo = modelosResumen.value.find(m => m.modelo_id === modeloId);
    if (modelo) {
        modelo.cantidad_cargada += incremento;
        modelo.cantidad_restante -= incremento;
    }
};

const recalcularCantidades = () => {
    modelosResumen.value.forEach(modelo => {
        modelo.cantidad_cargada = 0;
        modelo.cantidad_restante = modelo.cantidad_total;
    });

    controlStockItems.value.forEach(item => {
        actualizarCantidadModelo(item.modelo_id, 1);
    });
};

const mostrarMensaje = (texto: string, tipo: 'success' | 'error') => {
    mensaje.value = texto;
    tipoMensaje.value = tipo;
    setTimeout(() => {
        mensaje.value = '';
        tipoMensaje.value = '';
    }, 5000);
};

const procesarDespacho = async () => {
    if (!puedeDespachar.value) {
        mostrarMensaje('Debe completar todas las cantidades requeridas antes de enviar', 'error');
        return;
    }

    try {
        isLoadingDespacho.value = true;
        const response = await axios.post(props.urls.procesarDespacho, {
            remito_ids: remitosSeleccionados.value,
            control_stock_ids: controlStockItems.value.map(item => item.id)
        });

        if (response.data.success) {
            mostrarMensaje(response.data.message, 'success');
            // Limpiar el formulario después del éxito
            remitosSeleccionados.value = [];
            controlStockItems.value = [];
            modelosResumen.value = [];
            numeroSerie.value = '';
        } else {
            mostrarMensaje(response.data.message, 'error');
        }
    } catch (error) {
        console.error('Error al procesar despacho:', error);
        mostrarMensaje('Error al procesar el despacho', 'error');
    } finally {
        isLoadingDespacho.value = false;
    }
};

const formatearFecha = (fecha: string): string => {
    return new Date(fecha).toLocaleDateString('es-AR');
};

const handleEnterNumeroSerie = (event: KeyboardEvent) => {
    if (event.key === 'Enter') {
        buscarControlStock();
    }
};
</script>

<template>
    <Head title="Despacho" />
    
    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-20" style="background-color: #F4F4F4;">

            <div class="flex items-center gap-5 mt-10">
                <h1 class="text-[32px] font-bold text-gray-800">Despacho</h1>
            </div>

            <Transition name="message" appear>
                <div v-if="mensaje" class="fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg" 
                     :class="tipoMensaje === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
                    {{ mensaje }}
                </div>
            </Transition>

            <div class="max-w-md">
                <div class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label for="numero-serie" class="block text-sm font-medium text-gray-700 mb-2">
                            N° de Serie
                        </label>
                        <div class="relative">
                            <input
                                id="numero-serie"
                                v-model="numeroSerie"
                                type="text"
                                placeholder="1234"
                                class="w-full px-4 py-3 pr-12 border border-gray-300 focus:outline-none focus:ring-2 focus:border-blue-custom text-lg bg-white transition-all duration-200"
                                style="--tw-ring-color: rgba(13, 80, 156, 0.3); border-color: #d1d5db;"
                                @keydown="handleEnterNumeroSerie"
                                :disabled="isLoadingBusqueda"
                            />

                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <button
                        @click="buscarControlStock"
                        :disabled="isLoadingBusqueda || !numeroSerie.trim()"
                        class="px-6 py-3 text-white rounded-full hover:opacity-90 disabled:bg-gray-400 disabled:cursor-not-allowed font-medium text-lg transition-all duration-200 transform hover:scale-105 active:scale-95"
                        style="background-color: rgb(13, 80, 156);"
                    >
                        <span v-if="!isLoadingBusqueda">Buscar</span>
                        <span v-else class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Buscando...
                        </span>
                    </button>
                </div>
            </div>

            <div class="flex gap-6 flex-1">

                <div class="w-1/2">
                    <div class="bg-white shadow-sm overflow-hidden">
                        <div v-if="hasControlStockItems" class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-[#E1E5E9]">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Serie</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modelo</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <TransitionGroup name="table-row" tag="div" class="contents">
                                        <tr v-for="item in controlStockItems" 
                                            :key="item.id" 
                                            :data-item-id="item.id"
                                            class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ formatearFecha(item.fecha_embalado) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ item.n_serie }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ item.modelo_nombre }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-center">
                                                <button
                                                    @click="eliminarControlStock(item)"
                                                    class="text-red-600 hover:text-red-800 p-2  hover:bg-red-50 transition-all duration-200 transform hover:scale-110"
                                                    title="Eliminar"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="18" viewBox="0 0 14 18" fill="none">
                                                      <path d="M14 1H10.5L9.5 0H4.5L3.5 1H0V3H14M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16Z" fill="currentColor"/>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </TransitionGroup>
                                </tbody>
                            </table>
                        </div>
                        
                        <div v-else class="p-8 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-4">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="text-lg">No hay productos cargados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-1/2">
                    <div class="bg-white shadow-sm overflow-hidden">
                        <div v-if="hasRemitosSeleccionados && modelosResumen.length > 0 && !isLoadingModelos" class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-[#E1E5E9]">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modelo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad Cargada</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad Restante</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <TransitionGroup name="fade-slide" tag="div" class="contents">
                                        <tr v-for="modelo in modelosResumen" 
                                            :key="modelo.modelo_id" 
                                            class="hover:bg-gray-50 transition-all duration-200"
                                            :class="{ 
                                                'bg-green-50 border-l-4 border-green-400': modelo.cantidad_restante === 0, 
                                                'bg-yellow-50 border-l-4 border-yellow-400': modelo.cantidad_cargada > 0 && modelo.cantidad_restante > 0 
                                            }">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                {{ modelo.nombre_modelo }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="modelo.cantidad_cargada > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'">
                                                    {{ modelo.cantidad_cargada }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                      :class="modelo.cantidad_restante === 0 ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'">
                                                    {{ modelo.cantidad_restante }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ modelo.cantidad_total }}
                                            </td>
                                        </tr>
                                    </TransitionGroup>
                                </tbody>
                            </table>
                        </div>
                        
                        <div v-else class="p-8 text-center text-gray-500">
                            <div v-if="isLoadingModelos" class="flex flex-col items-center gap-4">
                                <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <p class="text-lg">Cargando modelos...</p>
                            </div>
                            <div v-else class="flex flex-col items-center gap-4">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="text-lg">Seleccione remitos para ver los modelos requeridos</p>
                            </div>
                        </div>
                    </div>

                    <Transition name="fade-up" appear>
                        <div v-if="hasControlStockItems && hasRemitosSeleccionados" class="mt-4 flex justify-end">
                            <button 
                                @click="procesarDespacho"
                                :disabled="!puedeDespachar || isLoadingDespacho"
                                class="text-white px-8 py-3 rounded-full font-medium text-lg min-w-[120px] transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                                :class="puedeDespachar ? 'hover:opacity-90' : 'opacity-50'"
                                style="background-color: rgb(13, 80, 156);">
                                <span v-if="!isLoadingDespacho">Enviar</span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    Procesando...
                                </span>
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>

            <div class="mt-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Remitos Disponibles</h2>
                <div class="grid grid-cols-4 gap-6">
                    <TransitionGroup name="remito-card" tag="div" class="contents">
                        <div v-for="remito in remitosParaDespacho" 
                             :key="remito.id"
                             class="min-h-[128px] w-full border bg-white px-4 py-4 flex flex-col gap-3  transition-all duration-300 hover:shadow-lg cursor-pointer transform hover:-translate-y-1"
                             :class="isRemitoSeleccionado(remito.id) ? 'border-gray-300 shadow-md bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                             @click="toggleRemitoSeleccion(remito.id)">

                            <div class="flex justify-between items-center">
                                <h3 class="font-extrabold text-lg text-gray-800">
                                    Remito N°{{ remito.n_remito }}
                                </h3>
                                <div class="flex-shrink-0">
                                    <div class="w-5 h-5 border-2 rounded flex items-center justify-center transition-all duration-200"
                                         :style="isRemitoSeleccionado(remito.id) ? { backgroundColor: 'rgb(13, 80, 156)', borderColor: 'rgb(13, 80, 156)' } : { borderColor: '#9ca3af' }">
                                        <svg v-if="isRemitoSeleccionado(remito.id)" 
                                             class="w-3 h-3 text-white" 
                                             fill="currentColor" 
                                             viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="text-sm text-gray-600">
                                    Cliente: <span class="font-medium text-gray-900">{{ remito.cliente }}</span>
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-1">
                                <div v-for="modelo in remito.modelos" 
                                     :key="modelo.id"
                                     class="flex justify-between items-center text-sm">
                                    <span class="text-gray-700">{{ modelo.nombre_modelo }}</span>
                                    <span class="text-gray-900 font-semibold bg-gray-100 px-2 py-1 rounded-full text-xs">
                                        {{ modelo.pivot.cantidad }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </TransitionGroup>

                    <!-- Estado vacío -->
                    <div v-if="!hasRemitos" class="col-span-4 text-center py-20 text-gray-500">
                        <div class="flex flex-col items-center gap-4">
                            <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-xl">No se encontraron remitos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

.message-enter-active, .message-leave-active {
    transition: all 0.3s ease;
}
.message-enter-from, .message-leave-to {
    opacity: 0;
    transform: translateX(100px);
}

.table-row-enter-active {
    transition: all 0.4s ease;
}
.table-row-leave-active {
    transition: all 0.3s ease;
}
.table-row-enter-from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
}
.table-row-leave-to {
    opacity: 0;
    transform: translateX(-100%) scale(0.95);
}

.slide-out {
    animation: slideOut 0.3s ease-in-out forwards;
}

@keyframes slideOut {
    0% {
        opacity: 1;
        transform: translateX(0);
    }
    100% {
        opacity: 0;
        transform: translateX(-100%);
    }
}

.fade-slide-enter-active, .fade-slide-leave-active {
    transition: all 0.3s ease;
}
.fade-slide-enter-from, .fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.remito-card-enter-active {
    transition: all 0.4s ease;
}
.remito-card-leave-active {
    transition: all 0.3s ease;
}
.remito-card-enter-from {
    opacity: 0;
    transform: translateY(20px) scale(0.9);
}
.remito-card-leave-to {
    opacity: 0;
    transform: translateY(-20px) scale(0.9);
}

.fade-up-enter-active, .fade-up-leave-active {
    transition: all 0.4s ease;
}
.fade-up-enter-from, .fade-up-leave-to {
    opacity: 0;
    transform: translateY(20px);
}

input:focus {
    box-shadow: 0 0 0 3px rgba(13, 80, 156, 0.1);
    border-color: rgb(13, 80, 156);
}

button:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

button:active:not(:disabled) {
    transform: translateY(0);
}

.bg-green-50 {
    background-color: #f0fdf4;
    border-left: 4px solid #22c55e;
}

.bg-yellow-50 {
    background-color: #fefce8;
    border-left: 4px solid #eab308;
}

@media (max-width: 1024px) {
    .grid-cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .grid-cols-4 {
        grid-template-columns: 1fr;
    }
}

</style>