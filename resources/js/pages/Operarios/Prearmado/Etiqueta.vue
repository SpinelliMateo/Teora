<template>
  <div class="min-h-screen bg-white">
    <!-- Botones de acción (no se imprimen) -->
    <div class="no-print flex justify-between items-center p-4 border-b">
      <button 
        @click="volver" 
        class="text-gray-600 hover:text-gray-900 flex items-center transition-colors"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
      </button>
      
      <button 
        @click="imprimir"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors shadow-sm"
      >
        Imprimir
      </button>
    </div>

    <!-- Etiqueta para imprimir -->
    <div class="print-area flex justify-center items-center min-h-screen p-8">
      <div class="etiqueta bg-white border-4 border-black shadow-lg">
        
        <!-- Header con logo teora -->
        <div class="header-section">
          <div class="flex items-start">
            <img 
              src="/logo-teora.png" 
              alt="Logo Teora" 
              class="logo-img"
              @error="handleLogoError"
            />
            <div v-if="logoError" class="logo-fallback">teora</div>
            <div class="company-info">
              <div class="company-name">Fabrica, distribuye y garant. J. A. Stefanelli</div>
              <div class="company-address">Avda. H. Yrigoyen 5672 Ezpeleta (Bs. As.)</div>
            </div>
          </div>
        </div>

        <!-- Información principal destacada -->
        <div class="main-info">
          <table class="main-table">
            <tr>
              <td class="main-cell fecha-cell">
                <div class="cell-label">Fecha</div>
                <div class="cell-value">{{ formatearFechaCorta(controlStock.fecha_prearmado) }}</div>
              </td>
              <td class="main-cell serie-cell">
                <div class="cell-label">N° de serie</div>
                <div class="cell-value serie-value">{{ controlStock.n_serie }}</div>
              </td>
              <td class="main-cell modelo-cell">
                <div class="cell-label">Modelo</div>
                <div class="cell-value">{{ controlStock.modelo.nombre_modelo }}</div>
              </td>
            </tr>
          </table>
        </div>

        <!-- Especificaciones técnicas - Grupo 1 -->
        <div class="specs-section">
          <table class="specs-table">
            <tr>
              <td class="spec-cell">
                <div class="spec-label">Tensión</div>
                <div class="spec-value">{{ controlStock.modelo.tension }}</div>
              </td>
              <td class="spec-cell">
                <div class="spec-label">Frecuencia</div>
                <div class="spec-value">{{ controlStock.modelo.frecuencia }}</div>
              </td>
              <td class="spec-cell">
                <div class="spec-label">Corriente</div>
                <div class="spec-value">{{ controlStock.modelo.corriente }}</div>
              </td>
              <td class="spec-cell">
                <div class="spec-label">Aislación</div>
                <div class="spec-value">{{ controlStock.modelo.aislacion }}</div>
              </td>
            </tr>
          </table>
        </div>

        <!-- Especificaciones técnicas - Grupo 2 -->
        <div class="specs-section">
          <table class="specs-table">
            <tr>
              <td class="spec-cell">
                <div class="spec-label">Sistema</div>
                <div class="spec-value">{{ controlStock.modelo.sistema }}</div>
              </td>
              <td class="spec-cell">
                <div class="spec-label">Vol. Bruto</div>
                <div class="spec-value">{{ controlStock.modelo.volumen }}</div>
              </td>
              <td class="spec-cell">
                <div class="spec-label">Ag. Espum.</div>
                <div class="spec-value">{{ controlStock.modelo.espumante }}</div>
              </td>
              <td class="spec-cell">
                <div class="spec-label">Clase Clim.</div>
                <div class="spec-value">{{ controlStock.modelo.clase }}</div>
              </td>
            </tr>
          </table>
        </div>

        <!-- Refrigerante destacado -->
        <div class="refrigerant-section">
          <table class="refrigerant-table">
            <tr>
              <td class="refrigerant-cell">
                <div class="refrigerant-label">Refrigerante</div>
                <div class="refrigerant-value">{{ controlStock.modelo.gas }}</div>
              </td>
              <td class="refrigerant-cell">
                <div class="refrigerant-label">Cantidad</div>
                <div class="refrigerant-value">{{ controlStock.modelo.cantidad_gas }}</div>
              </td>
            </tr>
          </table>
        </div>

        <!-- CÓDIGOS DE BARRAS SEPARADOS -->
        <div class="barcodes-section">
          <!-- Código de barras de Serie -->
          <div class="barcode-container">
            <div class="barcode-label">N° Serie</div>
            <img 
              :src="barcodeSerieUrl" 
              :alt="`Código de barras serie ${controlStock.n_serie}`"
              class="barcode-img"
              @error="handleBarcodeSerieError"
            />
            <div v-if="barcodeSerieError" class="barcode-error">
              <div class="barcode-error-text">CB Serie no disponible</div>
            </div>
            <div class="barcode-text">{{ controlStock.n_serie }}</div>
          </div>

          <!-- Código de barras de Modelo -->
          <div class="barcode-container">
            <div class="barcode-label">Modelo</div>
            <img 
              :src="barcodeModeloUrl" 
              :alt="`Código de barras modelo ${getModeloCode()}`"
              class="barcode-img"
              @error="handleBarcodeModeloError"
            />
            <div v-if="barcodeModeloError" class="barcode-error">
              <div class="barcode-error-text">CB Modelo no disponible</div>
            </div>
            <div class="barcode-text">{{ getModeloCode() }}</div>
          </div>
        </div>

        <!-- Footer con certificaciones y origen -->
        <div class="footer-section">
          <div class="flex justify-between items-center">
            <div class="cert-section">
              <img 
                src="/certificaciones.jpg" 
                alt="Certificados IQC S" 
                class="cert-img"
                @error="handleCertificadosError"
              />
              <div v-if="certificadosError" class="cert-fallback">
                CERT. IQC S
              </div>
            </div>
            <div class="origin-info">
              <div class="origin-country">Origen: ARGENTINA</div>
              <div class="origin-code">28044205 2004</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export default {
  props: {
    controlStock: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const barcodeSerieError = ref(false)
    const barcodeModeloError = ref(false)
    const logoError = ref(false)
    const certificadosError = ref(false)

    const formatearFechaCorta = (fecha) => {
      const date = new Date(fecha)
      const day = String(date.getDate()).padStart(2, '0')
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const year = date.getFullYear()
      return `${day}-${month}-${year}`
    }

    const getModeloCode = () => {
      if (!props.controlStock.modelo) return 'Sin modelo'
      return props.controlStock.modelo.modelo || 
             props.controlStock.modelo.codigo_modelo || 
             props.controlStock.modelo.nombre_modelo || 
             'Sin código'
    }

    // URLS CORREGIDAS - usando las mismas que en la vista de detalle
    const barcodeSerieUrl = computed(() => {
      return `/barcode/generate/${props.controlStock.id}`
    })

    const barcodeModeloUrl = computed(() => {
      return `/barcode/generate/modelo/${props.controlStock.id}`
    })

    const volver = () => {
      router.get(route('sectores.operarios.prearmado.detalle', props.controlStock.id))
    }

    const imprimir = () => {
      window.print()
    }

    const handleBarcodeSerieError = () => {
      barcodeSerieError.value = true
      console.error('Error cargando código de barras de serie')
    }

    const handleBarcodeModeloError = () => {
      barcodeModeloError.value = true
      console.error('Error cargando código de barras de modelo')
    }

    const handleLogoError = () => {
      logoError.value = true
      console.error('Error cargando logo teora')
    }

    const handleCertificadosError = () => {
      certificadosError.value = true
      console.error('Error cargando imagen de certificados')
    }

    return {
      formatearFechaCorta,
      getModeloCode,
      volver,
      imprimir,
      barcodeSerieUrl,
      barcodeModeloUrl,
      barcodeSerieError,
      barcodeModeloError,
      logoError,
      certificadosError,
      handleBarcodeSerieError,
      handleBarcodeModeloError,
      handleLogoError,
      handleCertificadosError
    }
  }
}
</script>

<style scoped>
/* CONFIGURACIÓN BASE */
.etiqueta {
  width: 100mm;
  height: 100mm;
  padding: 2.5mm;
  font-family: 'Arial', sans-serif;
  font-size: 8px;
  line-height: 1.2;
  overflow: hidden;
  position: relative;
}

/* HEADER */
.header-section {
  margin-bottom: 2mm;
  border-bottom: 2px solid #333;
  padding-bottom: 1.5mm;
  height: 9mm;
}

.logo-img {
  height: 8mm;
  margin-right: 2mm;
}

.logo-fallback {
  background: black;
  color: white;
  font-weight: bold;
  padding: 1.2mm 1.8mm;
  font-size: 6px;
  border-radius: 2px;
  margin-right: 2mm;
}

.company-info {
  font-size: 6px;
  line-height: 1.2;
  color: #333;
}

.company-name {
  font-weight: bold;
  font-size: 6.5px;
}

.company-address {
  color: #666;
  margin-top: 0.5mm;
}

/* INFORMACIÓN PRINCIPAL */
.main-info {
  margin-bottom: 2mm;
}

.main-table {
  width: 100%;
  border-collapse: collapse;
  border: 2px solid black;
}

.main-cell {
  border-right: 2px solid black;
  text-align: center;
  font-weight: bold;
  padding: 1.5mm;
  vertical-align: middle;
}

.main-cell:last-child {
  border-right: none;
}

.cell-label {
  font-size: 6px;
  color: #333;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  margin-bottom: 0.5mm;
}

.cell-value {
  font-size: 9px;
  font-weight: bold;
  color: #000;
  margin-top: 0.5mm;
}

.serie-value {
  font-size: 10px;
}

.fecha-cell { background: #f5f5f5; }
.serie-cell { background: #e8e8e8; }
.modelo-cell { background: #f0f0f0; }

/* ESPECIFICACIONES */
.specs-section {
  margin-bottom: 1.5mm;
}

.specs-table {
  width: 100%;
  border-collapse: collapse;
  border: 2px solid black;
}

.spec-cell {
  border-right: 1px solid black;
  text-align: center;
  padding: 1mm;
  background: #f8f8f8;
  vertical-align: middle;
}

.spec-cell:last-child {
  border-right: none;
}

.spec-label {
  font-weight: bold;
  font-size: 5px;
  color: #444;
  text-transform: uppercase;
  margin-bottom: 0.3mm;
}

.spec-value {
  font-size: 6.5px;
  font-weight: 600;
  margin-top: 0.3mm;
}

/* REFRIGERANTE */
.refrigerant-section {
  margin-bottom: 2.5mm;
}

.refrigerant-table {
  width: 100%;
  border-collapse: collapse;
  border: 3px solid black;
}

.refrigerant-cell {
  border-right: 2px solid black;
  text-align: center;
  padding: 1.5mm;
  background: #e8e8e8;
  vertical-align: middle;
}

.refrigerant-cell:last-child {
  border-right: none;
}

.refrigerant-label {
  font-weight: bold;
  font-size: 6px;
  color: #000;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  margin-bottom: 0.5mm;
}

.refrigerant-value {
  font-size: 8px;
  font-weight: bold;
  color: #000;
  margin-top: 0.5mm;
}

/* CÓDIGOS DE BARRAS - NUEVA SECCIÓN */
.barcodes-section {
  display: flex;
  gap: 2mm;
  margin-bottom: 2.5mm;
  border: 2px solid #000;
  padding: 1.5mm;
  background: #f5f5f5;
}

.barcode-container {
  flex: 1;
  text-align: center;
  border: 1px solid #ccc;
  padding: 1mm;
  background: white;
}

.barcode-label {
  font-size: 5.5px;
  font-weight: bold;
  color: #333;
  margin-bottom: 0.5mm;
  text-transform: uppercase;
}

.barcode-img {
  height: 10mm;
  max-width: 100%;
}

.barcode-error {
  border: 1px solid black;
  background: white;
  padding: 1mm;
  margin: 0.5mm 0;
}

.barcode-error-text {
  font-size: 4px;
  color: #000;
}

.barcode-text {
  font-family: monospace;
  font-weight: bold;
  font-size: 6px;
  margin-top: 0.5mm;
  letter-spacing: 0.5px;
  color: #000;
}

/* FOOTER */
.footer-section {
  border-top: 2px solid #000;
  padding-top: 1.5mm;
  margin-bottom: 0;
  height: 8mm;
}

.cert-section {
  display: flex;
  align-items: center;
  gap: 2mm;
}

.cert-img {
  height: 7.5mm;
}

.cert-fallback {
  background: white;
  border: 2px solid black;
  padding: 1.2mm;
  font-size: 5px;
  font-weight: bold;
}

.origin-info {
  text-align: right;
  font-size: 6px;
}

.origin-country {
  font-weight: bold;
  color: #000;
  font-size: 6.5px;
}

.origin-code {
  color: #444;
  margin-top: 0.5mm;
  font-size: 5.5px;
}

/* PRINT STYLES */
@media print {
  .no-print {
    display: none !important;
  }
  
  .print-area {
    min-height: auto;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    page-break-inside: avoid;
  }
  
  .etiqueta {
    border: 4px solid black !important;
    margin: 0;
    page-break-inside: avoid;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    width: 100mm !important;
    height: 100mm !important;
    box-shadow: none !important;
  }
  
  body {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  
  img {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    color-adjust: exact;
  }
  
  * {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  
  table, td {
    border-color: black !important;
  }
  
  @page {
    size: A4;
    margin: 10mm;
  }
}

/* UTILITY CLASSES */
.border-black {
  border-color: #000;
}

.font-bold {
  font-weight: 700;
}

.text-center {
  text-align: center;
}

.transition-colors {
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out;
}

.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.shadow-lg {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}
</style>