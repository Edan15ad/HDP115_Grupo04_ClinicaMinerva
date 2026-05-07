<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Clínica Minerva" />

    <div class="min-h-screen bg-slate-950 text-white">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 h-96 w-96 rounded-full bg-cyan-500/20 blur-3xl"></div>
            <div class="absolute top-80 -left-40 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
        </div>

        <header class="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-6 py-6">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-400 text-slate-950 shadow-lg shadow-cyan-500/30">
                    <i class="pi pi-heart-fill text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight">Clínica Minerva</h1>
                    <p class="text-sm text-cyan-100">Sistema de Gestión de Exámenes Clínicos</p>
                </div>
            </div>

            <nav class="flex items-center gap-3">
                <Link
                    v-if="canLogin"
                    href="/login"
                    class="rounded-full border border-white/20 px-5 py-2 text-sm font-semibold text-white transition hover:bg-white hover:text-slate-950"
                >
                    Iniciar sesión
                </Link>

                <Link
                    v-if="canRegister"
                    href="/register"
                    class="rounded-full bg-cyan-400 px-5 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300"
                >
                    Registrarse
                </Link>
            </nav>
        </header>

        <main class="relative z-10 mx-auto grid min-h-[calc(100vh-96px)] max-w-7xl grid-cols-1 items-center gap-12 px-6 py-10 lg:grid-cols-2">
            <section>
                <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-cyan-400/30 bg-cyan-400/10 px-4 py-2 text-sm text-cyan-100">
                    <i class="pi pi-shield"></i>
                    Plataforma segura para pacientes y laboratorio
                </div>

                <h2 class="max-w-2xl text-5xl font-black leading-tight tracking-tight md:text-6xl">
                    Gestión moderna de
                    <span class="text-cyan-300">exámenes clínicos</span>
                    y resultados médicos.
                </h2>

                <p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">
                    Registra pacientes, agenda citas, administra órdenes de laboratorio,
                    ingresa resultados, genera reportes PDF y controla el envío digital
                    de resultados desde una sola plataforma.
                </p>

                <div class="mt-8 flex flex-wrap gap-4">
                    <Link
                        href="/login"
                        class="rounded-2xl bg-cyan-400 px-6 py-3 font-bold text-slate-950 shadow-lg shadow-cyan-500/30 transition hover:bg-cyan-300"
                    >
                        Entrar al sistema
                    </Link>

                    <Link
                        href="/register"
                        class="rounded-2xl border border-white/20 px-6 py-3 font-bold text-white transition hover:bg-white hover:text-slate-950"
                    >
                        Crear cuenta paciente
                    </Link>
                </div>

                <div class="mt-10 grid max-w-xl grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <i class="pi pi-users text-2xl text-cyan-300"></i>
                        <p class="mt-3 text-sm text-slate-300">Pacientes</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <i class="pi pi-calendar text-2xl text-emerald-300"></i>
                        <p class="mt-3 text-sm text-slate-300">Citas</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <i class="pi pi-file-check text-2xl text-blue-300"></i>
                        <p class="mt-3 text-sm text-slate-300">Resultados</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <i class="pi pi-envelope text-2xl text-teal-300"></i>
                        <p class="mt-3 text-sm text-slate-300">Correos</p>
                    </div>
                </div>
            </section>

            <section class="relative">
                <div class="rounded-4xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
                    <div class="rounded-3xl bg-white p-6 text-slate-900">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-black">Flujo de atención</h3>
                                <p class="text-sm text-slate-500">Clínica Minerva</p>
                            </div>
                            <div class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-700">
                                Activo
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-4 rounded-2xl bg-cyan-50 p-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-500 text-white">
                                    <i class="pi pi-user-plus"></i>
                                </div>
                                <div>
                                    <p class="font-bold">Registro de paciente</p>
                                    <p class="text-sm text-slate-500">Datos personales y cuenta de acceso</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 rounded-2xl bg-emerald-50 p-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500 text-white">
                                    <i class="pi pi-calendar-plus"></i>
                                </div>
                                <div>
                                    <p class="font-bold">Solicitud de examen</p>
                                    <p class="text-sm text-slate-500">Fecha, hora y tipo de examen clínico</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 rounded-2xl bg-blue-50 p-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500 text-white">
                                    <i class="pi pi-microchip"></i>
                                </div>
                                <div>
                                    <p class="font-bold">Ingreso de resultados</p>
                                    <p class="text-sm text-slate-500">Validación por personal de laboratorio</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 rounded-2xl bg-teal-50 p-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-500 text-white">
                                    <i class="pi pi-send"></i>
                                </div>
                                <div>
                                    <p class="font-bold">Entrega digital</p>
                                    <p class="text-sm text-slate-500">PDF e historial clínico del paciente</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>