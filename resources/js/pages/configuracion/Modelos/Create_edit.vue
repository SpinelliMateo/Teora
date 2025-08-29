<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { ref, reactive, onMounted } from 'vue';

const { success, error } = useToast();

interface Modelo {
    id?: number;
    modelo: string;
    nombre_modelo: string;
    tension: string;
    frecuencia: string;
    corriente: string;
    potencia: string;
    aislacion: string;
    sistema: string;
    volumen: string;
    espumante: string;
    clase: string;
    gas: string;
    cantidad_gas: string;
}

const props = defineProps<{
    modelo?: Modelo;
    isEdit?: boolean;
}>();

const loading = ref(false);

const formData = reactive({
    modelo: '',
    nombre_modelo: '',
    tension: '',
    frecuencia: '',
    corriente: '',
    potencia: '',
    aislacion: '',
    sistema: '',
    volumen: '',
    espumante: '',
    clase: '',
    gas: '',
    cantidad_gas: '',
});

const resetForm = () => {
    formData.modelo = '';
    formData.nombre_modelo = '';
    formData.tension = '';
    formData.frecuencia = '';
    formData.corriente = '';
    formData.potencia = '';
    formData.aislacion = '';
    formData.sistema = '';
    formData.volumen = '';
    formData.espumante = '';
    formData.clase = '';
    formData.gas = '';
    formData.cantidad_gas = '';
};

onMounted(() => {
    if (props.isEdit && props.modelo) {
        formData.modelo = props.modelo.modelo;
        formData.nombre_modelo = props.modelo.nombre_modelo;
        formData.tension = props.modelo.tension;
        formData.frecuencia = props.modelo.frecuencia;
        formData.corriente = props.modelo.corriente;
        formData.potencia = props.modelo.potencia;
        formData.aislacion = props.modelo.aislacion;
        formData.sistema = props.modelo.sistema;
        formData.volumen = props.modelo.volumen;
        formData.espumante = props.modelo.espumante;
        formData.clase = props.modelo.clase;
        formData.gas = props.modelo.gas;
        formData.cantidad_gas = props.modelo.cantidad_gas;
    }
});

const guardarModelo = () => {
    loading.value = true;

    const data = { ...formData };

    if (props.isEdit && props.modelo) {
        router.put(route('modelos.update', props.modelo.id), data, {
            onError: (errors) => {
                loading.value = false;
                console.error('Errores de validación:', errors);

                if (errors.error) {
                    error(errors.error);
                } else {
                    const firstError = Object.values(errors)[0];
                    if (firstError) {
                        error(Array.isArray(firstError) ? firstError[0] : firstError);
                    } else {
                        error('Error al editar el modelo. Verifica que todos los campos estén completos.');
                    }
                }
            },
            onSuccess: (page) => {
                loading.value = false;
                const mensaje = (page.props.flash as any)?.success || 'Modelo editado correctamente';
                success(mensaje);
                router.get(route('modelos'));
            }
        });
    } else {
        router.post(route('modelos.store'), data, {
            onError: (errors) => {
                loading.value = false;
                console.error('Errores de validación:', errors);

                if (errors.error) {
                    error(errors.error);
                } else {
                    const firstError = Object.values(errors)[0];
                    if (firstError) {
                        error(Array.isArray(firstError) ? firstError[0] : firstError);
                    } else {
                        error('Error al crear el modelo. Verifica que todos los campos estén completos.');
                    }
                }
            },
            onSuccess: (page) => {
                loading.value = false;
                const mensaje = (page.props.flash as any)?.success || 'Modelo creado correctamente';
                success(mensaje);
                router.get(route('modelos'));
            }
        });
    }
};

const cancelar = () => {
    router.get(route('modelos'));
};
</script>

<template>
  <Head :title="isEdit ? 'Editar Modelo' : 'Crear Modelo'" />

  <AppLayout>
    <!-- fondo gris claro como en la referencia -->
    <div class="min-h-screen w-full bg-[#F4F4F4]">
      <div class="mx-auto max-w-[1180px] px-4 sm:px-6 lg:px-8 py-6 lg:py-10">

        <!-- Header con back + título del modelo -->
        <div class="flex items-center gap-3 mb-6">
          <button
            class="cursor-pointer inline-flex h-9 w-9 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-gray-200 hover:bg-gray-50"
            @click="cancelar"
            aria-label="Volver"
          >
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none">
              <path d="M10 20L0 10L10 0L11.7812 1.75L4.78125 8.75H20V11.25H4.78125L11.7812 18.25L10 20Z"
                    fill="#626262"/>
            </svg>
          </button>

          <h1 class="text-[28px] md:text-[32px] font-bold leading-tight text-gray-800">
            {{ formData.nombre_modelo || 'TEV500' }}
          </h1>
        </div>

        <!-- Card principal -->
        <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 p-6 md:p-8">
          <h2 class="text-lg font-semibold text-gray-900 mb-5">Datos generales</h2>

          <!-- Grillas: 4 columnas en md+ como tu referencia -->
          <!-- Fila 1 -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-5 md:gap-6 mb-5">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">N° modelo</label>
              <input v-model="formData.modelo" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="00003"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Modelo</label>
              <input v-model="formData.nombre_modelo" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="TEV500"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Tensión</label>
              <input v-model="formData.tension" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="220 V"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Frecuencia</label>
              <input v-model="formData.frecuencia" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="50 Hz"/>
            </div>
          </div>

          <!-- Fila 2 -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-5 md:gap-6 mb-5">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Corriente</label>
              <input v-model="formData.corriente" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="1.23 A"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Clase aislación</label>
              <input v-model="formData.aislacion" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="I"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Sistema</label>
              <input v-model="formData.sistema" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="Komp"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Vol. Bruto</label>
              <input v-model="formData.volumen" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="450 dc3"/>
            </div>
          </div>

          <!-- Fila 3 -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-5 md:gap-6 mb-8">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Agente espum.</label>
              <input v-model="formData.espumante" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="R-141b"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Clase clim.</label>
              <input v-model="formData.clase" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="4"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Refrigerante</label>
              <input v-model="formData.gas" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="R-134a"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Cant. refrig.</label>
              <input v-model="formData.cantidad_gas" type="text"
                     class="w-full h-10 rounded-sm border border-gray-200 bg-white px-3 text-gray-900 placeholder-gray-400 focus:border-[#0D509C] focus:ring-2 focus:ring-[#0D509C]/30 outline-none transition"
                     placeholder="150 gr"/>
            </div>
          </div>

          <!-- Botones -->
          <div class="flex justify-end gap-3">
            <button
              @click="cancelar"
              class="h-10 rounded-full cursor-pointer px-6 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 ring-1 ring-gray-200 transition"
            >
              Cancelar
            </button>

            <button
              @click="guardarModelo"
              :disabled="loading"
              class="h-10 rounded-full px-7 cursor-pointer text-sm font-semibold text-white bg-[#0D509C] hover:opacity-95 disabled:opacity-60 transition"
            >
              <span v-if="!loading">{{ isEdit ? 'Guardar cambios' : 'Crear modelo' }}</span>
              <span v-else>{{ isEdit ? 'Guardando...' : 'Creando...' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
