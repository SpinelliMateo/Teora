<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from '@/composables/useToast'
import { useQzTray } from '@/composables/useQzTray'

const { success, error } = useToast()
const { imprimirMultiplesConQZTray } = useQzTray();

type Operario = { id: number; nombre: string; apellido: string; codigo_qr: string }
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
type EstadisticaOperario = { operario: string; cantidad_embalados: number }
type Estadisticas = { operarios: EstadisticaOperario[]; total: number }

type ProductoValidado = {
  n_serie: string
  valido: boolean
  mensaje?: string
  producto?: ControlStock
}
type ProductoYaEmbalado = {
  n_serie: string
  fecha_embalado: string
  operario: string
}
type ProductoImprimir = {
  id: number
  n_serie: string
  modelo: { modelo_nombre: string }
  fecha_embalado: string
  fecha_embalado_iso: string
  qr_code: string
}

const props = defineProps<{ estadisticas: Estadisticas }>()

const currentStep = ref(1)
const numerosSerie = ref(['', '', '', ''])
const selectedOperarioId = ref<number | null>(null)
const nombreOperario = ref('')
const operarioEmbalador = ref('')
const productosValidados = ref<ProductoValidado[]>([])
const operarios = ref<Operario[]>([])
const cargando = ref(false)
const cargandoImpresion = ref(false)
const errorMsg = ref<string | null>(null)
const successMsg = ref<string | null>(null)
const timeoutId = ref<number | null>(null)
const productosAImprimir = ref<ProductoImprimir[]>([])

// Estados para productos ya embalados
const modalImpresion = ref(false)
const productosYaEmbalados = ref<ProductoYaEmbalado[]>([])
const productosValidosParaContinuar = ref<ProductoValidado[]>([])

const pageBgClass = computed(() => {
  if (currentStep.value === 1) return 'bg-gray-50'
  return 'bg-blue-50'
})

const stepCircleClass = (n: number) =>
  currentStep.value >= n
    ? 'bg-sky-800 text-white shadow'
    : 'bg-gray-300 text-gray-600'

const numerosSerieLlenos = computed(() => {
  return numerosSerie.value.filter(serie => serie.trim() !== '')
})

const productosValidosFinales = computed(() => {
  return productosValidados.value.filter(p => p.valido)
})

const puedeValidar = computed(() => {
  return numerosSerieLlenos.value.length > 0 && numerosSerieLlenos.value.length <= 4 && !cargando.value
})

const puedeGrabar = computed(() => {
  return productosValidosFinales.value.length > 0 && !!selectedOperarioId.value && !cargando.value
})

function limpiarMensajeDespues(delay = 5000) {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value)
  }

  timeoutId.value = setTimeout(() => {
    errorMsg.value = null
    successMsg.value = null
    timeoutId.value = null
  }, delay)
}

const enfocarPrimerInput = () => {
  setTimeout(() => {
    const input = document.querySelector('input[name="numero_serie_0"]') as HTMLInputElement
    if (input) {
      input.focus()
    }
  }, 200)
}

function manejarEnterEnSerie(index: number) {
  const valorActual = numerosSerie.value[index].trim();

  if (numerosSerie.value.some((num, i) => i !== index && num.trim() === valorActual)) {
    errorMsg.value = `No se puede ingresar 2 veces el mismo número de serie.`;
    limpiarMensajeDespues();
    numerosSerie.value[index] = '';
    const inputActual = document.querySelector(
      `input[name="numero_serie_${index}"]`
    ) as HTMLInputElement;
    inputActual.focus();
    return;
  }
  validarSerie(valorActual, index);
}

async function validarSerie(valorActual: string, index: number) {
  if (!puedeValidar.value) return;

  cargando.value = true;
  errorMsg.value = null;

  try {
    const { data } = await axios.post('/sectores/operarios/embalado/validar-serie', {
      serie: valorActual
    });

    if (data.success) {
      productosValidados.value.push({
        n_serie: valorActual,
        valido: true,
        producto: data.data as ControlStock
      });

      cargando.value = false;
      await nextTick();

      if (index < 3) {
        const nextInput = document.querySelector(
          `input[name="numero_serie_${index + 1}"]`
        ) as HTMLInputElement;
        if (nextInput) {
          nextInput.focus();
        }
      } else {
        continuarAOperario();
      }
    } else {
      errorMsg.value = data.message;
      numerosSerie.value[index] = '';

      cargando.value = false;
      await nextTick();

      const inputActual = document.querySelector(
        `input[name="numero_serie_${index}"]`
      ) as HTMLInputElement;
      if (inputActual) inputActual.focus();
    }
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || 'Error al validar los productos.';
    limpiarMensajeDespues();

    numerosSerie.value[index] = '';

    cargando.value = false;
    await nextTick();

    const inputActual = document.querySelector(
      `input[name="numero_serie_${index}"]`
    ) as HTMLInputElement;
    if (inputActual) inputActual.focus();
  }
}

async function cargarOperarios() {
  if (operarios.value.length > 0) return

  try {
    const { data } = await axios.get('/sectores/operarios/embalado/operarios')
    operarios.value = Array.isArray(data?.operarios) ? data.operarios : []
  } catch (e: any) {
    console.error('Error al cargar operarios:', e)
  }
}

function continuarAOperario() {
  currentStep.value = 2
  cargarOperarios()
  nextTick(() => {
    const operarioInput = document.querySelector('input[name="operario_nombre"]') as HTMLInputElement
    if (operarioInput) operarioInput.focus()
  })
}

function validarOperario() {
  const nombreBuscado = nombreOperario.value.trim().toLowerCase()

  if (nombreBuscado.length < 2) {
    selectedOperarioId.value = null
    return
  }

  const operarioEncontrado = operarios.value.find(op =>
    op.codigo_qr.toLowerCase().includes(nombreBuscado)
  )

  if (operarioEncontrado) {
    selectedOperarioId.value = operarioEncontrado.id
    operarioEmbalador.value = operarioEncontrado.nombre + ' ' + operarioEncontrado.apellido
    nextTick(() => {
      const operarioInput = document.querySelector('input[name="operario_nombre"]') as HTMLInputElement
      if (operarioInput) operarioInput.blur()
    })
  } else {
    selectedOperarioId.value = null
    errorMsg.value = 'Operario con el codigo ' + nombreBuscado + ' no encontrado'
    limpiarMensajeDespues()
    nombreOperario.value = ''
    nextTick(() => {
      const operarioInput = document.querySelector('input[name="operario_nombre"]') as HTMLInputElement
      if (operarioInput) operarioInput.focus()
    })
  }
}

async function grabar() {
  if (!puedeGrabar.value) return

  const datosEmbalado = {
    numeros_serie: productosValidosFinales.value.map(p => p.n_serie),
    operario_id: selectedOperarioId.value,
    continuar_con_embalados: false
  }

  cargando.value = true;
  errorMsg.value = null;


  try {
    const response = await axios.post('/sectores/operarios/embalado', datosEmbalado);

    console.log('Respuesta del servidor:', response.data);

    abrirModalImpresion(response.data);
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || 'No se pudo procesar el embalado.';
    limpiarMensajeDespues();
  } finally {
    cargando.value = false;
  }
}


function abrirModalImpresion(data: any) {
  modalImpresion.value = true
  productosAImprimir.value = data.productos || []
}

function generarQRDataURL(productoId: number): string {
  return `/barcode/generate/${productoId}`
}

const imprimirEtiquetasYaEmbalados = async () => {
  cargandoImpresion.value = true;
  errorMsg.value = null;
  successMsg.value = null;

  const productosIds = productosAImprimir.value
    .map(p => {
      const productoValidado = productosValidados.value.find(pv => pv.n_serie === p.n_serie)
      return productoValidado?.producto?.id
    })
    .filter(id => id !== undefined)

  if (productosIds.length > 0) {
    try {
      const response = await fetch('/sectores/operarios/embalado/imprimir-etiqueta', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content
        },
        body: JSON.stringify({
          control_stock_ids: productosIds
        })
      });

      const data = await response.json();

      if (!data.success) {
        error(data.message || "Error al generar etiquetas");
        return;
      }

      await imprimirMultiplesConQZTray(data.zpls);
      success(data.message || `${data.cantidad} etiquetas impresas correctamente.`);
      
      reiniciar();
    } catch (e) {
      console.error('Errores de impresión:', e);
      error('Error inesperado al imprimir. Por favor, intenta nuevamente.');
    }
  } else {
    errorMsg.value = 'No se pudieron obtener los IDs de los productos para reimprimir'
    limpiarMensajeDespues()
  }
  
  cargandoImpresion.value = false;
};

function reiniciar() {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value)
    timeoutId.value = null
  }

  currentStep.value = 1
  numerosSerie.value = ['', '', '', '']
  selectedOperarioId.value = null
  nombreOperario.value = ''
  operarioEmbalador.value = ''
  productosValidados.value = []
  modalImpresion.value = false
  productosYaEmbalados.value = []
  productosValidosParaContinuar.value = []
  errorMsg.value = null
  cargandoImpresion.value = false
  limpiarMensajeDespues()

  nextTick(() => {
    enfocarPrimerInput()
  })
}

function volver() {
  if (currentStep.value === 2) {
    currentStep.value = 1
    selectedOperarioId.value = null
    nombreOperario.value = ''
    operarioEmbalador.value = ''
    modalImpresion.value = false
    // Enfocar el primer input al volver
    enfocarPrimerInput()
  }
  errorMsg.value = null
}


onMounted(() => {
  cargarOperarios()
  enfocarPrimerInput()
})

watch(currentStep, (newStep) => {
  if (newStep === 1) {
    enfocarPrimerInput()
  }
})
</script>

<template>

  <Head title="Embalado" />
  <div class="min-h-screen transition-colors duration-200" :class="pageBgClass">
    <div class="max-w-4xl mx-auto px-4 py-6 md:py-10">

      <!-- Header responsive -->
      <div class="mb-6 md:mb-8">

        <!-- Móvil: título centrado arriba, pasos abajo -->
        <div class="block md:hidden text-center">
          <h1 class="text-lg font-semibold text-gray-800 mb-4">Embalado</h1>
          <div class="flex items-center justify-center space-x-4">
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
              :class="stepCircleClass(1)">1</div>
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
              :class="stepCircleClass(2)">2</div>
          </div>
        </div>

        <!-- Desktop: layout original con grid -->
        <div class="hidden md:grid grid-cols-3 items-center">
          <h1 class="text-lg font-semibold text-gray-800 justify-self-start">Embalado</h1>
          <div class="justify-self-center flex items-center space-x-4">
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
              :class="stepCircleClass(1)">1</div>
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
              :class="stepCircleClass(2)">2</div>
          </div>
          <div></div>
        </div>
      </div>

      <div class="grid lg:grid-cols-2 gap-6">
        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6 lg:p-8">

          <!-- STEP 1: Números de Serie -->
          <div v-if="currentStep === 1">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-3">N° de serie (máximo 4)</label>
              <div class="space-y-3">
                <input v-for="(serie, index) in numerosSerie" :key="index" v-model="numerosSerie[index]"
                  :name="`numero_serie_${index}`" type="text" :placeholder="`N° de serie ${index + 1}`" class="w-full rounded-xl border border-gray-300 px-3 py-2.5
                         focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                         transition text-sm md:text-base" @keyup.enter="manejarEnterEnSerie(index)"
                  :disabled="cargando" />
              </div>
            </div>

            <!-- Productos validados preview -->
            <div v-if="productosValidados.length > 0" class="mb-2">
              <div class="text-sm text-gray-600 mb-2">Productos encontrados:</div>
              <div class="space-y-1">
                <div v-for="producto in productosValidados" :key="producto.n_serie" class="p-2 rounded"
                  :class="producto.valido ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                  <strong>{{ producto.n_serie }}</strong>
                  <span v-if="producto.producto"> - {{ producto.producto.modelo.nombre_modelo ||
                    producto.producto.modelo.modelo }}</span>
                  <span v-if="!producto.valido"> - {{ producto.mensaje }}</span>
                </div>
              </div>
            </div>

            <!-- Error de validación (específico del paso) -->
            <div v-if="errorMsg" class="p-3 mb-2 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
              {{ errorMsg }}
            </div>

            <!-- Error de validación (específico del paso) -->
            <div v-if="successMsg"
              class="p-3 mb-2 rounded-lg bg-green-50 text-green-700 border border-green-200 text-sm">
              {{ successMsg }}
            </div>

            <!-- boton continuar -->
            <div class="text-center">
              <button
                class="w-full h-10 rounded-[20px] bg-sky-800 text-white font-medium
                       hover:bg-sky-900 disabled:opacity-50 disabled:cursor-not-allowed transition text-sm md:text-base"
                :disabled="!puedeValidar" @click="continuarAOperario">
                Continuar
              </button>
            </div>

          </div>

          <!-- STEP 2: Operario -->
          <div v-else-if="currentStep === 2">
            <div class="mb-6">
              <div class="mb-4">
                <p class="text-gray-600">Modelos a embalar:</p>
                <div class="space-y-1">
                  <div v-for="producto in productosValidosFinales" :key="producto.n_serie"
                    class="bg-gray-50 p-2 rounded">
                    <strong>{{ producto.n_serie }}</strong>
                    <span v-if="producto.producto"> - {{ producto.producto.modelo.nombre_modelo ||
                      producto.producto.modelo.modelo }}</span>
                  </div>
                </div>
              </div>

              <label class="block font-medium text-gray-700 mb-2">Operario</label>
              <div class="relative">
                <input v-model="nombreOperario" name="operario_nombre" type="text"
                  placeholder="Escanee o escriba el nombre del operario" class="w-full rounded-xl border border-gray-300 px-3 py-2.5
                         focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                         transition text-sm md:text-base" @keyup.enter="validarOperario" :disabled="cargando" />

                <div v-if="cargando" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                  <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-sky-800"></div>
                </div>
              </div>

              <!-- Operario seleccionado -->
              <div v-if="selectedOperarioId" class="mt-2 text-sm text-green-600">
                ✓ Operario seleccionado: {{ operarioEmbalador }}
              </div>
            </div>

            <!-- Error de validación -->
            <div v-if="errorMsg" class="mb-5 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
              {{ errorMsg }}
            </div>

            <div v-if="cargando" class="text-center text-sm text-gray-600 mt-4">
              Procesando embalado...
            </div>

            <div class="flex flex-col space-y-4 md:space-y-0 md:flex-row md:justify-center md:gap-4">
              <button class="w-full md:w-40 h-10 rounded-[20px] bg-transparent text-sky-800 font-medium
                       border border-sky-800 hover:bg-sky-50 transition text-sm md:text-base" @click="volver"
                :disabled="cargando">
                Volver
              </button>

              <button
                class="w-full md:w-40 h-10 rounded-[20px] bg-sky-800 text-white font-medium
                       hover:bg-sky-900 disabled:opacity-50 disabled:cursor-not-allowed transition text-sm md:text-base"
                :disabled="!puedeGrabar" @click="grabar">
                {{ cargando ? 'Procesando...' : 'Embalar' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Panel de estadísticas (fijo) -->
        <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6">
          <h3 class="text-base font-semibold text-gray-800 mb-4">Embalados de hoy</h3>

          <!-- Tabla de operarios -->
          <div class="space-y-2 mb-4">
            <div
              class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded-lg text-sm font-medium text-gray-600">
              <span>OPERARIO</span>
              <span>CANTIDAD</span>
            </div>

            <div v-if="props.estadisticas.operarios.length === 0" class="text-center py-8 text-gray-500 text-sm">
              No hay embalados registrados hoy
            </div>

            <div v-else v-for="operario in props.estadisticas.operarios" :key="operario.operario"
              class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded-lg text-sm">
              <span class="text-gray-700">{{ operario.operario }}</span>
              <span class="font-semibold text-gray-900">{{ operario.cantidad_embalados }}</span>
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

  <!-- Modal emergente de productos ya embalados -->
  <div v-if="modalImpresion" class="fixed inset-0 flex items-center justify-center h-full w-full">
    <div class="fixed inset-0 bg-black opacity-50 z-1"></div>
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full py-3 z-50 max-h-[90vh] relative">
      <div v-if="cargandoImpresion" class="absolute inset-0 bg-gray-200 opacity-70 z-50 ">
      </div>
      <div v-if="cargandoImpresion" class="absolute flex h-full w-full items-center justify-center pb-10 z-50">
        <div class="flex flex-col gap-10 items-center justify-center h-90 w-80 bg-white rounded-2xl">
          <div class="animate-spin rounded-full h-30 w-30 border-b-2 border-sky-800"></div>
          <p class="flex items-center gap-1">
            Imprimiendo
            <span class="dot animate-bounce">.</span>
            <span class="dot animate-bounce delay-200">.</span>
            <span class="dot animate-bounce delay-400">.</span>
          </p>

        </div>
      </div>
      <h2 class="text-lg font-bold text-gray-800 border-b border-gray-200 px-6 h-9">Productos a imprimir</h2>
      <div class="py-4 space-y-4 overflow-y-auto max-h-[70vh] px-6">
        <div v-for="(producto, index) in productosAImprimir" :key="producto.id"
          class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm text-black">
          <!-- Header de la tarjeta con título y botón -->
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-700">Datos generales - Etiqueta {{ index + 1 }}</h3>
          </div>

          <!-- Campos en grid de 3 columnas -->
          <div class="grid grid-cols-3 gap-4 mb-6">
            <!-- Ingreso a depósito -->
            <div>
              <label class="block text-gray-500 mb-2">Ingreso a depósito</label>
              <div class="relative">
                <div class="flex items-center text-center border border-gray-300 rounded px-3 py-2 h-10.5">
                  <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />
                  </svg>
                  <span class="text-sm text-gray-900 mt-0.5">{{ producto.fecha_embalado }}</span>
                </div>
              </div>
            </div>

            <!-- N° de serie -->
            <div>
              <label class="block text-gray-500 mb-2">N° de serie</label>
              <div class="border border-gray-300 rounded bg-white px-3 py-2">
                <span class="text-sm font-medium text-gray-900">{{ producto.n_serie }}</span>
              </div>
            </div>

            <!-- Modelo -->
            <div>
              <label class="block text-gray-500 mb-2">Modelo</label>
              <div class="border border-gray-300 rounded bg-white px-3 py-2">
                <span class="text-sm text-gray-900">{{ producto.modelo.modelo_nombre }}</span>
              </div>
            </div>
          </div>

          <!-- Código de barras -->
          <div class="bg-gray-50 rounded-lg p-4 flex justify-center">
            <div>
              <img :src="generarQRDataURL(producto.id)" :alt="`Código de barras ${producto.n_serie}`"
                class="h-16 w-auto mx-auto block mb-2" />
              <div class="text-center text-xs font-medium text-gray-700 tracking-wide">
                {{ producto.n_serie }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="px-6 flex justify-end border-t gap-2 border-gray-200">
        <button class="mt-4 bg-[#0D509C] text-white rounded-lg px-4 py-2"
          @click="imprimirEtiquetasYaEmbalados">Imprimir</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
select::-ms-expand {
  display: none;
}

.dot {
  display: inline-block;
}

.delay-200 {
  animation-delay: 0.2s;
}

.delay-400 {
  animation-delay: 0.4s;
}
</style>