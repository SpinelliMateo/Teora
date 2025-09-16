<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import 'vue-select/dist/vue-select.css';

const { success, error } = useToast();

// Interfaces
interface Modelo {
    id: number;
    nombre_modelo: string;
}

interface Operario {
    id: number;
    nombre: string;
}

interface Tecnico {
    id: number;
    name: string;
    apellido: string;
}

interface Subproblema {
    id: number;
    nombre: string;
}

interface Problema {
    id: number;
    nombre: string;
    subproblemas: Subproblema[];
}

interface DatoGrafico {
    modelo: string;
    cantidad: number;
}

const props = defineProps<{
    modelos: Modelo[];
    operarios: Operario[];
    tecnicos: Tecnico[];
    problemas: Problema[];
    datosGraficoProcesos: DatoGrafico[];
    datosGraficoServicios: DatoGrafico[];
}>()

// Variables reactivas para los filtros
const selectedModelo = ref('')
const selectedOperario = ref('')
const selectedTecnico = ref('')
const selectedProblema = ref('')
const selectedSubproblema = ref('')
const fechaDesde = ref('')
const fechaHasta = ref('')

// Computed para determinar automáticamente el tipo de gráfico
const tipoGrafico = computed(() => {
    // Si hay filtros de técnico, problema o subproblema, mostrar servicios
    if (selectedTecnico.value || selectedProblema.value || selectedSubproblema.value) {
        return 'servicios'
    }
    // Por defecto, mostrar procesos
    return 'procesos'
})

// Computed property para obtener subproblemas del problema seleccionado
const subproblemasDisponibles = computed(() => {
    if (!selectedProblema.value) {
        // Si no hay problema seleccionado, mostrar todos los subproblemas
        return props.problemas.flatMap(problema => problema.subproblemas)
    }

    const problemaSeleccionado = props.problemas.find(p => p.id == Number(selectedProblema.value))
    return problemaSeleccionado ? problemaSeleccionado.subproblemas : []
})

// Computed properties para bloqueo mutuo de filtros
const operarioDisabled = computed(() => {
    return !!(selectedTecnico.value || selectedProblema.value || selectedSubproblema.value)
})

const tecnicoProblemaSubproblemaDisabled = computed(() => {
    return !!selectedOperario.value
})

// Función para limpiar subproblema cuando cambia el problema
const onProblemaChange = () => {
    selectedSubproblema.value = ''
    onServiciosFiltroChange() // Aplicar bloqueo mutuo
}

// Función para limpiar filtros de servicios cuando se selecciona operario
const onOperarioChange = () => {
    if (selectedOperario.value) {
        selectedTecnico.value = ''
        selectedProblema.value = ''
        selectedSubproblema.value = ''
    }
}

// Función para limpiar operario cuando se selecciona algún filtro de servicios
const onServiciosFiltroChange = () => {
    if (selectedTecnico.value || selectedProblema.value || selectedSubproblema.value) {
        selectedOperario.value = ''
    }
}

// Variables para el gráfico
const chartCanvas = ref<HTMLCanvasElement | null>(null)
let chartInstance: any = null

// Datos del gráfico basados en el tipo seleccionado
const datosGrafico = computed(() => {
    if (tipoGrafico.value === 'procesos') {
        return props.datosGraficoProcesos || []
    } else {
        return props.datosGraficoServicios || []
    }
})

const total = computed(() => {
    return datosGrafico.value.reduce((sum, item) => sum + item.cantidad, 0)
})

const crearGrafico = async () => {
    if (!chartCanvas.value) return

    // Importar Chart.js dinámicamente
    const { Chart, registerables } = await import('chart.js')
    Chart.register(...registerables)

    // Destruir gráfico anterior si existe
    if (chartInstance) {
        chartInstance.destroy()
    }

    const ctx = chartCanvas.value.getContext('2d')
    if (!ctx) return

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datosGrafico.value.map(item => item.modelo),
            datasets: [{
                data: datosGrafico.value.map(item => item.cantidad),
                backgroundColor: '#0D509C',
                borderColor: '#3B82F6',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false,
                barPercentage: 0.3,
                categoryPercentage: 0.8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1F2937',
                    titleColor: '#FFFFFF',
                    bodyColor: '#FFFFFF',
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: '#6B7280'
                    },
                    grid: {
                        color: '#E5E7EB'
                    }
                },
                x: {
                    ticks: {
                        color: '#6B7280',
                        maxRotation: 45,
                        minRotation: 0
                    },
                    grid: {
                        display: false
                    }
                }
            },
            layout: {
                padding: {
                    top: 20,
                    right: 20,
                    bottom: 10,
                    left: 10
                }
            }
        }
    })
}

// Función para limpiar filtros
const limpiarFiltros = () => {
    selectedModelo.value = ''
    selectedOperario.value = ''
    selectedTecnico.value = ''
    selectedProblema.value = ''
    selectedSubproblema.value = ''
    fechaDesde.value = ''
    fechaHasta.value = ''

    success('🧹 Filtros limpiados correctamente. Mostrando todos los datos disponibles.')
}

// Variable para controlar si es la primera carga
const primeraLeyenda = ref(true)

// Watch para aplicar filtros en tiempo real
watch([selectedModelo, selectedOperario, selectedTecnico, selectedProblema, selectedSubproblema, fechaDesde, fechaHasta], () => {
    const filtros = {
        modelo_id: selectedModelo.value || null,
        operario_id: selectedOperario.value || null,
        tecnico_id: selectedTecnico.value || null,
        problema_id: selectedProblema.value || null,
        subproblema_id: selectedSubproblema.value || null,
        fecha_desde: fechaDesde.value || null,
        fecha_hasta: fechaHasta.value || null,
    }

    // Filtrar valores null
    const filtrosLimpios = Object.fromEntries(
        Object.entries(filtros).filter(([_, v]) => v !== null && v !== '')
    )

    // Marcar que ya no es la primera carga
    if (primeraLeyenda.value) {
        primeraLeyenda.value = false
    }

    router.get(route('reportes'), filtrosLimpios, {
        preserveState: true,
        preserveScroll: true,
        only: ['datosGraficoProcesos', 'datosGraficoServicios']
    })
}, { deep: true })

// Watch para recrear gráfico cuando cambien los datos
watch([datosGrafico, tipoGrafico], () => {
    nextTick(() => {
        crearGrafico()
    })
}, { deep: true })

onMounted(async () => {
    await nextTick()
    crearGrafico()
})
</script>

<template>

    <Head title="Reportes" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-5 lg:px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 lg:mt-10">
                <h1 class="text-[32px] font-bold text-gray-800">Reportes</h1>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-[8px] p-5 flex flex-col gap-3.5">
                <div>
                    <h2 class="text-black font-bold text-xl">Filtrar por</h2>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="flex flex-col gap-1.5">
                        <label for="modelo" class="text-[#5B5B5B] text-sm">Modelo</label>
                        <select name="modelo" id="modelo" v-model="selectedModelo"
                            class="border border-gray-200 py-2.5 px-3 rounded-md">
                            <option value="">TODOS</option>
                            <option v-for="modelo in modelos" :key="modelo.id" :value="modelo.id">
                                {{ modelo.nombre_modelo }}
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="operario" class="text-[#5B5B5B] text-sm">Operario</label>
                        <select name="operario" id="operario" v-model="selectedOperario" @change="onOperarioChange"
                            :disabled="operarioDisabled"
                            class="border border-gray-200 py-2.5 px-3 rounded-md disabled:bg-gray-100 disabled:cursor-not-allowed">
                            <option value="">NINGUNO</option>
                            <option v-for="operario in operarios" :key="operario.id" :value="operario.id">
                                {{ operario.nombre }}
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="fecha-desde" class="text-[#5B5B5B] text-sm">Desde</label>
                        <input type="date" name="fecha-desde" id="fecha-desde" v-model="fechaDesde"
                            class="border border-gray-200 py-2.5 px-3 rounded-md" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="fecha-hasta" class="text-[#5B5B5B] text-sm">Hasta</label>
                        <input type="date" name="fecha-hasta" id="fecha-hasta" v-model="fechaHasta"
                            class="border border-gray-200 py-2.5 px-3 rounded-md" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="tecnico" class="text-[#5B5B5B] text-sm h-10">Técnico</label>
                        <select name="tecnico" id="tecnico" v-model="selectedTecnico" @change="onServiciosFiltroChange"
                            :disabled="tecnicoProblemaSubproblemaDisabled"
                            class="border border-gray-200 py-2.5 px-3 rounded-md disabled:bg-gray-100 disabled:cursor-not-allowed">
                            <option value="">-</option>
                            <option v-for="tecnico in tecnicos" :key="tecnico.id" :value="tecnico.id">
                                {{ tecnico.name }} {{ tecnico.apellido }}
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="problema" class="text-[#5B5B5B] text-sm">Problema servicio técnico</label>
                        <select name="problema" id="problema" v-model="selectedProblema" @change="onProblemaChange"
                            :disabled="tecnicoProblemaSubproblemaDisabled"
                            class="border border-gray-200 py-2.5 px-3 rounded-md disabled:bg-gray-100 disabled:cursor-not-allowed">
                            <option value="">-</option>
                            <option v-for="problema in problemas" :key="problema.id" :value="problema.id">
                                {{ problema.nombre }}
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="subproblema" class="text-[#5B5B5B] text-sm">Subproblema servicio técnico</label>
                        <select name="subproblema" id="subproblema" v-model="selectedSubproblema"
                            @change="onServiciosFiltroChange" :disabled="tecnicoProblemaSubproblemaDisabled"
                            class="border border-gray-200 py-2.5 px-3 rounded-md disabled:bg-gray-100 disabled:cursor-not-allowed">
                            <option value="">-</option>
                            <option v-for="subproblema in subproblemasDisponibles" :key="subproblema.id"
                                :value="subproblema.id">
                                {{ subproblema.nombre }}
                            </option>
                        </select>
                    </div>
                    <div class="flex flex-col justify-end lg:justify-normal gap-1.5 items-end">
                        <label class="text-[#5B5B5B] text-sm">&nbsp;</label>
                        <button @click="limpiarFiltros"
                            class="bg-[#0D509C] text-white px-4 py-2 rounded-full lg:w-[173px] cursor-pointer">
                            Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- Gráfico -->
            <div class="bg-white rounded-[8px] p-5 flex flex-col gap-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-black font-bold text-xl">
                        {{ tipoGrafico === 'procesos' ? 'Cantidad de modelos finalizados' : 'Servicios Técnicos por modelo' }}
                    </h2>
                    <div class="text-sm text-gray-600">
                        <span class="font-medium">Total: {{ total }}</span>
                    </div>
                </div>

                <div class="relative h-96">
                    <canvas ref="chartCanvas"></canvas>
                </div>

                <!-- Mensaje cuando no hay datos -->
                <div v-if="datosGrafico.length === 0" class="text-center py-8 text-gray-500">
                    <p>No hay datos para mostrar con los filtros seleccionados</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>