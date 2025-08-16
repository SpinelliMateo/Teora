<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

type Producto = {
  id: number
  n_serie: string
  modelo: { modelo_nombre: string }
  fecha_embalado: string
  fecha_embalado_iso: string
  qr_code: string
}

const props = defineProps<{
  productos: Producto[]
  fecha_proceso: string
  hora_proceso: string
}>()

const imprimiendo = ref<{ [key: number]: boolean }>({})

function imprimirEtiqueta(producto: Producto) { /* ——— TU FUNCIÓN ORIGINAL SIN CAMBIOS ——— */ 
  imprimiendo.value[producto.id] = true
  const etiquetaHTML = `
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Etiqueta - ${producto.n_serie}</title>
      <style>
        body{margin:0;padding:20px;font-family:Arial,sans-serif;background:#fff}
        .etiqueta{width:300px;border:2px solid #000;background:#fff;margin:0 auto}
        .fila{display:flex;border-bottom:1px solid #000}
        .fila:last-of-type{border-bottom:none}
        .label{background:#f0f0f0;padding:8px 12px;font-weight:bold;font-size:12px;border-right:1px solid #000;width:120px;box-sizing:border-box}
        .valor{padding:8px 12px;font-size:12px;flex:1;font-weight:bold}
        .codigo-barras{text-align:center;padding:15px;border-top:1px solid #000}
        .codigo-barras img{max-width:250px;height:60px;margin-bottom:5px}
        .codigo-texto{font-family:'Courier New',monospace;font-size:11px;font-weight:bold;letter-spacing:1px}
        @media print{body{margin:0;padding:0}.etiqueta{margin:0}}
      </style>
    </head>
    <body>
      <div class="etiqueta">
        <div class="fila"><div class="label">Ing. a depósito:</div><div class="valor">${producto.fecha_embalado}</div></div>
        <div class="fila"><div class="label">Modelo:</div><div class="valor">${producto.modelo.modelo_nombre}</div></div>
        <div class="fila"><div class="label">Serie:</div><div class="valor">${producto.n_serie}</div></div>
        <div class="codigo-barras">
          <img src="/barcode/generate/${producto.id}" alt="Código de barras ${producto.n_serie}" />
          <div class="codigo-texto">${producto.n_serie}</div>
        </div>
      </div>
    </body>
    </html>
  `
  const ventanaImpresion = window.open('', '_blank', 'width=400,height=500,menubar=no,toolbar=no,location=no,status=no,scrollbars=no,resizable=no')
  if (ventanaImpresion) {
    ventanaImpresion.document.write(etiquetaHTML)
    ventanaImpresion.document.close()
    setTimeout(() => {
      ventanaImpresion.print()
      ventanaImpresion.onafterprint = () => { ventanaImpresion.close(); imprimiendo.value[producto.id] = false }
      setTimeout(() => { if (ventanaImpresion && !ventanaImpresion.closed) ventanaImpresion.close(); imprimiendo.value[producto.id] = false }, 3000)
    }, 1000)
  } else {
    alert('No se pudo abrir la ventana de impresión. Verifique que no esté bloqueada por el navegador.')
    imprimiendo.value[producto.id] = false
  }
}

function imprimirTodasLasEtiquetas() {
  props.productos.forEach((producto, index) => {
    setTimeout(() => imprimirEtiqueta(producto), index * 1500)
  })
}

function volverAEmbalado() {
  router.visit(route('sectores.operarios.embalado.index'))
}

function generarQRDataURL(productoId: number): string {
  return `/barcode/generate/${productoId}`
}
</script>

<template>
  <Head title="Etiquetas" />

  <div class="min-h-screen bg-gray-100 p-4">
    <div class="max-w-4xl mx-auto">
      <!-- Header con botón de volver -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <button
            @click="volverAEmbalado"
            class="flex items-center gap-2 text-gray-600 hover:text-gray-800 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span class="text-lg font-medium">Etiquetas</span>
          </button>
        </div>
        
        <button
          @click="imprimirTodasLasEtiquetas"
          class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors font-medium"
        >
          Imprimir etiquetas
        </button>
      </div>

      <!-- Lista de productos -->
      <div class="space-y-4">
        <div
          v-for="producto in productos"
          :key="producto.id"
          class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm"
        >
          <!-- Header de la tarjeta con título y botón -->
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Datos generales</h3>
            <button
              @click="imprimirEtiqueta(producto)"
              :disabled="imprimiendo[producto.id]"
              class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ imprimiendo[producto.id] ? 'Imprimiendo…' : 'Imprimir etiqueta' }}
            </button>
          </div>

          <!-- Campos en grid de 3 columnas -->
          <div class="grid grid-cols-3 gap-4 mb-6">
            <!-- Ingreso a depósito -->
            <div>
              <label class="block text-xs text-gray-500 mb-2">Ingreso a depósito</label>
              <div class="relative">
                <div class="flex items-center border border-gray-300 rounded bg-gray-50 px-3 py-2">
                  <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />
                  </svg>
                  <span class="text-sm text-gray-900">{{ producto.fecha_embalado }}</span>
                  <svg class="w-4 h-4 text-gray-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- N° de serie -->
            <div>
              <label class="block text-xs text-gray-500 mb-2">N° de serie</label>
              <div class="border border-gray-300 rounded bg-white px-3 py-2">
                <span class="text-sm font-medium text-gray-900">{{ producto.n_serie }}</span>
              </div>
            </div>

            <!-- Modelo -->
            <div>
              <label class="block text-xs text-gray-500 mb-2">Modelo</label>
              <div class="border border-gray-300 rounded bg-white px-3 py-2">
                <span class="text-sm text-gray-900">{{ producto.modelo.modelo_nombre }}</span>
              </div>
            </div>
          </div>

          <!-- Código de barras -->
          <div class="bg-gray-50 rounded-lg p-4 inline-block">
            <img
              :src="generarQRDataURL(producto.id)"
              :alt="`Código de barras ${producto.n_serie}`"
              class="h-16 w-auto mx-auto block mb-2"
              @error="$event.target.style.display='none'"
            />
            <div class="text-center text-xs font-medium text-gray-700 tracking-wide">
              {{ producto.n_serie }}
            </div>
          </div>
        </div>
      </div>

      <!-- Estado vacío -->
      <div v-if="productos.length === 0" class="text-center py-16">
        <div class="bg-white rounded-lg border border-gray-200 p-8 inline-block shadow-sm">
          <h3 class="text-lg font-medium text-gray-900 mb-2">No hay productos para etiquetar</h3>
          <p class="text-gray-600 mb-4">No se encontraron productos embalados para generar etiquetas.</p>
          <button
            @click="volverAEmbalado"
            class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
          >
            Volver a Embalado
          </button>
        </div>
      </div>
    </div>
  </div>
</template>