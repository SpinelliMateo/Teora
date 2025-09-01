<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref, computed, nextTick, onMounted } from 'vue'
import axios from 'axios'

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
const operario = ref('')
const controlStock = ref<ControlStock | null>(null)
const cargando = ref(false)
const errorMsg = ref<string | null>(null)
const numeroSerieInput = ref<HTMLInputElement | null>(null)
const numeroMotorInput = ref<HTMLInputElement | null>(null)
const operarioInput = ref<HTMLInputElement | null>(null)
const timeoutId = ref<number | null>(null)
const flashTimeoutId = ref<number | null>(null)
const showFlashMessage = ref(true)

const nombreModelo = computed(() => {
  if (!controlStock.value?.modelo) return ''
  const modelo = controlStock.value.modelo
  return modelo.nombre_modelo || `Modelo #${modelo.id}`
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


function limpiarMensajeDespues(delay = 10000) {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value)
  }

  timeoutId.value = setTimeout(() => {
    errorMsg.value = null
    timeoutId.value = null
  }, delay)
}
function limpiarMensajeFlash() {
  if (flashTimeoutId.value) {
    clearTimeout(flashTimeoutId.value)
  }

  flashTimeoutId.value = setTimeout(() => {
    showFlashMessage.value = false
    flashTimeoutId.value = null
  }, 10000)
}

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
      numeroMotorInput.value?.focus()
    } else {
      errorMsg.value = data.message
      limpiarMensajeDespues()
      numeroSerie.value = ''
      await nextTick()
      numeroSerieInput.value?.focus()
    }
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || 'Error al validar el producto.'
    limpiarMensajeDespues()
  } finally {
    cargando.value = false
  }
}

async function continuarAOperario() {
  if (!numeroMotor.value.trim()) return

  cargando.value = true
  errorMsg.value = null

  try {
    const { data } = await axios.post('/sectores/operarios/armado/validar-step-2', {
      numero_motor: numeroMotor.value.trim()
    })

    if (data.success) {
      controlStock.value = {
        ...controlStock.value,
        ...data.data
      }
      currentStep.value = 3
      setTimeout(() => {
        operarioInput.value?.focus()
      }, 10) // 10ms es suficiente
    } else {
      errorMsg.value = data.message
      limpiarMensajeDespues()
      numeroMotor.value = ''
      await nextTick()
      numeroMotorInput.value?.focus()
    }
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || 'Error al validar el motor.'
    limpiarMensajeDespues()
  } finally {
    cargando.value = false
  }
}

async function verificarOperario() {
  if (!operario.value.trim()) return

  cargando.value = true
  errorMsg.value = null

  try {
    const { data } = await axios.post('/sectores/operarios/armado/validar-operario', {
      operario: operario.value.trim()
    })

    if (data.success) {
      controlStock.value = data.data
      await grabar()
    } else {
      errorMsg.value = data.message
      limpiarMensajeDespues()
      operario.value = ''
      await nextTick()
      operarioInput.value?.focus()
    }
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || 'Error al validar el operario.'
    limpiarMensajeDespues()
  } finally {
    cargando.value = false
  }
}

async function grabar() {

  return new Promise((resolve, reject) => {
    router.post('/sectores/operarios/armado',
      {
        numero_serie: numeroSerie.value,
        numero_motor: numeroMotor.value,
        operario: operario.value,
      },
      {
        onStart: () => {
          cargando.value = true
          errorMsg.value = null
        },
        onSuccess: () => {
          reiniciar()
          resolve(true)
        },
        onError: (errors) => {
          errorMsg.value = (errors as any)?.message || 'No se pudo procesar el armado.'
          limpiarMensajeDespues()
          reject(errors)
        },
        onFinish: () => {
          cargando.value = false
        }
      }
    )
  })
}

function reiniciar() {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value)
    timeoutId.value = null
  }

  currentStep.value = 1
  numeroSerie.value = ''
  numeroMotor.value = ''
  operario.value = ''
  controlStock.value = null
  errorMsg.value = null

  if (flashMessage.value?.message && flashMessage.value.success) {
    showFlashMessage.value = true
    limpiarMensajeFlash()
  } else {
    showFlashMessage.value = false
  }

  nextTick(() => {
    numeroSerieInput.value?.focus()
  })
}

onMounted(() => {
  if (numeroSerieInput.value) numeroSerieInput.value.focus()
  if (flashMessage.value?.message) {
    showFlashMessage.value = true
    limpiarMensajeFlash()
  }
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
        <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6 lg:p-8">

          <!-- STEP 1: Número de Serie -->
          <div v-if="currentStep === 1">
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">N° de serie</label>
              <input ref="numeroSerieInput" v-model="numeroSerie" name="numero_serie" type="text"
                placeholder="Ingrese el número de serie" class="w-full rounded-xl border border-gray-300 px-3 py-2.5
                       focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                       transition text-sm md:text-base" @keyup.enter="validarYContinuar" />
            </div>

            <!-- Error de validación (específico del paso) -->
            <div v-if="errorMsg" class="p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
              {{ errorMsg }}
            </div>
            <div v-if="flashMessage?.message && showFlashMessage"
              class="mb-6 p-3 rounded-lg text-sm transition-all duration-300" :class="flashMessage.success
                ? 'bg-green-50 text-green-700 border border-green-200'
                : 'bg-red-50 text-red-700 border border-red-200'">
              {{ flashMessage.message }}
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
              <input ref="numeroMotorInput" v-model="numeroMotor" name="numero_motor" type="text"
                placeholder="Ingrese el número de motor" class="w-full rounded-xl border border-gray-300 px-3 py-2.5
                       focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                       transition text-sm md:text-base" @keyup.enter="continuarAOperario" />
            </div>

            <div v-if="errorMsg" class="mb-5 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
              {{ errorMsg }}
            </div>

          </div>

          <!-- STEP 3: Selección de Operario -->
          <div v-else>
            <div class="mb-6">
              <div class="mb-4">
                <p class="text-sm text-gray-600">N° de serie</p>
                <p class="text-xl font-semibold text-gray-900">{{ numeroSerie }}</p>
                <p class="text-sm text-gray-600">{{ nombreModelo }}</p>
                <p class="text-sm text-gray-600 mt-2">N° de motor: <span class="font-medium">{{ numeroMotor }}</span>
                </p>
              </div>

              <label class="block text-sm font-medium text-gray-700 mb-2">Operario</label>
              <div class="relative">
                <input ref="operarioInput" v-model="operario" name="operario" type="text"
                  placeholder="Escanee código del operario" class="w-full rounded-xl border border-gray-300 px-3 py-2.5
               focus:border-sky-800 focus:ring-2 focus:ring-sky-200
               transition text-sm md:text-base" @keyup.enter="verificarOperario" :disabled="cargando" />

                <div v-if="cargando" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                  <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-sky-800"></div>
                </div>
              </div>
            </div>

            <div v-if="errorMsg" class="mb-5 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
              {{ errorMsg }}
            </div>

            <div v-if="cargando" class="text-center text-sm text-gray-600 mt-4">
              Procesando armado...
            </div>
          </div>
        </div>

        <!-- Panel de estadísticas (fijo) -->
        <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6">
          <h3 class="text-base font-semibold text-gray-800 mb-4">Armados de hoy</h3>

          <!-- Tabla de operarios -->
          <div class="space-y-2 mb-4">
            <div
              class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded-lg text-sm font-medium text-gray-600">
              <span>OPERARIO</span>
              <span>CANTIDAD</span>
            </div>

            <div v-if="props.estadisticas.operarios.length === 0" class="text-center py-8 text-gray-500 text-sm">
              No hay armados registrados hoy
            </div>

            <div v-else v-for="operario in props.estadisticas.operarios" :key="operario.operario"
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
select::-ms-expand {
  display: none;
}
</style>