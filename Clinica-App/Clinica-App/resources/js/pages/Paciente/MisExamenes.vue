<script setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const loading = ref(false);
const saving = ref(false);
const modalVisible = ref(false);

const ordenes = ref([]);
const examenesDisponibles = ref([]);
const horarios = ref([]);

const filtros = ref({
    estado: '',
    fecha: '',
});

const form = ref({
    examen_id: '',
    fecha_cita: '',
    hora_cita: '',
    observaciones: '',
});

const mensaje = ref('');
const error = ref('');

const estadosOrden = [
    { label: 'Todos', value: '' },
    { label: 'Pendiente', value: 'pendiente' },
    { label: 'En Recepcion', value: 'recepcionado' },
    { label: 'En laboratorio', value: 'en_laboratorio' },
    { label: 'Finalizado', value: 'finalizado' },
    { label: 'Entregado', value: 'entregado' },
    { label: 'Cancelado', value: 'cancelado' },
];

const unwrap = (payload) => {
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.data)) return payload.data;
    return [];
};

const fechaMinima = computed(() => {
    return new Date().toISOString().slice(0, 10);
});

const abrirCalendario = (event) => {
    if (event?.target?.showPicker) {
        event.target.showPicker();
    }
};

const normalizarFecha = (value) => {
    if (!value) return '';
    return String(value).slice(0, 10);
};

const examenSeleccionado = computed(() => {
    return examenesDisponibles.value.find((item) => Number(item.id) === Number(form.value.examen_id));
});

const totalSolicitud = computed(() => {
    return Number(examenSeleccionado.value?.precio ?? 0).toFixed(2);
});

const resumen = computed(() => {
    const totalOrdenes = ordenes.value.length;

    const pendientes = ordenes.value.filter((orden) =>
        ['pendiente', 'recepcionado'].includes(String(orden.estado).toLowerCase())
    ).length;

    const laboratorio = ordenes.value.filter((orden) =>
        String(orden.estado).toLowerCase() === 'en_laboratorio'
    ).length;

    const finalizados = ordenes.value.filter((orden) =>
        ['finalizado', 'entregado'].includes(String(orden.estado).toLowerCase())
    ).length;

    return {
        totalOrdenes,
        pendientes,
        laboratorio,
        finalizados,
    };
});

const limpiarAlertas = () => {
    mensaje.value = '';
    error.value = '';
};

const cargarMisExamenes = async () => {
    loading.value = true;
    limpiarAlertas();

    try {
        const params = {};

        if (filtros.value.estado) {
            params.estado = filtros.value.estado;
        }

        if (filtros.value.fecha) {
            params.fecha = normalizarFecha(filtros.value.fecha);
        }

        const response = await axios.get('/api/paciente/mis-examenes', { params });
        ordenes.value = unwrap(response.data);
    } catch (err) {
        console.error(err);
        error.value = err.response?.data?.mensaje || 'No se pudieron cargar tus exámenes.';
    } finally {
        loading.value = false;
    }
};

const cargarExamenesDisponibles = async () => {
    try {
        const response = await axios.get('/api/paciente/examenes-disponibles');
        examenesDisponibles.value = unwrap(response.data);
    } catch (err) {
        console.error(err);
        error.value = err.response?.data?.mensaje || 'No se pudieron cargar los exámenes disponibles.';
    }
};

const cargarHorariosDisponibles = async () => {
    horarios.value = [];
    form.value.hora_cita = '';

    const fecha = normalizarFecha(form.value.fecha_cita);

    if (!fecha) return;

    try {
        const response = await axios.get('/api/paciente/horarios-disponibles', {
            params: { fecha },
        });

        horarios.value = unwrap(response.data);
    } catch (err) {
        console.error(err);
        error.value = err.response?.data?.mensaje || 'No se pudieron cargar los horarios disponibles.';
    }
};

const abrirModal = async () => {
    limpiarAlertas();

    form.value = {
        examen_id: '',
        fecha_cita: '',
        hora_cita: '',
        observaciones: '',
    };

    horarios.value = [];
    modalVisible.value = true;

    await cargarExamenesDisponibles();
};

const cerrarModal = () => {
    modalVisible.value = false;
};

const solicitarExamen = async () => {
    limpiarAlertas();

    const fecha = normalizarFecha(form.value.fecha_cita);

    if (!form.value.examen_id || !fecha || !form.value.hora_cita) {
        error.value = 'Selecciona examen, fecha y horario.';
        return;
    }

    saving.value = true;

    try {
        const response = await axios.post('/api/paciente/solicitar-examen', {
            examen_id: form.value.examen_id,
            fecha_cita: fecha,
            hora_cita: form.value.hora_cita,
            observaciones: form.value.observaciones,
        });

        mensaje.value = response.data?.mensaje || 'Solicitud enviada correctamente.';
        modalVisible.value = false;

        await cargarMisExamenes();
    } catch (err) {
        console.error(err);

        if (err.response?.data?.errors) {
            const firstError = Object.values(err.response.data.errors)[0]?.[0];
            error.value = firstError || 'Revisa los datos ingresados.';
        } else {
            error.value = err.response?.data?.mensaje || 'No se pudo enviar la solicitud.';
        }
    } finally {
        saving.value = false;
    }
};

const limpiarFiltros = async () => {
    filtros.value = {
        estado: '',
        fecha: '',
    };

    await cargarMisExamenes();
};

const badgeClassOrden = (estado) => {
    const value = String(estado ?? '').toLowerCase();

    if (['finalizado', 'entregado'].includes(value)) {
        return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200';
    }

    if (['pendiente', 'recepcionado'].includes(value)) {
        return 'bg-amber-100 text-amber-700 ring-1 ring-amber-200';
    }

    if (value === 'en_laboratorio') {
        return 'bg-blue-100 text-blue-700 ring-1 ring-blue-200';
    }

    if (value === 'cancelado') {
        return 'bg-red-100 text-red-700 ring-1 ring-red-200';
    }

    return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
};

const badgeClassDetalle = (estado) => {
    const value = String(estado ?? '').toLowerCase();

    if (value === 'finalizado') return 'bg-emerald-50 text-emerald-700';
    if (value === 'en_proceso') return 'bg-blue-50 text-blue-700';
    if (value === 'muestra_tomada') return 'bg-cyan-50 text-cyan-700';
    if (value === 'cancelado') return 'bg-red-50 text-red-700';

    return 'bg-amber-50 text-amber-700';
};

const formatMoney = (value) => {
    return `$${Number(value ?? 0).toFixed(2)}`;
};

const formatDate = (value) => {
    if (!value) return '—';

    const raw = String(value).slice(0, 10);
    const [year, month, day] = raw.split('-');

    if (!year || !month || !day) return raw;

    return `${day}/${month}/${year}`;
};

const formatHour = (value) => {
    if (!value) return '—';
    return String(value).slice(0, 5);
};

const getNombreExamenes = (orden) => {
    const detalles = orden.detalles ?? [];

    if (detalles.length === 0) return 'Sin exámenes';

    return detalles
        .map((detalle) => detalle.examen?.nombre ?? 'Examen')
        .join(', ');
};

watch(
    () => form.value.fecha_cita,
    () => {
        cargarHorariosDisponibles();
    }
);

onMounted(async () => {
    await cargarMisExamenes();
    await cargarExamenesDisponibles();
});
</script>

<template>
    <Head title="Mis exámenes" />

    <AuthenticatedLayout active-module="mis-examenes">
        <div class="w-full max-w-none space-y-6">
            <section class="overflow-hidden rounded-4xl bg-gradient-to-r from-slate-950 via-slate-900 to-cyan-950 p-6 text-white shadow-xl">
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.3em] text-cyan-300">
                            Portal del paciente
                        </p>

                        <h1 class="mt-2 text-3xl font-black md:text-4xl">
                            Mis exámenes
                        </h1>

                        <p class="mt-2 max-w-2xl text-slate-300">
                            Consulta tus solicitudes, revisa el estado de tus exámenes y agenda una nueva toma de muestra.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Button
                            label="Actualizar"
                            icon="pi pi-refresh"
                            class="rounded-2xl! border-white/20! bg-white/10! text-white! hover:bg-white/20!"
                            :loading="loading"
                            @click="cargarMisExamenes"
                        />

                        <Button
                            label="Solicitar nuevo examen"
                            icon="pi pi-plus"
                            class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!"
                            @click="abrirModal"
                        />
                    </div>
                </div>
            </section>

            <section
                v-if="mensaje"
                class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700"
            >
                <i class="pi pi-check-circle mr-2"></i>
                {{ mensaje }}
            </section>

            <section
                v-if="error"
                class="rounded-3xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700"
            >
                <i class="pi pi-exclamation-triangle mr-2"></i>
                {{ error }}
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card class="rounded-3xl! bg-white! text-slate-900! shadow-sm">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-500">Total solicitudes</p>
                                <p class="mt-2 text-3xl font-black text-slate-950">{{ resumen.totalOrdenes }}</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-950 text-white">
                                <i class="pi pi-clipboard text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="rounded-3xl! bg-white! text-slate-900! shadow-sm">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-500">Pendientes</p>
                                <p class="mt-2 text-3xl font-black text-amber-600">{{ resumen.pendientes }}</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                                <i class="pi pi-clock text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="rounded-3xl! bg-white! text-slate-900! shadow-sm">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-500">En laboratorio</p>
                                <p class="mt-2 text-3xl font-black text-blue-600">{{ resumen.laboratorio }}</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
                                <i class="pi pi-sync text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="rounded-3xl! bg-white! text-slate-900! shadow-sm">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-500">Finalizados</p>
                                <p class="mt-2 text-3xl font-black text-emerald-600">{{ resumen.finalizados }}</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                                <i class="pi pi-check-circle text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </section>

            <Card class="rounded-3xl! bg-white! text-slate-900! shadow-sm">
                <template #title>
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">
                                Historial de exámenes solicitados
                            </h2>
                            <p class="mt-1 text-sm font-normal text-slate-500">
                                Puedes filtrar por estado o por fecha de cita.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <select
                                v-model="filtros.estado"
                                class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                                @change="cargarMisExamenes"
                            >
                                <option
                                    v-for="estado in estadosOrden"
                                    :key="estado.value"
                                    :value="estado.value"
                                >
                                    {{ estado.label }}
                                </option>
                            </select>

                            <input
                                v-model="filtros.fecha"
                                type="date"
                                class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                                @click="abrirCalendario"
                                @focus="abrirCalendario"
                                @change="cargarMisExamenes"
                            />

                            <Button
                                label="Limpiar"
                                icon="pi pi-filter-slash"
                                severity="secondary"
                                class="rounded-2xl!"
                                @click="limpiarFiltros"
                            />
                        </div>
                    </div>
                </template>

                <template #content>
                    <div
                        v-if="loading"
                        class="flex min-h-80 items-center justify-center"
                    >
                        <div class="text-center">
                            <i class="pi pi-spin pi-spinner text-4xl text-cyan-500"></i>
                            <p class="mt-4 font-bold text-slate-600">
                                Cargando tus exámenes...
                            </p>
                        </div>
                    </div>

                    <div
                        v-else-if="ordenes.length === 0"
                        class="flex min-h-80 items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-slate-50"
                    >
                        <div class="max-w-md text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-cyan-100 text-cyan-600">
                                <i class="pi pi-folder-open text-3xl"></i>
                            </div>

                            <h3 class="mt-4 text-xl font-black text-slate-900">
                                No tienes exámenes solicitados
                            </h3>

                            <p class="mt-2 text-sm text-slate-500">
                                Cuando solicites un examen, aparecerá aquí con su fecha, horario y estado.
                            </p>

                            <Button
                                label="Solicitar mi primer examen"
                                icon="pi pi-plus"
                                class="mt-5 rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!"
                                @click="abrirModal"
                            />
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <article
                            v-for="orden in ordenes"
                            :key="orden.id"
                            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-cyan-200 hover:shadow-md"
                        >
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-black text-slate-950">
                                            {{ orden.correlativo }}
                                        </h3>

                                        <span
                                            class="rounded-full px-3 py-1 text-xs font-black uppercase"
                                            :class="badgeClassOrden(orden.estado)"
                                        >
                                            {{ orden.estado }}
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm font-bold text-slate-700">
                                        {{ getNombreExamenes(orden) }}
                                    </p>

                                    <div class="mt-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                                        <div class="rounded-2xl bg-slate-50 p-3">
                                            <p class="text-xs font-black uppercase tracking-wide text-slate-400">
                                                Fecha cita
                                            </p>
                                            <p class="mt-1 font-bold text-slate-800">
                                                {{ formatDate(orden.cita?.fecha_cita) }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 p-3">
                                            <p class="text-xs font-black uppercase tracking-wide text-slate-400">
                                                Hora
                                            </p>
                                            <p class="mt-1 font-bold text-slate-800">
                                                {{ formatHour(orden.cita?.hora_cita) }}
                                            </p>
                                        </div>

                                        <div class="rounded-2xl bg-slate-50 p-3">
                                            <p class="text-xs font-black uppercase tracking-wide text-slate-400">
                                                Total
                                            </p>
                                            <p class="mt-1 font-bold text-slate-800">
                                                {{ formatMoney(orden.total) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-3xl bg-cyan-50 p-4 text-cyan-800 xl:w-72">
                                    <p class="text-xs font-black uppercase tracking-wide text-cyan-500">
                                        Estado de recepción
                                    </p>
                                    <p class="mt-2 text-sm font-bold">
                                        Tu solicitud será revisada por recepción. Si el examen es confirmado, podrás presentarte en la fecha indicada.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-100">
                                <table class="w-full min-w-[720px] text-left text-sm">
                                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3">Examen</th>
                                            <th class="px-4 py-3">Precio</th>
                                            <th class="px-4 py-3">Estado</th>
                                            <th class="px-4 py-3">Observaciones</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr
                                            v-for="detalle in orden.detalles"
                                            :key="detalle.id"
                                            class="border-t border-slate-100"
                                        >
                                            <td class="px-4 py-3 font-bold text-slate-800">
                                                {{ detalle.examen?.nombre ?? 'Examen' }}
                                                <p class="text-xs font-normal text-slate-500">
                                                    {{ detalle.examen?.codigo ?? '—' }}
                                                </p>
                                            </td>

                                            <td class="px-4 py-3 font-bold text-slate-700">
                                                {{ formatMoney(detalle.precio_unitario) }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <span
                                                    class="rounded-full px-3 py-1 text-xs font-black uppercase"
                                                    :class="badgeClassDetalle(detalle.estado)"
                                                >
                                                    {{ detalle.estado }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-3 text-slate-500">
                                                {{ detalle.observaciones || '—' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    </div>
                </template>
            </Card>
        </div>

        <Dialog
            v-model:visible="modalVisible"
            modal
            header="Solicitar nuevo examen"
            :style="{ width: 'min(720px, 95vw)' }"
            class="rounded-3xl"
        >
            <div class="space-y-5">
                <div class="rounded-3xl bg-slate-50 p-4">
                    <p class="text-sm font-bold text-slate-700">
                        Selecciona el examen que necesitas y el horario en el que puedes presentarte a la clínica.
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        La solicitud quedará en estado pendiente hasta que recepción la revise.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-black text-slate-700">
                            Examen disponible
                        </label>

                        <select
                            v-model="form.examen_id"
                            class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                        >
                            <option value="">Selecciona un examen</option>

                            <option
                                v-for="examen in examenesDisponibles"
                                :key="examen.id"
                                :value="examen.id"
                            >
                                {{ examen.codigo }} - {{ examen.nombre }} - ${{ Number(examen.precio ?? 0).toFixed(2) }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-black text-slate-700">
                            Fecha de cita
                        </label>

                        <input
                            v-model="form.fecha_cita"
                            type="date"
                            :min="fechaMinima"
                            class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                            @click="abrirCalendario"
                            @focus="abrirCalendario"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-black text-slate-700">
                            Horario disponible
                        </label>

                        <select
                            v-model="form.hora_cita"
                            :disabled="!form.fecha_cita"
                            class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100 disabled:bg-slate-100 disabled:text-slate-400"
                        >
                            <option value="">
                                {{ form.fecha_cita ? 'Selecciona una hora' : 'Primero selecciona fecha' }}
                            </option>

                            <option
                                v-for="item in horarios"
                                :key="item.hora"
                                :value="item.hora"
                                :disabled="!item.disponible"
                            >
                                {{ item.hora }} {{ item.disponible ? '' : '- ocupado' }}
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-black text-slate-700">
                            Observaciones opcionales
                        </label>

                        <textarea
                            v-model="form.observaciones"
                            maxlength="100"
                            rows="3"
                            placeholder="Ejemplo: prefiero horario temprano, síntomas o indicación médica..."
                            class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                        ></textarea>

                        <p class="mt-1 text-right text-xs font-bold text-slate-400">
                            {{ form.observaciones.length }}/100
                        </p>
                    </div>
                </div>

                <div
                    v-if="examenSeleccionado"
                    class="rounded-3xl border border-cyan-100 bg-cyan-50 p-4"
                >
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-black text-cyan-900">
                                {{ examenSeleccionado.nombre }}
                            </p>
                            <p class="mt-1 text-xs font-medium text-cyan-700">
                                Tiempo estimado de entrega:
                                {{ examenSeleccionado.tiempo_entrega_horas }} horas
                            </p>
                        </div>

                        <div class="text-left md:text-right">
                            <p class="text-xs font-black uppercase tracking-wide text-cyan-500">
                                Total
                            </p>
                            <p class="text-2xl font-black text-cyan-900">
                                ${{ totalSolicitud }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="secondary"
                        class="rounded-2xl!"
                        :disabled="saving"
                        @click="cerrarModal"
                    />

                    <Button
                        label="Enviar solicitud"
                        icon="pi pi-send"
                        class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!"
                        :loading="saving"
                        @click="solicitarExamen"
                    />
                </div>
            </div>
        </Dialog>
    </AuthenticatedLayout>
</template>