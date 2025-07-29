<script setup lang="ts">

import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import axios from 'axios';
import { jsPDF } from 'jspdf';

type Usuario = {
    id: number;
    name: string;
    apellido: string;
};

const generarPdf = () => {
    const doc = new jsPDF();

    // Configuración de colores
    const primaryColor = [13, 80, 156]; // #0D509C
    const grayColor = [91, 91, 91]; // #5B5B5B
    const lightGrayColor = [225, 229, 233]; // #F4F4F4
    const borderColor = [200, 200, 200];

    // Márgenes y dimensiones
    const margin = 15;
    const pageWidth = doc.internal.pageSize.width;
    const pageHeight = doc.internal.pageSize.height;
    const contentWidth = pageWidth - (margin * 2);

    let currentY = margin;

    // Función auxiliar para agregar texto con formato
    const addText = (text, x, y, options = {}) => {
        const {
            fontSize = 10,
            fontStyle = 'normal',
            color = [0, 0, 0],
            align = 'left',
            maxWidth = null
        } = options;

        doc.setFontSize(fontSize);
        doc.setFont('helvetica', fontStyle);
        doc.setTextColor(...color);

        if (maxWidth) {
            const lines = doc.splitTextToSize(text, maxWidth);
            doc.text(lines, x, y, { align });
            return lines.length * (fontSize * 0.35);
        } else {
            doc.text(text, x, y, { align });
            return fontSize * 0.35;
        }
    };

    // Función para agregar rectángulo con borde
    const addRect = (x, y, width, height, fillColor = null, strokeColor = null) => {
        if (fillColor) {
            doc.setFillColor(...fillColor);
        }
        if (strokeColor) {
            doc.setDrawColor(...strokeColor);
            doc.setLineWidth(0.5);
        }

        if (fillColor && strokeColor) {
            doc.rect(x, y, width, height, 'FD');
        } else if (fillColor) {
            doc.rect(x, y, width, height, 'F');
        } else if (strokeColor) {
            doc.rect(x, y, width, height, 'S');
        }
    };

    // Título principal centrado
    addText(`Servicio técnico N°${props.servicio_tecnico.id}`, pageWidth / 2, currentY + 10, {
        fontSize: 16,
        fontStyle: 'bold',
        align: 'center'
    });

    currentY += 12;

    // Crear el rectángulo principal con borde
    const mainRectHeight = 160; // Altura ajustable según contenido

    // Padding interno
    const innerMargin = margin + 8;
    const innerWidth = contentWidth - 16;
    currentY += 12;

    // Primera fila - 3 columnas
    const col1Width = innerWidth / 3;
    const col2Width = innerWidth / 3;
    const col3Width = innerWidth / 3;

    addText(`Fecha: ${props.servicio_tecnico.created_at}`, innerMargin, currentY, { fontSize: 9 });
    addText(`Serie: ${form.serie}`, innerMargin + col1Width, currentY, { fontSize: 9 });
    addText(`Factura: ${form.factura}`, innerMargin + col1Width + col2Width, currentY, { fontSize: 9 });

    currentY += 6;


    // Segunda fila (Modelo y Fecha salida)
    addText(`Modelo: ${modeloSeleccionado.value}`, innerMargin, currentY, { fontSize: 9 });
    let fechaSalida = form.fecha_salida ? form.fecha_salida.split('-').reverse().join('/') : '';
    addText(`Fecha de salida: ${fechaSalida}`, innerMargin + col1Width, currentY, { fontSize: 9 });
    currentY += 6;

    // Tercera fila (Contacto, Cliente, Factura)
    addText(`Contacto: ${form.contacto}`, innerMargin, currentY, { fontSize: 9 });
    addText(`Cliente: ${form.cliente_distribuidor}`, innerMargin + col1Width, currentY, { fontSize: 9 });
    addText(`Factura: ${form.factura}`, innerMargin + col1Width + col2Width, currentY, { fontSize: 9 });
    currentY += 6;

    // Cuarta fila (Dirección, Localidad, Teléfono)
    addText(`Dirección: ${form.direccion}`, innerMargin, currentY, { fontSize: 9 });
    addText(`Localidad: ${form.localidad}`, innerMargin + col1Width, currentY, { fontSize: 9 });
    addText(`Teléfono: ${form.telefono}`, innerMargin + col1Width + col2Width, currentY, { fontSize: 9 });
    currentY += 6;

    // Quinta fila (Reinc, Interno/Externo, Técnico)
    addText(`Reinc: ${form.reinc}`, innerMargin, currentY, { fontSize: 9 });
    addText(`Interno/Externo: ${form.interno_externo}`, innerMargin + col1Width, currentY, { fontSize: 9 });
    const tecnicoAsignado = props.usuarios?.find((u: any) => u.id === form.user_id);
    addText(`Técnico: ${tecnicoAsignado ? `${tecnicoAsignado.name || ''} ${tecnicoAsignado.apellido || ''}`.trim() : ''}`, innerMargin + col1Width + col2Width, currentY, { fontSize: 9 });
    currentY += 6;

    // Sexta fila (Problema, Subproblema)
    const problemaNombre = props.problemas?.find((p: any) => p.id === form.problema_id)?.nombre;
    addText(`Problema: ${problemaNombre || ''}`, innerMargin, currentY, { fontSize: 9 });
    const subproblemaNombre = subproblemas.value?.find((s: any) => s.id === form.subproblema_id)?.nombre;
    addText(`Subproblema: ${subproblemaNombre || ''}`, innerMargin + col1Width, currentY, { fontSize: 9 });
    currentY += 6;

    doc.setDrawColor(...borderColor);
    doc.setLineWidth(0.3);
    doc.line(innerMargin, currentY + 2, innerMargin + innerWidth, currentY + 2);

    currentY += 12;


    // Séptima fila - Estado y pagos (4 columnas)
    const col4Width = innerWidth / 4;
    addText(`Pagado: ${form.pagado ? 'realizado' : 'pendiente'}`, innerMargin + col4Width, currentY, { fontSize: 9 });
    addText(`Estado: ${form.estado || 'pendiente'}`, innerMargin + col4Width * 2, currentY, { fontSize: 9 });
    addText(`Importe: ${form.importe ? `$${form.importe}` : '$40,000.00'}`, innerMargin + col4Width * 3, currentY, { fontSize: 9 });

    currentY += 6;

    doc.setDrawColor(...borderColor);
    doc.setLineWidth(0.3);
    doc.line(innerMargin, currentY + 2, innerMargin + innerWidth, currentY + 2);
    currentY += 12;



    if (props.servicio_tecnico.actividades && props.servicio_tecnico.actividades.length > 0) {
        const tableStartY = currentY;
        const rowHeight = 15;

        // Usar mismo padding horizontal que el resto
        const tableX = innerMargin;
        const tableWidth = innerWidth;

        // Columnas ajustadas al nuevo ancho
        const fechaColWidth = 30;
        const responsableColWidth = 50;
        const actividadColWidth = tableWidth - fechaColWidth - responsableColWidth;

        // Encabezado
        addText('Fecha', tableX + 2, currentY + 10, {
            fontSize: 10,
            fontStyle: 'bold'
        });
        addText('Actividad', tableX + fechaColWidth + 2, currentY + 10, {
            fontSize: 10,
            fontStyle: 'bold'
        });
        addText('Responsable', tableX + fechaColWidth + actividadColWidth + 2, currentY + 10, {
            fontSize: 10,
            fontStyle: 'bold'
        });

        currentY += rowHeight;

        // Filas de datos
        props.servicio_tecnico.actividades.forEach((actividad, index) => {
            const isEven = index % 2 === 0;
            const backgroundColor = isEven ? lightGrayColor : [255, 255, 255];

            // Fondo de la fila
            addRect(tableX, currentY, tableWidth, rowHeight, backgroundColor);

            // Fecha
            const fechaActividad = new Date(actividad.created_at).toLocaleDateString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            addText(fechaActividad, tableX + 2, currentY + 10, { fontSize: 9 });

            // Actividad
            let actividadTexto = actividad.titulo;
            if (actividadTexto.length > 50) {
                actividadTexto = actividadTexto.substring(0, 50) + '...';
            }
            addText(actividadTexto, tableX + fechaColWidth + 2, currentY + 10, {
                fontSize: 9
            });
            // Responsable
            addText(
                actividad.user
                    ? `${actividad.user.name || ''} ${actividad.user.apellido || ''}`.trim()
                    : '',
                tableX + fechaColWidth + actividadColWidth + 2,
                currentY + 10,
                { fontSize: 9 }
            );

            currentY += rowHeight;

            // Nueva página si es necesario
            if (currentY > pageHeight - 40) {
                doc.addPage();
                currentY = margin;
            }
        });

    }

    // Guardar el PDF
    doc.save(`servicio-tecnico-${props.servicio_tecnico.id}.pdf`);
};

const { success, error, warning, info } = useToast();

const props = defineProps({
    servicio_tecnico: Object,
    usuarios: Array as () => Usuario[],
    problemas: Array,
    can: Object
})

console.log(props.servicio_tecnico);

// Normaliza fechas y reinc
function normalizeDate(fecha) {
    if (!fecha) return '';
    // Si ya es YYYY-MM-DD, no tocar
    if (/^\d{4}-\d{2}-\d{2}/.test(fecha)) return fecha;
    // Si es DD/MM/YYYY
    if (/^\d{2}\/\d{2}\/\d{4}/.test(fecha)) {
        return fecha.split('/').reverse().join('-');
    }
    return fecha;
}

const defaultForm = (props) => {
    return {
        ...props.servicio_tecnico,
        created_at: normalizeDate(props.servicio_tecnico.created_at),
        fecha_salida: normalizeDate(props.servicio_tecnico.fecha_salida),
        reinc: props.servicio_tecnico.reinc === 'No' ? 'No' : 'Si',
    };
}
const form = useForm(defaultForm(props));

const update_servicio_tecnico = (() => {
    form.put('/update_servicio_tecnico', {
        onError(errors) {
            const firstError = Object.values(errors)[0];
            if (firstError) {
                error(firstError); // tu función toast de error
            }
        },
        onSuccess() {
            success('Servicio técnico guardado correctamente.');
        }
    });
});

const loadingModelo = ref(false)
const modeloSeleccionado = ref(null)
const timerSerie = ref(null);
const create_actividad_modal = ref(false);
const actividad_selected = ref(null);

onMounted(async () => {
    modeloSeleccionado.value = props.servicio_tecnico.modelo.nombre_modelo
    await get_subproblemas_by_id(form.subproblema_id);
})

watch(() => form.serie, async (serie) => {
    loadingModelo.value = true;
    clearTimeout(timerSerie.value);
    timerSerie.value = setTimeout(async () => {
        try {
            const response = await axios.get(route('stock_by_n_serie', { n_serie: serie }))
            if (response.data.modelo) {
                form.modelo_id = response.data.modelo.id;
                modeloSeleccionado.value = response.data.modelo.nombre_modelo;
                form.fecha_salida = normalizeDate(response.data.fecha_salida?.substring(0, 10));
            } else {
                error('No se encontro el numero de serie.');
            }
        } catch (e) {
            console.error(e);
            modeloSeleccionado.value = null;
            form.fecha_salida = null;
            form.modelo_id = null;
        } finally {
            loadingModelo.value = false;
        }
    }, 500);
})

const form_actividad = useForm({
    titulo: null,
    descripcion: null,
    servicio_tecnico_id: null,
    actividad_id: null,
});

const create_actividad_servicio_tecnico = (() => {
    form_actividad.servicio_tecnico_id = props.servicio_tecnico.id;
    form_actividad.post('/create_actividad_servicio_tecnico', {
        onError(errors) {
            const firstError = Object.values(errors)[0];
            if (firstError) {
                error(firstError); // tu función toast de error
            }
        },
        onSuccess() {
            success('Actividad creada correctamente.');
            create_actividad_modal.value = false;
            form_actividad.reset()
        }
    });
});

let loading_subproblemas = ref(false);
let subproblemas = ref([]);
const get_subproblemas_by_id = async () => {
    try {
        loading_subproblemas.value = true;
        const response = await axios.get(route('get_subproblemas_by_id', { id: form.problema_id }));
        if (response.data.subproblemas) {
            subproblemas.value = response.data.subproblemas;
        }
    } catch (e) {
        console.error(e);
        error(e?.response?.data?.message || 'Error al obtener subproblemas');
    } finally {
        loading_subproblemas.value = false;
    }
}
</script>


<template>

    <Head title="Servicio técnico Detalle" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 mt-10">
                <button class="cursor-pointer" @click="router.get('/servicio-tecnico');">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 20L0 10L10 0L11.7812 1.75L4.78125 8.75H20V11.25H4.78125L11.7812 18.25L10 20Z"
                            fill="#626262" />
                    </svg>
                </button>
                <h1 class="text-[32px] font-bold text-gray-800">Servicio técnico {{ servicio_tecnico.id }}</h1>
            </div>

            <div class="flex items-center justify-end gap-4">
                <div class="flex items-center gap-2">
                    <button @click="generarPdf"
                        class="w-[180px] flex justify-center items-center py-2 bg-blue-600 text-white rounded-full cursor-pointer"
                        style="background-color: #0D509C;">
                        Imprimir
                    </button>
                    <button v-if="can.gestionar" @click="update_servicio_tecnico"
                        class="w-[180px] flex justify-center items-center py-2 bg-blue-600 text-white rounded-full cursor-pointer"
                        style="background-color: #0D509C;">
                        Guardar
                    </button>
                </div>
            </div>


            <div class="mt-4 bg-white p-6 rounded-md">
                <div class="flex items-center gap-6 mb-5">
                    <div class="flex-1">
                        <label for="id" class="block text-sm text-[#5B5B5B]">N°</label>
                        <input type="text" id="id" v-model="form.id" :disabled="!can?.gestionar"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                    </div>
                    <div class="flex-1">
                        <label for="fecha" class="block text-sm text-[#5B5B5B]">Fecha</label>
                        <input type="date" id="fecha" v-model="form.created_at" disabled
                            class="mt-1 p-2 w-full border border-gray-300 rounded-md bg-gray-100">
                    </div>
                    <div class="flex-1">
                        <label for="serie" class="block text-sm text-[#5B5B5B]">Serie</label>
                        <input type="text" id="serie" v-model="form.serie" :disabled="!can?.gestionar"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                    </div>
                    <div class="flex-1">
                        <label for="factura" class="block text-sm text-[#5B5B5B]">Factura</label>
                        <input type="text" id="factura" v-model="form.factura" :disabled="!can?.gestionar"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                    </div>
                </div>
                <div class="flex items-center gap-6 mb-5">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <label for="modelo" class="block text-sm text-[#5B5B5B]">Modelo</label>
                            <div v-if="loadingModelo" class="text-sm text-blue-600 font-medium flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 200 200">
                                    <radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125"
                                        gradientTransform="scale(1.5)">
                                        <stop offset="0" stop-color="#0D509C"></stop>
                                        <stop offset=".3" stop-color="#0D509C" stop-opacity=".9"></stop>
                                        <stop offset=".6" stop-color="#0D509C" stop-opacity=".6"></stop>
                                        <stop offset=".8" stop-color="#0D509C" stop-opacity=".3"></stop>
                                        <stop offset="1" stop-color="#0D509C" stop-opacity="0"></stop>
                                    </radialGradient>
                                    <circle transform-origin="center" fill="none" stroke="url(#a12)" stroke-width="15"
                                        stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0"
                                        cx="100" cy="100" r="70">
                                        <animateTransform type="rotate" attributeName="transform" calcMode="spline"
                                            dur="2" values="360;0" keyTimes="0;1" keySplines="0 0 1 1"
                                            repeatCount="indefinite"></animateTransform>
                                    </circle>
                                    <circle transform-origin="center" fill="none" opacity=".2" stroke="#0D509C"
                                        stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle>
                                </svg>
                            </div>
                        </div>
                        <input type="text" id="modelo" disabled v-model="modeloSeleccionado"
                            class="mt-1 p-2 w-full border border-gray-300 rounded-md bg-gray-100">
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <label for="fecha_salida" class="block text-sm text-[#5B5B5B]">Fecha de salida</label>
                            <div v-if="loadingModelo" class="text-sm text-blue-600 font-medium flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 200 200">
                                    <radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125"
                                        gradientTransform="scale(1.5)">
                                        <stop offset="0" stop-color="#0D509C"></stop>
                                        <stop offset=".3" stop-color="#0D509C" stop-opacity=".9"></stop>
                                        <stop offset=".6" stop-color="#0D509C" stop-opacity=".6"></stop>
                                        <stop offset=".8" stop-color="#0D509C" stop-opacity=".3"></stop>
                                        <stop offset="1" stop-color="#0D509C" stop-opacity="0"></stop>
                                    </radialGradient>
                                    <circle transform-origin="center" fill="none" stroke="url(#a12)" stroke-width="15"
                                        stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0"
                                        cx="100" cy="100" r="70">
                                        <animateTransform type="rotate" attributeName="transform" calcMode="spline"
                                            dur="2" values="360;0" keyTimes="0;1" keySplines="0 0 1 1"
                                            repeatCount="indefinite"></animateTransform>
                                    </circle>
                                    <circle transform-origin="center" fill="none" opacity=".2" stroke="#0D509C"
                                        stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle>
                                </svg>
                            </div>
                        </div>
                        <input type="date" id="fecha_salida" v-model="form.fecha_salida" disabled
                            class="mt-1 p-2 w-full border border-gray-300 rounded-md bg-gray-100">
                    </div>
                    <div class="flex-1">
                        <label for="contacto" class="block text-sm text-[#5B5B5B]">Contacto</label>
                        <input type="text" id="contacto" v-model="form.contacto" :disabled="!can?.gestionar"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                    </div>
                    <div class="flex-1">
                        <label for="cliente" class="block text-sm text-[#5B5B5B]">Cliente</label>
                        <input type="text" id="cliente" v-model="form.cliente_distribuidor" :disabled="!can?.gestionar"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                    </div>
                </div>
                <div class="flex items-center gap-6 mb-5">
                    <div class="flex-1">
                        <label for="direccion" class="block text-sm text-[#5B5B5B]">Dirección</label>
                        <input type="text" id="direccion" v-model="form.direccion" :disabled="!can?.gestionar"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                    </div>
                    <div class="flex-1">
                        <label for="localidad" class="block text-sm text-[#5B5B5B]">Localidad</label>
                        <input type="text" id="localidad" v-model="form.localidad" :disabled="!can?.gestionar"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                    </div>
                    <div class="flex-1">
                        <label for="telefono" class="block text-sm text-[#5B5B5B]">Teléfono</label>
                        <input type="text" id="telefono" v-model="form.telefono" :disabled="!can?.gestionar"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                    </div>
                    <div class="flex-1">
                        <label for="reinc" class="block text-sm text-[#5B5B5B]">Reinc.</label>
                        <select v-model="form.reinc" :disabled="!can?.gestionar"
                            :class="['mt-1 h-10 p-2 w-full border border-gray-300 rounded-md outline-none', !can?.gestionar ? 'bg-gray-100' : 'bg-white']"
                            name="reinc" id="reinc">
                            <option value="Si">Si</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-6 mb-5">
                    <div class="flex-1">
                        <label for="tecnico" class="block text-sm text-[#5B5B5B]">Técnico</label>
                        <select v-model="form.user_id" id="tecnico" :disabled="!can?.gestionar"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                            <option value="">Seleccionar técnico</option>
                            <option v-for="tecnico in usuarios" :key="tecnico.id" :value="tecnico.id">
                                {{ tecnico.name.charAt(0).toUpperCase() + tecnico.name.slice(1) }} {{
                                    tecnico.apellido.charAt(0).toUpperCase() + tecnico.apellido.slice(1) }}
                            </option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="interno_externo" class="block text-sm text-[#5B5B5B]">Interno / externo</label>
                        <select v-model="form.interno_externo" :disabled="!can?.gestionar"
                            :class="['mt-1 h-10 p-2 w-full border border-gray-300 rounded-md outline-none', !can?.gestionar ? 'bg-gray-100' : 'bg-white']"
                            name="interno_externo" id="interno_externo">
                            <option value="Interno">Interno</option>
                            <option value="Externo">Externo</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="problema" class="block text-sm text-[#5B5B5B]">Problema</label>
                        <select v-model="form.problema_id" @change="get_subproblemas_by_id" name="problema"
                            :disabled="!can?.gestionar" id="problema_id"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                            <option v-for="(problema, index) in problemas" :key="index" :value="problema.id">{{
                                problema.nombre }}</option>
                        </select>
                        <!-- <label for="problema" class="block text-sm text-[#5B5B5B]">Problema</label>
                        <input type="text" id="problema" v-model="form.problema" class="mt-1 p-2 w-full border border-gray-300 rounded-md" > -->
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <label for="sub_problema" class="block text-sm text-[#5B5B5B]">Sub problema</label>
                            <div v-if="loading_subproblemas"
                                class="text-sm text-blue-600 font-medium flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 200 200">
                                    <radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125"
                                        gradientTransform="scale(1.5)">
                                        <stop offset="0" stop-color="#0D509C"></stop>
                                        <stop offset=".3" stop-color="#0D509C" stop-opacity=".9"></stop>
                                        <stop offset=".6" stop-color="#0D509C" stop-opacity=".6"></stop>
                                        <stop offset=".8" stop-color="#0D509C" stop-opacity=".3"></stop>
                                        <stop offset="1" stop-color="#0D509C" stop-opacity="0"></stop>
                                    </radialGradient>
                                    <circle transform-origin="center" fill="none" stroke="url(#a12)" stroke-width="15"
                                        stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0"
                                        cx="100" cy="100" r="70">
                                        <animateTransform type="rotate" attributeName="transform" calcMode="spline"
                                            dur="2" values="360;0" keyTimes="0;1" keySplines="0 0 1 1"
                                            repeatCount="indefinite"></animateTransform>
                                    </circle>
                                    <circle transform-origin="center" fill="none" opacity=".2" stroke="#0D509C"
                                        stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle>
                                </svg>
                            </div>
                        </div>
                        <select v-model="form.subproblema_id" name="subproblema" id="subproblema_id"
                            :disabled="!can?.gestionar"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                            <option v-for="(sub, index) in subproblemas" :key="index" :value="sub.id">{{ sub.nombre }}
                            </option>
                        </select>
                        <!-- <label for="subproblema" class="block text-sm text-[#5B5B5B]">Sub problema</label>
                        <input type="text" id="subproblema" v-model="form.sub_problema" class="mt-1 p-2 w-full border border-gray-300 rounded-md" > -->
                    </div>
                </div>
                <div class="flex items-center gap-6 mb-5">
                    <div class="flex-1">
                        <label for="pagado" class="block text-sm text-[#5B5B5B]">Pagado</label>
                        <select v-model="form.pagado" :disabled="!can?.gestionar"
                            :class="['mt-1 h-10 p-2 w-full border border-gray-300 rounded-md outline-none', !can?.gestionar ? 'bg-gray-100' : 'bg-white']"
                            name="interno_externo" id="interno_externo">
                            <option :value="0">Pendiente</option>
                            <option :value="1">Realizado</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="estado" class="block text-sm text-[#5B5B5B]">Estado</label>
                        <select v-model="form.estado" :disabled="!can?.gestionar"
                            :class="['mt-1 h-10 p-2 w-full border border-gray-300 rounded-md outline-none', !can?.gestionar ? 'bg-gray-100' : 'bg-white']"
                            name="interno_externo" id="interno_externo">
                            <option value="Pendiente">Pendiente</option>
                            <option value="Finalizado">Finalizado</option>
                            <option value="Urgente">Urgente</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label for="importe" class="block text-sm text-[#5B5B5B]">Importe</label>
                        <input type="number" id="importe" v-model="form.importe" :disabled="!can?.gestionar"
                            :class="['mt-1 p-2 w-full border border-gray-300 rounded-md', !can?.gestionar ? 'bg-gray-100' : 'bg-white']">
                    </div>
                    <div class="flex-1">

                    </div>
                </div>
            </div>

            <div class="mt-4 bg-white p-6 rounded-md">
                <h2 class="text-[20px] font-bold mb-10">Actividad</h2>

                <!-- Contenedor alineado a la izquierda -->
                <div class="flex flex-col items-start w-full">
                    <!-- Círculo + texto en una fila -->
                    <div v-for="(actividad, index) in servicio_tecnico.actividades" :key="index"
                        class="flex items-start gap-2 w-full">
                        <!-- Punto -->
                        <div class="flex flex-col items-center flex-shrink-0">
                            <svg v-if="servicio_tecnico.actividades.length == index + 1" width="13" height="12"
                                viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="6.48535" cy="6" r="5.5" fill="#786E65" stroke="#AEAEAE" />
                            </svg>
                            <svg v-else width="13" height="13" viewBox="0 0 13 13" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="6.5" cy="6.5" r="5.5" fill="#D9D9D9" stroke="#AEAEAE" />
                            </svg>
                            <!-- Línea vertical debajo del círculo -->
                            <span v-if="servicio_tecnico.actividades.length != index + 1"
                                class="block w-[1px] h-12 bg-[#AEAEAE]"></span>
                        </div>

                        <!-- Contenido principal usando grid para alineación -->
                        <div class="grid grid-cols-[1fr_auto] items-start gap-4 w-full">
                            <!-- Texto al lado del círculo -->
                            <div class="relative bottom-5">
                                <span class="font-semibold text-[16px] text-[#7C7C7C]">{{ actividad.titulo }}</span>
                                <p class="text-[16px] text-[#4F4F4F]">{{ actividad.descripcion }} - {{
                                    actividad.updated_at }} - {{
                                        actividad.user?.name ? actividad.user.name : '' }} {{ actividad.user?.apellido ?
                                        actividad.user.apellido : '' }}</p>
                            </div>

                            <!-- Botón de editar alineado a la derecha -->
                            <!-- <div class="relative bottom-4 flex-shrink-0">
                                <button @click="create_actividad_modal = !create_actividad_modal; actividad_selected = actividad; form_actividad.titulo = actividad.titulo; form_actividad.descripcion = actividad.descripcion;" class="cursor-pointer hover:bg-neutral-100 duration-300 p-2 rounded-full">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17.71 4.0425C18.1 3.6525 18.1 3.0025 17.71 2.6325L15.37 0.2925C15 -0.0975 14.35 -0.0975 13.96 0.2925L12.12 2.1225L15.87 5.8725M0 14.2525V18.0025H3.75L14.81 6.9325L11.06 3.1825L0 14.2525Z" fill="#D9D9D9"/>
                                    </svg>
                                </button>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
            <button @click="create_actividad_modal = !create_actividad_modal;"
                class="w-[180px] flex justify-center items-center py-2 bg-blue-600 text-white rounded-full cursor-pointer mt-6"
                style="background-color: #0D509C;">
                Crear actividad
            </button>
        </div>

        <!-- Modal actividad -->
        <div v-if="create_actividad_modal"
            @click.self="create_actividad_modal = !create_actividad_modal; actividad_selected = null"
            class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div class="bg-white rounded-lg p-6 w-[400px] modal-animation overflow-y-auto max-h-[90vh]">
                <h2 class="text-xl font-semibold mb-4 text-black">Nueva actividad</h2>
                <!-- <h2 v-else class="text-xl font-semibold mb-4 text-black">Actualizar actividad</h2> -->

                <div class="w-full">
                    <div class="w-full flex flex-col gap-5 mb-4">
                        <div class="w-full">
                            <label for="titulo" class="block text-sm text-[#5B5B5B]">Titulo</label>
                            <input type="text" id="titulo" v-model="form_actividad.titulo"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                        <div class="w-full">
                            <label for="descripcion" class="block text-sm text-[#5B5B5B]">Descripción</label>
                            <input type="text" id="descripcion" v-model="form_actividad.descripcion"
                                class="mt-1 p-2 w-full border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <div class="flex justify-end space-x-2">
                            <button @click="create_actividad_modal = !create_actividad_modal; actividad_selected = null"
                                class="px-6 py-2 bg-white text-[#0D509C] border border-[#0D509C] hover:shadow-md rounded-full duration-300 cursor-pointer">Cancelar</button>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button @click="create_actividad_servicio_tecnico"
                                class="px-6 py-2 bg-[#0D509C] text-white rounded-full hover:shadow-md duration-300 cursor-pointer">Guardar</button>
                            <!-- <button v-else @click="update_actividad_servicio_tecnico(actividad_selected.id)" class="px-6 py-2 bg-[#0D509C] text-white rounded-full hover:shadow-md duration-300 cursor-pointer">Guardar</button> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.custom-v-select .vs__dropdown-toggle {
    padding-top: 0.35rem;
    padding-bottom: 0.35rem;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    cursor: pointer;
}
</style>