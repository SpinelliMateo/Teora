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

const props = defineProps<{ estadisticas: Estadisticas }>()

const page = usePage()
const flashMessage = computed(() => page.props.flash as any)

const currentStep = ref(1)
const numerosSerie = ref(['', '', '', ''])
const selectedOperarioId = ref<number | null>(null)
const nombreOperario = ref('')
const productosValidados = ref<ProductoValidado[]>([])
const operarios = ref<Operario[]>([])
const cargando = ref(false)
const errorMsg = ref<string | null>(null)
const primerInput = ref<HTMLInputElement | null>(null)

// Estados para productos ya embalados
const mostrarModalReimpresion = ref(false)
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

const puedeValidar = computed(() => {
  return numerosSerieLlenos.value.length > 0 && numerosSerieLlenos.value.length <= 4 && !cargando.value
})

async function validarProductos() {
  if (!puedeValidar.value) return
  
  cargando.value = true
  errorMsg.value = null
  
  try {
    const { data } = await axios.post('/sectores/operarios/embalado/validar-productos', {
      numeros_serie: numerosSerieLlenos.value
    })
    
    if (data.success) {
      productosValidados.value = data.productos_validados || []
      
      // Verificar si hay productos ya embalados
      if (data.tiene_productos_ya_embalados) {
        mostrarModalReimpresion.value = true
        productosYaEmbalados.value = data.productos_ya_embalados || []
        productosValidosParaContinuar.value = data.productos_validos || []
      } else {
        // Si no hay productos ya embalados, continuar al paso 2
        continuarAOperario()
      }
    } else {
      errorMsg.value = data.message
    }
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || 'Error al validar los productos.'
  } finally {
    cargando.value = false
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

function continuarConEmbalados() {
  mostrarModalReimpresion.value = false
  
  // Solo continuar con productos válidos (no embalados)
  if (productosValidosParaContinuar.value.length > 0) {
    continuarAOperario()
  } else {
    // Si no hay productos válidos para continuar, reiniciar
    errorMsg.value = 'No hay productos válidos para embalar. Todos los productos ya fueron embalados.'
    setTimeout(() => {
      reiniciar()
    }, 2000)
  }
}

function imprimirEtiquetasYaEmbalados() {
  // Obtener los IDs de los productos ya embalados
  const productosIds = productosYaEmbalados.value
    .map(p => {
      // Buscar el producto validado correspondiente
      const productoValidado = productosValidados.value.find(pv => pv.n_serie === p.n_serie)
      return productoValidado?.producto?.id
    })
    .filter(id => id !== undefined)
  
  if (productosIds.length > 0) {
    // Cerrar modal primero
    mostrarModalReimpresion.value = false
    
    // Usar POST para enviar los IDs y redirigir a etiquetas
    router.post('/sectores/operarios/embalado/reimprimir-etiqueta', {
      control_stock_ids: productosIds
    })
  } else {
    // Si no hay IDs válidos, mostrar error
    errorMsg.value = 'No se pudieron obtener los IDs de los productos para reimprimir'
  }
}

function cerrarModalReimpresion() {
  mostrarModalReimpresion.value = false
  // Limpiar datos y volver al estado inicial
  reiniciar()
}

function continuarAOperario() {
  currentStep.value = 2
  cargarOperarios()
  nextTick(() => {
    const operarioInput = document.querySelector('input[name="operario_nombre"]') as HTMLInputElement
    if (operarioInput) operarioInput.focus()
  })
}

// Auto-completar operario (simulando scanner)
function buscarOperario() {
  const nombreBuscado = nombreOperario.value.trim().toLowerCase()
  if (nombreBuscado.length < 2) {
    selectedOperarioId.value = null
    return
  }
  
  const operarioEncontrado = operarios.value.find(op => 
    op.nombre.toLowerCase().includes(nombreBuscado)
  )
  
  if (operarioEncontrado) {
    selectedOperarioId.value = operarioEncontrado.id
    nombreOperario.value = operarioEncontrado.nombre
  } else {
    selectedOperarioId.value = null
  }
}

function puedeGrabar() {
  return numerosSerieLlenos.value.length > 0 && !!selectedOperarioId.value && !cargando.value
}

function grabar() {
  if (!puedeGrabar()) return
  
  const datosEmbalado = {
    numeros_serie: numerosSerieLlenos.value,
    operario_id: selectedOperarioId.value,
    continuar_con_embalados: false
  }
  
  router.post('/sectores/operarios/embalado', datosEmbalado, {
    onStart: () => { 
      cargando.value = true
      errorMsg.value = null 
    },
    onSuccess: () => { 
      // El controlador se encarga de la redirección a etiquetas
      // No necesitamos hacer nada más aquí
    },
    onError: (errors) => { 
      errorMsg.value = (errors as any)?.message || 'No se pudo procesar el embalado.' 
    },
    onFinish: () => { 
      cargando.value = false 
    }
  })
}

function reiniciar() {
  currentStep.value = 1
  numerosSerie.value = ['', '', '', '']
  selectedOperarioId.value = null
  nombreOperario.value = ''
  productosValidados.value = []
  mostrarModalReimpresion.value = false
  productosYaEmbalados.value = []
  productosValidosParaContinuar.value = []
  errorMsg.value = null
  nextTick(() => {
    if (primerInput.value) primerInput.value.focus()
  })
  
  // Limpiar mensaje flash después de reiniciar
  if (flashMessage.value?.message) {
    // Recargar estadísticas sin recargar toda la página
    router.reload({ only: ['estadisticas'] })
  }
}

function volver() {
  if (currentStep.value === 2) {
    currentStep.value = 1
    selectedOperarioId.value = null
    nombreOperario.value = ''
    // No limpiar productosValidados para mantener el estado
    mostrarModalReimpresion.value = false
  }
  errorMsg.value = null
}

function manejarEnterEnSerie(index: number) {
  if (index < 3 && numerosSerie.value[index].trim() !== '') {
    // Mover al siguiente input
    const nextInput = document.querySelector(`input[name="numero_serie_${index + 1}"]`) as HTMLInputElement
    if (nextInput) nextInput.focus()
  } else {
    // Si es el último input o no hay más inputs, validar
    validarProductos()
  }
}

// Auto focus en el primer input
nextTick(() => {
  if (primerInput.value) primerInput.value.focus()
})

// Cargar operarios al inicio
cargarOperarios()
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
          
          <!-- Mensaje flash (éxito/error global) -->
          <div v-if="flashMessage?.message" 
               class="mb-6 p-3 rounded-lg text-sm"
               :class="flashMessage.success 
                 ? 'bg-green-50 text-green-700 border border-green-200' 
                 : 'bg-red-50 text-red-700 border border-red-200'">
            {{ flashMessage.message }}
          </div>

          <!-- Modal emergente de productos ya embalados -->
          <div v-if="mostrarModalReimpresion" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Productos ya embalados</h3>
                <button @click="cerrarModalReimpresion" class="text-gray-400 hover:text-gray-600">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
              
              <div class="text-sm text-gray-600 mb-4">
                Los siguientes productos ya fueron embalados:
              </div>
              
              <div class="space-y-2 mb-6">
                <div v-for="producto in productosYaEmbalados" :key="producto.n_serie"
                     class="text-sm bg-yellow-50 p-3 rounded border-l-4 border-yellow-400">
                  <div class="font-medium text-gray-900">{{ producto.n_serie }}</div>
                  <div class="text-gray-600">Embalado el {{ producto.fecha_embalado }} por {{ producto.operario }}</div>
                </div>
              </div>
              
              <div v-if="productosValidosParaContinuar.length > 0" class="text-center mb-4 text-sm text-gray-700">
                ¿Desea continuar con los productos válidos restantes?
              </div>
              <div v-else class="text-center mb-4 text-sm text-red-600">
                Todos los productos ya fueron embalados.
              </div>
              
              <div class="flex space-x-2">
                <button
                  @click="cerrarModalReimpresion"
                  class="flex-1 px-3 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-sm font-medium"
                >
                  Cancelar
                </button>
                
                <button
                  v-if="productosValidosParaContinuar.length > 0"
                  @click="continuarConEmbalados"
                  class="flex-1 px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-sm font-medium"
                >
                  Continuar
                </button>
                
                <button
                  @click="imprimirEtiquetasYaEmbalados"
                  class="flex-1 px-3 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition text-sm font-medium"
                >
                  Reimprimir
                </button>
              </div>
            </div>
          </div>

          <!-- STEP 1: Números de Serie -->
          <div v-if="currentStep === 1">
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-3">N° de serie (máximo 4)</label>
              <div class="space-y-3">
                <input
                  v-for="(serie, index) in numerosSerie"
                  :key="index"
                  :ref="index === 0 ? 'primerInput' : undefined"
                  v-model="numerosSerie[index]"
                  :name="`numero_serie_${index}`"
                  type="text"
                  :placeholder="`N° de serie ${index + 1}`"
                  class="w-full rounded-xl border border-gray-300 px-3 py-2.5
                         focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                         transition text-sm md:text-base"
                  @keyup.enter="manejarEnterEnSerie(index)"
                  :disabled="cargando"
                />
              </div>
            </div>

            <!-- Productos validados preview -->
            <div v-if="productosValidados.length > 0" class="mb-4">
              <div class="text-sm text-gray-600 mb-2">Productos encontrados:</div>
              <div class="space-y-1">
                <div v-for="producto in productosValidados" :key="producto.n_serie"
                     class="text-xs p-2 rounded"
                     :class="producto.valido ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                  <strong>{{ producto.n_serie }}</strong>
                  <span v-if="producto.producto"> - {{ producto.producto.modelo.nombre_modelo || producto.producto.modelo.modelo }}</span>
                  <span v-if="!producto.valido"> - {{ producto.mensaje }}</span>
                </div>
              </div>
            </div>

            <!-- Error de validación -->
            <div v-if="errorMsg"
                 class="mb-5 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
              {{ errorMsg }}
            </div>

            <!-- CTA -->
            <div class="text-center">
              <button
                class="w-full max-w-80 h-10 rounded-[20px] bg-sky-800 text-white font-medium
                       hover:bg-sky-900 disabled:opacity-50 disabled:cursor-not-allowed transition text-sm md:text-base"
                :disabled="!puedeValidar"
                @click="validarProductos"
              >
                {{ cargando ? 'Validando...' : 'Validar productos' }}
              </button>
            </div>
          </div>

          <!-- STEP 2: Operario -->
          <div v-else-if="currentStep === 2">
            <div class="mb-6">
              <div class="mb-4">
                <p class="text-sm text-gray-600">Productos a embalar:</p>
                <div class="space-y-1">
                  <div v-for="producto in productosValidados.filter(p => p.valido)" :key="producto.n_serie"
                       class="text-sm bg-gray-50 p-2 rounded">
                    <strong>{{ producto.n_serie }}</strong>
                    <span v-if="producto.producto"> - {{ producto.producto.modelo.nombre_modelo || producto.producto.modelo.modelo }}</span>
                  </div>
                </div>
              </div>
              
              <label class="block text-sm font-medium text-gray-700 mb-2">Operario (escanear o escribir)</label>
              <input
                v-model="nombreOperario"
                name="operario_nombre"
                type="text"
                placeholder="Escanee o escriba el nombre del operario"
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5
                       focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                       transition text-sm md:text-base"
                @input="buscarOperario"
                @keyup.enter="grabar"
              />
              
              <!-- Operario seleccionado -->
              <div v-if="selectedOperarioId" class="mt-2 text-sm text-green-600">
                ✓ Operario seleccionado: {{ nombreOperario }}
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
            <div class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded-lg text-sm font-medium text-gray-600">
              <span>OPERARIO</span>
              <span>CANTIDAD</span>
            </div>
            
            <div v-if="props.estadisticas.operarios.length === 0" 
                 class="text-center py-8 text-gray-500 text-sm">
              No hay embalados registrados hoy
            </div>
            
            <div v-else
                 v-for="operario in props.estadisticas.operarios" 
                 :key="operario.operario"
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
</template>

<style scoped>
select::-ms-expand { display: none; }
</style>