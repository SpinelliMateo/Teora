<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { ref, nextTick, onMounted } from 'vue'

const numeroSerie = ref('')
const cargando = ref(false)
const inputRef = ref<HTMLInputElement>()
const message = ref<{ text: any; success: boolean } | null>(null)
const timeoutId = ref<number | null>(null)

const page = usePage()

function puedeGrabar() {
  return numeroSerie.value.trim() !== '' && !cargando.value
}

function limpiarMensajeDespues(delay = 5000) {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value)
  }

  timeoutId.value = setTimeout(() => {
    message.value = null
    timeoutId.value = null
  }, delay)
}

function grabar() {
  if (!puedeGrabar()) return

  router.post('/sectores/operarios/inyectado',
    {
      numero_serie: numeroSerie.value.trim()
    },
    {
      onStart: () => { cargando.value = true },
      onSuccess: (page) => {
        console.log(page.props.flash);

        const flash = page.props.flash as { message?: string }
        message.value = { text: flash.message || '', success: true }
        numeroSerie.value = ''
        nextTick(() => {
          inputRef.value?.focus()
        })
      },
      onError: (errors) => {
        const errorMessage = Object.values(errors)[0] || 'Error desconocido'
        message.value = { text: errorMessage, success: false }
        numeroSerie.value = ''
        nextTick(() => {
          inputRef.value?.focus()
        })
      },
      onFinish: () => { cargando.value = false, limpiarMensajeDespues() }
    }
  )
}

function handleKeyPress(event: KeyboardEvent) {
  if (event.key === 'Enter' && puedeGrabar()) {
    grabar()
  }
}

onMounted(() => {
  inputRef.value?.focus()
})
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
        <!-- ...existing code... -->
        <div v-if="message" class="mb-6 p-3 rounded-lg text-sm"
          :class="message.success ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
          {{ message.text }}
        </div>
        <!-- ...existing code... -->

        <!-- Input Número de Serie -->
        <div class=" mb-6 md:mb-8">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            N° de serie
          </label>
          <input ref="inputRef" v-model="numeroSerie" type="text" placeholder="1234" class="w-full rounded-xl border border-gray-300 px-3 py-2.5
                   focus:border-sky-800 focus:ring-2 focus:ring-sky-200
                   transition text-sm md:text-base" @keypress="handleKeyPress" autofocus />
        </div>

      </div>
    </div>
  </div>
</template>