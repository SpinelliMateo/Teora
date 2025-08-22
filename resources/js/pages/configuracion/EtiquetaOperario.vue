<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, nextTick } from 'vue';

interface Sector {
    id: number;
    nombre: string;
    descripcion: string;
    activo: boolean;
}

interface Operario {
    id: number;
    nombre: string;
    apellido: string;
    n_legajo: string;
    codigo_qr: string;
    activo: boolean;
    sectores?: Sector[];
}

const props = defineProps<{ 
    operario: Operario;
}>();

const barcodeUrl = ref('');
const barcodeLoaded = ref(false);
const barcodeError = ref(false);

const onBarcodeLoad = () => {
    barcodeLoaded.value = true;
    console.log('Código de barras cargado exitosamente');
};

const onBarcodeError = () => {
    barcodeError.value = true;
    console.error('Error al cargar el código de barras');
};

const imprimir = async () => {
    if (!barcodeLoaded.value && !barcodeError.value) {
        console.log('Esperando que se cargue el código de barras...');
        // Esperar hasta 3 segundos para que cargue el código de barras
        let attempts = 0;
        const maxAttempts = 30; // 3 segundos (100ms * 30)
        
        while (!barcodeLoaded.value && !barcodeError.value && attempts < maxAttempts) {
            await new Promise(resolve => setTimeout(resolve, 100));
            attempts++;
        }
    }
    
    // Pequeña pausa adicional para asegurar el renderizado
    await nextTick();
    setTimeout(() => {
        window.print();
    }, 100);
};

// Auto-imprimir si viene de un modal
const autoImprimir = () => {
    // Si detectamos que venimos del modal (por el opener), auto-imprimimos
    if (window.opener) {
        // Esperar un momento para que todo se cargue
        setTimeout(() => {
            imprimir();
        }, 1000);
    }
};

onMounted(() => {
    barcodeUrl.value = `/operarios/barcode/${props.operario.id}`;
    autoImprimir();
});
</script>

<template>
    <Head title="Etiqueta de Operario" />

    <div class="min-h-screen p-4" style="background-color: #F4F4F4;">
        <!-- Botón de acción (no se imprime) -->
        <div class="flex justify-center mb-8 no-print">
            <button @click="imprimir"
                class="flex items-center gap-2 px-6 py-2 text-white rounded-full cursor-pointer"
                style="background-color: #0D509C;"
                :disabled="!barcodeLoaded && !barcodeError">
                🖨️ {{ barcodeLoaded ? 'Imprimir Etiqueta' : 'Cargando...' }}
            </button>
        </div>

        <!-- Estado de carga -->
        <div v-if="!barcodeLoaded && !barcodeError" class="flex justify-center mb-4 no-print">
            <div class="text-blue-600">
                <p>⏳ Generando código de barras...</p>
            </div>
        </div>

        <!-- Error de carga -->
        <div v-if="barcodeError" class="flex justify-center mb-4 no-print">
            <div class="text-red-600 text-center">
                <p>❌ Error al generar el código de barras</p>
                <p class="text-sm">URL: {{ barcodeUrl }}</p>
            </div>
        </div>

        <!-- Etiqueta (se imprime) - Diseño simple como la imagen -->
        <div class="flex justify-center">
            <div class="etiqueta bg-white border-2 border-black p-8 text-center">
                <!-- Código de barras en la parte superior -->
                <div class="mb-4">
                    <!-- Mostrar código de barras si se cargó correctamente -->
                    <img v-if="barcodeLoaded" 
                         :src="barcodeUrl" 
                         :alt="'Código de barras: ' + operario.codigo_qr"
                         class="mx-auto max-w-full h-auto"
                         style="max-height: 70px;" 
                         @load="onBarcodeLoad"
                         @error="onBarcodeError" />
                    
                    <!-- Mostrar imagen con eventos si aún no se ha cargado -->
                    <img v-else-if="!barcodeError" 
                         :src="barcodeUrl" 
                         :alt="'Código de barras: ' + operario.codigo_qr"
                         class="mx-auto max-w-full h-auto"
                         style="max-height: 70px;"
                         @load="onBarcodeLoad"
                         @error="onBarcodeError" />
                    
                    <!-- Fallback si hay error -->
                    <div v-else class="h-16 flex items-center justify-center border border-dashed border-gray-400">
                        <span class="text-xs text-gray-500">Error en código de barras</span>
                    </div>
                </div>

                <!-- Código QR legible debajo del código de barras -->
                <p class="text-sm font-mono font-bold mb-6">{{ operario.codigo_qr }}</p>
                
                <!-- Nombre del operario -->
                <h1 class="text-xl font-bold text-black uppercase">
                    {{ operario.nombre }} {{ operario.apellido }}
                </h1>
            </div>
        </div>

        <!-- Debug info (no se imprime) -->
        <div class="no-print mt-8 text-center text-xs text-gray-500" v-if="barcodeError">
            <p>Debug - URL del código de barras: {{ barcodeUrl }}</p>
            <p>ID del operario: {{ operario.id }}</p>
        </div>
    </div>
</template>

<style scoped>
.etiqueta {
    width: 350px;
    min-height: 200px;
}

/* Estilos para impresión */
@media print {
    .no-print {
        display: none !important;
    }
    
    body {
        margin: 0;
        padding: 0;
    }
    
    .etiqueta {
        border: 2px solid #000;
        margin: 0;
        page-break-inside: avoid;
        width: 300px;
    }
}
</style>