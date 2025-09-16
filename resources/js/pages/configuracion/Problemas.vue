<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const { success, error, warning, info } = useToast();

const props = defineProps({
    problemas: Array,
})

const form = useForm({
    id: '',
    problema: '',
    subproblemas: [''],
});

let modal_create_problema = ref(false);

const agregarSubproblema = () => {
    form.subproblemas.push('');
};

const eliminarSubproblema = (index: number) => {
    if (form.subproblemas.length > 1) {
        form.subproblemas.splice(index, 1);
    }
};

const limpiarFormulario = () => {
    form.reset();
    form.subproblemas = [''];
};

let loading_new_problema = ref(false);

// Función para crear el problema
const create_problema = () => {
    loading_new_problema.value = true;
    const subproblemasLimpios = form.subproblemas.filter(sub => sub.trim() !== '');
    
    if (!form.problema.trim()) {
        error('El problema es requerido');
        return;
    }
    
    if (subproblemasLimpios.length === 0) {
        error('Debe agregar al menos un subproblema');
        return;
    }
    
    form.subproblemas = subproblemasLimpios;
    
    form.post(route('create_problema'), {
        onError(errors) {
            loading_new_problema.value = false;
            modal_create_problema.value = false;
            console.log(errors);
            const firstError = Object.values(errors)[0];
            if (firstError) {
                error(firstError);
            }
        },
        onSuccess() {
            loading_new_problema.value = false;
            modal_create_problema.value = false;
            limpiarFormulario();
            success('Problema y subproblemas creados correctamente.');
        }
    });
}

// Función para cerrar el modal
const cerrarModal = () => {
    modal_create_problema.value = false;
    limpiarFormulario();
};


// ====================================== //
const modal_edit_problema = ref(false);

// Cargar el problema seleccionado en el formulario
const abrirModalEditar = (problema) => {
    form.id = problema.id;
    form.problema = problema.nombre;
    form.subproblemas = problema.subproblemas.map(s => s.nombre);
    modal_edit_problema.value = true;
};

const cerrarModalEditar = () => {
    modal_edit_problema.value = false;
    limpiarFormulario();
};

let loading_update_problema = ref(false);

const update_problema = () => {
    loading_update_problema.value = true;
    const subproblemasLimpios = form.subproblemas.filter(sub => sub.trim() !== '');

    if (!form.problema.trim()) {
        error('El problema es requerido');
        return;
    }

    if (subproblemasLimpios.length === 0) {
        error('Debe agregar al menos un subproblema');
        return;
    }

    form.subproblemas = subproblemasLimpios;
   
    form.put(route('update_problema'), {
        onSuccess: () => {
            loading_update_problema.value = false;
            cerrarModalEditar();
            success('Problema actualizado correctamente');
        },
        onError: (errors) => {
            loading_update_problema.value = false;
            const firstError = Object.values(errors)[0];
            if (firstError) error(firstError);
        }
    });
};

let modal_delete_problema = ref(false);
let loading_delete_problema = ref(false);
const delete_problema = () =>{
    loading_delete_problema.value = true;
    form.id = form.problema.id;
    form.delete(route('delete_problema'), {
        onSuccess: () => {
            loading_delete_problema.value = false;
            limpiarFormulario();
            modal_delete_problema.value = false;
            success('Problema eliminado correctamente');
        },
        onError: (errors) => {
            loading_delete_problema.value = false;
            limpiarFormulario();
            const firstError = Object.values(errors)[0];
            if (firstError) error(firstError);
        }
    });
}


</script>

<template>
    <Head title="Stock Detalle" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-5 lg:px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 lg:mt-10">
                <button class="cursor-pointer" @click="router.get('/configuracion');">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 20L0 10L10 0L11.7812 1.75L4.78125 8.75H20V11.25H4.78125L11.7812 18.25L10 20Z" fill="#626262"/>
                    </svg>
                </button>
                <h1 class="text-[32px] font-bold text-gray-800">Problemas y subproblemas de servicios técnicos</h1>
            </div>
            
            <div class="flex items-center lg:justify-end gap-4">
                <button @click="modal_create_problema = true;" class="flex items-center justify-center w-full lg:w-[200px] gap-2 px-6 py-2 text-white rounded-full cursor-pointer" style="background-color: #0D509C;">
                    Añadir Problema
                </button>
            </div>

            <div class="overflow-x-auto">
                <table v-if="problemas?.length > 0" class="w-full bg-white overflow-hidden">
                    <thead class="bg-[#E1E5E9]">
                        <tr>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">PROBLEMA</th>
                            <th class="py-3 px-4 text-left text-sm font-medium text-gray-600 uppercase tracking-wider">SUBPROBLEMAS</th>
                            <th class="py-3 px-4 text-right text-sm font-medium text-gray-600 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="(item, index) in problemas" :key="index" class="">
                            <td class="py-3 px-4 text-sm text-left">{{ item.nombre }}</td>
                            <td class="py-3 px-4 text-sm text-left ">
                                <span v-for="(sub, index) in item.subproblemas" :key="index">
                                    {{ sub.nombre }}<span v-if="index < item.subproblemas.length - 1">, </span>
                                </span>
                            </td>

                            <td class="py-3 px-4 text-sm text-right flex items-center gap-4">
                                <button @click="modal_delete_problema = true; form.problema = item" class="cursor-pointer hover:opacity-70">
                                    <svg width="14" height="18" viewBox="0 0 14 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M14 1H10.5L9.5 0H4.5L3.5 1H0V3H14M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16Z" fill="#D9D9D9"/>
                                    </svg>
                                </button>
                                <button  @click="abrirModalEditar(item)" class="cursor-pointer hover:opacity-70">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17.71 4.0425C18.1 3.6525 18.1 3.0025 17.71 2.6325L15.37 0.2925C15 -0.0975 14.35 -0.0975 13.96 0.2925L12.12 2.1225L15.87 5.8725M0 14.2525V18.0025H3.75L14.81 6.9325L11.06 3.1825L0 14.2525Z" fill="#D9D9D9"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-else class="text-lg text-neutral-400 text-center mt-10">No hay registros.</div>
            </div>
        </div>
    </AppLayout>

    <!-- Modal para crear problema -->
    <div v-if="modal_create_problema" @click.self="cerrarModal" class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="w-[644px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-black">Nuevo Problema</h2>
            </div>

            <div class="mb-6">
                <label for="problema" class="block text-sm font-medium text-[#5B5B5B]">Problema</label>
                <div class="flex items-center gap-4">
                    <input type="text" id="problema" v-model="form.problema"  class="mt-1 p-2 w-full h-[40px] border border-gray-300 rounded-md text-black">
                    <span @click="limpiarFormulario">
                        <svg width="14" height="18" viewBox="0 0 14 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 1H10.5L9.5 0H4.5L3.5 1H0V3H14M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16Z" fill="#0D509C"/>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="mb-6">
                <label for="problema" class="block text-sm font-medium text-[#5B5B5B]">Subproblema</label>

                <div class="space-y-3">
                    <div
                        v-for="(subproblema, index) in form.subproblemas"
                        :key="index"
                        class="flex items-center gap-3"
                    >
                        <div class="flex-1">
                            <input
                                type="text"
                                v-model="form.subproblemas[index]"
                                class="mt-1 p-2 w-full h-[40px] border border-gray-300 rounded-md text-black"
                            />
                        </div>
                        <button 
                            @click="eliminarSubproblema(index)" 
                            type="button" 
                            class=" hover:bg-red-50 rounded-md "
                            :disabled="form.subproblemas.length === 1"
                            :class="{ 'opacity-50 cursor-not-allowed': form.subproblemas.length === 1 }"
                        >
                            <svg width="14" height="18" viewBox="0 0 14 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 1H10.5L9.5 0H4.5L3.5 1H0V3H14M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16Z" fill="#0D509C"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="w-full text-end text-[#0D509C] mt-4">
                    <button
                        @click="agregarSubproblema"
                        type="button"
                        class="cursor-pointer"
                    >
                            + Agregar Subproblema
                    </button>
                </div>
            </div>

            <div class="flex justify-between items-center pt-4">
                <button 
                    @click="cerrarModal" 
                    type="button"
                    class="w-[173px] py-2 border border-[#0D509C] text-[#0D509C] hover:shadow-lg duration-300 rounded-full cursor-pointer"
                >
                    Volver
                </button>
                <button v-if="!loading_new_problema"
                    type="button"
                    class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full disabled:opacity-50 hover:shadow-lg duration-300 disabled:cursor-not-allowed cursor-pointer"
                    @click="create_problema"
                >
                    Guardar
                </button>

                <button v-if="loading_new_problema" class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full disabled:opacity-50 hover:shadow-lg duration-300 disabled:cursor-not-allowed cursor-pointer flex justify-center">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125" gradientTransform="scale(1.5)"><stop offset="0" stop-color="#FFFFFF"></stop><stop offset=".3" stop-color="#FFFFFF" stop-opacity=".9"></stop><stop offset=".6" stop-color="#FFFFFF" stop-opacity=".6"></stop><stop offset=".8" stop-color="#FFFFFF" stop-opacity=".3"></stop><stop offset="1" stop-color="#FFFFFF" stop-opacity="0"></stop></radialGradient><circle transform-origin="center" fill="none" stroke="url(#a12)" stroke-width="15" stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0" cx="100" cy="100" r="70"><animateTransform type="rotate" attributeName="transform" calcMode="spline" dur="2" values="360;0" keyTimes="0;1" keySplines="0 0 1 1" repeatCount="indefinite"></animateTransform></circle><circle transform-origin="center" fill="none" opacity=".2" stroke="#FFFFFF" stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal para editar problema -->
    <div v-if="modal_edit_problema" @click.self="cerrarModalEditar" class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="w-[644px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-black">Editar Problema</h2>
            </div>

            <div class="mb-6">
                <label for="problema" class="block text-sm font-medium text-[#5B5B5B]">Problema</label>
                <div class="flex items-center gap-4">
                    <input type="text" id="problema" v-model="form.problema"  class="mt-1 p-2 w-full h-[40px] border border-gray-300 rounded-md text-black">
                    <span @click="limpiarFormulario" class="cursor-pointer">
                        <svg width="14" height="18" viewBox="0 0 14 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 1H10.5L9.5 0H4.5L3.5 1H0V3H14M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16Z" fill="#0D509C"/>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-[#5B5B5B]">Subproblemas</label>

                <div v-for="(sub, index) in form.subproblemas" :key="index" class="flex items-center gap-3 mt-2">
                    <input
                        type="text"
                        v-model="form.subproblemas[index]"
                        class="p-2 w-full h-[40px] border border-gray-300 rounded-md text-black"
                    />
                    <button @click="eliminarSubproblema(index)" type="button" :disabled="form.subproblemas.length === 1" class="hover:bg-red-50 rounded-md cursor-pointer" :class="{ 'opacity-50 cursor-not-allowed': form.subproblemas.length === 1 }">
                        <svg width="14" height="18" viewBox="0 0 14 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 1H10.5L9.5 0H4.5L3.5 1H0V3H14M1 16C1 16.5304 1.21071 17.0391 1.58579 17.4142C1.96086 17.7893 2.46957 18 3 18H11C11.5304 18 12.0391 17.7893 12.4142 17.4142C12.7893 17.0391 13 16.5304 13 16V4H1V16Z" fill="#0D509C"/>
                        </svg>
                    </button>
                </div>

                <div class="w-full text-end text-[#0D509C] mt-4">
                    <button @click="agregarSubproblema" type="button" class="cursor-pointer">+ Agregar Subproblema</button>
                </div>
            </div>

            <div class="flex justify-between items-center pt-4">
                <button @click="cerrarModalEditar" type="button" class="w-[173px] py-2 border border-[#0D509C] text-[#0D509C] rounded-full hover:shadow-lg duration-300 cursor-pointer">Volver</button>
                <button v-if="!loading_update_problema" type="button" @click="update_problema" class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full disabled:opacity-50 hover:shadow-lg duration-300 disabled:cursor-not-allowed cursor-pointer">Guardar</button>
                <button v-if="loading_update_problema" class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full disabled:opacity-50 hover:shadow-lg duration-300 disabled:cursor-not-allowed cursor-pointer flex justify-center">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125" gradientTransform="scale(1.5)"><stop offset="0" stop-color="#FFFFFF"></stop><stop offset=".3" stop-color="#FFFFFF" stop-opacity=".9"></stop><stop offset=".6" stop-color="#FFFFFF" stop-opacity=".6"></stop><stop offset=".8" stop-color="#FFFFFF" stop-opacity=".3"></stop><stop offset="1" stop-color="#FFFFFF" stop-opacity="0"></stop></radialGradient><circle transform-origin="center" fill="none" stroke="url(#a12)" stroke-width="15" stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0" cx="100" cy="100" r="70"><animateTransform type="rotate" attributeName="transform" calcMode="spline" dur="2" values="360;0" keyTimes="0;1" keySplines="0 0 1 1" repeatCount="indefinite"></animateTransform></circle><circle transform-origin="center" fill="none" opacity=".2" stroke="#FFFFFF" stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de eliminar -->
    <div v-if="modal_delete_problema" @click.self="modal_delete_problema = false; form.reset()" class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50" style="background-color: rgba(0, 0, 0, 0.5);">
    <div class="w-[400px] bg-white rounded-lg p-6 max-h-[90vh] overflow-y-auto modal-animation">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">¿Estás seguro de eliminar el problema <span class="font-bold">{{form.problema.nombre}}</span>?</h2>

        <div class="flex justify-between gap-4">
        <button @click="modal_delete_problema = false; form.reset()"
                class="w-[173px] py-2 bg-gray-200 text-gray-800 rounded-full cursor-pointer hover:shadow-md duration-300">
            Cancelar
        </button>
        <button v-if="!loading_delete_problema" @click="delete_problema"
                class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full disabled:opacity-50 hover:shadow-lg duration-300 disabled:cursor-not-allowed cursor-pointer">
            Eliminar
        </button>
        <button v-if="loading_delete_problema" class="w-[173px] py-2 bg-[#0D509C] text-white rounded-full disabled:opacity-50 hover:shadow-lg duration-300 disabled:cursor-not-allowed cursor-pointer flex justify-center">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125" gradientTransform="scale(1.5)"><stop offset="0" stop-color="#FFFFFF"></stop><stop offset=".3" stop-color="#FFFFFF" stop-opacity=".9"></stop><stop offset=".6" stop-color="#FFFFFF" stop-opacity=".6"></stop><stop offset=".8" stop-color="#FFFFFF" stop-opacity=".3"></stop><stop offset="1" stop-color="#FFFFFF" stop-opacity="0"></stop></radialGradient><circle transform-origin="center" fill="none" stroke="url(#a12)" stroke-width="15" stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0" cx="100" cy="100" r="70"><animateTransform type="rotate" attributeName="transform" calcMode="spline" dur="2" values="360;0" keyTimes="0;1" keySplines="0 0 1 1" repeatCount="indefinite"></animateTransform></circle><circle transform-origin="center" fill="none" opacity=".2" stroke="#FFFFFF" stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle></svg>
        </button>
        </div>
    </div>
    </div>

</template>