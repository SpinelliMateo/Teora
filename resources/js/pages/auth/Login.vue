<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <!-- <AuthBase title="Log in to your account" description="Enter your email and password below to log in"> -->
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 " style="background-color: #F4F4F4;">
        <Head title="Log in" />

        <div class="bg-white p-10 w-[392px]">
            <div class="flex justify-center">
                <img 
                    :src="'/logo-teora.png'" 
                    alt="Logo" 
                    class="transition-all duration-300"
                />
            </div>
            <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
                {{ status }}
            </div>

            <h2 class="text-black w-full text-center font-semibold text-[32px] mt-4">Iniciar sesión</h2>
    
            <form @submit.prevent="submit" class="flex flex-col gap-6 mt-5">
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <label for="email" class="" style="color: #5B5B5B;">Email</label>
                        <input v-model="form.email" type="email" class="py-2 border border-neutral-300 focus:outline-none text-black rounded-md px-2">
                        <!-- <Label for="email">Email address</Label> -->
                        <!-- <Input
                            id="email"
                            type="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="email@example.com"
                        /> -->
                        <InputError :message="form.errors.email" />
                    </div>
    
                    <div class="grid gap-2">
                        <label for="password" class="" style="color: #5B5B5B;">Contraseña</label>
                        <input v-model="form.password" type="password" class="py-2 border border-neutral-300 focus:outline-none text-black rounded-md px-2">
                        <!-- <Label for="email">Email address</Label> -->
                        <!-- <Input
                            id="email"
                            type="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            v-model="form.email"
                            placeholder="email@example.com"
                        /> -->
                        <InputError :message="form.errors.password" />
                    </div>
    
                    <!-- <div class="flex items-center justify-between">
                        <Label for="remember" class="flex items-center space-x-3">
                            <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                            <span>Remember me</span>
                        </Label>
                    </div> -->
    
                    <Button type="submit" class="mt-4 w-full" style="background-color: #0D509C;" :tabindex="4" :disabled="form.processing">
                    <!-- <button> -->
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <span class="text-white">Iniciar sesión</span>
                    <!-- </button> -->
                    </Button>
                </div>
    
                <!-- <div class="text-center text-sm text-muted-foreground">
                    Don't have an account?
                    <TextLink :href="route('register')" :tabindex="5">Sign up</TextLink>
                </div> -->
            </form>
        </div>
    </div>
    <!-- </AuthBase> -->
</template>
