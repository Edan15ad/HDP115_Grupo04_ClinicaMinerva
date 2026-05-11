<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    nombres: '',
    apellidos: '',
    correo: '',
    dui: '',
    telefono: '',
    fecha_nacimiento: '',
    genero: '',
    direccion: '',
    password: '',
    password_confirmation: '',
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
                        <p class="text-slate-200">Registro completo de expediente clínico</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-400 text-slate-950">
                            <i class="pi pi-check"></i>
                        </div>
                        <p class="text-slate-200">Acceso a resultados en formato PDF</p>
                    </div>
                </div>
            </section>

            <section class="rounded-4xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                <div class="mb-6">
                    <h2 class="text-3xl font-black">Registro</h2>
                    <p class="mt-1 text-slate-400">Completa tu expediente clínico para crear la cuenta.</p>
                </div>

                <div v-if="form.errors.error" class="mb-4 rounded-xl bg-red-500/10 border border-red-500/20 p-4 text-sm text-red-300">
                    {{ form.errors.error }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Nombres</label>
                            <InputText v-model="form.nombres" class="w-full" placeholder="Tus nombres" :class="{ 'p-invalid': form.errors.nombres }" />
                            <small v-if="form.errors.nombres" class="text-red-300">{{ form.errors.nombres }}</small>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Apellidos</label>
                            <InputText v-model="form.apellidos" class="w-full" placeholder="Tus apellidos" :class="{ 'p-invalid': form.errors.apellidos }" />
                            <small v-if="form.errors.apellidos" class="text-red-300">{{ form.errors.apellidos }}</small>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Fecha de Nacimiento</label>
                            <input type="date" v-model="form.fecha_nacimiento" class="p-inputtext p-component w-full bg-slate-900/50 text-white" :class="{ 'p-invalid': form.errors.fecha_nacimiento }" />
                            <small v-if="form.errors.fecha_nacimiento" class="text-red-300">{{ form.errors.fecha_nacimiento }}</small>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Género</label>
                            <select v-model="form.genero" class="p-inputtext p-component w-full bg-slate-900/50 text-white" :class="{ 'p-invalid': form.errors.genero }">
                                <option value="" disabled selected>Selecciona tu género</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino">Femenino</option>
                                <option value="Otro">Otro</option>
                            </select>
                            <small v-if="form.errors.genero" class="text-red-300">{{ form.errors.genero }}</small>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">Correo electrónico</label>
                        <InputText v-model="form.correo" type="email" class="w-full" placeholder="paciente@minerva.com" :class="{ 'p-invalid': form.errors.correo }" />
                        <small v-if="form.errors.correo" class="text-red-300">{{ form.errors.correo }}</small>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">DUI (Sin guiones)</label>
                            <InputText v-model="form.dui" class="w-full font-mono" maxlength="9" placeholder="123456789" :class="{ 'p-invalid': form.errors.dui }" />
                            <small v-if="form.errors.dui" class="text-red-300 font-bold block mt-1">{{ form.errors.dui }}</small>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Teléfono</label>
                            <InputText v-model="form.telefono" class="w-full" maxlength="8" placeholder="77665544" :class="{ 'p-invalid': form.errors.telefono }" />
                            <small v-if="form.errors.telefono" class="text-red-300">{{ form.errors.telefono }}</small>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-200">Dirección</label>
                        <InputText v-model="form.direccion" class="w-full" placeholder="Tu dirección completa" :class="{ 'p-invalid': form.errors.direccion }" />
                        <small v-if="form.errors.direccion" class="text-red-300">{{ form.errors.direccion }}</small>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Contraseña</label>
                            <Password v-model="form.password" class="w-full" inputClass="w-full" placeholder="********" toggleMask :class="{ 'p-invalid': form.errors.password }" />
                            <small v-if="form.errors.password" class="text-red-300">{{ form.errors.password }}</small>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-200">Confirmar</label>
                            <Password v-model="form.password_confirmation" class="w-full" inputClass="w-full" placeholder="********" :feedback="false" toggleMask :class="{ 'p-invalid': form.errors.password_confirmation }" />
                        </div>
                    </div>

                    <Button type="submit" label="Crear cuenta clínica" icon="pi pi-user-plus" class="w-full justify-center rounded-2xl! border-0! bg-cyan-400! py-3! font-bold! text-slate-950! hover:bg-cyan-300!" :loading="form.processing" />

                    <p class="text-center text-sm text-slate-400">
                        ¿Ya tienes cuenta?
                        <Link href="/login" class="font-bold text-cyan-300 hover:text-cyan-200">Inicia sesión</Link>
                    </p>
                </form>
            </section>
        </div>
    </div>
</template>