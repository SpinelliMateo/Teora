<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import type { Modelo, OrdenFabricacion } from '@/types'

const props = defineProps<{
  modelos: Modelo[]
  ordenesFabricacion: (OrdenFabricacion & { modelos: { pivot: { cantidad: number } }[] })[]
}>()

const ordenes = ref([...props.ordenesFabricacion])
const searchTerm = ref('')
const open_filtros = ref(false)
const showModal = ref(false)
const isSubmitting = ref(false)

// Filtros
const filtroNoOrden = ref('')
const filtroFechaDesde = ref('')
const filtroFechaHasta = ref('')

// Fechas mínimas para validación
const fechaHoy = ref('')
const fechaMinFinalizacion = ref('')

// Inicializar fechas
onMounted(() => {
  const today = new Date()
  fechaHoy.value = formatDate(today)
  form.fecha = fechaHoy.value

  // Inicializar fecha de finalización como fecha actual + 7 días
  const defaultEndDate = new Date()
  defaultEndDate.setDate(defaultEndDate.getDate() + 7)
  form.fecha_finalizacion = formatDate(defaultEndDate)
  fechaMinFinalizacion.value = form.fecha
})

// Formatea fecha como YYYY-MM-DD para inputs type="date"
function formatDate(date: Date): string {
  return date.toISOString().split('T')[0]
}

// Actualizar fecha mínima de finalización cuando cambia la fecha inicial
function updateFechaMinFinalizacion() {
  fechaMinFinalizacion.value = form.fecha

  // Si la fecha de finalización es anterior a la nueva fecha inicial, actualizarla
  if (form.fecha_finalizacion < form.fecha) {
    form.fecha_finalizacion = form.fecha
  }
}

const filteredData = computed(() => {
  return ordenes.value.filter(item => {
    // Filtro por número de orden
    const noOrdenMatch = !filtroNoOrden.value ||
      (item.no_orden && typeof item.no_orden === 'string' &&
        item.no_orden.toLowerCase().includes(filtroNoOrden.value.toLowerCase()));

    // Filtro por fecha desde
    const fechaDesdeMatch = !filtroFechaDesde.value ||
      (item.fecha && new Date(item.fecha) >= new Date(filtroFechaDesde.value));

    // Filtro por fecha hasta
    const fechaHastaMatch = !filtroFechaHasta.value ||
      (item.fecha_finalizacion && new Date(item.fecha_finalizacion) <= new Date(filtroFechaHasta.value));

    return noOrdenMatch && fechaDesdeMatch && fechaHastaMatch;
  });
})

// Usamos useForm solo para la estructura y validación, pero no para enviar
const form = useForm({
  fecha: '',
  fecha_finalizacion: '',
  no_orden: '',
  modelo_productos: [] as { modelo_id: string; cantidad: number; nombre?: string }[]
})

// Lista de modelos seleccionados para UI mejorada
const modeloSeleccionado = ref('')
const cantidadSeleccionada = ref(1)
const isOrderExpired = computed(() => {
  return (orden) => {
    if (orden.estado !== 'pendiente') return false;

    const today = new Date();
    const fechaFinalizacion = new Date(orden.fecha_finalizacion);

    // Comparar solo las fechas sin horas
    today.setHours(0, 0, 0, 0);
    fechaFinalizacion.setHours(0, 0, 0, 0);

    return fechaFinalizacion < today;
  };
});


function agregarModelo() {
  // Validar que se ha seleccionado un modelo
  if (!modeloSeleccionado.value) {
    alert('Por favor seleccione un modelo')
    return
  }

  // Verificar si el modelo ya está en la lista
  const modeloExistente = form.modelo_productos.find(item => item.modelo_id === modeloSeleccionado.value)

  if (modeloExistente) {
    // Si ya existe, aumentar su cantidad
    modeloExistente.cantidad += cantidadSeleccionada.value
  } else {
    // Si no existe, agregarlo a la lista
    const modeloInfo = props.modelos.find(modelo => modelo.id === Number(modeloSeleccionado.value))

    form.modelo_productos.push({
      modelo_id: modeloSeleccionado.value,
      cantidad: cantidadSeleccionada.value,
      nombre: modeloInfo ? `${modeloInfo.modelo} - ${modeloInfo.nombre_modelo}` : ''
    })
  }

  // Resetear selección
  modeloSeleccionado.value = ''
  cantidadSeleccionada.value = 1
}

function eliminarModelo(index: number) {
  form.modelo_productos.splice(index, 1)
}

function limpiarFiltros() {
  filtroNoOrden.value = ''
  filtroFechaDesde.value = ''
  filtroFechaHasta.value = ''
}

async function submitForm() {
  // Validar que haya al menos un modelo seleccionado
  if (form.modelo_productos.length === 0) {
    alert('Debe seleccionar al menos un modelo')
    return
  }

  try {
    isSubmitting.value = true
    // Usamos axios para enviar la solicitud
    const response = await axios.post('/ordenes-fabricacion', form.data())

    // Extraemos la orden de la respuesta
    const nuevaOrden = response.data.orden

    // Asegúrate de que nuevaOrden tenga todas las propiedades necesarias
    if (nuevaOrden) {
      // Asegurarnos de que modelos exista y sea un array
      if (!nuevaOrden.modelos) {
        nuevaOrden.modelos = []
      }

      // Asegurarnos de que tenga las propiedades para evitar errores de undefined
      nuevaOrden.no_orden = nuevaOrden.no_orden || ''
      nuevaOrden.fecha = nuevaOrden.fecha || ''
      nuevaOrden.fecha_finalizacion = nuevaOrden.fecha_finalizacion || ''

      // la insertamos al comienzo del arreglo reactivo
      ordenes.value.unshift(nuevaOrden)

    }

    // cerramos modal y reseteamos form
    showModal.value = false
    form.reset()
    form.modelo_productos = []
  } catch (error) {
    console.error('Error al enviar el formulario:', error)
    // Aquí manejo de errores de validación
    if (error.response && error.response.data && error.response.data.errors) {
      // Mostrar errores específicos
      alert('Error de validación: ' + Object.values(error.response.data.errors).flat().join('\n'))
    } else {
      alert('Hubo un error al guardar la orden')
    }
  } finally {
    isSubmitting.value = false
  }
}

function openModal() {
  showModal.value = true

  // Inicializar con la fecha de hoy
  const today = new Date()
  form.fecha = formatDate(today)

  // Fecha de finalización por defecto: +7 días
  const defaultEndDate = new Date()
  defaultEndDate.setDate(defaultEndDate.getDate() + 7)
  form.fecha_finalizacion = formatDate(defaultEndDate)

  // Limpiar modelos
  form.modelo_productos = []
}

function closeModal() {
  showModal.value = false
  form.reset()
  form.modelo_productos = []
}


</script>

<template>

  <Head title="Órdenes de fabricación" />

  <AppLayout>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 px-20" style="background-color: #F4F4F4;">
      <h1 class="text-[32px] font-bold text-gray-800 mt-8">Órdenes de fabricación</h1>

      <div class="flex justify-end items-center mb-8">
        <div class="flex gap-2">
          <div class="relative">
            <input type="text" placeholder="Buscar por Nº Orden"
              class="px-10 py-2 border rounded-full focus:outline-none text-black placeholder-blue-800 w-[200px]"
              style="border-color: #0D509C;" v-model="filtroNoOrden" />
            <span class="absolute left-3 top-3 text-gray-400">
              <!-- ícono búsqueda -->
              <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                <path
                  d="M6.5 13C4.68333 13 3.146 12.3707 1.888 11.112C0.63 9.85333 0.000667196 8.316 5.29101e-07 6.5C-0.000666138 4.684 0.628667 3.14667 1.888 1.888C3.14733 0.629333 4.68467 0 6.5 0C8.31533 0 9.853 0.629333 11.113 1.888C12.373 3.14667 13.002 4.684 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.81267 10.5627 9.688 9.688C10.5633 8.81333 11.0007 7.75067 11 6.5C10.9993 5.24933 10.562 4.187 9.688 3.313C8.814 2.439 7.75133 2.00133 6.5 2C5.24867 1.99867 4.18633 2.43633 3.313 3.313C2.43967 4.18967 2.002 5.252 2 6.5C1.998 7.748 2.43567 8.81067 3.313 9.688C4.19033 10.5653 5.25267 11.0027 6.5 11Z"
                  fill="#0D509C" />
              </svg>
            </span>
          </div>

          <button @click="open_filtros = !open_filtros"
            class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-full cursor-pointer"
            style="background-color: #0D509C;">
            <!-- ícono filtro -->
            <svg width="17" height="16" viewBox="0 0 17 16" fill="none">
              <path
                d="M7.02076 16C6.73743 16 6.49976 15.904 6.30776 15.712C6.11576 15.52 6.02009 15.2827 6.02076 15V9L0.220761 1.6C-0.0292387 1.26667 -0.0669053 0.916667 0.107761 0.55C0.282428 0.183334 0.586761 0 1.02076 0H15.0208C15.4541 0 15.7584 0.183334 15.9338 0.55C16.1091 0.916667 16.0714 1.26667 15.8208 1.6L10.0208 9V15C10.0208 15.2833 9.92476 15.521 9.73276 15.713C9.54076 15.905 9.30343 16.0007 9.02076 16H7.02076Z"
                fill="white" />
            </svg>
            Filtros
          </button>

          <button @click="openModal"
            class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-full cursor-pointer"
            style="background-color: #0D509C;">
            Añadir OF
          </button>
        </div>
      </div>

      <transition name="slide-fade" enter-active-class="transition-all duration-300 ease-in-out"
        enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-300 ease-in-out" leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2">
        <div v-if="open_filtros" class="text-black flex flex-col rounded-lg bg-white p-5 mb-10">
          <h2 class="font-semibold text-xl mb-5">Filtrar por</h2>
          <div class="w-full flex items-center gap-6 flex-wrap">
            <!-- Filtro por rango de fechas -->
            <div class="flex flex-col w-[200px] gap-2">
              <label class="text-gray-600">Fecha desde</label>
              <input type="date" v-model="filtroFechaDesde" class="border border-neutral-200 rounded-md py-2 px-2" />
            </div>

            <div class="flex flex-col w-[200px] gap-2">
              <label class="text-gray-600">Fecha hasta</label>
              <input type="date" v-model="filtroFechaHasta" class="border border-neutral-200 rounded-md py-2 px-2" />
            </div>

            <!-- Botón para limpiar filtros -->
            <div class="flex items-end h-full">
              <button @click="limpiarFiltros" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                Limpiar filtros
              </button>
            </div>
          </div>
        </div>
      </transition>

      <div class="overflow-x-auto">
        <table class="w-full bg-white overflow-hidden">
          <thead class="" style="background-color:  #E1E5E9;">
            <tr>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Fecha</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">N° de orden
              </th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Fecha de
                finalización</th>
              <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Cant.
                productos</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">Estado</th>
              <th class="py-3 px-4"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#D9D9D9]">
            <tr v-for="item in filteredData" :key="item.id"
              :class="isOrderExpired(item) ? 'bg-[#FAE8E8]' : 'bg-white'">
              <td class="py-3 px-4 text-sm text-gray-800">{{ item.fecha }}</td>
              <td class="py-3 px-4 text-sm text-gray-800">{{ item.no_orden }}</td>
              <td class="py-3 px-4 text-sm text-gray-800">{{ item.fecha_finalizacion }}</td>
              <td class="py-3 px-4 text-sm text-center text-gray-800">
                {{ item.total_modelos }}
              </td>
              <td class="py-3 px-4 text-sm text-gray-800">{{ item.estado.charAt(0).toUpperCase() + item.estado.slice(1)
              }}</td>
              <td class="py-3 px-4 text-center">
                <Link :href="`/ordenes-fabricacion/${item.id}/edit`"
                  class="inline-block p-2 rounded-full hover:bg-gray-100">
                <svg width="8" height="14" viewBox="0 0 8 14" fill="none">
                  <path d="M1 1l6 6-6 6" stroke="#333" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
                </svg>
                </Link>
              </td>
            </tr>
            <tr v-if="filteredData.length === 0">
              <td colspan="5" class="py-4 text-center text-gray-500">No se encontraron resultados</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal para crear nueva orden de fabricación -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Fondo semi-transparente sutil -->
      <div class="absolute inset-0 bg-black/80 bg-opacity-25" @click="closeModal"></div>

      <!-- Contenido del modal -->
      <div class="relative bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-semibold text-gray-800">Nueva Orden de Fabricación</h3>
          <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitForm">
          <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
              <label for="fecha" class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
              <input type="date" id="fecha" v-model="form.fecha"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :min="fechaHoy" @change="updateFechaMinFinalizacion" required />
            </div>

            <div>
              <label for="fecha_finalizacion" class="block text-sm font-medium text-gray-700 mb-1">Fecha de
                finalización</label>
              <input type="date" id="fecha_finalizacion" v-model="form.fecha_finalizacion"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :min="fechaMinFinalizacion" required />
            </div>
          </div>

          <div class="mb-6">
            <label for="no_orden" class="block text-sm font-medium text-gray-700 mb-1">Número de orden</label>
            <input type="text" id="no_orden" v-model="form.no_orden"
              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              required />
          </div>

          <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
              <h4 class="text-md font-medium text-gray-800">Modelos y cantidades</h4>
            </div>

            <!-- Selector de modelos mejorado -->
            <div class="bg-gray-50 p-4 rounded-lg mb-4 border border-gray-200">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                <div class="col-span-2">
                  <label for="modelo" class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
                  <select id="modelo" v-model="modeloSeleccionado"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" disabled selected>Seleccionar modelo</option>
                    <option v-for="m in modelos" :key="m.id" :value="m.id">{{ m.modelo }} - {{ m.nombre_modelo }}
                    </option>
                  </select>
                </div>

                <div>
                  <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
                  <div class="flex">
                    <input type="number" id="cantidad" v-model="cantidadSeleccionada"
                      class="w-full border border-gray-300 rounded-l-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      min="1" />
                    <button type="button" @click="agregarModelo"
                      class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700"
                      style="background-color: #0D509C;">
                      Agregar
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Lista de modelos seleccionados -->
            <div class="bg-white rounded-lg border border-gray-200">
              <div class="p-3 bg-gray-50 border-b border-gray-200 rounded-t-lg">
                <h5 class="font-medium">Modelos seleccionados</h5>
              </div>

              <div class="p-0">
                <div v-if="form.modelo_productos.length === 0" class="p-4 text-center text-gray-500">
                  No hay modelos seleccionados
                </div>

                <ul v-else class="divide-y divide-gray-200">
                  <li v-for="(item, index) in form.modelo_productos" :key="index"
                    class="flex justify-between items-center p-3">
                    <div class="flex items-center">
                      <span class="font-medium mr-2">{{ item.nombre }}</span>
                      <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                        {{ item.cantidad }} unidades
                      </span>
                    </div>

                    <button type="button" @click="eliminarModelo(index)" class="text-red-500 hover:text-red-700">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                      </svg>
                    </button>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Mensaje de validación para modelos -->
            <div v-if="form.modelo_productos.length === 0" class="mt-2 text-sm text-red-500">
              Debe seleccionar al menos un modelo
            </div>
          </div>

          <div class="flex justify-end space-x-3">
            <button type="button" @click="closeModal"
              class="border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50">
              Cancelar
            </button>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
              style="background-color: #0D509C;" :disabled="isSubmitting || form.modelo_productos.length === 0">
              {{ isSubmitting ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>