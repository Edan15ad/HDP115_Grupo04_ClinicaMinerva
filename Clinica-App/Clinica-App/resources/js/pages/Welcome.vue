<script setup>
import { Head, Link } from '@inertiajs/vue3';
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
                    <p class="text-sm text-cyan-100">Sistema de Gestión de Exámenes</p>
                </div>
            </div>

            <nav class="flex items-center gap-3">
                <Link
                    href="/login"
                    class="rounded-full border border-white/20 px-5 py-2 text-sm font-semibold text-white transition hover:bg-white hover:text-slate-950"
                >
                    Iniciar sesión
                </Link>

                <Link
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
                    y resultados.
                </h2>

                <p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">
                    Registra pacientes, agenda citas, administra órdenes de laboratorio,
                    ingresa resultados y controla el envío digital desde una sola plataforma.
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
            </section>
        </main>
    </div>
</template>