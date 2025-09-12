<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, nextTick, computed, onUnmounted } from 'vue';

// Interfaces
interface ChartData {
  label: string;
  value: number;
  date: string;
}

interface Alert {
  id: number;
  fechaAlerta: string;
  numeroSerie: string;
  numeroOrden: string;
  modelo: string;
  fechaFinalizacion: string;
  motivo: string;
  tipo: 'error' | 'warning' | 'info';
}

interface Activity {
  id: number;
  fecha: string;
  descripcion: string;
  tipo: 'carga' | 'modificacion' | 'pausa' | 'reanudacion' | 'finalizacion';
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
];

// Estados reactivos
const currentTime = ref(new Date());
const selectedMonth = ref(new Date().getMonth());
const selectedYear = ref(new Date().getFullYear());

// Datos de ejemplo para los gráficos
const prestamadoData: ChartData[] = [
  { label: 'Ene', value: 15, date: '14/03/2024' },
  { label: 'Feb', value: 8, date: '23/03/2024' },
  { label: 'Mar', value: 12, date: '23/03/2024' },
  { label: 'Abr', value: 18, date: '15/04/2024' },
  { label: 'May', value: 22, date: '20/05/2024' },
];

const inyectadoData: ChartData[] = [
  { label: 'Ene', value: 25, date: '14/03/2024' },
  { label: 'Feb', value: 0, date: '24/03/2024' },
  { label: 'Mar', value: 0, date: '24/03/2024' },
  { label: 'Abr', value: 15, date: '18/04/2024' },
  { label: 'May', value: 10, date: '22/05/2024' },
];

const armadoData: ChartData[] = [
  { label: 'Abuso', value: 20, date: '24/03/2024' },
  { label: 'Arleno', value: 25, date: '24/03/2024' },
  { label: 'Golpe', value: 35, date: '24/03/2024' },
  { label: 'Acoso', value: 18, date: '24/03/2024' },
  { label: 'Robo', value: 30, date: '25/03/2024' },
];

const embaladoData: ChartData[] = [
  { label: 'Abuso', value: 30, date: '14/03/2024' },
  { label: 'Arleno', value: 22, date: '24/03/2024' },
  { label: 'Golpe', value: 25, date: '24/03/2024' },
  { label: 'Acoso', value: 15, date: '24/03/2024' },
  { label: 'Robo', value: 28, date: '24/03/2024' },
];

// Datos de alertas
const alerts = ref<Alert[]>([
  { id: 1, fechaAlerta: '04/03/25', numeroSerie: '1234', numeroOrden: '00004', modelo: '0001', fechaFinalizacion: '04/03/25', motivo: 'Falta material', tipo: 'error' },
  { id: 2, fechaAlerta: '04/03/25', numeroSerie: '1235', numeroOrden: '00005', modelo: '0002', fechaFinalizacion: '04/03/25', motivo: 'Falta energía', tipo: 'warning' },
  { id: 3, fechaAlerta: '04/03/25', numeroSerie: '1236', numeroOrden: '00006', modelo: '0003', fechaFinalizacion: '04/03/25', motivo: 'Falta material', tipo: 'error' },
  { id: 4, fechaAlerta: '04/03/25', numeroSerie: '1237', numeroOrden: '00007', modelo: '0004', fechaFinalizacion: '04/03/25', motivo: 'Falta de corte', tipo: 'info' },
  { id: 5, fechaAlerta: '04/03/25', numeroSerie: '1238', numeroOrden: '00008', modelo: '0005', fechaFinalizacion: '04/03/25', motivo: 'Falta material', tipo: 'error' },
  { id: 6, fechaAlerta: '04/03/25', numeroSerie: '1239', numeroOrden: '00009', modelo: '0006', fechaFinalizacion: '04/03/25', motivo: 'Falta de corte', tipo: 'info' },
  { id: 7, fechaAlerta: '04/03/25', numeroSerie: '1240', numeroOrden: '00010', modelo: '0007', fechaFinalizacion: '04/03/25', motivo: 'Falta energía', tipo: 'warning' },
  { id: 8, fechaAlerta: '04/03/25', numeroSerie: '1241', numeroOrden: '00011', modelo: '0008', fechaFinalizacion: '04/03/25', motivo: 'Falta material', tipo: 'error' },
]);

// Datos de actividades recientes
const activities = ref<Activity[]>([
  { id: 1, fecha: '15-05-2024', descripcion: 'Se cargó el orden 00738', tipo: 'carga' },
  { id: 2, fecha: '15-05-2024', descripcion: 'Se modificó orden 00738', tipo: 'modificacion' },
  { id: 3, fecha: '08-05-2024', descripcion: 'Empalme nuevos de cadena a compra en la orden 8284', tipo: 'modificacion' },
  { id: 4, fecha: '06-05-2024', descripcion: 'Inicio de prueba de elemento compra en la orden 6829', tipo: 'carga' },
  { id: 5, fecha: '06-05-2024', descripcion: 'Se pausó el orden 00924', tipo: 'pausa' },
  { id: 6, fecha: '07-05-2024', descripcion: 'Se retomó la orden 8284', tipo: 'reanudacion' },
  { id: 7, fecha: '07-05-2024', descripcion: 'Se cargó el orden 6829', tipo: 'carga' },
  { id: 8, fecha: '07-05-2024', descripcion: 'Se cargó el orden 00924', tipo: 'carga' },
]);

// Referencias para los canvas de los gráficos
const prestamadoCanvas = ref<HTMLCanvasElement | null>(null);
const inyectadoCanvas = ref<HTMLCanvasElement | null>(null);
const armadoCanvas = ref<HTMLCanvasElement | null>(null);
const embaladoCanvas = ref<HTMLCanvasElement | null>(null);

let chartInstances: any[] = [];
let timeInterval: NodeJS.Timeout | null = null;

// Computed properties
const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                   'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const currentMonthName = computed(() => monthNames[selectedMonth.value]);

const alertsByType = computed(() => {
  return {
    error: alerts.value.filter(alert => alert.tipo === 'error').length,
    warning: alerts.value.filter(alert => alert.tipo === 'warning').length,
    info: alerts.value.filter(alert => alert.tipo === 'info').length,
  };
});

const getAlertClass = (tipo: string) => {
  switch (tipo) {
    case 'error': return 'bg-red-50';
    case 'warning': return 'bg-orange-50';
    case 'info': return 'bg-yellow-50';
    default: return 'bg-gray-50';
  }
};

const getAlertTextClass = (tipo: string) => {
  switch (tipo) {
    case 'error': return 'text-red-600';
    case 'warning': return 'text-orange-600';
    case 'info': return 'text-yellow-600';
    default: return 'text-gray-600';
  }
};



// Generar calendario del mes actual
const generateCalendar = computed(() => {
  const year = selectedYear.value;
  const month = selectedMonth.value;
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startingDayOfWeek = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1; // Lunes = 0
  const daysInMonth = lastDay.getDate();
  
  const calendar = [];
  
  // Días del mes anterior
  const prevMonth = new Date(year, month - 1, 0);
  for (let i = startingDayOfWeek - 1; i >= 0; i--) {
    calendar.push({
      day: prevMonth.getDate() - i,
      isCurrentMonth: false,
      isToday: false,
      hasDelivery: false
    });
  }
  
  // Días del mes actual
  const today = new Date();
  for (let day = 1; day <= daysInMonth; day++) {
    const isToday = year === today.getFullYear() && 
                   month === today.getMonth() && 
                   day === today.getDate();
    calendar.push({
      day,
      isCurrentMonth: true,
      isToday,
      hasDelivery: [4, 11, 17, 25].includes(day) // Días con entregas
    });
  }
  
  // Días del mes siguiente para completar la semana
  const remainingDays = 42 - calendar.length; // 6 semanas × 7 días
  for (let day = 1; day <= remainingDays; day++) {
    calendar.push({
      day,
      isCurrentMonth: false,
      isToday: false,
      hasDelivery: false
    });
  }
  
  return calendar;
});

// Funciones para navegar el calendario
const previousMonth = () => {
  if (selectedMonth.value === 0) {
    selectedMonth.value = 11;
    selectedYear.value--;
  } else {
    selectedMonth.value--;
  }
};

const nextMonth = () => {
  if (selectedMonth.value === 11) {
    selectedMonth.value = 0;
    selectedYear.value++;
  } else {
    selectedMonth.value++;
  }
};

// Función para crear gráfico individual
const crearGrafico = async (canvas: HTMLCanvasElement, data: ChartData[], titulo: string) => {
  if (!canvas) return;

  try {
    // Importar Chart.js dinámicamente
    const { Chart, registerables } = await import('chart.js');
    Chart.register(...registerables);

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.map(item => item.label),
        datasets: [{
          data: data.map(item => item.value),
          backgroundColor: '#0D509C',
          borderColor: '#3B82F6',
          borderWidth: 1,
          borderRadius: 4,
          borderSkipped: false,
          barPercentage: 0.6,
          categoryPercentage: 0.8,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#1F2937',
            titleColor: '#FFFFFF',
            bodyColor: '#FFFFFF',
            cornerRadius: 8,
            displayColors: false,
            callbacks: {
              title: function(context) {
                return `${titulo}: ${context[0].label}`;
              },
              label: function(context) {
                return `Cantidad: ${context.parsed.y}`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
              color: '#6B7280'
            },
            grid: {
              color: '#E5E7EB'
            }
          },
          x: {
            ticks: {
              color: '#6B7280',
              maxRotation: 45,
              minRotation: 0
            },
            grid: {
              display: false
            }
          }
        },
        layout: {
          padding: {
            top: 10,
            right: 10,
            bottom: 10,
            left: 10
          }
        },
        interaction: {
          intersect: false,
          mode: 'index'
        }
      }
    });
  } catch (error) {
    console.error('Error creando gráfico:', error);
    return null;
  }
};

// Función para crear todos los gráficos
const crearGraficos = async () => {
  await nextTick();

  // Destruir gráficos anteriores si existen
  chartInstances.forEach(chart => {
    if (chart) {
      chart.destroy();
    }
  });
  chartInstances = [];

  // Crear nuevos gráficos
  const charts = [
    { canvas: prestamadoCanvas.value, data: prestamadoData, title: 'Prestamado' },
    { canvas: inyectadoCanvas.value, data: inyectadoData, title: 'Inyectado' },
    { canvas: armadoCanvas.value, data: armadoData, title: 'Armado' },
    { canvas: embaladoCanvas.value, data: embaladoData, title: 'Embalado' }
  ];

  for (const chart of charts) {
    if (chart.canvas) {
      const chartInstance = await crearGrafico(chart.canvas, chart.data, chart.title);
      if (chartInstance) {
        chartInstances.push(chartInstance);
      }
    }
  }
};

// Actualizar reloj cada segundo
const updateTime = () => {
  currentTime.value = new Date();
};

// Formatear fecha para mostrar
const formatTime = (date: Date) => {
  return date.toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
};

const formatDate = (date: Date) => {
  return date.toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

// Calcular totales
const totales = computed(() => ({
  prestamado: prestamadoData.reduce((sum, item) => sum + item.value, 0),
  inyectado: inyectadoData.reduce((sum, item) => sum + item.value, 0),
  armado: armadoData.reduce((sum, item) => sum + item.value, 0),
  embalado: embaladoData.reduce((sum, item) => sum + item.value, 0)
}));

// Lifecycle hooks
onMounted(() => {
  crearGraficos();
  updateTime();
  timeInterval = setInterval(updateTime, 1000);
});

onUnmounted(() => {
  // Limpiar gráficos
  chartInstances.forEach(chart => {
    if (chart) {
      chart.destroy();
    }
  });
  
  // Limpiar intervalo
  if (timeInterval) {
    clearInterval(timeInterval);
  }
});
</script>

<template>
  <Head title="Dashboard" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 px-20 py-4 bg-gray-50">
      <!-- Header con título y reloj -->
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-medium text-gray-700">Inicio</h1>
          
          
        </div>

      </div>

      <!-- Tarjetas de métricas superiores -->
      <div class="grid grid-cols-4 gap-4 mb-6">
        <!-- Prestamado -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-4xl font-light text-blue-500">{{ totales.prestamado }}</p>
              <p class="text-sm text-gray-500">Prearmado</p>
              <div class="flex items-center mt-2">
                <span class="text-xs text-green-500">+12%</span>
                <svg class="w-3 h-3 ml-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>
              </div>
            </div>
            <div class="text-blue-400">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Inyectado -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-4xl font-light text-blue-500">{{ totales.inyectado }}</p>
              <p class="text-sm text-gray-500">Inyectado</p>
              <div class="flex items-center mt-2">
                <span class="text-xs text-red-500">-8%</span>
                <svg class="w-3 h-3 ml-1 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
              </div>
            </div>
            <div class="text-blue-400">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Armado -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-4xl font-light text-blue-500">{{ totales.armado }}</p>
              <p class="text-sm text-gray-500">Armado</p>
              <div class="flex items-center mt-2">
                <span class="text-xs text-green-500">+5%</span>
                <svg class="w-3 h-3 ml-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>
              </div>
            </div>
            <div class="text-blue-400">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a1 1 0 01-1-1V9a1 1 0 011-1h1a2 2 0 100-4H4a1 1 0 01-1-1V4a1 1 0 011-1h3a1 1 0 001-1z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Embalados -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-4xl font-light text-blue-500">{{ totales.embalado }}</p>
              <p class="text-sm text-gray-500">Embalados</p>
              <div class="flex items-center mt-2">
                <span class="text-xs text-green-500">+15%</span>
                <svg class="w-3 h-3 ml-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                </svg>
              </div>
            </div>
            <div class="text-blue-400">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h1.586a1 1 0 01.707.293l1.414 1.414a1 1 0 00.707.293H15a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráficos de barras con Chart.js -->
      <div class="grid grid-cols-2 gap-6">
        <!-- Prestamado Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Prestamado</h3>
            <div class="text-sm text-gray-500">
              Total: {{ totales.prestamado }} | Desde: 14/03/2024 | Hasta: 20/05/2024
            </div>
          </div>
          <div class="relative h-64">
            <canvas ref="prestamadoCanvas"></canvas>
          </div>
        </div>

        <!-- Inyectado Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Inyectado</h3>
            <div class="text-sm text-gray-500">
              Total: {{ totales.inyectado }} | Desde: 14/03/2024 | Hasta: 22/05/2024
            </div>
          </div>
          <div class="relative h-64">
            <canvas ref="inyectadoCanvas"></canvas>
          </div>
        </div>

        <!-- Armado Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Armado</h3>
            <div class="text-sm text-gray-500">
              Total: {{ totales.armado }} | Desde: 24/03/2024 | Hasta: 25/03/2024
            </div>
          </div>
          <div class="relative h-64">
            <canvas ref="armadoCanvas"></canvas>
          </div>
        </div>

        <!-- Embalado Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Embalado</h3>
            <div class="text-sm text-gray-500">
              Total: {{ totales.embalado }} | Desde: 14/03/2024 | Hasta: 24/03/2024
            </div>
          </div>
          <div class="relative h-64">
            <canvas ref="embaladoCanvas"></canvas>
          </div>
        </div>
      </div>

      <!-- Sección Alertas -->
      <div class="mt-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-700">Alertas</h3>
            <div class="flex items-center space-x-4 text-sm">
              <span class="flex items-center">
                <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                Críticas: {{ alertsByType.error }}
              </span>
              <span class="flex items-center">
                <div class="w-3 h-3 bg-orange-500 rounded-full mr-2"></div>
                Advertencias: {{ alertsByType.warning }}
              </span>
              <span class="flex items-center">
                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                Info: {{ alertsByType.info }}
              </span>
            </div>
          </div>
          
          <!-- Tabla de alertas -->
          <div class="overflow-x-auto max-h-96">
            <table class="w-full">
              <thead class="bg-blue-50 sticky top-0">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° DE ORDEN</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MODELO</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">FECHA FINALIZACIÓN</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MOTIVO</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACCIONES</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr 
                  v-for="alert in alerts" 
                  :key="alert.id" 
                  :class="getAlertClass(alert.tipo)"
                  class="hover:bg-opacity-80 transition-colors"
                >
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ alert.fechaAlerta }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ alert.numeroSerie }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ alert.numeroOrden }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ alert.modelo }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ alert.fechaFinalizacion }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm" :class="getAlertTextClass(alert.tipo)">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                          :class="{
                            'bg-red-100 text-red-800': alert.tipo === 'error',
                            'bg-orange-100 text-orange-800': alert.tipo === 'warning',
                            'bg-yellow-100 text-yellow-800': alert.tipo === 'info'
                          }">
                      {{ alert.motivo }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div class="flex space-x-2">
                      <button class="text-indigo-600 hover:text-indigo-900 text-xs bg-indigo-100 px-2 py-1 rounded">
                        Ver
                      </button>
                      <button class="text-green-600 hover:text-green-900 text-xs bg-green-100 px-2 py-1 rounded">
                        Resolver
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Sección inferior con Actividades recientes y Entregas -->
      <div class="grid grid-cols-2 gap-6 mt-6">
        <!-- Actividades recientes -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-700">Actividades recientes</h3>
              <button class="text-blue-600 hover:text-blue-800 text-sm">Ver todas</button>
            </div>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div 
                v-for="activity in activities.slice(0, 8)" 
                :key="activity.id"
                class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors"
              >

                <div class="flex-1 min-w-0">
                  <div class="flex justify-between items-start">
                    <div>
                      <p class="text-sm text-blue-600 font-medium">{{ activity.descripcion }}</p>
                      <div class="flex items-center mt-1">
                        <span class="text-xs text-gray-500">{{ activity.fecha }}</span>
                        <span class="mx-2 text-gray-300">•</span>
                        <span class="text-xs text-gray-400 capitalize">{{ activity.tipo }}</span>
                      </div>
                    </div>
                    <div class="flex-shrink-0">
                      <button class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Indicador de más actividades -->
            <div class="mt-4 pt-4 border-t border-gray-200">
              <button class="w-full text-center text-sm text-gray-500 hover:text-gray-700 py-2">
                Ver más actividades...
              </button>
            </div>
          </div>
        </div>

        <!-- Entregas -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-700">Calendario de Entregas</h3>
              <div class="flex items-center space-x-2">
                <button 
                  @click="previousMonth"
                  class="p-1 hover:bg-gray-100 rounded transition-colors"
                >
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                  </svg>
                </button>
                <span class="text-sm text-gray-600 font-medium min-w-20 text-center">{{ currentMonthName }}</span>
                <button 
                  @click="nextMonth"
                  class="p-1 hover:bg-gray-100 rounded transition-colors"
                >
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
          <div class="p-6">
            <!-- Calendario -->
            <div class="grid grid-cols-7 gap-1 text-center text-xs mb-4">
              <!-- Días de la semana -->
              <div class="p-2 text-gray-500 font-semibold">L</div>
              <div class="p-2 text-gray-500 font-semibold">M</div>
              <div class="p-2 text-gray-500 font-semibold">X</div>
              <div class="p-2 text-gray-500 font-semibold">J</div>
              <div class="p-2 text-gray-500 font-semibold">V</div>
              <div class="p-2 text-gray-500 font-semibold">S</div>
              <div class="p-2 text-gray-500 font-semibold">D</div>
              
              <!-- Días del calendario -->
              <div 
                v-for="(day, index) in generateCalendar" 
                :key="index"
                class="relative p-2 h-8 flex items-center justify-center"
                :class="{
                  'text-gray-400': !day.isCurrentMonth,
                  'text-gray-900': day.isCurrentMonth && !day.isToday,
                  'bg-blue-500 text-white rounded-full font-semibold': day.isToday,
                  'hover:bg-gray-100 cursor-pointer': day.isCurrentMonth && !day.isToday
                }"
              >
                {{ day.day }}
                <!-- Indicador de entrega -->
                <div 
                  v-if="day.hasDelivery && !day.isToday" 
                  class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 bg-blue-500 rounded-full"
                ></div>
              </div>
            </div>
            
            <!-- Próximas entregas -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-700 mb-3">Próximas entregas</h4>
              <div class="space-y-2">
                <div class="flex items-center justify-between p-2 bg-blue-50 rounded">
                  <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                    <span class="text-sm text-gray-700">Orden #00789</span>
                  </div>
                  <span class="text-xs text-gray-500">Hoy</span>
                </div>
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                  <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                    <span class="text-sm text-gray-700">Orden #00856</span>
                  </div>
                  <span class="text-xs text-gray-500">Mañana</span>
                </div>
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                  <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-700">Orden #00923</span>
                  </div>
                  <span class="text-xs text-gray-500">En 3 días</span>
                </div>
              </div>
            </div>
            
            <!-- Estadísticas de entregas -->
            <div class="border-t border-gray-200 pt-4 mt-4">
              <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                  <div class="text-lg font-semibold text-blue-600">12</div>
                  <div class="text-xs text-gray-500">Este mes</div>
                </div>
                <div>
                  <div class="text-lg font-semibold text-green-600">8</div>
                  <div class="text-xs text-gray-500">Completadas</div>
                </div>
                <div>
                  <div class="text-lg font-semibold text-orange-600">4</div>
                  <div class="text-xs text-gray-500">Pendientes</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer con información adicional -->
      <div class="mt-6 grid grid-cols-3 gap-6">
        <!-- Resumen de producción -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <h4 class="text-sm font-medium text-gray-700 mb-3">Resumen de Producción</h4>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Eficiencia promedio</span>
              <span class="text-sm font-medium text-green-600">87.5%</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Tiempo promedio/orden</span>
              <span class="text-sm font-medium text-blue-600">2.3h</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Órdenes completadas hoy</span>
              <span class="text-sm font-medium text-gray-900">15</span>
            </div>
          </div>
        </div>

        <!-- Estado del sistema -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <h4 class="text-sm font-medium text-gray-700 mb-3">Estado del Sistema</h4>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Servidor</span>
              <div class="flex items-center">
                <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                <span class="text-sm text-green-600">Operativo</span>
              </div>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Base de datos</span>
              <div class="flex items-center">
                <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                <span class="text-sm text-green-600">Operativo</span>
              </div>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Última sincronización</span>
              <span class="text-sm text-gray-500">{{ formatTime(currentTime) }}</span>
            </div>
          </div>
        </div>

        <!-- Accesos rápidos -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <h4 class="text-sm font-medium text-gray-700 mb-3">Accesos Rápidos</h4>
          <div class="grid grid-cols-2 gap-2">
            <button class="flex items-center justify-center p-2 text-sm bg-blue-50 text-blue-700 rounded hover:bg-blue-100 transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Nueva Orden
            </button>
            <button class="flex items-center justify-center p-2 text-sm bg-green-50 text-green-700 rounded hover:bg-green-100 transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              Reportes
            </button>
            <button class="flex items-center justify-center p-2 text-sm bg-orange-50 text-orange-700 rounded hover:bg-orange-100 transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              Configuración
            </button>
            <button class="flex items-center justify-center p-2 text-sm bg-purple-50 text-purple-700 rounded hover:bg-purple-100 transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Ayuda
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>