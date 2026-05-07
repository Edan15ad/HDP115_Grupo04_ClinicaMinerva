<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    correo: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar sesión" />

    <div class="flex min-h-screen bg-slate-950">
        <div class="hidden flex-1 items-center justify-center bg-gradient-to-br from-cyan-500 via-teal-500 to-emerald-500 p-12 lg:flex">
            <div class="max-w-lg text-white">
                <div class="mb-8 flex h-16 w-16 items-center justify-center rounded-3xl bg-white/20 backdrop-blur">
                    <i class="pi pi-heart-fill text-3xl"></i>
                </div>

                <h1 class="text-5xl font-black leading-tight">
                    Bienvenido a Clínica Minerva
                </h1>

                <p class="mt-6 text-lg leading-8 text-white/90">
                    Accede al panel según tu rol: paciente, recepcionista, laboratorio
                    o administrador.
                </p>

                <div class="mt-10 grid grid-cols-2 gap-4">
                    <div class="rounded-2xl bg-white/15 p-4 backdrop-blur">
                        <i class="pi pi-calendar mb-3 text-2xl"></i>
                        <p class="font-bold">Citas</p>
                        <p class="text-sm text-white/80">Agendamiento clínico</p>
                    </div>
                    <div class="rounded-2xl bg-white/15 p-4 backdrop-blur">
                        <i class="pi pi-file-pdf mb-3 text-2xl"></i>
                        <p class="font-bold">Resultados</p>
                        <p class="text-sm text-white/80">Reportes PDF</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-1 items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center lg:text-left">
                    <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-cyan-400 text-slate-950 lg:mx-0">
                        <i class="pi pi-sign-in text-2xl"></i>
                    </div>

                    <h2 class="text-3xl font-black text-white">Iniciar sesión</h2>
                    <p class="mt-2 text-slate-400">
                        Ingresa con tu correo y contraseña.
                    </p>
                </div>

                <form
                    @submit.prevent="submit"
                    class="space-y-5 rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur"
                >
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">
                            Correo electrónico
                        </label>
                        <InputText
                            v-model="form.correo"
                            type="email"
                            placeholder="tucorreo@dominio.com"
                            class="w-full"
                            :class="{ 'p-invalid': form.errors.correo }"
                        />
                        <small v-if="form.errors.correo" class="mt-1 block text-red-300">
                            {{ form.errors.correo }}
                        </small>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">
                            Contraseña
                        </label>
                        <Password
                            v-model="form.password"
                            placeholder="********"
                            class="w-full"
                            inputClass="w-full"
                            :feedback="false"
                            toggleMask
                            :class="{ 'p-invalid': form.errors.password }"
                        />
                        <small v-if="form.errors.password" class="mt-1 block text-red-300">
                            {{ form.errors.password }}
                        </small>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-slate-300">
                            <Checkbox v-model="form.remember" binary />
                            Recordarme
                        </label>

                        <Link
                            href="/forgot-password"
                            class="text-sm font-semibold text-cyan-300 hover:text-cyan-200"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>

                    <Button
                        type="submit"
                        label="Entrar al sistema"
                        icon="pi pi-arrow-right"
                        iconPos="right"
                        class="w-full justify-center rounded-2xl! border-0! bg-cyan-400! py-3! font-bold! text-slate-950! hover:bg-cyan-300!"
                        :loading="form.processing"
                    />

                    <p class="text-center text-sm text-slate-400">
                        ¿No tienes cuenta?
                        <Link href="/register" class="font-bold text-cyan-300 hover:text-cyan-200">
                            Regístrate
                        </Link>
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>