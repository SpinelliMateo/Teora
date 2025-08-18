<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref, computed, watch, nextTick } from 'vue'
import axios from 'axios'

type Operario = { id: number; nombre: string }
type ControlStock = {
  id: number
  n_serie: string
  modelo: {
    id: number
    nombre_modelo?: string
    modelo?: string
    descripcion?: string
  }
}
type EstadisticaOperario = { operario: string; cantidad_armados: number }
type Estadisticas = { operarios: EstadisticaOperario[]; total: number }

const props = defineProps<{ estadisticas: Estadisticas }>()

const page = usePage()
const flashMessage = computed(() => page.props.flash as any)

const currentStep = ref(1)
const numeroSerie = ref('')
const numeroMotor = ref('')
const selectedOperarioId = ref<number | null>(null)
const controlStock = ref<ControlStock | null>(null)
const operarios = ref<Operario[]>([])
const cargando = ref(false)
const errorMsg = ref<string | null>(null)
const numeroSerieInput = ref<HTMLInputElement | null>(null)

const nombreModelo = computed(() => {
  if (!controlStock.value?.modelo) return ''
  const modelo = controlStock.value.modelo
  return modelo.nombre_modelo || modelo.descripcion || modelo.modelo || `Modelo #${modelo.id}`
})

const nombreOperario = computed(() => {
  const op = operarios.value.find(o => o.id === selectedOperarioId.value)
  return op?.nombre ?? ''
})

const pageBgClass = computed(() => {
  if (currentStep.value === 1) return 'bg-gray-50'
  if (currentStep.value === 2) return 'bg-blue-50'
  return 'bg-green-50'
})

const stepCircleClass = (n: number) =>
  currentStep.value >= n
    ? 'bg-sky-800 text-white shadow'
    : 'bg-gray-300 text-gray-600'

async function validarYContinuar() {
  if (!numeroSerie.value.trim()) return
  
  cargando.value = true
  errorMsg.value = null
  
  try {
    const { data } = await axios.post('/sectores/operarios/armado/validar', {
      numero_serie: numeroSerie.value.trim()
    })
    
    if (data.success) {
      controlStock.value = data.data
      currentStep.value = 2
      await nextTick()
      // Enfocar el input de número de motor
      const motorInput = document.querySelector('input[name="numero_motor"]') as HTMLInputElement
      if (motorInput) motorInput.focus()
    } else {
      errorMsg.value = data.message
    }
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || 'Error al validar el producto.'
  } finally {
    cargando.value = false
  }
}

async function cargarOperarios() {
  if (operarios.value.length > 0) return
  
  try {
    const { data } = await axios.get('/sectores/operarios/armado/operarios')
    operarios.value = Array.isArray(data?.operarios) ? data.operarios : []
  } catch (e: any) {
    console.error('Error al cargar operarios:', e)
  }
}

function continuarAOperario() {
  if (!numeroMotor.value.trim()) return
  currentStep.value = 3
  cargarOperarios()
}

function puedeGrabar() {
  return !!numeroSerie.value && !!numeroMotor.value && !!selectedOperarioId.value && !cargando.value
}

function grabar() {
  if (!puedeGrabar()) return
  
  router.post('/sectores/operarios/armado', 
    {
      numero_serie: numeroSerie.value,
      numero_motor: numeroMotor.value,
      operario_id: selectedOperarioId.value,
    },
    {
      onStart: () => { cargando.value = true; errorMsg.value = null },
      onSuccess: () => { reiniciar() },
      onError: (errors) => { errorMsg.value = (errors as any)?.message || 'No se pudo procesar el armado.' },
      onFinish: () => { cargando.value = false }
    }
  )
}

function reiniciar() {
  currentStep.value = 1
  numeroSerie.value = ''
  numeroMotor.value = ''
  selectedOperarioId.value = null
  controlStock.value = null
  errorMsg.value = null
  nextTick(() => {
    if (numeroSerieInput.value) numeroSerieInput.value.focus()
  })
  
  // Limpiar mensaje flash después de reiniciar
  if (flashMessage.value?.message) {
    // El mensaje flash se limpia automáticamente en la próxima navegación
    window.location.reload()
  }
}

function volver() {
  if (currentStep.value === 3) {
    currentStep.value = 2
  } else if (currentStep.value === 2) {
    currentStep.value = 1
    controlStock.value = null
  }
  errorMsg.value = null
}

// Auto focus en el input inicial
nextTick(() => {
  if (numeroSerieInput.value) numeroSerieInput.value.focus()
})
</script>

<template>
  <Head title="Armado" />
  <div class="min-h-screen transition-colors duration-200" :class="pageBgClass">
    <div class="max-w-4xl mx-auto px-4 py-6 md:py-10">
      <!-- Header responsive -->
      <div class="mb-6 md:mb-8">
        <!-- Móvil: título centrado arriba, pasos abajo -->
        <div class="block md:hidden text-center">
          <h1 class="text-lg font-semibold text-gray-800 mb-4">Armado</h1>
          <div class="flex items-center justify-center space-x-4">
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
                 :class="stepCircleClass(1)">1</div>
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
                 :class="stepCircleClass(2)">2</div>
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
                 :class="stepCircleClass(3)">3</div>
          </div>
        </div>
        
        <!-- Desktop: layout original con grid -->
        <div class="hidden md:grid grid-cols-3 items-center">
          <h1 class="text-lg font-semibold text-gray-800 justify-self-start">Armado</h1>
          <div class="justify-self-center flex items-center space-x-4">
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
                 :class="stepCircleClass(1)">1</div>
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
                 :class="stepCircleClass(2)">2</div>
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
                 :class="stepCircleClass(3)">3</div>
          </div>
          <div></div>
        </div>
      </div>

      <div class="grid lg:grid-cols-2 gap-6">
        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6 lg:p-8">
          
          <!-- Mensaje flash (éxito/error global) -->
          <div v-if="flashMessage?.message" 
               class="mb-6 p-3 rounded-lg text-sm"
               :class="flashMessage.success 
                 ? 'bg-green-50 text-green-700 border border-green-200' 
                 : 'bg-red-50 text-red-700 border border-red-200'">
            {{ flashMessage.message }}
          </div>

          <!-- STEP 1: Número de Serie -->
          <div v-if="currentStep === 1">
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">N° de serie</label>
              <input
                ref="numeroSerieInput"
                v-model="numeroSerie"
                type="text"
                placeholder="Ingrese el número de serie"
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5
                       focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                       transition text-sm md:text-base"
                @keyup.enter="validarYContinuar"
                :disabled="cargando"
              />
            </div>

            <!-- Error de validación (específico del paso) -->
            <div v-if="errorMsg"
                 class="mb-5 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
              {{ errorMsg }}
            </div>

            <!-- CTA -->
            <div class="text-center">
              <button
                class="w-full max-w-80 h-10 rounded-[20px] bg-sky-800 text-white font-medium
                       hover:bg-sky-900 disabled:opacity-50 disabled:cursor-not-allowed transition text-sm md:text-base"
                :disabled="!numeroSerie.trim() || cargando"
                @click="validarYContinuar"
              >
                {{ cargando ? 'Validando...' : 'Validar' }}
              </button>
            </div>
          </div>

          <!-- STEP 2: Número de Motor -->
          <div v-else-if="currentStep === 2">
            <div class="mb-6">
              <div class="mb-4">
                <p class="text-sm text-gray-600">N° de serie</p>
                <p class="text-xl font-semibold text-gray-900">{{ numeroSerie }}</p>
                <p class="text-sm text-gray-600 mt-1">{{ nombreModelo }}</p>
              </div>
              
              <label class="block text-sm font-medium text-gray-700 mb-2">N° de motor</label>
              <input
                v-model="numeroMotor"
                name="numero_motor"
                type="text"
                placeholder="Ingrese el número de motor"
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5
                       focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                       transition text-sm md:text-base"
                @keyup.enter="continuarAOperario"
              />
            </div>

            <div class="flex flex-col space-y-4 md:space-y-0 md:flex-row md:justify-center md:gap-4">
              <button
                class="w-full md:w-40 h-10 rounded-[20px] bg-transparent text-sky-800 font-medium
                       border border-sky-800 hover:bg-sky-50 transition text-sm md:text-base"
                @click="volver"
              >
                Volver
              </button>

              <button
                class="w-full md:w-40 h-10 rounded-[20px] bg-sky-800 text-white font-medium
                       hover:bg-sky-900 disabled:opacity-50 transition text-sm md:text-base"
                :disabled="!numeroMotor.trim()"
                @click="continuarAOperario"
              >
                Continuar
              </button>
            </div>
          </div>

          <!-- STEP 3: Selección de Operario -->
          <div v-else>
            <div class="mb-6">
              <div class="mb-4">
                <p class="text-sm text-gray-600">N° de serie</p>
                <p class="text-xl font-semibold text-gray-900">{{ numeroSerie }}</p>
                <p class="text-sm text-gray-600">{{ nombreModelo }}</p>
                <p class="text-sm text-gray-600 mt-2">N° de motor: <span class="font-medium">{{ numeroMotor }}</span></p>
              </div>
              
              <label class="block text-sm font-medium text-gray-700 mb-2">Operario</label>
              <div class="relative">
                <select
                  v-model.number="selectedOperarioId"
                  class="w-full rounded-xl border border-gray-300 pr-10 pl-3 py-2.5
                         focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                         transition bg-white appearance-none text-sm md:text-base"
                >
                  <option :value="null" disabled>Seleccionar operario...</option>
                  <option v-for="op in operarios" :key="op.id" :value="op.id">
                    {{ op.nombre }}
                  </option>
                </select>
                <!-- caret -->
                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500"
                     viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z"/>
                </svg>
              </div>
            </div>

            <!-- Error de validación -->
            <div v-if="errorMsg"
                 class="mb-5 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
              {{ errorMsg }}
            </div>

            <div class="flex flex-col space-y-4 md:space-y-0 md:flex-row md:justify-center md:gap-4">
              <button
                class="w-full md:w-40 h-10 rounded-[20px] bg-transparent text-sky-800 font-medium
                       border border-sky-800 hover:bg-sky-50 transition text-sm md:text-base"
                @click="volver"
                :disabled="cargando"
              >
                Volver
              </button>

              <button
                class="w-full md:w-40 h-10 rounded-[20px] bg-sky-800 text-white font-medium
                       hover:bg-sky-900 disabled:opacity-50 transition text-sm md:text-base"
                :disabled="!puedeGrabar()"
                @click="grabar"
              >
                {{ cargando ? 'Procesando...' : 'Grabar' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Panel de estadísticas (fijo) -->
        <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6">
          <h3 class="text-base font-semibold text-gray-800 mb-4">Armados de hoy</h3>
          
          <!-- Tabla de operarios -->
          <div class="space-y-2 mb-4">
            <div class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded-lg text-sm font-medium text-gray-600">
              <span>OPERARIO</span>
              <span>CANTIDAD</span>
            </div>
            
            <div v-if="props.estadisticas.operarios.length === 0" 
                 class="text-center py-8 text-gray-500 text-sm">
              No hay armados registrados hoy
            </div>
            
            <div v-else
                 v-for="operario in props.estadisticas.operarios" 
                 :key="operario.operario"
                 class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded-lg text-sm">
              <span class="text-gray-700">{{ operario.operario }}</span>
              <span class="font-semibold text-gray-900">{{ operario.cantidad_armados }}</span>
            </div>
          </div>

          <!-- Total -->
          <div class="border-t pt-4">
            <div class="flex justify-between items-center text-lg font-bold text-gray-900">
              <span>Total</span>
              <span>{{ props.estadisticas.total }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
select::-ms-expand { display: none; }
</style>