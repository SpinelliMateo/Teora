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
        
        <!-- Header con logo teora - MÁS COMPACTO -->
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

        <!-- Información principal destacada - OPTIMIZADA -->
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

        <!-- Especificaciones técnicas - Grupo 1 COMPACTADO -->
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

        <!-- Especificaciones técnicas - Grupo 2 COMPACTADO -->
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

        <!-- Refrigerante destacado OPTIMIZADO -->
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

        <!-- Código de barras principal COMPACTADO -->
        <div class="barcode-section">
          <img 
            :src="barcodeUrl" 
            :alt="`Código de barras ${controlStock.codigo_barras}`"
            class="barcode-img"
            @error="handleBarcodeError"
          />
          <div v-if="barcodeError" class="barcode-error">
            <div class="barcode-error-text">Código de barras no disponible</div>
          </div>
          <div class="barcode-text">
            {{ controlStock.codigo_barras || controlStock.n_serie || 'SIN-CODIGO' }}
          </div>
        </div>

        <!-- Footer con certificaciones y origen COMPACTADO -->
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

        <!-- Código de barras inferior COMPACTADO -->
        <div class="barcode-bottom">
          <img 
            :src="barcodeLargoUrl" 
            :alt="`Código de barras largo ${generarCodigoLargo()}`"
            class="barcode-long-img"
            @error="handleBarcodeLargoError"
          />
          <div v-if="barcodeLargoError" class="barcode-long-error">
            <div class="barcode-long-error-text">Código de barras largo no disponible</div>
          </div>
          <div class="barcode-long-text">
            {{ generarCodigoLargo() || 'SIN-CODIGO-LARGO' }}
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
    const barcodeError = ref(false)
    const barcodeLargoError = ref(false)
    const logoError = ref(false)
    const certificadosError = ref(false)

    const formatearFechaCorta = (fecha) => {
      const date = new Date(fecha)
      const day = String(date.getDate()).padStart(2, '0')
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const year = date.getFullYear()
      return `${day}-${month}-${year}`
    }

    const generarCodigoLargo = () => {
      const codigoBarras = props.controlStock.codigo_barras || props.controlStock.n_serie || 'DEFAULT';
      const numeroSerie = String(props.controlStock.n_serie || '0').padStart(4, '0');
      return codigoBarras + '00' + numeroSerie;
    }

    const barcodeUrl = computed(() => {
      return `/barcode/etiqueta/${props.controlStock.id}`
    })

    const barcodeLargoUrl = computed(() => {
      return `/barcode/etiqueta-largo/${props.controlStock.id}`
    })

    const volver = () => {
      router.get(route('sectores.operarios.prearmado.detalle', props.controlStock.id))
    }

    const imprimir = () => {
      window.print()
    }

    const handleBarcodeError = () => {
      barcodeError.value = true
      console.error('Error cargando código de barras principal')
    }

    const handleBarcodeLargoError = () => {
      barcodeLargoError.value = true
      console.error('Error cargando código de barras largo')
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
      generarCodigoLargo,
      volver,
      imprimir,
      barcodeUrl,
      barcodeLargoUrl,
      barcodeError,
      barcodeLargoError,
      logoError,
      certificadosError,
      handleBarcodeError,
      handleBarcodeLargoError,
      handleLogoError,
      handleCertificadosError
    }
  }
}
</script>

<style scoped>
/* CONFIGURACIÓN BASE OPTIMIZADA */
.etiqueta {
  width: 100mm;
  height: 100mm;
  padding: 1.8mm;
  font-family: 'Arial', sans-serif;
  font-size: 6px; /* Reducido de 9px */
  line-height: 1.1; /* Reducido de 1.3 */
  overflow: hidden;
  position: relative;
}

/* HEADER COMPACTADO */
.header-section {
  margin-bottom: 1mm; /* Reducido de 2mm */
  border-bottom: 1px solid #ddd;
  padding-bottom: 0.8mm; /* Reducido de 1.5mm */
  height: 6.5mm; /* Fijo y reducido */
}

.logo-img {
  height: 6mm; /* Reducido de 9mm */
  margin-right: 1.5mm;
}

.logo-fallback {
  background: black;
  color: white;
  font-weight: bold;
  padding: 0.8mm 1.2mm;
  font-size: 5px;
  border-radius: 2px;
  margin-right: 1.5mm;
}

.company-info {
  font-size: 4.5px; /* Reducido de 6.5px */
  line-height: 1.1;
  color: #333;
}

.company-name {
  font-weight: bold;
}

.company-address {
  color: #666;
}

/* INFORMACIÓN PRINCIPAL OPTIMIZADA */
.main-info {
  margin-bottom: 1.2mm; /* Reducido de 2mm */
}

.main-table {
  width: 100%;
  border-collapse: collapse;
  border: 2px solid black;
}

.main-cell {
  border-right: 2px solid black;
  text-center: center;
  font-weight: bold;
  padding: 1mm; /* Reducido de 1.5mm */
  vertical-align: middle;
}

.main-cell:last-child {
  border-right: none;
}

.cell-label {
  font-size: 4.5px; /* Reducido de 6px */
  color: #333;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.cell-value {
  font-size: 7px; /* Reducido de 9-11px */
  font-weight: bold;
  color: #000;
  margin-top: 0.3mm;
}

.serie-value {
  font-size: 8px; /* Un poco más grande para el número de serie */
}

.fecha-cell { background: #f5f5f5; }
.serie-cell { background: #e8e8e8; }
.modelo-cell { background: #f0f0f0; }

/* ESPECIFICACIONES COMPACTADAS */
.specs-section {
  margin-bottom: 1mm; /* Reducido de 1.5mm */
}

.specs-table {
  width: 100%;
  border-collapse: collapse;
  border: 2px solid black;
}

.spec-cell {
  border-right: 1px solid black;
  text-align: center;
  padding: 0.6mm; /* Reducido de 1mm */
  background: #f8f8f8;
  vertical-align: middle;
}

.spec-cell:last-child {
  border-right: none;
}

.spec-label {
  font-weight: bold;
  font-size: 4px; /* Reducido de 5.5px */
  color: #444;
  text-transform: uppercase;
}

.spec-value {
  font-size: 5px; /* Reducido de 6.5px */
  font-weight: 600;
  margin-top: 0.2mm;
}

/* REFRIGERANTE COMPACTADO */
.refrigerant-section {
  margin-bottom: 1.2mm; /* Reducido de 2mm */
}

.refrigerant-table {
  width: 100%;
  border-collapse: collapse;
  border: 3px solid black;
}

.refrigerant-cell {
  border-right: 2px solid black;
  text-align: center;
  padding: 1mm; /* Reducido de 1.5mm */
  background: #e8e8e8;
  vertical-align: middle;
}

.refrigerant-cell:last-child {
  border-right: none;
}

.refrigerant-label {
  font-weight: bold;
  font-size: 4.5px; /* Reducido de 6px */
  color: #000;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.refrigerant-value {
  font-size: 6px; /* Reducido de 7px */
  font-weight: bold;
  color: #000;
  margin-top: 0.3mm;
}

/* CÓDIGO DE BARRAS PRINCIPAL COMPACTADO */
.barcode-section {
  text-align: center;
  margin-bottom: 1.2mm; /* Reducido de 2mm */
  padding: 0.8mm; /* Reducido de 1mm */
  border: 2px solid #000;
  background: #f5f5f5;
}

.barcode-img {
  height: 8mm; /* Reducido de 11mm */
  max-width: 100%;
}

.barcode-error {
  border: 2px solid black;
  background: white;
  padding: 0.8mm;
  margin: 0.3mm 0;
}

.barcode-error-text {
  font-size: 4px; /* Reducido de 5px */
  color: #000;
}

.barcode-text {
  font-family: monospace;
  font-weight: bold;
  font-size: 6.5px; /* Reducido de 9px */
  margin-top: 0.3mm;
  letter-spacing: 0.8px;
  color: #000;
}

/* FOOTER COMPACTADO */
.footer-section {
  border-top: 2px solid #000;
  padding-top: 1mm; /* Reducido de 1.5mm */
  margin-bottom: 1mm; /* Reducido de 1.5mm */
  height: 5.5mm; /* Fijo y reducido */
}

.cert-section {
  display: flex;
  align-items: center;
  gap: 1.5mm;
}

.cert-img {
  height: 5.5mm; /* Reducido de 8mm */
}

.cert-fallback {
  background: white;
  border: 2px solid black;
  padding: 0.8mm;
  font-size: 4px; /* Reducido de 5px */
  font-weight: bold;
}

.origin-info {
  text-align: right;
  font-size: 4.5px; /* Reducido de 5.5px */
}

.origin-country {
  font-weight: bold;
  color: #000;
}

.origin-code {
  color: #444;
  margin-top: 0.2mm;
}

/* CÓDIGO DE BARRAS INFERIOR COMPACTADO */
.barcode-bottom {
  text-align: center;
  border-top: 3px solid #000;
  padding-top: 1mm; /* Reducido de 1.5mm */
  background: #f0f0f0;
}

.barcode-long-img {
  height: 6mm; /* Reducido de 8mm */
  max-width: 100%;
}

.barcode-long-error {
  border: 2px solid black;
  background: white;
  padding: 0.8mm;
  margin: 0.3mm 0;
}

.barcode-long-error-text {
  font-size: 4px; /* Reducido de 5px */
  color: #000;
}

.barcode-long-text {
  font-family: monospace;
  font-size: 5.5px; /* Reducido de 8px */
  margin-top: 0.3mm;
  letter-spacing: 0.4px;
  color: #000;
  font-weight: bold;
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