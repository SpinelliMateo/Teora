<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import axios from 'axios'

type Operario = { id: number; nombre: string }
type Modelo = {
  id: number
  nombre?: string
  descripcion?: string
  codigo?: string
  orden_fabricacion_id?: number
  orden?: { id: number; codigo?: string }
}

const props = defineProps<{ prearmadores: Operario[] }>()

const currentStep = ref(1)
const selectedOperarioId = ref<number | null>(null)
const selectedModeloId = ref<number | null>(null)
const modelos = ref<Modelo[]>([])
const cargando = ref(false)
const errorMsg = ref<string | null>(null)

const nombreOperario = computed(() => {
  const op = props.prearmadores.find(o => o.id === selectedOperarioId.value)
  return op?.nombre ?? ''
})

const selectedModelo = computed(() =>
  modelos.value.find(m => m.id === selectedModeloId.value)
)

function displayNombreModelo(m: Modelo) {
  return m.nombre || m.descripcion || m.codigo || `Modelo #${m.id}`
}

async function cargarModelos(operarioId: number) {
  cargando.value = true
  errorMsg.value = null
  modelos.value = []
  selectedModeloId.value = null
  try {
    const { data } = await axios.get(`/sectores/operarios/prearmado/${operarioId}/modelos`)
    modelos.value = Array.isArray(data?.modelos) ? data.modelos : []
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || 'No se pudieron cargar los modelos.'
  } finally {
    cargando.value = false
  }
}

watch(selectedOperarioId, (val) => { if (val) cargarModelos(val) })

function puedeGrabar() {
  return !!selectedOperarioId.value && !!selectedModeloId.value && !cargando.value
}

function grabar() {
  if (!puedeGrabar()) return
  currentStep.value = 2
}

function cancelar() {
  currentStep.value = 1
  selectedOperarioId.value = null
  selectedModeloId.value = null
  modelos.value = []
  errorMsg.value = null
}

function continuar() {
  if (!selectedOperarioId.value || !selectedModeloId.value) return
  router.post('/sectores/operarios/prearmado', 
    {
      operario_id: selectedOperarioId.value,
      modelos: [selectedModeloId.value],
    },
    {
      onStart: () => { cargando.value = true; errorMsg.value = null },
      onSuccess: () => { cancelar() },
      onError: (errors) => { errorMsg.value = (errors as any)?.message || 'No se pudo prearmar el modelo.' },
      onFinish: () => { cargando.value = false }
    }
  )
}

/** —— UI helpers —— **/
const pageBgClass = computed(() =>
  currentStep.value === 1 ? 'bg-gray-50' : 'bg-pink-50'
)
const stepCircleClass = (n: number) =>
  currentStep.value >= n
    ? 'bg-sky-800 text-white shadow'
    : 'bg-gray-300 text-gray-600'
</script>

<template>
  <Head title="Prearmado" />
  <div class="min-h-screen transition-colors duration-200" :class="pageBgClass">
    <div class="max-w-2xl mx-auto px-4 py-6 md:py-10">
      <!-- Header responsive -->
      <div class="mb-6 md:mb-8">
        <!-- Móvil: título centrado arriba, pasos abajo -->
        <div class="block md:hidden text-center">
          <h1 class="text-lg font-semibold text-gray-800 mb-4">Prearmado</h1>
          <div class="flex items-center justify-center space-x-4">
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
                 :class="stepCircleClass(1)">1</div>
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
                 :class="stepCircleClass(2)">2</div>
          </div>
        </div>
        
        <!-- Desktop: layout original con grid -->
        <div class="hidden md:grid grid-cols-3 items-center">
          <h1 class="text-lg font-semibold text-gray-800 justify-self-start">Prearmado</h1>
          <div class="justify-self-center flex items-center space-x-4">
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
                 :class="stepCircleClass(1)">1</div>
            <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-semibold"
                 :class="stepCircleClass(2)">2</div>
          </div>
          <div></div>
        </div>
      </div>

      <!-- STEP 1 -->
      <div v-if="currentStep === 1"
           class="bg-white rounded-2xl shadow-lg p-4 md:p-6 lg:p-8">
        <!-- Operario -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Operario</label>
          <div class="relative">
            <select
              v-model.number="selectedOperarioId"
              class="w-full rounded-xl border border-gray-300 pr-10 pl-3 py-2.5
                     focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                     transition bg-white appearance-none text-sm md:text-base"
            >
              <option :value="null" disabled>Seleccionar operario...</option>
              <option v-for="op in props.prearmadores" :key="op.id" :value="op.id">
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

        <!-- Modelo -->
        <div class="mb-6 md:mb-8" v-if="selectedOperarioId">
          <label class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
          <div class="relative">
            <select
              v-model.number="selectedModeloId"
              :disabled="cargando"
              class="w-full rounded-xl border border-gray-300 pr-10 pl-3 py-2.5
                     focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                     transition bg-white disabled:bg-gray-100 disabled:text-gray-500
                     appearance-none text-sm md:text-base"
            >
              <option :value="null" disabled>
                {{ cargando ? 'Cargando modelos...' : 'Seleccionar modelo...' }}
              </option>
              <option v-for="m in modelos" :key="m.id" :value="m.id">
                {{ displayNombreModelo(m) }}
              </option>
            </select>
            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500"
                 viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z"/>
            </svg>
          </div>
        </div>

        <!-- Error -->
        <div v-if="errorMsg"
             class="mb-5 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
          {{ errorMsg }}
        </div>

        <!-- CTA responsive -->
        <div class="text-center">
          <button
            class="w-full max-w-80 h-10 rounded-[20px] bg-sky-800 text-white font-medium
                   hover:bg-sky-900 disabled:opacity-50 disabled:cursor-not-allowed transition text-sm md:text-base"
            :disabled="!puedeGrabar()"
            @click="grabar"
          >
            Grabar
          </button>
        </div>

        <div v-if="!selectedOperarioId" class="mt-4 p-4 text-gray-500 text-center text-sm">
          Selecciona un operario para ver sus modelos disponibles.
        </div>
      </div>

      <!-- STEP 2 -->
      <div v-else class="bg-white rounded-2xl shadow-lg px-4 md:px-6 py-8 md:py-10 text-center">
        <h2 class="text-base font-medium text-gray-700 mb-1">Modelo</h2>
        <div class="text-2xl md:text-4xl font-extrabold tracking-tight text-gray-900 mb-8 md:mb-10 break-words">
          {{ selectedModelo ? displayNombreModelo(selectedModelo) : '' }}
        </div>

        <div v-if="errorMsg"
             class="mb-6 p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
          {{ errorMsg }}
        </div>

        <div class="flex flex-col space-y-4 md:space-y-0 md:flex-row md:justify-center md:gap-4">
          <!-- Botón Cancelar: transparente + texto sky -->
          <button
            class="w-full md:w-80 h-10 rounded-[20px] bg-transparent text-sky-800 font-medium
                   border border-sky-800 hover:bg-sky-50 transition text-sm md:text-base"
            @click="cancelar"
            :disabled="cargando"
          >
            Cancelar
          </button>

          <button
            class="w-full md:w-80 h-10 rounded-[20px] bg-sky-800 text-white font-medium
                   hover:bg-sky-900 disabled:opacity-50 transition text-sm md:text-base"
            @click="continuar"
            :disabled="cargando"
          >
            {{ cargando ? 'Procesando...' : 'Continuar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* oculta caret heredado en navegadores legacy */
select::-ms-expand { display: none; }
/* Mantengo el CSS mínimo: todo el estilo está en Tailwind */
</style>