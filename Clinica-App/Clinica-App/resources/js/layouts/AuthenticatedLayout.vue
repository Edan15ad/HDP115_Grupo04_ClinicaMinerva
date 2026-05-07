<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

defineProps({
    activeModule: {
        type: String,
        default: 'inicio',
    },
});

const emit = defineEmits(['update:activeModule']);

const page = usePage();
const sidebarOpen = ref(false);

const user = computed(() => page.props.auth?.user ?? {});

const userName = computed(() => {
    return user.value.nombre || user.value.name || 'Usuario';
});

const userEmail = computed(() => {
    return user.value.correo || user.value.email || 'usuario@minerva.com';
});

const userRole = computed(() => {
    return user.value.rol || 'administrador';
});

const modules = computed(() => {
    const all = [
        {
            key: 'inicio',
            label: 'Inicio',
            icon: 'pi pi-home',
            roles: ['paciente', 'recepcionista', 'laboratorio', 'administrador'],
        },
        {
            key: 'pacientes',
            label: 'Pacientes',
            icon: 'pi pi-users',
            roles: ['recepcionista', 'administrador'],
        },
        {
            key: 'citas',
            label: 'Citas',
            icon: 'pi pi-calendar',
            roles: ['paciente', 'recepcionista', 'administrador'],
        },
        {
            key: 'examenes',
            label: 'Exámenes',
            icon: 'pi pi-list-check',
            roles: ['recepcionista', 'laboratorio', 'administrador'],
        },
        {
            key: 'ordenes',
            label: 'Órdenes',
            icon: 'pi pi-clipboard',
            roles: ['recepcionista', 'laboratorio', 'administrador'],
        },
        {
            key: 'resultados',
            label: 'Resultados',
            icon: 'pi pi-file-check',
            roles: ['paciente', 'laboratorio', 'administrador'],
        },
        {
            key: 'correos',
            label: 'Envíos correo',
            icon: 'pi pi-envelope',
            roles: ['recepcionista', 'laboratorio', 'administrador'],
        },
        {
            key: 'usuarios',
            label: 'Usuarios',
            icon: 'pi pi-user-edit',
            roles: ['administrador'],
        },
    ];

    return all.filter((item) => item.roles.includes(userRole.value));
});

const setModule = (key) => {
    emit('update:activeModule', key);
    sidebarOpen.value = false;
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <header class="fixed left-0 top-0 z-50 h-16 w-full border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="flex h-full items-center justify-between px-4 lg:px-6">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 lg:hidden"
                        @click="sidebarOpen = true"
                    >
                        <i class="pi pi-bars text-xl"></i>
                    </button>

                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-500 text-white shadow-lg shadow-cyan-500/30">
                            <i class="pi pi-heart-fill"></i>
                        </div>
                        <div>
                            <h1 class="text-base font-black leading-tight text-slate-900">
                                Clínica Minerva
                            </h1>
                            <p class="text-xs font-medium text-slate-500">
                                Gestión de exámenes clínicos
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-bold text-slate-900">{{ userName }}</p>
                        <p class="text-xs text-slate-500">{{ userEmail }}</p>
                    </div>

                    <div class="hidden rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold capitalize text-cyan-700 sm:block">
                        {{ userRole }}
                    </div>

                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600"
                        title="Cerrar sesión"
                        @click="logout"
                    >
                        <i class="pi pi-sign-out"></i>
                    </button>
                </div>
            </div>
        </header>

        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <aside
            class="fixed left-0 top-16 z-50 h-[calc(100vh-4rem)] w-72 transform border-r border-slate-200 bg-white transition-transform duration-300 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-full flex-col">
                <div class="border-b border-slate-100 p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-400">
                        Menú
                    </p>
                    <h2 class="mt-1 text-lg font-black text-slate-900">Módulos</h2>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                    <button
                        v-for="item in modules"
                        :key="item.key"
                        type="button"
                        class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-bold transition"
                        :class="
                            activeModule === item.key
                                ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950'
                        "
                        @click="setModule(item.key)"
                    >
                        <i :class="item.icon"></i>
                        <span>{{ item.label }}</span>
                    </button>
                </nav>

                <div class="border-t border-slate-100 p-4">
                    <div class="rounded-3xl bg-slate-950 p-4 text-white">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-cyan-400 text-slate-950">
                                <i class="pi pi-shield"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold">Sesión segura</p>
                                <p class="text-xs text-slate-400">Control por roles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="min-h-screen pt-16 lg:pl-72">
            <section class="p-4 md:p-6 lg:p-8">
                <slot />
            </section>
        </main>
    </div>
</template>