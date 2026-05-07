<script setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const activeModule = ref('inicio');
const loading = ref(true);

const usuarios = ref([]);
const pacientes = ref([]);
const examenes = ref([]);
const citas = ref([]);
const ordenes = ref([]);
const resultados = ref([]);
const enviosCorreo = ref([]);

const unwrap = (payload) => {
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.data)) return payload.data;
    return [];
};

const fetchAll = async () => {
    loading.value = true;

    try {
        const [
            usuariosRes,
            pacientesRes,
            examenesRes,
            citasRes,
            ordenesRes,
            resultadosRes,
            enviosRes,
        ] = await Promise.all([
            axios.get('/api/usuarios'),
            axios.get('/api/pacientes'),
            axios.get('/api/examenes'),
            axios.get('/api/citas'),
            axios.get('/api/ordenes'),
            axios.get('/api/resultados'),
            axios.get('/api/envios-correo'),
        ]);

        usuarios.value = unwrap(usuariosRes.data);
        pacientes.value = unwrap(pacientesRes.data);
        examenes.value = unwrap(examenesRes.data);
        citas.value = unwrap(citasRes.data);
        ordenes.value = unwrap(ordenesRes.data);
        resultados.value = unwrap(resultadosRes.data);
        enviosCorreo.value = unwrap(enviosRes.data);
    } catch (error) {
        console.error('Error cargando dashboard:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchAll);

const citasPendientes = computed(() => {
    return citas.value.filter((cita) =>
        ['agendada', 'confirmada'].includes(String(cita.estado).toLowerCase())
    ).length;
});

const ordenesPendientes = computed(() => {
    return ordenes.value.filter((orden) =>
        ['pendiente', 'recepcionado', 'en_laboratorio'].includes(String(orden.estado).toLowerCase())
    ).length;
});

const resultadosFinalizados = computed(() => {
    return resultados.value.filter((resultado) =>
        String(resultado.estado).toLowerCase() === 'finalizado'
    ).length;
});

const correosPendientes = computed(() => {
    return enviosCorreo.value.filter((envio) =>
        String(envio.estado_envio).toLowerCase() === 'pendiente'
    ).length;
});

const cards = computed(() => [
    {
        title: 'Pacientes',
        value: pacientes.value.length,
        icon: 'pi pi-users',
        color: 'cyan',
        description: 'Pacientes registrados',
    },
    {
        title: 'Citas pendientes',
        value: citasPendientes.value,
        icon: 'pi pi-calendar-clock',
        color: 'emerald',
        description: 'Citas agendadas o confirmadas',
    },
    {
        title: 'Órdenes activas',
        value: ordenesPendientes.value,
        icon: 'pi pi-clipboard',
        color: 'blue',
        description: 'Pendientes o en laboratorio',
    },
    {
        title: 'Resultados listos',
        value: resultadosFinalizados.value,
        icon: 'pi pi-file-check',
        color: 'teal',
        description: 'Resultados finalizados',
    },
]);

const moduleTitle = computed(() => {
    const titles = {
        inicio: 'Panel general',
        pacientes: 'Gestión de pacientes',
        citas: 'Gestión de citas',
        examenes: 'Catálogo de exámenes',
        ordenes: 'Órdenes clínicas',
        resultados: 'Resultados clínicos',
        correos: 'Envíos por correo',
        usuarios: 'Gestión de usuarios',
    };

    return titles[activeModule.value] ?? 'Panel general';
});

const badgeClass = (estado) => {
    const value = String(estado ?? '').toLowerCase();

    if (['activo', 'finalizado', 'enviado', 'entregado', 'confirmada'].includes(value)) {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (['pendiente', 'agendada', 'borrador', 'recepcionado'].includes(value)) {
        return 'bg-amber-100 text-amber-700';
    }

    if (['cancelado', 'cancelada', 'fallido', 'inactivo'].includes(value)) {
        return 'bg-red-100 text-red-700';
    }

    return 'bg-slate-100 text-slate-700';
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout v-model:activeModule="activeModule">
        <div class="mb-8">
            <div class="flex flex-col gap-4 rounded-4xl bg-gradient-to-r from-slate-950 via-slate-900 to-cyan-950 p-6 text-white shadow-xl md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.3em] text-cyan-300">
                        Clínica Minerva
                    </p>
                    <h1 class="mt-2 text-3xl font-black md:text-4xl">
                        {{ moduleTitle }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-slate-300">
                        Dashboard dinámico centralizado para administrar pacientes,
                        citas, exámenes, órdenes, resultados y envíos de correo.
                    </p>
                </div>

                <Button
                    label="Actualizar"
                    icon="pi pi-refresh"
                    class="rounded-2xl! border-white/20! bg-white/10! text-white! hover:bg-white/20!"
                    :loading="loading"
                    @click="fetchAll"
                />
            </div>
        </div>

        <div
            v-if="loading"
            class="flex min-h-100 items-center justify-center rounded-4xl bg-white shadow-sm"
        >
            <div class="text-center">
                <i class="pi pi-spin pi-spinner text-4xl text-cyan-500"></i>
                <p class="mt-4 font-bold text-slate-600">Cargando información...</p>
            </div>
        </div>

        <div v-else>
            <section v-if="activeModule === 'inicio'" class="space-y-8">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <Card
                        v-for="card in cards"
                        :key="card.title"
                        class="overflow-hidden rounded-3xl! shadow-sm"
                    >
                        <template #content>
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-bold text-slate-500">{{ card.title }}</p>
                                    <p class="mt-2 text-4xl font-black text-slate-950">{{ card.value }}</p>
                                    <p class="mt-2 text-sm text-slate-500">{{ card.description }}</p>
                                </div>

                                <div
                                    class="flex h-14 w-14 items-center justify-center rounded-2xl text-white"
                                    :class="{
                                        'bg-cyan-500': card.color === 'cyan',
                                        'bg-emerald-500': card.color === 'emerald',
                                        'bg-blue-500': card.color === 'blue',
                                        'bg-teal-500': card.color === 'teal',
                                    }"
                                >
                                    <i :class="card.icon" class="text-2xl"></i>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    <Card class="rounded-3xl! shadow-sm xl:col-span-2">
                        <template #title>
                            <div class="flex items-center justify-between">
                                <span>Órdenes recientes</span>
                                <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700">
                                    {{ ordenes.length }} registros
                                </span>
                            </div>
                        </template>

                        <template #content>
                            <DataTable :value="ordenes.slice(0, 6)" size="small" stripedRows>
                                <Column field="id" header="ID" />
                                <Column field="correlativo" header="Correlativo" />
                                <Column field="estado" header="Estado">
                                    <template #body="{ data }">
                                        <span
                                            class="rounded-full px-3 py-1 text-xs font-bold"
                                            :class="badgeClass(data.estado)"
                                        >
                                            {{ data.estado }}
                                        </span>
                                    </template>
                                </Column>
                                <Column field="total" header="Total">
                                    <template #body="{ data }">
                                        ${{ Number(data.total ?? 0).toFixed(2) }}
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>

                    <Card class="rounded-3xl! shadow-sm">
                        <template #title>Estado de envíos</template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="rounded-2xl bg-amber-50 p-4">
                                    <p class="text-sm font-bold text-amber-700">Correos pendientes</p>
                                    <p class="mt-1 text-3xl font-black text-amber-800">{{ correosPendientes }}</p>
                                </div>
                                <div class="rounded-2xl bg-emerald-50 p-4">
                                    <p class="text-sm font-bold text-emerald-700">Resultados finalizados</p>
                                    <p class="mt-1 text-3xl font-black text-emerald-800">{{ resultadosFinalizados }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </section>

            <section v-if="activeModule === 'pacientes'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>Pacientes registrados</template>
                    <template #content>
                        <DataTable :value="pacientes" paginator :rows="10" stripedRows>
                            <Column field="id" header="ID" sortable />
                            <Column field="nombres" header="Nombres" sortable />
                            <Column field="apellidos" header="Apellidos" sortable />
                            <Column field="dui" header="DUI" />
                            <Column field="telefono" header="Teléfono" />
                            <Column header="Correo">
                                <template #body="{ data }">
                                    {{ data.usuario?.correo ?? data.usuario?.email ?? '—' }}
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeModule === 'citas'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>Citas clínicas</template>
                    <template #content>
                        <DataTable :value="citas" paginator :rows="10" stripedRows>
                            <Column field="id" header="ID" sortable />
                            <Column field="fecha_cita" header="Fecha" sortable />
                            <Column field="hora_cita" header="Hora" />
                            <Column header="Paciente">
                                <template #body="{ data }">
                                    {{ data.paciente?.nombres }} {{ data.paciente?.apellidos }}
                                </template>
                            </Column>
                            <Column field="estado" header="Estado">
                                <template #body="{ data }">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-bold"
                                        :class="badgeClass(data.estado)"
                                    >
                                        {{ data.estado }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeModule === 'examenes'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>Catálogo de exámenes</template>
                    <template #content>
                        <DataTable :value="examenes" paginator :rows="10" stripedRows>
                            <Column field="id" header="ID" sortable />
                            <Column field="codigo" header="Código" sortable />
                            <Column field="nombre" header="Examen" sortable />
                            <Column field="precio" header="Precio">
                                <template #body="{ data }">
                                    ${{ Number(data.precio ?? 0).toFixed(2) }}
                                </template>
                            </Column>
                            <Column field="tiempo_entrega_horas" header="Entrega" />
                            <Column field="estado" header="Estado">
                                <template #body="{ data }">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-bold"
                                        :class="badgeClass(data.estado)"
                                    >
                                        {{ data.estado }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeModule === 'ordenes'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>Órdenes clínicas</template>
                    <template #content>
                        <DataTable :value="ordenes" paginator :rows="10" stripedRows>
                            <Column field="id" header="ID" sortable />
                            <Column field="correlativo" header="Correlativo" sortable />
                            <Column header="Paciente">
                                <template #body="{ data }">
                                    {{ data.paciente?.nombres }} {{ data.paciente?.apellidos }}
                                </template>
                            </Column>
                            <Column field="fecha_orden" header="Fecha" sortable />
                            <Column field="total" header="Total">
                                <template #body="{ data }">
                                    ${{ Number(data.total ?? 0).toFixed(2) }}
                                </template>
                            </Column>
                            <Column field="estado" header="Estado">
                                <template #body="{ data }">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-bold"
                                        :class="badgeClass(data.estado)"
                                    >
                                        {{ data.estado }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeModule === 'resultados'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>Resultados clínicos</template>
                    <template #content>
                        <DataTable :value="resultados" paginator :rows="10" stripedRows>
                            <Column field="id" header="ID" sortable />
                            <Column field="fecha_resultado" header="Fecha" sortable />
                            <Column field="archivo_pdf" header="PDF" />
                            <Column field="correo_enviado" header="Correo">
                                <template #body="{ data }">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-bold"
                                        :class="data.correo_enviado ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                    >
                                        {{ data.correo_enviado ? 'Enviado' : 'Pendiente' }}
                                    </span>
                                </template>
                            </Column>
                            <Column field="estado" header="Estado">
                                <template #body="{ data }">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-bold"
                                        :class="badgeClass(data.estado)"
                                    >
                                        {{ data.estado }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeModule === 'correos'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>Envíos por correo</template>
                    <template #content>
                        <DataTable :value="enviosCorreo" paginator :rows="10" stripedRows>
                            <Column field="id" header="ID" sortable />
                            <Column field="correo_destino" header="Destino" sortable />
                            <Column field="fecha_envio" header="Fecha envío" />
                            <Column field="archivo_adjunto" header="Adjunto" />
                            <Column field="estado_envio" header="Estado">
                                <template #body="{ data }">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-bold"
                                        :class="badgeClass(data.estado_envio)"
                                    >
                                        {{ data.estado_envio }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeModule === 'usuarios'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>Usuarios del sistema</template>
                    <template #content>
                        <DataTable :value="usuarios" paginator :rows="10" stripedRows>
                            <Column field="id" header="ID" sortable />
                            <Column field="nombre" header="Nombre" sortable />
                            <Column field="apellido" header="Apellido" sortable />
                            <Column field="correo" header="Correo" sortable />
                            <Column field="rol" header="Rol">
                                <template #body="{ data }">
                                    <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-bold capitalize text-cyan-700">
                                        {{ data.rol }}
                                    </span>
                                </template>
                            </Column>
                            <Column field="estado" header="Estado">
                                <template #body="{ data }">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-bold"
                                        :class="badgeClass(data.estado)"
                                    >
                                        {{ data.estado }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>
        </div>
    </AuthenticatedLayout>
</template>