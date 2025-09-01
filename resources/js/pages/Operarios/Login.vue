<!-- resources/js/Pages/Operarios/Login.vue -->

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10" style="background-color: #F4F4F4;">
        <Head title="Acceso Operarios" />

        <div class="bg-white p-10 w-[392px]">
            <div class="flex justify-center">
                <img 
                    :src="'/logo-teora.png'" 
                    alt="Logo" 
                    class="transition-all duration-300"
                />
            </div>

            <h2 class="text-black w-full text-center font-semibold text-[32px] mt-4">
                Acceso por Sector
            </h2>
            <p class="text-center text-sm mt-2" style="color: #5B5B5B;">
                Ingrese el código de su sector
            </p>

            <form @submit.prevent="handleSubmit" class="flex flex-col gap-6 mt-5">
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <label for="codigo" class="" style="color: #5B5B5B;">
                            Código de sector
                        </label>
                        <input
                            id="codigo"
                            v-model="form.codigo"
                            type="text"
                            required
                            class="py-2 border border-neutral-300 focus:outline-none text-black rounded-md px-2"
                            placeholder="Código del sector"
                            @input="form.codigo = $event.target.value.toUpperCase()"
                            autofocus
                        />
                        
                        <div v-if="form.errors.codigo" class="text-red-600 text-sm mt-1">
                            {{ form.errors.codigo }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mt-4 w-full py-2 px-4 text-white font-medium rounded-md transition-all duration-200 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                        style="background-color: #0D509C;"
                    >
                        <span v-if="form.processing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Verificando...
                        </span>
                        <span v-else>
                            Ingresar
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    codigo: ''
})

const handleSubmit = () => {
    form.post(route('sectores.operarios.login.post'))
}
</script>