<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import toastr from 'toastr';

interface Props {
    modelos: any[];
    ordenFabricacion: any[];
    operarios: any[];
    modelosOrden: any[]; 
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Órdenes de fabricación',
        href: '/ordenes-fabricacion',
    },
    {
        title: `Orden ${props.ordenFabricacion.no_orden}`,
        href: '#',
    },
];

const isOrderExpired = computed(() => {
  return (orden) => {
    if (orden.estado !== 'pendiente') return false;

    const today = new Date();
    const fechaFinalizacion = new Date(orden.fecha_finalizacion);

    // Comparar solo las fechas sin horas
    today.setHours(0, 0, 0, 0);
    fechaFinalizacion.setHours(0, 0, 0, 0);

    return fechaFinalizacion < today;
  };
});

const ordenData = reactive({
    fecha: props.ordenFabricacion.fecha,
    no_orden: props.ordenFabricacion.no_orden,
    fecha_finalizacion: props.ordenFabricacion.fecha_finalizacion,
    estado: props.ordenFabricacion.estado,
    operarios: props.ordenFabricacion.operarios?.map(op => op.id) || []
});

const valoresOriginales = reactive({
    fecha: props.ordenFabricacion.fecha,
    no_orden: props.ordenFabricacion.no_orden,
    fecha_finalizacion: props.ordenFabricacion.fecha_finalizacion,
    operarios: props.ordenFabricacion.operarios?.map(op => op.id) || []
});

const hayDiferencias = () => {
    return (
        ordenData.fecha !== valoresOriginales.fecha ||
        ordenData.no_orden !== valoresOriginales.no_orden ||
        ordenData.fecha_finalizacion !== valoresOriginales.fecha_finalizacion ||
        JSON.stringify(ordenData.operarios.sort()) !== JSON.stringify(valoresOriginales.operarios.sort())
    );
};

// Estados para dropdowns y modales
const dropdownOperarios = ref(false);
const isUpdating = ref(false);
const errors = ref({});
const showModalAgregarModelo = ref(false);
const showModalEditarModelo = ref(false);
const modeloSeleccionadoEdit = ref(null);

// Estados para transiciones
const modelosEnTransicion = ref(new Set());
const modeloEliminandose = ref(null);

// Estados para modal de agregar modelo
const nuevoModelo = reactive({
    modelo_id: '',
    cantidad: 1
});

// Estados para modal de editar modelo
const editarModelo = reactive({
    id: '',
    cantidad: 1
});

// Variable para evitar actualizaciones automáticas mientras se seleccionan operarios
const isSelectingOperarios = ref(false);

// Computed para obtener nombres de operarios seleccionados
const operariosSeleccionados = computed(() => {
    return props.operarios.filter(op => ordenData.operarios.includes(op.id));
});

const operariosSeleccionadosTexto = computed(() => {
    if (operariosSeleccionados.value.length === 0) return 'Seleccionar operarios';
    if (operariosSeleccionados.value.length === 1) return operariosSeleccionados.value[0].nombre_completo;
    return `${operariosSeleccionados.value.length} operarios seleccionados`;
});

const page = usePage();

// Configurar toastr
onMounted(() => {
    // Configuración de toastr mejorada
    toastr.options = {
        closeButton: true,
        debug: false,
        newestOnTop: true,
        progressBar: true,
        positionClass: "toast-top-right",
        preventDuplicates: true,
        onclick: null,
        showDuration: "200",
        hideDuration: "800",
        timeOut: "4000",
        extendedTimeOut: "1000",
        showEasing: "swing",
        hideEasing: "linear",
        showMethod: "fadeIn",
        hideMethod: "fadeOut"
    };

    document.addEventListener('click', cerrarDropdown);
});

let timeoutId: number | null = null;
const actualizarConDebounce = () => {
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
    
    timeoutId = setTimeout(() => {
        // Solo actualizar si hay diferencias
        if (hayDiferencias()) {
            actualizarOrden();
        }
    }, 800);
};

// Actualizar valores originales después de una actualización exitosa
const actualizarOrden = async () => {
    if (isUpdating.value || isSelectingOperarios.value) return;
    
    isUpdating.value = true;
    errors.value = {};

    router.put(`/ordenes-fabricacion/${props.ordenFabricacion.id}`, ordenData, {
        preserveScroll: true,
        preserveState: true,
        only: ['flash'], 
        onSuccess: (page) => {
            if (page.props.flash?.success) {
                toastr.success(page.props.flash.success, 'Actualizado');
                
                // Actualizar valores originales después del éxito
                valoresOriginales.fecha = ordenData.fecha;
                valoresOriginales.no_orden = ordenData.no_orden;
                valoresOriginales.fecha_finalizacion = ordenData.fecha_finalizacion;
                valoresOriginales.operarios = [...ordenData.operarios];
            }
            setTimeout(() => {
                isUpdating.value = false;
            }, 300);
        },
        onError: (errors) => {
            console.log('Errores:', errors);
            Object.values(errors).flat().forEach(error => {
                toastr.error(error as string, 'Error');
            });
            isUpdating.value = false;
        },
        onFinish: () => {
            setTimeout(() => {
                isUpdating.value = false;
            }, 300);
        }
    });
};

// Función para toggle operario sin auto-actualizar
const toggleOperario = (operarioId: number) => {
    const index = ordenData.operarios.indexOf(operarioId);
    if (index > -1) {
        ordenData.operarios.splice(index, 1);
    } else {
        ordenData.operarios.push(operarioId);
    }
};

// Función para aplicar cambios de operarios
const aplicarCambiosOperarios = () => {
    dropdownOperarios.value = false;
    isSelectingOperarios.value = false;
    actualizarOrden();
};

// Función para abrir modal de operarios
const abrirDropdownOperarios = () => {
    isSelectingOperarios.value = true;
    dropdownOperarios.value = true;
};

// Función para cerrar dropdown al hacer click fuera
const cerrarDropdown = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.dropdown-operarios')) {
        if (dropdownOperarios.value && isSelectingOperarios.value) {
            aplicarCambiosOperarios();
        }
        dropdownOperarios.value = false;
        isSelectingOperarios.value = false;
    }
};

// Función para obtener la fecha mínima (hoy)
const fechaMinima = computed(() => {
    return new Date().toISOString().split('T')[0];
});

// Función para obtener la fecha mínima de finalización (fecha seleccionada + 1 día)
const fechaFinalizacionMinima = computed(() => {
    if (!ordenData.fecha) return fechaMinima.value;
    const fechaInicio = new Date(ordenData.fecha);
    fechaInicio.setDate(fechaInicio.getDate() + 1);
    return fechaInicio.toISOString().split('T')[0];
});

const volver = () => {
    router.visit('/ordenes-fabricacion');
};

// Funciones para modal de agregar modelo con transiciones
const abrirModalAgregarModelo = () => {
    nuevoModelo.modelo_id = '';
    nuevoModelo.cantidad = 1;
    showModalAgregarModelo.value = true;
    
    // Pequeño delay para la animación
    nextTick(() => {
        const modal = document.querySelector('.modal-content');
        if (modal) {
            modal.classList.add('modal-enter');
        }
    });
};

const cerrarModalAgregarModelo = () => {
    const modal = document.querySelector('.modal-content');
    if (modal) {
        modal.classList.add('modal-exit');
        setTimeout(() => {
            showModalAgregarModelo.value = false;
            nuevoModelo.modelo_id = '';
            nuevoModelo.cantidad = 1;
        }, 200);
    } else {
        showModalAgregarModelo.value = false;
        nuevoModelo.modelo_id = '';
        nuevoModelo.cantidad = 1;
    }
};

const agregarModelo = () => {
    if (!nuevoModelo.modelo_id || nuevoModelo.cantidad < 1) {
        toastr.error('Por favor selecciona un modelo y una cantidad válida', 'Error de validación');
        return;
    }

    // Añadir indicador de carga al botón
    const btn = event.target as HTMLButtonElement;
    const originalText = btn.textContent;
    btn.textContent = 'Agregando...';
    btn.disabled = true;

    router.post(`/ordenes-fabricacion/${props.ordenFabricacion.id}/modelos`, nuevoModelo, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.success) {
                toastr.success(page.props.flash.success, 'Modelo agregado');
                
                // Animación de entrada para el nuevo modelo
                setTimeout(() => {
                    const nuevaFila = document.querySelector('tbody tr:last-child');
                    if (nuevaFila && !nuevaFila.classList.contains('empty-row')) {
                        nuevaFila.classList.add('modelo-nuevo');
                        setTimeout(() => {
                            nuevaFila.classList.remove('modelo-nuevo');
                        }, 600);
                    }
                }, 100);
            }
            cerrarModalAgregarModelo();
        },
        onError: (errors) => {
            Object.values(errors).flat().forEach(error => {
                toastr.error(error as string, 'Error');
            });
        },
        onFinish: () => {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    });
};

// Funciones para modal de editar modelo
const abrirModalEditarModelo = (modelo: any) => {
    modeloSeleccionadoEdit.value = modelo;
    editarModelo.id = modelo.id;
    editarModelo.cantidad = modelo.pivot.cantidad;
    showModalEditarModelo.value = true;
    
    nextTick(() => {
        const modal = document.querySelector('.modal-content');
        if (modal) {
            modal.classList.add('modal-enter');
        }
    });
};

const cerrarModalEditarModelo = () => {
    const modal = document.querySelector('.modal-content');
    if (modal) {
        modal.classList.add('modal-exit');
        setTimeout(() => {
            showModalEditarModelo.value = false;
            modeloSeleccionadoEdit.value = null;
            editarModelo.id = '';
            editarModelo.cantidad = 1;
        }, 200);
    } else {
        showModalEditarModelo.value = false;
        modeloSeleccionadoEdit.value = null;
        editarModelo.id = '';
        editarModelo.cantidad = 1;
    }
};

const actualizarModelo = () => {
    if (editarModelo.cantidad < 1) {
        toastr.error('La cantidad debe ser mayor a 0', 'Error de validación');
        return;
    }

    // Indicador visual de actualización
    modelosEnTransicion.value.add(editarModelo.id);

    router.put(`/ordenes-fabricacion/${props.ordenFabricacion.id}/modelos/${editarModelo.id}`, {
        cantidad: editarModelo.cantidad
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.success) {
                toastr.success(page.props.flash.success, 'Modelo actualizado');
                
                // Animación de actualización
                const fila = document.querySelector(`tr[data-modelo-id="${editarModelo.id}"]`);
                if (fila) {
                    fila.classList.add('modelo-actualizado');
                    setTimeout(() => {
                        fila.classList.remove('modelo-actualizado');
                    }, 800);
                }
            }
            cerrarModalEditarModelo();
        },
        onError: (errors) => {
            Object.values(errors).flat().forEach(error => {
                toastr.error(error as string, 'Error');
            });
        },
        onFinish: () => {
            modelosEnTransicion.value.delete(editarModelo.id);
        }
    });
};

const eliminarModelo = () => {
    if (confirm('¿Estás seguro de que deseas eliminar este modelo de la orden?')) {
        // Animación de eliminación
        modeloEliminandose.value = editarModelo.id;
        
        router.delete(`/ordenes-fabricacion/${props.ordenFabricacion.id}/modelos/${editarModelo.id}`, {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.flash?.success) {
                    toastr.success(page.props.flash.success, 'Modelo eliminado');
                }
                cerrarModalEditarModelo();
            },
            onError: (errors) => {
                Object.values(errors).flat().forEach(error => {
                    toastr.error(error as string, 'Error');
                });
                modeloEliminandose.value = null;
            },
            onFinish: () => {
                setTimeout(() => {
                    modeloEliminandose.value = null;
                }, 300);
            }
        });
    }
};


</script>

<template>
    <Head :title="`Orden de fabricación ${ordenFabricacion.no_orden}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-7 p-4 px-20 transition-all duration-300" style="background-color: #F4F4F4;">
            <div class="flex items-center justify-between mt-8">
                <div class="flex items-center gap-4">
                    <button
                        @click="volver"
                        class="p-2 rounded-full hover:bg-gray-200 transition-all duration-200 hover:scale-105 cursor-pointer"
                        title="Volver a órdenes de fabricación"
                    >
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                    </button>
                    <h1 class="text-[32px] font-bold text-gray-800 transition-colors duration-200">
                        Orden de fabricación {{ ordenFabricacion.no_orden }}
                    </h1>
                </div>
                
                <!-- Indicador de actualización mejorado -->
                <div 
                    v-if="isUpdating" 
                    class="flex items-center text-blue-600 bg-blue-50 px-3 py-2 rounded-full transition-all duration-300"
                >
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium">Guardando cambios...</span>
                </div>
            </div>

            <div class="text-black flex flex-col rounded-lg bg-white p-5 pb-12 shadow-sm border border-gray-100 transition-all duration-200 hover:shadow-md">
                <h2 class="font-semibold text-xl mb-5 text-gray-800">Datos generales</h2>
                <div class="w-full flex items-start gap-6 flex-wrap">
                    <div class="flex flex-col min-w-[200px] flex-1 gap-2">
                        <label class="text-gray-600 font-medium">Fecha</label>
                        <input
                            v-model="ordenData.fecha"
                            @blur="() => actualizarConDebounce('fecha')"
                            type="date"
                            :min="fechaMinima"
                            :disabled="isUpdating"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            :class="{ 'border-red-500 ring-red-200': errors.fecha, 'bg-gray-50 cursor-not-allowed': isUpdating }"
                        />
                        <span v-if="errors.fecha" class="text-red-500 text-sm animate-pulse">
                            {{ errors.fecha[0] }}
                        </span>
                    </div>
                  
                    <div class="flex flex-col min-w-[200px] flex-1 gap-2">
                        <label class="text-gray-600 font-medium">N° de orden</label>
                        <input
                            v-model="ordenData.no_orden"
                            @blur="() => actualizarConDebounce('no_orden')"
                            type="text"
                            :disabled="isUpdating"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            :class="{ 'border-red-500 ring-red-200': errors.no_orden, 'bg-gray-50 cursor-not-allowed': isUpdating }"
                        />
                        <span v-if="errors.no_orden" class="text-red-500 text-sm animate-pulse">
                            {{ errors.no_orden[0] }}
                        </span>
                    </div>

                    <div class="flex flex-col min-w-[200px] flex-1 gap-2">
                        <label class="text-gray-600 font-medium">Fecha de finalización</label>
                        <input
                            v-model="ordenData.fecha_finalizacion"
                            @blur="() => actualizarConDebounce('fecha_finalizacion')"
                            type="date"
                            :min="fechaFinalizacionMinima"
                            :disabled="isUpdating"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            :class="[
                                { 'border-red-500 ring-red-200': errors.fecha_finalizacion, 'bg-gray-50 cursor-not-allowed': isUpdating },
                                isOrderExpired(ordenData) ? 'bg-[#FAE8E8]' : 'bg-white'
                            ]"
                        />
                        <span v-if="errors.fecha_finalizacion" class="text-red-500 text-sm animate-pulse">
                            {{ errors.fecha_finalizacion[0] }}
                        </span>
                    </div>

                    <div class="flex flex-col min-w-[200px] flex-1 gap-2 dropdown-operarios relative">
                        <label class="text-gray-600 font-medium">Operarios</label>
                        <div class="relative">
                            <button
                                @click="abrirDropdownOperarios"
                                type="button"
                                :disabled="isUpdating"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-left flex items-center justify-between transition-all duration-200 hover:border-gray-400"
                                :class="{ 'border-red-500 ring-red-200': errors.operarios, 'bg-gray-50 cursor-not-allowed': isUpdating }"
                            >
                                <span class="truncate">{{ operariosSeleccionadosTexto }}</span>
                                <svg class="w-4 h-4 ml-2 transition-transform duration-200" :class="{ 'rotate-180': dropdownOperarios }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- Dropdown con transición -->
                            <Transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div
                                    v-show="dropdownOperarios && !isUpdating"
                                    class="absolute z-20 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                                >
                                    <div class="p-2">
                                        <div
                                            v-for="operario in operarios"
                                            :key="operario.id"
                                            class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer transition-colors duration-150"
                                            @click="toggleOperario(operario.id)"
                                        >
                                            <input
                                                type="checkbox"
                                                :checked="ordenData.operarios.includes(operario.id)"
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 transition-colors duration-150"
                                                readonly
                                            />
                                            <label class="ml-2 text-sm text-gray-900 cursor-pointer flex-1">
                                                {{ operario.nombre_completo }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="p-2 border-t border-gray-200">
                                        <button
                                            @click="aplicarCambiosOperarios"
                                            class="w-full px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm transition-colors duration-200 font-medium"
                                        >
                                            Aplicar cambios
                                        </button>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                        <span v-if="errors.operarios" class="text-red-500 text-sm animate-pulse">
                            {{ errors.operarios[0] }}
                        </span>
                    </div>
                </div>
                
                <!-- Mostrar operarios seleccionados con transición -->
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 transform -translate-y-2"
                    enter-to-class="opacity-100 transform translate-y-0"
                    leave-active-class="transition-all duration-200"
                    leave-from-class="opacity-100 transform translate-y-0"
                    leave-to-class="opacity-0 transform -translate-y-2"
                >
                    <div v-if="operariosSeleccionados.length > 0" class="mt-4">
                        <label class="text-gray-600 text-sm mb-2 block font-medium">Operarios asignados:</label>
                        <div class="flex flex-wrap gap-2">
                            <TransitionGroup
                                enter-active-class="transition-all duration-300"
                                enter-from-class="opacity-0 transform scale-95"
                                enter-to-class="opacity-100 transform scale-100"
                                leave-active-class="transition-all duration-200"
                                leave-from-class="opacity-100 transform scale-100"
                                leave-to-class="opacity-0 transform scale-95"
                            >
                                <span
                                    v-for="operario in operariosSeleccionados"
                                    :key="operario.id"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800 transition-all duration-200 hover:bg-blue-200"
                                >
                                    {{ operario.nombre_completo }}
                                </span>
                            </TransitionGroup>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Botón Agregar Modelo mejorado -->
            <div class="flex justify-end">
                <button
                    @click="abrirModalAgregarModelo"
                    class="flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-full cursor-pointer font-medium transition-all duration-200 hover:bg-blue-700 hover:scale-105 shadow-md hover:shadow-lg"
                    style="background-color: rgb(13, 80, 156);"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Agregar modelo
                </button>
            </div>

            <!-- Tabla con transiciones mejoradas -->
            <div class="overflow-x-auto shadow-sm rounded-lg border border-gray-200">
                <table class="w-full bg-white overflow-hidden">
                    <thead class="" style="background-color: #E1E5E9;">
                        <tr>
                            <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">N° modelo</th>
                            <th class="py-4 px-6 text-left text-sm font-semibold text-gray-700 uppercase tracking-wider">Nombre</th>
                            <th class="py-4 px-6 text-center text-sm font-semibold text-gray-700 uppercase tracking-wider">Cantidad</th>
                            <th class="py-4 px-6 text-center text-sm font-semibold text-gray-700 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <TransitionGroup
                            enter-active-class="transition-all duration-500"
                            enter-from-class="opacity-0 transform translate-x-4"
                            enter-to-class="opacity-100 transform translate-x-0"
                            leave-active-class="transition-all duration-300"
                            leave-from-class="opacity-100 transform translate-x-0"
                            leave-to-class="opacity-0 transform -translate-x-4 scale-95"
                        >
                            <tr 
                                v-for="item in modelosOrden" 
                                :key="item.id"
                                :data-modelo-id="item.id"
                                class="hover:bg-gray-50 transition-colors duration-150"
                                :class="{
                                    'bg-red-50 opacity-50': modeloEliminandose === item.id,
                                    'bg-blue-50': modelosEnTransicion.has(item.id)
                                }"
                            >
                                <td class="py-4 px-6 text-sm text-gray-800 font-medium">{{ item.modelo }}</td>
                                <td class="py-4 px-6 text-sm text-gray-800">{{ item.nombre_modelo }}</td>
                                <td class="py-4 px-6 text-center text-sm text-gray-800">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-800">
                                        {{ item.pivot?.cantidad }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <button
                                      @click="abrirModalEditarModelo(item)"
                                      class="inline-flex items-center justify-center p-2 rounded-full hover:bg-gray-100 transition-all duration-200 hover:scale-110 cursor-pointer"
                                      title="Editar modelo"
                                    >
                                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                                        <path d="M17.71 4.0425C18.1 3.6525 18.1 3.0025 17.71 2.6325L15.37 0.2925C15 -0.0975 14.35 -0.0975 13.96 0.2925L12.12 2.1225L15.87 5.8725M0 14.2525V18.0025H3.75L14.81 6.9325L11.06 3.1825L0 14.2525Z" fill="#D9D9D9"/>
                                      </svg>

                                    </button>
                                </td>
                            </tr>
                        </TransitionGroup>
    
                        <tr v-if="modelosOrden.length === 0" class="empty-row">
                            <td colspan="4" class="py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 00-.707 13H2"/>
                                    </svg>
                                    <p class="font-medium">No se encontraron modelos</p>
                                    <p class="text-sm text-gray-400">Agrega un modelo para comenzar</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Agregar Modelo con transiciones mejoradas -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModalAgregarModelo" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 backdrop-blur-sm">
                <div class="modal-content bg-white rounded-xl p-6 w-full max-w-md mx-4 shadow-2xl border border-gray-200 transform transition-all duration-300">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Agregar Modelo</h3>
                        <button 
                            @click="cerrarModalAgregarModelo" 
                            class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100 transition-all duration-200 cursor-pointer"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Modelo</label>
                            <select
                                v-model="nuevoModelo.modelo_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            >
                                <option value="">Seleccionar modelo</option>
                                <option v-for="modelo in modelos" :key="modelo.id" :value="modelo.id">
                                    {{ modelo.modelo }} - {{ modelo.nombre_modelo }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cantidad</label>
                            <input
                                v-model.number="nuevoModelo.cantidad"
                                type="number"
                                min="1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            />
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 mt-8">
                        <button
                            @click="cerrarModalAgregarModelo"
                            class="px-6 py-3 text-gray-700 bg-gray-100 hover:bg-gray-200 cursor-pointer transition-all rounded-full duration-200 font-medium "
                        >
                            Cancelar
                        </button>
                        <button
                            @click="agregarModelo"
                            class="flex items-center gap-2 px-6 py-3 bg-[#0D509C] hover:bg-[#0A3C78]  text-white rounded-full cursor-pointer font-medium transition-all duration-200 shadow-md hover:shadow-lg"
                            >
                            Agregar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Modal Editar Modelo con transiciones mejoradas -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModalEditarModelo" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 backdrop-blur-sm">
                <div class="modal-content bg-white rounded-xl p-6 w-full max-w-md mx-4 shadow-2xl border border-gray-200 transform transition-all duration-300">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Editar Modelo</h3>
                        <button 
                            @click="cerrarModalEditarModelo" 
                            class="text-gray-400 hover:text-gray-600 p-1 rounded-full cursor-pointer hover:bg-gray-100 transition-all duration-200"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Modelo</label>
                            <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 font-medium">
                                {{ modeloSeleccionadoEdit?.modelo }} - {{ modeloSeleccionadoEdit?.nombre_modelo }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Cantidad</label>
                            <input
                                v-model.number="editarModelo.cantidad"
                                type="number"
                                min="1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            />
                        </div>
                    </div>
                    
                    <div class="flex justify-between gap-3 mt-8">
                        <button
                            @click="eliminarModelo"
                            class="px-6 py-3 text-white bg-red-600 cursor-pointer rounded-full hover:bg-red-700 transition-all duration-200 font-medium shadow-md hover:shadow-lg"
                        >
                            Eliminar
                        </button>
                        <div class="flex gap-3">
                            <button
                                @click="cerrarModalEditarModelo"
                                class="px-6 py-3 text-gray-700 bg-gray-100 rounded-full cursor-pointer hover:bg-gray-200 transition-all duration-200 font-medium"
                            >
                                Cancelar
                            </button>
                            <button
                                @click="actualizarModelo"
                                class="px-6 py-3 rounded-full bg-[#0D509C] hover:bg-[#0A3C78] text-white cursor-pointer transition-all duration-200 font-medium shadow-md hover:shadow-lg"
                            >
                                Actualizar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
        
    </AppLayout>
</template>

<style>
@import 'toastr/build/toastr.min.css';

/* Animaciones personalizadas */
.modal-enter {
    animation: modalEnter 0.3s ease-out;
}

.modal-exit {
    animation: modalExit 0.2s ease-in;
}

@keyframes modalEnter {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes modalExit {
    from {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
    to {
        opacity: 0;
        transform: scale(0.95) translateY(-5px);
    }
}

/* Animación para modelo nuevo */
.modelo-nuevo {
    animation: modeloNuevo 0.6s ease-out;
}

@keyframes modeloNuevo {
    0% {
        background-color: #dbeafe;
        transform: scale(1.02);
    }
    50% {
        background-color: #bfdbfe;
    }
    100% {
        background-color: transparent;
        transform: scale(1);
    }
}

/* Animación para modelo actualizado */
.modelo-actualizado {
    animation: modeloActualizado 0.8s ease-out;
}

@keyframes modeloActualizado {
    0% {
        background-color: #d1fae5;
        transform: scale(1.01);
    }
    50% {
        background-color: #a7f3d0;
    }
    100% {
        background-color: transparent;
        transform: scale(1);
    }
}

/* Mejoras generales de hover */
input:hover:not(:disabled) {
    border-color: #9ca3af;
}

select:hover:not(:disabled) {
    border-color: #9ca3af;
}

/* Transición suave para toastr */
.toast {
    transition: all 0.3s ease;
}

/* Animación de carga sutil */
@keyframes pulseSubtle {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

.loading-subtle {
    animation: pulseSubtle 2s infinite;
}
</style>