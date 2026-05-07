<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    nombre: '',
    apellido: '',
    correo: '',
    password: '',
    password_confirmation: '',
    rol: 'paciente',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Registro de paciente" />

    <div class="min-h-screen bg-slate-950 px-6 py-10 text-white">
        <div class="mx-auto grid min-h-[calc(100vh-80px)] max-w-6xl grid-cols-1 items-center gap-10 lg:grid-cols-2">
            <section>
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-4 py-2 text-sm text-cyan-100">
                    <i class="pi pi-user-plus"></i>
                    Registro de nuevo paciente
                </div>

                <h1 class="text-5xl font-black leading-tight">
                    Crea tu cuenta y agenda tus
                    <span class="text-cyan-300">exámenes clínicos</span>.
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">
                    Tu cuenta permite consultar citas, historial clínico y resultados
                    enviados por la Clínica Minerva.
                </p>

                <div class="mt-8 space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-400 text-slate-950">
                            <i class="pi pi-check"></i>
                        </div>
                        <p class="text-slate-200">Registro seguro de datos personales</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-400 text-slate-950">
                            <i class="pi pi-check"></i>
                        </div>
                        <p class="text-slate-200">Acceso a resultados e historial</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-400 text-slate-950">
                            <i class="pi pi-check"></i>
                        </div>
                        <p class="text-slate-200">Notificaciones por correo electrónico</p>
                    </div>
                </div>
            </section>

            <section class="rounded-4xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                <div class="mb-6">
                    <h2 class="text-3xl font-black">Registro</h2>
                    <p class="mt-1 text-slate-400">Completa tus datos para crear la cuenta.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">
                                Nombre
                            </label>
                            <InputText
                                v-model="form.nombre"
                                class="w-full"
                                placeholder="Juan"
                                :class="{ 'p-invalid': form.errors.nombre }"
                            />
                            <small v-if="form.errors.nombre" class="text-red-300">
                                {{ form.errors.nombre }}
                            </small>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">
                                Apellido
                            </label>
                            <InputText
                                v-model="form.apellido"
                                class="w-full"
                                placeholder="Pérez"
                                :class="{ 'p-invalid': form.errors.apellido }"
                            />
                            <small v-if="form.errors.apellido" class="text-red-300">
                                {{ form.errors.apellido }}
                            </small>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">
                            Correo electrónico
                        </label>
                        <InputText
                            v-model="form.correo"
                            type="email"
                            class="w-full"
                            placeholder="paciente@minerva.com"
                            :class="{ 'p-invalid': form.errors.correo }"
                        />
                        <small v-if="form.errors.correo" class="text-red-300">
                            {{ form.errors.correo }}
                        </small>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">
                            Contraseña
                        </label>
                        <Password
                            v-model="form.password"
                            class="w-full"
                            inputClass="w-full"
                            placeholder="********"
                            toggleMask
                            :class="{ 'p-invalid': form.errors.password }"
                        />
                        <small v-if="form.errors.password" class="text-red-300">
                            {{ form.errors.password }}
                        </small>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">
                            Confirmar contraseña
                        </label>
                        <Password
                            v-model="form.password_confirmation"
                            class="w-full"
                            inputClass="w-full"
                            placeholder="********"
                            :feedback="false"
                            toggleMask
                            :class="{ 'p-invalid': form.errors.password_confirmation }"
                        />
                        <small v-if="form.errors.password_confirmation" class="text-red-300">
                            {{ form.errors.password_confirmation }}
                        </small>
                    </div>

                    <Button
                        type="submit"
                        label="Crear cuenta"
                        icon="pi pi-user-plus"
                        class="w-full justify-center rounded-2xl! border-0! bg-cyan-400! py-3! font-bold! text-slate-950! hover:bg-cyan-300!"
                        :loading="form.processing"
                    />

                    <p class="text-center text-sm text-slate-400">
                        ¿Ya tienes cuenta?
                        <Link href="/login" class="font-bold text-cyan-300 hover:text-cyan-200">
                            Inicia sesión
                        </Link>
                    </p>
                </form>
            </section>
        </div>
    </div>
</template>