<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref, computed, nextTick } from 'vue'

const numeroSerie = ref('')
const cargando = ref(false)
const inputRef = ref<HTMLInputElement>()

const page = usePage()
const flashMessage = computed(() => page.props.flash as any)

function puedeGrabar() {
  return numeroSerie.value.trim() !== '' && !cargando.value
}

function grabar() {
  if (!puedeGrabar()) return
  
  router.post('/sectores/operarios/inyectado', 
    {
      numero_serie: numeroSerie.value.trim()
    },
    {
      onStart: () => { cargando.value = true },
      onSuccess: () => { 
        numeroSerie.value = ''
        // Enfocar el input después de limpiar
        nextTick(() => {
          inputRef.value?.focus()
        })
      },
      onError: () => {},
      onFinish: () => { cargando.value = false }
    }
  )
}

function handleKeyPress(event: KeyboardEvent) {
  if (event.key === 'Enter' && puedeGrabar()) {
    grabar()
  }
}
</script>

<template>
  <Head title="Inyectado" />
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 py-6 md:py-10">
      <!-- Header -->
      <div class="mb-6 md:mb-8 text-center">
        <h1 class="text-lg font-semibold text-gray-800">Inyectado</h1>
      </div>

      <!-- Main Card -->
      <div class="bg-white rounded-2xl shadow-lg p-4 md:p-6 lg:p-8">
        <!-- Mensaje de éxito/error -->
        <div v-if="flashMessage?.message" 
             class="mb-6 p-3 rounded-lg text-sm"
             :class="flashMessage.success 
               ? 'bg-green-50 text-green-700 border border-green-200' 
               : 'bg-red-50 text-red-700 border border-red-200'">
          {{ flashMessage.message }}
        </div>

        <!-- Input Número de Serie -->
        <div class="mb-6 md:mb-8">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            N° de serie
          </label>
          <input
            ref="inputRef"
            v-model="numeroSerie"
            type="text"
            placeholder="1234"
            class="w-full rounded-xl border border-gray-300 px-3 py-2.5
                   focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                   transition text-sm md:text-base"
            @keypress="handleKeyPress"
            autofocus
          />
        </div>

        <!-- Botón Grabar -->
        <div class="text-center">
          <button
            class="w-full max-w-80 h-10 rounded-[20px] bg-sky-800 text-white font-medium
                   hover:bg-sky-900 disabled:opacity-50 disabled:cursor-not-allowed 
                   transition text-sm md:text-base"
            :disabled="!puedeGrabar()"
            @click="grabar"
          >
            {{ cargando ? 'Procesando...' : 'Grabar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>