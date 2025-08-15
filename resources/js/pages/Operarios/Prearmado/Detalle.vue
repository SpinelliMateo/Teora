<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="flex items-center justify-between px-4 md:px-6 py-4">
        <div class="flex items-center space-x-2 md:space-x-4">
          <button 
            @click="volver" 
            class="text-gray-600 hover:text-gray-900 flex items-center"
          >
            <svg class="w-5 h-5 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <h1 class="text-lg md:text-xl font-bold text-gray-900 truncate">
            <span class="hidden sm:inline">Etiqueta N° serie</span>
            <span class="sm:hidden">Etiqueta</span>
            {{ controlStock.n_serie }}
          </h1>
        </div>
        
        <button 
          @click="imprimirEtiqueta"
          class="bg-blue-600 text-white px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium hover:bg-blue-700 transition whitespace-nowrap"
        >
          <span class="hidden sm:inline">Imprimir etiqueta</span>
          <span class="sm:hidden">Imprimir</span>
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto p-4 md:p-6">
      <div class="bg-white rounded-lg shadow-lg p-4 md:p-8">
        <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-4 md:mb-6">Datos generales</h2>

        <!-- Datos en grid responsive -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 md:mb-8">
          <!-- Fecha -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Fecha</label>
            <div class="flex items-center">
              <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z"/>
              </svg>
              <input 
                type="text" 
                :value="formatearFecha(controlStock.fecha_prearmado)"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
                readonly
              />
            </div>
          </div>

          <!-- N° de serie -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">N° de serie</label>
            <input 
              type="text" 
              :value="controlStock.n_serie"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>

          <!-- Modelo -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Modelo</label>
            <input 
              type="text" 
              :value="controlStock.modelo?.nombre_modelo || 'Sin modelo'"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>

          <!-- Tensión -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Tensión</label>
            <input 
              type="text" 
              :value="controlStock.modelo?.tension || 'N/A'"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>

          <!-- Frecuencia -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Frecuencia</label>
            <input 
              type="text" 
              :value="controlStock.modelo?.frecuencia || 'N/A'"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>

          <!-- Corriente -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Corriente</label>
            <input 
              type="text" 
              :value="controlStock.modelo?.corriente || 'N/A'"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>

          <!-- Clase aislación -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Clase aislación</label>
            <input 
              type="text" 
              :value="controlStock.modelo?.aislacion || 'N/A'"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>

          <!-- Sistema -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Sistema</label>
            <input 
              type="text" 
              :value="controlStock.modelo?.sistema || 'N/A'"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>

          <!-- Vol. Bruto -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Vol. Bruto</label>
            <input 
              type="text" 
              :value="controlStock.modelo?.volumen || 'N/A'"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>

          <!-- Agente espum. -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Agente espum.</label>
            <input 
              type="text" 
              :value="controlStock.modelo?.espumante || 'N/A'"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>

          <!-- Clase clim. -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Clase clim.</label>
            <input 
              type="text" 
              :value="controlStock.modelo?.clase || 'N/A'"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>

          <!-- Refrigerante -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Refrigerante</label>
            <input 
              type="text" 
              :value="controlStock.modelo?.gas || 'N/A'"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>

          <!-- Cant. refrig -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-2">Cant. refrig</label>
            <input 
              type="text" 
              :value="controlStock.modelo?.cantidad_gas || 'N/A'"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 text-sm" 
              readonly
            />
          </div>
        </div>

        <!-- Códigos de barras -->
        <div class="border-t pt-4 md:pt-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-sm font-medium text-gray-600">Códigos de barras</h3>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8">
            <!-- Código de barras N° Serie -->
            <div class="text-center bg-white border-2 border-gray-200 p-4 md:p-6 rounded-lg">
              <h4 class="text-sm font-medium text-gray-700 mb-3">N° de Serie</h4>
              <div class="mb-4 flex justify-center">
                <img 
                  :src="barcodeSerieUrl" 
                  :alt="`Código de barras serie ${controlStock.codigo_barras}`"
                  class="max-w-full h-12 md:h-16"
                  @error="handleImageError"
                  @load="handleImageLoad"
                />
                
                <div v-if="imageError" class="border-2 border-dashed border-gray-300 p-4 rounded bg-gray-50">
                  <p class="text-xs md:text-sm text-gray-500 mb-2">Código de barras</p>
                  <p class="font-mono text-sm md:text-lg font-bold break-all">{{ controlStock.n_serie || 'Sin código' }}</p>
                </div>
              </div>
              
              <div class="text-xs md:text-sm text-gray-800 font-mono font-bold tracking-wider break-all">
                {{ controlStock.n_serie || 'Sin código' }}
              </div>
            </div>

            <!-- Código de barras Modelo -->
            <div class="text-center bg-white border-2 border-gray-200 p-4 md:p-6 rounded-lg">
              <h4 class="text-sm font-medium text-gray-700 mb-3">Modelo</h4>
              <div class="mb-4 flex justify-center">
                <img 
                  :src="barcodeModeloUrl" 
                  :alt="`Código de barras modelo ${getModeloCode()}`"
                  class="max-w-full h-12 md:h-16"
                  @error="handleModeloImageError"
                  @load="handleModeloImageLoad"
                />
                
                <div v-if="modeloImageError" class="border-2 border-dashed border-gray-300 p-4 rounded bg-gray-50">
                  <p class="text-xs md:text-sm text-gray-500 mb-2">Código de barras</p>
                  <p class="font-mono text-sm md:text-lg font-bold break-all">{{ getModeloCode() }}</p>
                </div>
              </div>
              
              <div class="text-xs md:text-sm text-gray-800 font-mono font-bold tracking-wider break-all">
                {{ getModeloCode() }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export default {
  props: {
    controlStock: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const imageError = ref(false)
    const imageLoaded = ref(false)
    const modeloImageError = ref(false)
    const modeloImageLoaded = ref(false)

    const formatearFecha = (fecha) => {
      if (!fecha) return 'Sin fecha'
      const date = new Date(fecha)
      return date.toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    }

    const getModeloCode = () => {
      if (!props.controlStock.modelo) return 'Sin modelo'
      return props.controlStock.modelo.modelo || 
             props.controlStock.modelo.codigo_modelo || 
             props.controlStock.modelo.nombre_modelo || 
             'Sin código'
    }

    const volver = () => {
      router.get(route('sectores.operarios.sector.prearmado'))
    }

    const imprimirEtiqueta = () => {
      router.get(route('sectores.operarios.sector.prearmado.etiqueta', props.controlStock.id))
    }

    const barcodeSerieUrl = computed(() => {
      return `/barcode/generate/${props.controlStock.id}`
    })

    const barcodeModeloUrl = computed(() => {
      return `/barcode/generate/modelo/${props.controlStock.id}`
    })

    const handleImageError = () => {
      imageError.value = true
      console.error('Error cargando imagen del código de barras de serie')
    }

    const handleImageLoad = () => {
      imageLoaded.value = true
      imageError.value = false
    }

    const handleModeloImageError = () => {
      modeloImageError.value = true
      console.error('Error cargando imagen del código de barras de modelo')
    }

    const handleModeloImageLoad = () => {
      modeloImageLoaded.value = true
      modeloImageError.value = false
    }

    return {
      formatearFecha,
      getModeloCode,
      volver,
      imprimirEtiqueta,
      barcodeSerieUrl,
      barcodeModeloUrl,
      imageError,
      imageLoaded,
      modeloImageError,
      modeloImageLoaded,
      handleImageError,
      handleImageLoad,
      handleModeloImageError,
      handleModeloImageLoad
    }
  }
}
</script>

<style scoped>
@media print {
  .bg-gray-50 {
    background: white !important;
  }
  
  .shadow-lg {
    box-shadow: none !important;
  }
  
  button {
    display: none !important;
  }
  
  .border-2 {
    border: 1px solid #000 !important;
  }
  
  .text-gray-600,
  .text-gray-800,
  .text-gray-900 {
    color: black !important;
  }
}
</style>