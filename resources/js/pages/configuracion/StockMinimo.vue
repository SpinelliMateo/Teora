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
    stock_minimo: Array,
   
})
const form = useForm({
    id: '',
    stock_minimo: ''
});

let modal_stock_minimo = ref(false);
const modelo_seleccionado = ref(null);
const searchTerm = ref('');
const timer = ref(null);

const update_stock_minimo = () => {
    form.id = modelo_seleccionado.value.id;
    form.put('/update_stock_minimo', {
        onError(errors) {
            modal_stock_minimo.value = false;
            console.log(errors);
            // error(errors);
            const firstError = Object.values(errors)[0];
            if (firstError) {
                error(firstError); // tu función toast de error
            }
        },
        onSuccess() {
            modal_stock_minimo.value = false;
            console.log('Formulario enviado con éxito');
            success('Stock mínimo guardado correctamente.');
        }
    });
}

const handleSearch = () => {
    clearTimeout(timer.value);
    timer.value = setTimeout(() => {
        // console.log(searchTerm.value);
        router.get('/stock-minimo', {
            search: searchTerm.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 500);
}
</script>


<template>
    <Head title="Stock Detalle" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-4 p-4 px-20" style="background-color: #F4F4F4;">
            <div class="flex items-center gap-5 mt-10">
                <button class="cursor-pointer" @click="router.get('/configuracion');">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 20L0 10L10 0L11.7812 1.75L4.78125 8.75H20V11.25H4.78125L11.7812 18.25L10 20Z" fill="#626262"/>
                    </svg>
                </button>
                <h1 class="text-[32px] font-bold text-gray-800">Stock Mínimo</h1>
            </div>
            <div class="flex justify-end items-center ">
                <div class="relative">
                    <input
                        type="text"
                        placeholder="Buscar"
                        @input="handleSearch"
                        class="px-10 py-2 border rounded-full focus:outline-none text-black  placeholder-[#0D509C] w-[300px]" style="border-color: #0D509C;"
                        v-model="searchTerm"
                    />
                    <span class="absolute left-3 top-3 text-gray-400">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6.5 13C4.68333 13 3.146 12.3707 1.888 11.112C0.63 9.85333 0.000667196 8.316 5.29101e-07 6.5C-0.000666138 4.684 0.628667 3.14667 1.888 1.888C3.14733 0.629333 4.68467 0 6.5 0C8.31533 0 9.853 0.629333 11.113 1.888C12.373 3.14667 13.002 4.684 13 6.5C13 7.23333 12.8833 7.925 12.65 8.575C12.4167 9.225 12.1 9.8 11.7 10.3L17.3 15.9C17.4833 16.0833 17.575 16.3167 17.575 16.6C17.575 16.8833 17.4833 17.1167 17.3 17.3C17.1167 17.4833 16.8833 17.575 16.6 17.575C16.3167 17.575 16.0833 17.4833 15.9 17.3L10.3 11.7C9.8 12.1 9.225 12.4167 8.575 12.65C7.925 12.8833 7.23333 13 6.5 13ZM6.5 11C7.75 11 8.81267 10.5627 9.688 9.688C10.5633 8.81333 11.0007 7.75067 11 6.5C10.9993 5.24933 10.562 4.187 9.688 3.313C8.814 2.439 7.75133 2.00133 6.5 2C5.24867 1.99867 4.18633 2.43633 3.313 3.313C2.43967 4.18967 2.002 5.252 2 6.5C1.998 7.748 2.43567 8.81067 3.313 9.688C4.19033 10.5653 5.25267 11.0027 6.5 11Z" fill="#0D509C"/>
                        </svg>
                    </span>
                </div>
            </div>
        

            <div class="overflow-x-auto">
                <table class="w-full bg-white overflow-hidden">
                    <thead class="bg-[#E1E5E9]">
                        <tr>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Modelo</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Nombre</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider">Stock Mínimo</th>
                            <th class="py-3 px-4 text-center text-sm font-medium text-gray-600 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="(item, index) in stock_minimo.data" :key="index" class="">
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.modelo.modelo }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.modelo.nombre_modelo }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">{{ item.stock_minimo }}</td>
                            <td class="py-3 px-4 text-sm text-center text-gray-800">
                                <button @click="modal_stock_minimo = !modal_stock_minimo; modelo_seleccionado = item; form.stock_minimo = item.stock_minimo" class="cursor-pointer">
                                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.71 4.0425C18.1 3.6525 18.1 3.0025 17.71 2.6325L15.37 0.2925C15 -0.0975 14.35 -0.0975 13.96 0.2925L12.12 2.1225L15.87 5.8725M0 14.2525V18.0025H3.75L14.81 6.9325L11.06 3.1825L0 14.2525Z" fill="#D9D9D9"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-center items-center mt-4">
                <button
                    :disabled="!props.stock_minimo.prev_page_url"
                    @click="$inertia.get(props.stock_minimo.prev_page_url, { fecha: fecha })"
                    class="px-2 py-1 cursor-pointer hover:bg-[#0D509C] hover:text-white text-black duration-300  rounded disabled:opacity-50"
                >
                    < 
                </button>

                <span class="text-gray-700 px-2">{{ props.stock_minimo.current_page }} de {{ props.stock_minimo.last_page }}</span>

                <button
                    :disabled="!props.stock_minimo.next_page_url"
                    @click="$inertia.get(props.stock_minimo.next_page_url)"
                    class="px-2 py-1 cursor-pointer hover:bg-[#0D509C] hover:text-white text-black duration-300  rounded disabled:opacity-50"
                >
                    >
                </button>
            </div>
        </div>
    </AppLayout>

    <div v-if="modal_stock_minimo" @click.self="modal_stock_minimo = !modal_stock_minimo; modelo_seleccionado = null" class="fixed inset-0 bg-opacity-50 flex justify-center items-center z-50" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="bg-white rounded-lg p-6 w-96 modal-animation">
            <h2 class="text-xl font-semibold mb-4 text-black">Modelo {{ modelo_seleccionado.modelo.nombre_modelo }}</h2>

            <div>
                <div class="mb-4">
                    <label for="stock_minimo" class="block text-sm font-medium text-gray-700">Cantidad</label>
                    <input type="number" id="stock_minimo" v-model="form.stock_minimo"  class="mt-1 p-2 w-full border border-gray-300 rounded-md text-black" placeholder="10">
                </div>

                <div class="flex justify-end space-x-2">
                    <button @click="update_stock_minimo" class="px-4 py-2 bg-[#0D509C] text-white rounded-md duration-300 hover:bg-theme-600 cursor-pointer">Guardar</button>
                </div>
            </div>
        </div>
    </div>
   
   
</template>
