<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    activeModule: {
        type: String,
        default: 'inicio',
    },
});

const perfilVisible = ref(false);
const perfilLoading = ref(false);
const perfilSaving = ref(false);
const perfilError = ref('');
const perfilMensaje = ref('');

const perfil = ref({
    usuario_nombre: '',
    usuario_apellido: '',
    correo: '',
    paciente_nombres: '',
    paciente_apellidos: '',
    dui: '',
    fecha_nacimiento: '',
    telefono: '',
    direccion: '',
});

const abrirPerfil = async () => {
    perfilVisible.value = true;
    perfilLoading.value = true;
    perfilError.value = '';
    perfilMensaje.value = '';

    try {
        const response = await axios.get('/api/paciente/perfil');
        const data = response.data?.data;

        perfil.value = {
            usuario_nombre: data?.usuario?.nombre ?? '',
            usuario_apellido: data?.usuario?.apellido ?? '',
            correo: data?.usuario?.correo ?? '',
            paciente_nombres: data?.paciente?.nombres ?? '',
            paciente_apellidos: data?.paciente?.apellidos ?? '',
            dui: data?.paciente?.dui ?? '',
            fecha_nacimiento: data?.paciente?.fecha_nacimiento ?? '',
            telefono: data?.paciente?.telefono ?? '',
            direccion: data?.paciente?.direccion ?? '',
        };
    } catch (error) {
        console.error(error);
        perfilError.value = error.response?.data?.mensaje || 'No se pudo cargar el perfil.';
    } finally {
        perfilLoading.value = false;
    }
};

const guardarPerfil = async () => {
    perfilSaving.value = true;
    perfilError.value = '';
    perfilMensaje.value = '';

    try {
        const response = await axios.put('/api/paciente/perfil', {
            correo: perfil.value.correo,
            dui: perfil.value.dui,
            telefono: perfil.value.telefono,
            direccion: perfil.value.direccion,
        });

        perfilMensaje.value = response.data?.mensaje || 'Perfil actualizado correctamente.';
    } catch (error) {
        console.error(error);

        if (error.response?.data?.errors) {
            const firstError = Object.values(error.response.data.errors)[0]?.[0];
            perfilError.value = firstError || 'Revisa los datos ingresados.';
        } else {
            perfilError.value = error.response?.data?.mensaje || 'No se pudo actualizar el perfil.';
        }
    } finally {
        perfilSaving.value = false;
    }
};

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
            route: '/dashboard',
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
            key: 'mis-examenes',
            label: 'Mis exámenes',
            icon: 'pi pi-list-check',
            roles: ['paciente'],
            route: '/paciente/mis-examenes',
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

const setModule = (item) => {
    sidebarOpen.value = false;

    if (item.route) {
        router.visit(item.route);
        return;
    }

    if (page.component === 'Dashboard') {
        emit('update:activeModule', item.key);
        return;
    }

    router.visit(`/dashboard?modulo=${item.key}`);
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
                        v-if="userRole === 'paciente'"
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700 hover:bg-cyan-100"
                        title="Ver perfil"
                        @click="abrirPerfil"
                    >
                        <i class="pi pi-user"></i>
                    </button>

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
                            props.activeModule === item.key
                                ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950'
                        "
                        @click="setModule(item)"
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

        <main class="min-h-screen bg-slate-100 pt-16 lg:pl-72">
            <section class="w-full p-4 md:p-5 lg:p-6">
                <slot />
            </section>
        </main>


        <Dialog
    v-model:visible="perfilVisible"
    modal
    header="Mi perfil"
    :style="{ width: 'min(760px, 95vw)' }"
    class="rounded-3xl"
>
    <div v-if="perfilLoading" class="flex min-h-60 items-center justify-center">
        <div class="text-center">
            <i class="pi pi-spin pi-spinner text-4xl text-cyan-500"></i>
            <p class="mt-4 font-bold text-slate-600">Cargando perfil...</p>
        </div>
    </div>

    <div v-else class="space-y-5">
        <div class="rounded-3xl bg-slate-50 p-4">
            <p class="text-sm font-bold text-slate-700">
                Aquí puedes consultar tu información personal y actualizar tus datos de contacto.
            </p>
            <p class="mt-1 text-xs text-slate-500">
                Solo puedes editar correo, DUI, teléfono y dirección.
            </p>
        </div>

        <div
            v-if="perfilMensaje"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700"
        >
            <i class="pi pi-check-circle mr-2"></i>
            {{ perfilMensaje }}
        </div>

        <div
            v-if="perfilError"
            class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700"
        >
            <i class="pi pi-exclamation-triangle mr-2"></i>
            {{ perfilError }}
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-black text-slate-700">
                    Nombre de usuario
                </label>
                <input
                    :value="`${perfil.usuario_nombre} ${perfil.usuario_apellido}`"
                    disabled
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 text-sm font-bold text-slate-500"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-black text-slate-700">
                    Correo
                </label>
                <input
                    v-model="perfil.correo"
                    type="email"
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-black text-slate-700">
                    Nombre del paciente
                </label>
                <input
                    :value="`${perfil.paciente_nombres} ${perfil.paciente_apellidos}`"
                    disabled
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 text-sm font-bold text-slate-500"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-black text-slate-700">
                    Fecha de nacimiento
                </label>
                <input
                    :value="perfil.fecha_nacimiento || '—'"
                    disabled
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 text-sm font-bold text-slate-500"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-black text-slate-700">
                    DUI
                </label>
                <input
                    v-model="perfil.dui"
                    maxlength="9"
                    placeholder="000000000"
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-black text-slate-700">
                    Teléfono
                </label>
                <input
                    v-model="perfil.telefono"
                    maxlength="8"
                    placeholder="00000000"
                    class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                />
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-black text-slate-700">
                    Dirección
                </label>
                <textarea
                    v-model="perfil.direccion"
                    maxlength="150"
                    rows="3"
                    placeholder="Dirección del paciente"
                    class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                ></textarea>

                <p class="mt-1 text-right text-xs font-bold text-slate-400">
                    {{ perfil.direccion?.length || 0 }}/150
                </p>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
            <Button
                label="Cerrar"
                icon="pi pi-times"
                severity="secondary"
                class="rounded-2xl!"
                :disabled="perfilSaving"
                @click="perfilVisible = false"
            />

            <Button
                label="Guardar cambios"
                icon="pi pi-save"
                class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!"
                :loading="perfilSaving"
                @click="guardarPerfil"
            />
        </div>
    </div>
</Dialog>

    </div>
</template>