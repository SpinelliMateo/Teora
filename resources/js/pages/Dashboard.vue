<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, nextTick, computed, onUnmounted, reactive, watch } from 'vue';
import axios from 'axios';

interface Props {
  metricas: {
    prearmado: number;
    inyectado: number;
    armado: number;
    embalado: number;
  };
  alertas: Alert[];
  entregas: {
    ordenes: EntregaOrden[];
    estadisticas: {
      este_mes: number;
      completadas: number;
      pendientes: number;
      vencidas: number;
    };
  };
  actividades: Activity[]; 
}

const props = defineProps<Props>();

interface ChartData {
  label: string;
  value: number;
  date: string;
}

interface Alert {
  id: number;
  fecha_alerta: string;
  usuario: string;
  serie: string;
  modelo: string;
  motivo: string;
  tipo: 'error' | 'warning' | 'info';
  dias_transcurridos: number;
}

interface EntregaOrden {
  id: number;
  no_orden: string;
  fecha_finalizacion: string;
  fecha_finalizacion_formato: string;
  modelos: ModeloEntrega[];
  operarios: OperarioEntrega[];
  estado: string;
  estado_visual: 'completado' | 'vencido' | 'pendiente';
}

interface ModeloEntrega {
  nombre: string;
  modelo: string;
  cantidad: number;
}

interface OperarioEntrega {
  nombre: string;
  legajo: string;
}

interface CalendarDay {
  day: number;
  isCurrentMonth: boolean;
  isToday: boolean;
  hasDelivery: boolean;
  entregas: EntregaOrden[];
  tipoIndicador?: 'completado' | 'vencido' | 'pendiente' | null;
}

interface Tooltip {
  show: boolean;
  x: number;
  y: number;
  content: {
    dia: number;
    entregas: EntregaOrden[];
  } | null;
}

interface Activity {
  id: number;
  fecha: string;
  fecha_completa: string;
  descripcion: string;
  tipo: string;
  usuario: string;
  modulo: string | null;
  icono: string;
  color: string;
  tiempo_transcurrido: string;
  referencia_id: number | null;
  referencia_tipo: string | null;
  datos_adicionales: any;
}

interface ChartConfig {
  data: ChartData[];
  total: number;
  fechaInicio: string;
  fechaFin: string;
  loading: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
];

// Estados reactivos
const entregasDelMes = ref<Record<number, EntregaOrden[]>>({});
const selectedMonth = ref(new Date().getMonth());
const selectedYear = ref(new Date().getFullYear());
const currentTime = ref(new Date());
const isLoadingCalendar = ref(false); // Para mostrar loading durante el cambio
const tooltip = reactive<Tooltip>({
  show: false,
  x: 0,
  y: 0,
  content: null
});

// Configuración de gráficos
const graficos = reactive<Record<string, ChartConfig>>({
  prearmado: {
    data: [],
    total: 0,
    fechaInicio: '',
    fechaFin: '',
    loading: false
  },
  inyectado: {
    data: [],
    total: 0,
    fechaInicio: '',
    fechaFin: '',
    loading: false
  },
  armado: {
    data: [],
    total: 0,
    fechaInicio: '',
    fechaFin: '',
    loading: false
  },
  embalado: {
    data: [],
    total: 0,
    fechaInicio: '',
    fechaFin: '',
    loading: false
  }
});

// Fechas por defecto (últimos 6 meses)
const fechaPorDefecto = {
  inicio: new Date(new Date().setMonth(new Date().getMonth() - 5)),
  fin: new Date()
};

// Formularios de fechas para cada gráfico
const formsGraficos = reactive({
  prearmado: {
    fechaInicio: fechaPorDefecto.inicio.toISOString().split('T')[0],
    fechaFin: fechaPorDefecto.fin.toISOString().split('T')[0],
    mostrarForm: false
  },
  inyectado: {
    fechaInicio: fechaPorDefecto.inicio.toISOString().split('T')[0],
    fechaFin: fechaPorDefecto.fin.toISOString().split('T')[0],
    mostrarForm: false
  },
  armado: {
    fechaInicio: fechaPorDefecto.inicio.toISOString().split('T')[0],
    fechaFin: fechaPorDefecto.fin.toISOString().split('T')[0],
    mostrarForm: false
  },
  embalado: {
    fechaInicio: fechaPorDefecto.inicio.toISOString().split('T')[0],
    fechaFin: fechaPorDefecto.fin.toISOString().split('T')[0],
    mostrarForm: false
  }
});

const activities = ref<Activity[]>(props.actividades || []);
const cargandoActividades = ref(false);
const offsetActividades = ref(10); // Para paginación

const prearmadoCanvas = ref<HTMLCanvasElement | null>(null);
const inyectadoCanvas = ref<HTMLCanvasElement | null>(null);
const armadoCanvas = ref<HTMLCanvasElement | null>(null);
const embaladoCanvas = ref<HTMLCanvasElement | null>(null);

let chartInstances: any[] = [];
let timeInterval: NodeJS.Timeout | null = null;

const cargarEntregasDelMes = async (mes?: number, anio?: number) => {
  isLoadingCalendar.value = true;
  
  try {
    // Limpiar datos anteriores inmediatamente
    entregasDelMes.value = {};
    
    const response = await axios.get('/dashboard/entregas-mes', {
      params: {
        mes: mes || selectedMonth.value + 1,
        anio: anio || selectedYear.value
      }
    });
    
    // Usar nextTick para asegurar que la UI se actualice
    await nextTick();
    entregasDelMes.value = response.data;
    await nextTick(); // Segundo nextTick para garantizar la actualización
    
    console.log('Entregas del mes cargadas:', response.data);
  } catch (error) {
    console.error('Error cargando entregas del mes:', error);
  } finally {
    isLoadingCalendar.value = false;
  }
};

// Función para cargar datos del gráfico
const cargarDatosGrafico = async (tipo: string, fechaInicio?: string, fechaFin?: string) => {
  console.log(`Cargando datos para gráfico ${tipo}...`);
  graficos[tipo].loading = true;
  
  try {
    const params: any = { tipo };
    
    if (fechaInicio && fechaFin) {
      params.fecha_inicio = fechaInicio;
      params.fecha_fin = fechaFin;
    }
    
    const response = await axios.get('/dashboard/graficos', { params });
    console.log(`Datos recibidos para ${tipo}:`, response.data);
    
    graficos[tipo].data = response.data.datos;
    graficos[tipo].total = response.data.total;
    graficos[tipo].fechaInicio = response.data.fecha_inicio;
    graficos[tipo].fechaFin = response.data.fecha_fin;
    
    // Recrear el gráfico específico
    await recrearGrafico(tipo);
    
  } catch (error) {
    console.error(`Error cargando datos del gráfico ${tipo}:`, error);
  } finally {
    graficos[tipo].loading = false;
  }
};

// Función para aplicar filtro de fechas
const aplicarFiltroFechas = async (tipo: string) => {
  const form = formsGraficos[tipo];
  await cargarDatosGrafico(tipo, form.fechaInicio, form.fechaFin);
  form.mostrarForm = false;
};



// Función para recrear un gráfico específico
const recrearGrafico = async (tipo: string) => {
  await nextTick();
  
  let canvas;
  switch (tipo) {
    case 'prearmado':
      canvas = prearmadoCanvas.value;
      break;
    case 'inyectado':
      canvas = inyectadoCanvas.value;
      break;
    case 'armado':
      canvas = armadoCanvas.value;
      break;
    case 'embalado':
      canvas = embaladoCanvas.value;
      break;
  }
  
  if (canvas && graficos[tipo].data.length > 0) {
    // Destruir gráfico anterior si existe
    const existingChart = chartInstances.find(chart => chart.canvas === canvas);
    if (existingChart) {
      existingChart.destroy();
      chartInstances = chartInstances.filter(chart => chart !== existingChart);
    }
    
    const newChart = await crearGrafico(canvas, graficos[tipo].data, tipo.charAt(0).toUpperCase() + tipo.slice(1));
    if (newChart) {
      chartInstances.push(newChart);
    }
  }
};

// Computed properties
const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                   'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

const currentMonthName = computed(() => monthNames[selectedMonth.value]);

const alertsByType = computed(() => {
  return {
    error: props.alertas.filter(alert => alert.tipo === 'error').length,
    warning: props.alertas.filter(alert => alert.tipo === 'warning').length,
    info: props.alertas.filter(alert => alert.tipo === 'info').length,
  };
});

const getAlertClass = (tipo: string) => {
  switch (tipo) {
    case 'error': return 'bg-red-50';
    case 'warning': return 'bg-orange-50';
    case 'info': return '';
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

// Función para obtener clase del indicador
const getIndicadorClass = (tipoIndicador: string | null) => {
  switch (tipoIndicador) {
    case 'completado':
      return 'bg-green-500';
    case 'vencido':
      return 'bg-red-500';
    case 'pendiente':
      return 'bg-blue-500';
    default:
      return 'bg-gray-400';
  }
};

// Función para anillos del día actual
const getIndicadorRingClass = (tipoIndicador: string | null) => {
  switch (tipoIndicador) {
    case 'completado':
      return 'ring-green-500';
    case 'vencido':
      return 'ring-red-500';
    case 'pendiente':
      return 'ring-blue-500';
    default:
      return 'ring-gray-400';
  }
};

// Generar calendario del mes actual - MEJORADO para reactividad
const generateCalendar = computed(() => {
  const year = selectedYear.value;
  const month = selectedMonth.value;
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startingDayOfWeek = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
  const daysInMonth = lastDay.getDate();
  
  console.log(`Generando calendario para ${month + 1}/${year}`);
  console.log('Entregas del mes disponibles:', entregasDelMes.value);
  
  const calendar: CalendarDay[] = [];
  
  // Días del mes anterior
  const prevMonth = new Date(year, month - 1, 0);
  for (let i = startingDayOfWeek - 1; i >= 0; i--) {
    calendar.push({
      day: prevMonth.getDate() - i,
      isCurrentMonth: false,
      isToday: false,
      hasDelivery: false,
      entregas: [],
      tipoIndicador: null
    });
  }
  
  // Días del mes actual
  const today = new Date();
  for (let day = 1; day <= daysInMonth; day++) {
    const isToday = year === today.getFullYear() && 
                   month === today.getMonth() && 
                   day === today.getDate();
                   
    // Forzar reactividad usando el valor actual de entregasDelMes
    const entregasDelDia = entregasDelMes.value[day] || [];
    
    // Debug para días con entregas
    if (entregasDelDia.length > 0) {
      console.log(`Día ${day} tiene ${entregasDelDia.length} entregas:`, entregasDelDia);
    }
    
    // Determinar el tipo de indicador basado en el estado de las entregas
    let tipoIndicador: 'completado' | 'vencido' | 'pendiente' | null = null;
    if (entregasDelDia.length > 0) {
      const hasCompletado = entregasDelDia.some(e => e.estado_visual === 'completado');
      const hasVencido = entregasDelDia.some(e => e.estado_visual === 'vencido');
      
      if (hasCompletado && entregasDelDia.every(e => e.estado_visual === 'completado')) {
        tipoIndicador = 'completado';
      } else if (hasVencido) {
        tipoIndicador = 'vencido';
      } else {
        tipoIndicador = 'pendiente';
      }
      
      console.log(`Día ${day}: indicador = ${tipoIndicador}`);
    }
    
    calendar.push({
      day,
      isCurrentMonth: true,
      isToday,
      hasDelivery: entregasDelDia.length > 0,
      entregas: entregasDelDia,
      tipoIndicador
    });
  }
  
  // Días del mes siguiente para completar la semana
  const remainingDays = 42 - calendar.length;
  for (let day = 1; day <= remainingDays; day++) {
    calendar.push({
      day,
      isCurrentMonth: false,
      isToday: false,
      hasDelivery: false,
      entregas: [],
      tipoIndicador: null
    });
  }
  
  console.log('Calendario generado:', calendar.filter(d => d.hasDelivery));
  return calendar;
});
  
const showTooltip = (event: MouseEvent, day: CalendarDay) => {
  if (day.hasDelivery && day.entregas.length > 0) {
    const rect = (event.target as HTMLElement).getBoundingClientRect();
    tooltip.x = rect.left + rect.width / 2;
    tooltip.y = rect.top - 10;
    tooltip.content = {
      dia: day.day,
      entregas: day.entregas
    };
    tooltip.show = true;
  }
};

const hideTooltip = () => {
  tooltip.show = false;
  tooltip.content = null;
};

// Computed para próximas entregas
const proximasEntregas = computed(() => {
  const hoy = new Date();
  const manana = new Date(hoy);
  manana.setDate(hoy.getDate() + 1);
  
  return props.entregas.ordenes
    .filter(orden => {
      const fechaEntrega = new Date(orden.fecha_finalizacion);
      return fechaEntrega >= hoy;
    })
    .slice(0, 3)
    .map(orden => {
      const fechaEntrega = new Date(orden.fecha_finalizacion);
      const diffDays = Math.ceil((fechaEntrega.getTime() - hoy.getTime()) / (1000 * 60 * 60 * 24));
      
      let etiqueta = '';
      if (diffDays === 0) {
        etiqueta = 'Hoy';
      } else if (diffDays === 1) {
        etiqueta = 'Mañana';
      } else {
        etiqueta = `En ${diffDays} días`;
      }
      
      return {
        ...orden,
        etiqueta,
        color: diffDays === 0 ? 'blue' : diffDays === 1 ? 'orange' : 'green'
      };
    });
});

const previousMonth = async () => {
  if (selectedMonth.value === 0) {
    selectedMonth.value = 11;
    selectedYear.value--;
  } else {
    selectedMonth.value--;
  }
  await cargarEntregasDelMes();
};

const nextMonth = async () => {
  if (selectedMonth.value === 11) {
    selectedMonth.value = 0;
    selectedYear.value++;
  } else {
    selectedMonth.value++;
  }
  await cargarEntregasDelMes();
};

const getColorActividad = (color: string) => {
  const colores: Record<string, string> = {
    'blue': 'bg-blue-100 text-blue-600',
    'green': 'bg-green-100 text-green-600',
    'yellow': 'bg-yellow-100 text-yellow-600',
    'red': 'bg-red-100 text-red-600',
    'purple': 'bg-purple-100 text-purple-600',
    'gray': 'bg-gray-100 text-gray-600',
    'indigo': 'bg-indigo-100 text-indigo-600',
    'pink': 'bg-pink-100 text-pink-600',
    'orange': 'bg-orange-100 text-orange-600'
  };
  
  return colores[color] || 'bg-gray-100 text-gray-600';
};

const getIconoActividad = (icono: string) => {
  const iconos: Record<string, string> = {
    'plus': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>',
    'edit': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
    'trash': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
    'play': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293L12 11l.707-.707A1 1 0 0113.414 10H15M6 20a2 2 0 01-2-2V6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6z"></path></svg>',
    'pause': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
    'check': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
    'x': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
    'upload': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>',
    'download': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>',
    'settings': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>',
    'user': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
    'file': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>'
  };
  
  return iconos[icono] || '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
};

const cargarMasActividades = async () => {
  if (cargandoActividades.value) return;
  
  cargandoActividades.value = true;
  
  try {
    const response = await axios.get('/dashboard/actividades-recientes', {
      params: {
        limite: 10,
        offset: offsetActividades.value
      }
    });
    
    // Agregar las nuevas actividades a las existentes
    const nuevasActividades = response.data;
    activities.value.push(...nuevasActividades);
    
    // Actualizar el offset para la próxima carga
    offsetActividades.value += nuevasActividades.length;
    
  } catch (error) {
    console.error('Error cargando más actividades:', error);
  } finally {
    cargandoActividades.value = false;
  }
};

const getEstadoClass = (estadoVisual: string) => {
  switch (estadoVisual) {
    case 'completado':
      return 'text-green-600 bg-green-50 border-green-200';
    case 'vencido':
      return 'text-red-600 bg-red-50 border-red-200';
    case 'pendiente':
      return 'text-blue-600 bg-blue-50 border-blue-200';
    default:
      return 'text-gray-600 bg-gray-50 border-gray-200';
  }
};

const getEstadoTexto = (estadoVisual: string) => {
  switch (estadoVisual) {
    case 'completado':
      return 'Completado';
    case 'vencido':
      return 'Vencido';
    case 'pendiente':
      return 'Pendiente';
    default:
      return 'Estado desconocido';
  }
};

// Función para crear gráfico individual
const crearGrafico = async (canvas: HTMLCanvasElement, data: ChartData[], titulo: string) => {
  if (!canvas || !data.length) {
    console.log('Canvas o datos no disponibles para', titulo);
    return null;
  }

  try {
    // Importar Chart.js dinámicamente
    const { Chart, registerables } = await import('chart.js');
    Chart.register(...registerables);

    const ctx = canvas.getContext('2d');
    if (!ctx) return null;

    console.log(`Creando gráfico ${titulo} con datos:`, data);

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

// Watcher para reactividad adicional
watch([selectedMonth, selectedYear], async () => {
  await cargarEntregasDelMes();
}, { deep: true });

// Lifecycle hooks
onMounted(async () => {
  console.log('Dashboard montado, cargando datos...');
  
  updateTime();
  timeInterval = setInterval(updateTime, 1000);
  
  // Cargar datos iniciales de todos los gráficos
  await Promise.all([
    cargarDatosGrafico('prearmado'),
    cargarDatosGrafico('inyectado'),
    cargarDatosGrafico('armado'),
    cargarDatosGrafico('embalado')
  ]);
  
  // Cargar entregas del mes actual
  await cargarEntregasDelMes();
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
        <!-- Prearmado -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <p class="text-3xl font-light text-blue-500">{{ metricas.prearmado }}</p>
              <p class="text-lg text-gray-600 font-medium">Prearmado</p>
            </div>
          </div>
        </div>

        <!-- Inyectado -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <p class="text-3xl font-light text-blue-500">{{ metricas.inyectado }}</p>
              <p class="text-lg text-gray-600 font-medium">Inyectado</p>
            </div>
          </div>
        </div>

        <!-- Armado -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <p class="text-3xl font-light text-blue-500">{{ metricas.armado }}</p>
              <p class="text-lg text-gray-600 font-medium">Armado</p>
            </div>
          </div>
        </div>

        <!-- Embalado -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <p class="text-3xl font-light text-blue-500">{{ metricas.embalado }}</p>
              <p class="text-lg text-gray-600 font-medium">Embalado</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráficos de barras dinámicos -->
      <div class="grid grid-cols-2 gap-6">
        <!-- Prearmado Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Prearmado</h3>
            <div class="flex items-center space-x-2">
              <div class="text-sm text-gray-500">
                <span v-if="!graficos.prearmado.loading">
                  Total: {{ graficos.prearmado.total }} | 
                  {{ graficos.prearmado.fechaInicio }} - {{ graficos.prearmado.fechaFin }}
                </span>
                <span v-else>Cargando...</span>
              </div>
              <button 
                @click="formsGraficos.prearmado.mostrarForm = !formsGraficos.prearmado.mostrarForm"
                class="text-blue-600 hover:text-blue-800 text-sm px-2 py-1 rounded border border-blue-300 hover:border-blue-500"
              >
                Filtrar
              </button>
            </div>
          </div>
          
          <!-- Formulario de fechas -->
          <div v-if="formsGraficos.prearmado.mostrarForm" class="mb-4 p-4 bg-gray-50 rounded">
            <div class="flex items-end space-x-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                <input 
                  type="date" 
                  v-model="formsGraficos.prearmado.fechaInicio"
                  class="border border-gray-300 rounded px-3 py-2 text-sm"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                <input 
                  type="date" 
                  v-model="formsGraficos.prearmado.fechaFin"
                  class="border border-gray-300 rounded px-3 py-2 text-sm"
                >
              </div>
              <button 
                @click="aplicarFiltroFechas('prearmado')"
                class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
              >
                Aplicar
              </button>
              <button 
                @click="formsGraficos.prearmado.mostrarForm = false"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-400"
              >
                Cancelar
              </button>
            </div>
          </div>
          
          <div class="relative h-64">
            <div v-if="graficos.prearmado.loading" class="absolute inset-0 flex items-center justify-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
            <canvas v-show="!graficos.prearmado.loading" ref="prearmadoCanvas"></canvas>
          </div>
        </div>

        <!-- Inyectado Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Inyectado</h3>
            <div class="flex items-center space-x-2">
              <div class="text-sm text-gray-500">
                <span v-if="!graficos.inyectado.loading">
                  Total: {{ graficos.inyectado.total }} | 
                  {{ graficos.inyectado.fechaInicio }} - {{ graficos.inyectado.fechaFin }}
                </span>
                <span v-else>Cargando...</span>
              </div>
              <button 
                @click="formsGraficos.inyectado.mostrarForm = !formsGraficos.inyectado.mostrarForm"
                class="text-blue-600 hover:text-blue-800 text-sm px-2 py-1 rounded border border-blue-300 hover:border-blue-500"
              >
                Filtrar
              </button>
            </div>
          </div>
          
          <!-- Formulario de fechas -->
          <div v-if="formsGraficos.inyectado.mostrarForm" class="mb-4 p-4 bg-gray-50 rounded">
            <div class="flex items-end space-x-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                <input 
                  type="date" 
                  v-model="formsGraficos.inyectado.fechaInicio"
                  class="border border-gray-300 rounded px-3 py-2 text-sm"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                <input 
                  type="date" 
                  v-model="formsGraficos.inyectado.fechaFin"
                  class="border border-gray-300 rounded px-3 py-2 text-sm"
                >
              </div>
              <button 
                @click="aplicarFiltroFechas('inyectado')"
                class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
              >
                Aplicar
              </button>
              <button 
                @click="formsGraficos.inyectado.mostrarForm = false"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-400"
              >
                Cancelar
              </button>
            </div>
          </div>
          
          <div class="relative h-64">
            <div v-if="graficos.inyectado.loading" class="absolute inset-0 flex items-center justify-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
            <canvas v-show="!graficos.inyectado.loading" ref="inyectadoCanvas"></canvas>
          </div>
        </div>

        <!-- Armado Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Armado</h3>
            <div class="flex items-center space-x-2">
              <div class="text-sm text-gray-500">
                <span v-if="!graficos.armado.loading">
                  Total: {{ graficos.armado.total }} | 
                  {{ graficos.armado.fechaInicio }} - {{ graficos.armado.fechaFin }}
                </span>
                <span v-else>Cargando...</span>
              </div>
              <button 
                @click="formsGraficos.armado.mostrarForm = !formsGraficos.armado.mostrarForm"
                class="text-blue-600 hover:text-blue-800 text-sm px-2 py-1 rounded border border-blue-300 hover:border-blue-500"
              >
                Filtrar
              </button>
            </div>
          </div>
          
          <!-- Formulario de fechas -->
          <div v-if="formsGraficos.armado.mostrarForm" class="mb-4 p-4 bg-gray-50 rounded">
            <div class="flex items-end space-x-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                <input 
                  type="date" 
                  v-model="formsGraficos.armado.fechaInicio"
                  class="border border-gray-300 rounded px-3 py-2 text-sm"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                <input 
                  type="date" 
                  v-model="formsGraficos.armado.fechaFin"
                  class="border border-gray-300 rounded px-3 py-2 text-sm"
                >
              </div>
              <button 
                @click="aplicarFiltroFechas('armado')"
                class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
              >
                Aplicar
              </button>
              <button 
                @click="formsGraficos.armado.mostrarForm = false"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-400"
              >
                Cancelar
              </button>
            </div>
          </div>
          
          <div class="relative h-64">
            <div v-if="graficos.armado.loading" class="absolute inset-0 flex items-center justify-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
            <canvas v-show="!graficos.armado.loading" ref="armadoCanvas"></canvas>
          </div>
        </div>

        <!-- Embalado Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Embalado</h3>
            <div class="flex items-center space-x-2">
              <div class="text-sm text-gray-500">
                <span v-if="!graficos.embalado.loading">
                  Total: {{ graficos.embalado.total }} | 
                  {{ graficos.embalado.fechaInicio }} - {{ graficos.embalado.fechaFin }}
                </span>
                <span v-else>Cargando...</span>
              </div>
              <button 
                @click="formsGraficos.embalado.mostrarForm = !formsGraficos.embalado.mostrarForm"
                class="text-blue-600 hover:text-blue-800 text-sm px-2 py-1 rounded border border-blue-300 hover:border-blue-500"
              >
                Filtrar
              </button>
            </div>
          </div>
          
          <!-- Formulario de fechas -->
          <div v-if="formsGraficos.embalado.mostrarForm" class="mb-4 p-4 bg-gray-50 rounded">
            <div class="flex items-end space-x-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                <input 
                  type="date" 
                  v-model="formsGraficos.embalado.fechaInicio"
                  class="border border-gray-300 rounded px-3 py-2 text-sm"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                <input 
                  type="date" 
                  v-model="formsGraficos.embalado.fechaFin"
                  class="border border-gray-300 rounded px-3 py-2 text-sm"
                >
              </div>
              <button 
                @click="aplicarFiltroFechas('embalado')"
                class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
              >
                Aplicar
              </button>
              <button 
                @click="formsGraficos.embalado.mostrarForm = false"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-400"
              >
                Cancelar
              </button>
            </div>
          </div>
          
          <div class="relative h-64">
            <div v-if="graficos.embalado.loading" class="absolute inset-0 flex items-center justify-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
            <canvas v-show="!graficos.embalado.loading" ref="embaladoCanvas"></canvas>
          </div>
        </div>
      </div>

      <!-- Sección Alertas -->
      <div class="mt-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">

          
          <!-- Tabla de alertas -->
          <div class="overflow-x-auto max-h-96">
            <table class="w-full">
              <thead class="bg-blue-50 sticky top-0">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">FECHA ALERTA</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">USUARIO</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° DE SERIE</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MODELO</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MOTIVO</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DÍAS</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr 
                  v-for="alert in alertas" 
                  :key="alert.id" 
                  :class="getAlertClass(alert.tipo)"
                  class="hover:bg-opacity-80 transition-colors"
                >
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ alert.fecha_alerta }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ alert.usuario }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ alert.serie }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ alert.modelo }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm" :class="getAlertTextClass(alert.tipo)">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                          :class="{
                            'bg-red-100 text-red-800': alert.tipo === 'error',
                            'bg-yellow-100 text-yellow-800': alert.tipo === 'info'
                          }">
                      {{ alert.motivo }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span class="font-medium" :class="getAlertTextClass(alert.tipo)">
                      {{ alert.dias_transcurridos }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Sección inferior con Actividades recientes y Entregas -->
      <div class="grid grid-cols-2 gap-6 mt-6">
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
  <div class="px-6 py-4 border-b border-gray-200">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-medium text-gray-700">Actividades recientes</h3>
      <button 
        @click="cargarMasActividades" 
        class="text-blue-600 hover:text-blue-800 text-sm"
        :disabled="cargandoActividades"
      >
        <span v-if="cargandoActividades">Cargando...</span>
        <span v-else>Ver más</span>
      </button>
    </div>
  </div>
  
  <div class="p-6">
    <!-- Mensaje si no hay actividades -->
    <div v-if="!activities || activities.length === 0" class="text-center py-8">
      <div class="text-gray-400 mb-2">
        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
      </div>
      <p class="text-gray-500 text-sm">No hay actividades recientes</p>
    </div>

    <!-- Lista de actividades -->
    <div v-else class="space-y-4">
      <div 
        v-for="activity in activities.slice(0, 8)" 
        :key="activity.id"
        class="flex items-start space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group"
      >
        <!-- Icono de la actividad -->
        <div class="flex-shrink-0 mt-0.5">
          <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="getColorActividad(activity.color)">
            <div v-html="getIconoActividad(activity.icono)"></div>
          </div>
        </div>
        
        <!-- Contenido de la actividad -->
        <div class="flex-1 min-w-0">
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <p class="text-sm text-gray-900 font-medium">{{ activity.descripcion }}</p>
              
              <!-- Información adicional -->
              <div class="flex items-center mt-1 space-x-2 text-xs text-gray-500">
                <span>{{ activity.usuario }}</span>
                <span>•</span>
                <span>{{ activity.tiempo_transcurrido }}</span>
                <span v-if="activity.modulo">•</span>
                <span v-if="activity.modulo" class="px-2 py-0.5 bg-gray-100 rounded-full">{{ activity.modulo }}</span>
              </div>
              
              <!-- Datos adicionales si existen -->
              <div v-if="activity.datos_adicionales && Object.keys(activity.datos_adicionales).length > 0" 
                   class="mt-2 text-xs text-gray-400">
                <div v-for="(value, key) in activity.datos_adicionales" :key="key" class="inline-block mr-3">
                  <strong>{{ key }}:</strong> {{ value }}
                </div>
              </div>
            </div>
            
            <!-- Menú de opciones -->
            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
              <button class="text-gray-400 hover:text-gray-600 p-1">
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
    <div v-if="activities && activities.length > 8" class="mt-4 pt-4 border-t border-gray-200">
      <button 
        @click="cargarMasActividades"
        :disabled="cargandoActividades"
        class="w-full text-center text-sm text-gray-500 hover:text-gray-700 py-2 disabled:opacity-50"
      >
        <span v-if="cargandoActividades">
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500 inline" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Cargando más actividades...
        </span>
        <span v-else>Ver más actividades...</span>
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
                  :disabled="isLoadingCalendar"
                >
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                  </svg>
                </button>
                <span class="text-sm text-gray-600 font-medium min-w-24 text-center">{{ currentMonthName }} {{ selectedYear }}</span>
                <button 
                  @click="nextMonth"
                  class="p-1 hover:bg-gray-100 rounded transition-colors"
                  :disabled="isLoadingCalendar"
                >
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
          
          <div class="p-6">
            <!-- Loading indicator -->
            <div v-if="isLoadingCalendar" class="flex items-center justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <!-- Calendar content -->
            <div v-else>
              <!-- Leyenda -->
              <div class="flex items-center space-x-4 mb-4 text-xs">
                <div class="flex items-center space-x-1">
                  <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span class="text-gray-600">Completado</span>
                </div>
                <div class="flex items-center space-x-1">
                  <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                  <span class="text-gray-600">Pendiente</span>
                </div>
                <div class="flex items-center space-x-1">
                  <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                  <span class="text-gray-600">Vencido</span>
                </div>
              </div>
              
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
                  :key="`${selectedMonth}-${selectedYear}-${index}`"
                  class="relative p-2 h-8 flex items-center justify-center cursor-pointer transition-colors"
                  :class="{
                    'text-gray-400': !day.isCurrentMonth,
                    'text-gray-900': day.isCurrentMonth && !day.isToday,
                    'bg-blue-500 text-white rounded-full font-semibold': day.isToday,
                    'hover:bg-gray-100': day.isCurrentMonth && !day.isToday && !day.hasDelivery,
                    'hover:bg-gray-50': day.isCurrentMonth && day.hasDelivery
                  }"
                  @mouseenter="showTooltip($event, day)"
                  @mouseleave="hideTooltip"
                >
                  {{ day.day }}
                  <!-- Indicador de entrega con colores según estado -->
                  <div 
                    v-if="day.hasDelivery && !day.isToday" 
                    class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 rounded-full"
                    :class="getIndicadorClass(day.tipoIndicador)"
                  ></div>
                  <!-- Indicador alternativo para día actual con entregas -->
                  <div 
                    v-if="day.hasDelivery && day.isToday" 
                    class="absolute top-0.5 right-0.5 w-1.5 h-1.5 rounded-full"
                    :class="getIndicadorClass(day.tipoIndicador)"
                  ></div>
                </div>
              </div>
              
            
            </div>
          </div>
        </div>

        <!-- Tooltip -->
        <div 
          v-if="tooltip.show && tooltip.content" 
          class="fixed z-50 bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-sm pointer-events-none"
          :style="{
            left: tooltip.x + 'px',
            top: (tooltip.y - 10) + 'px',
            transform: 'translateX(-50%) translateY(-100%)'
          }"
        >
          <div class="text-sm font-medium text-gray-900 mb-2">
            Entregas del día {{ tooltip.content.dia }}
          </div>
          
          <div class="space-y-2">
            <div 
              v-for="entrega in tooltip.content.entregas" 
              :key="entrega.id"
              class="border rounded p-2"
              :class="getEstadoClass(entrega.estado_visual)"
            >
              <div class="flex justify-between items-start mb-1">
                <span class="text-sm font-medium">Orden #{{ entrega.no_orden }}</span>
                <span class="text-xs px-2 py-1 rounded-full border" :class="getEstadoClass(entrega.estado_visual)">
                  {{ getEstadoTexto(entrega.estado_visual) }}
                </span>
              </div>
              
              <div class="text-xs text-gray-600 space-y-1">
                <div><strong>Fecha:</strong> {{ entrega.fecha_finalizacion }}</div>
                
                <div v-if="entrega.modelos && entrega.modelos.length > 0">
                  <strong>Modelos:</strong>
                  <div class="ml-2">
                    <div v-for="modelo in entrega.modelos" :key="modelo.modelo" class="flex justify-between">
                      <span>{{ modelo.nombre }}</span>
                      <span class="text-gray-500">x{{ modelo.cantidad }}</span>
                    </div>
                  </div>
                </div>
                
                <div v-if="entrega.operarios && entrega.operarios.length > 0">
                  <strong>Operarios:</strong>
                  <div class="ml-2">
                    <div v-for="operario in entrega.operarios" :key="operario.legajo">
                      {{ operario.nombre }} ({{ operario.legajo }})
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>