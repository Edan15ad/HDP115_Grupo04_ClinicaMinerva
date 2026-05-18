<script setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const page = usePage();

const getModuloInicial = () => {
    const params = new URLSearchParams(window.location.search);
    return params.get('modulo') || 'inicio';
};

const activeModule = ref(getModuloInicial());
const loading = ref(true);

const usuarios = ref([]);
const pacientes = ref([]);
const examenes = ref([]);
const citas = ref([]);
const ordenes = ref([]);
const resultados = ref([]);
const enviosCorreo = ref([]);
const pendientesLaboratorio = ref([]);

const resultadoModalVisible = ref(false);
const resultadoLoading = ref(false);
const resultadoSaving = ref(false);
const resultadoError = ref('');
const resultadoMensaje = ref('');
const detalleSeleccionado = ref(null);
const parametrosResultado = ref([]);
const resultadoForm = ref({});
const observacionesResultado = ref('');

const resultadoPacienteVisible = ref(false);
const resultadoPacienteLoading = ref(false);
const resultadoPacienteError = ref('');
const resultadoPacienteSeleccionado = ref(null);

const filtros = ref({
    pacientes: { fecha: '', usuario: '', estado: '' },
    citas: { fecha: '', usuario: '', estado: '' },
    examenes: { fecha: '', usuario: '', estado: '' },
    ordenes: { fecha: '', usuario: '', estado: '' },
    resultados: { fecha: '', usuario: '', estado: '' },
    correos: { fecha: '', usuario: '', estado: '' },
    usuarios: { fecha: '', usuario: '', estado: '' },
});

const userRole = computed(() => {
    return page.props.auth?.user?.rol || '';
});

const esPaciente = computed(() => {
    return String(userRole.value).toLowerCase() === 'paciente';
});

const puedeGestionarMuestra = computed(() => {
    return ['recepcionista', 'administrador'].includes(String(userRole.value).toLowerCase());
});

const puedeRegistrarResultados = computed(() => {
    return String(userRole.value).toLowerCase() === 'laboratorio';
});

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
        enviosCorreo.value = unwrap(enviosRes.data);

        if (esPaciente.value) {
            const pacienteResultadosRes = await axios.get('/api/paciente/resultados');
            resultados.value = unwrap(pacienteResultadosRes.data);
        } else {
            resultados.value = unwrap(resultadosRes.data);
        }

        if (puedeRegistrarResultados.value) {
            const pendientesRes = await axios.get('/api/laboratorio/resultados-pendientes');
            pendientesLaboratorio.value = unwrap(pendientesRes.data);
        } else {
            pendientesLaboratorio.value = [];
        }
    } catch (error) {
        console.error('Error cargando dashboard:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchAll);

const formatFecha = (value) => {
    if (!value) return '—';
    return String(value).slice(0, 10);
};

const textoNormalizado = (value) => {
    return String(value ?? '').toLowerCase().trim();
};

const coincideTexto = (textoBase, filtro) => {
    if (!filtro) return true;
    return textoNormalizado(textoBase).includes(textoNormalizado(filtro));
};

const coincideFecha = (fechaBase, filtroFecha) => {
    if (!filtroFecha) return true;
    return formatFecha(fechaBase) === filtroFecha;
};

const coincideEstado = (estadoBase, filtroEstado) => {
    if (!filtroEstado) return true;
    return textoNormalizado(estadoBase) === textoNormalizado(filtroEstado);
};

const nombrePaciente = (item) => {
    const paciente =
        item?.paciente ??
        item?.orden?.paciente ??
        item?.detalle_orden?.orden?.paciente ??
        item?.detalleOrden?.orden?.paciente ??
        item;

    return `${paciente?.nombres ?? ''} ${paciente?.apellidos ?? ''}`.trim();
};

const limpiarFiltros = (modulo) => {
    filtros.value[modulo] = {
        fecha: '',
        usuario: '',
        estado: '',
    };
};

const getExamenesOrden = (orden) => {
    const detalles = orden?.detalles ?? [];

    if (!detalles.length) return '—';

    return detalles
        .map((detalle) => detalle.examen?.nombre ?? 'Examen')
        .join(', ');
};

const getEstadoExamenOrden = (orden) => {
    const detalles = orden?.detalles ?? [];

    if (!detalles.length) return orden?.estado ?? '—';

    const estados = detalles.map((detalle) => String(detalle.estado ?? '').toLowerCase());

    if (estados.every((estado) => estado === 'finalizado')) return 'finalizado';
    if (estados.some((estado) => estado === 'en_proceso')) return 'en_proceso';
    if (estados.some((estado) => estado === 'muestra_tomada')) return 'muestra_tomada';
    if (estados.some((estado) => estado === 'pendiente')) return 'pendiente';
    if (estados.every((estado) => estado === 'cancelado')) return 'cancelado';

    return orden?.estado ?? '—';
};

const textoEstadoExamenOrden = (estado) => {
    const value = String(estado ?? '').toLowerCase();

    const labels = {
        finalizado: 'Procesado',
        en_proceso: 'Pendiente de resultado',
        muestra_tomada: 'Muestra tomada',
        pendiente: 'Pendiente',
        cancelado: 'Cancelado',
        en_laboratorio: 'Pendiente de resultado',
    };

    return labels[value] ?? estado ?? '—';
};

const textoEstadoOrden = (estado) => {
    const value = String(estado ?? '').toLowerCase();

    const labels = {
        pendiente: 'Pendiente',
        recepcionado: 'Recepcionado',
        en_laboratorio: 'En laboratorio',
        finalizado: 'Finalizado',
        entregado: 'Entregado',
        cancelado: 'Cancelado',
    };

    return labels[value] ?? estado ?? '—';
};

const textoDisponibilidadExamen = (estado) => {
    const value = String(estado ?? '').toLowerCase();

    if (value === 'activo') return 'Disponible';
    if (value === 'inactivo') return 'No disponible';

    return estado ?? '—';
};

const textoEstadoResultado = (estado) => {
    const value = String(estado ?? '').toLowerCase();

    const labels = {
        pendiente_resultado: 'Pendiente resultado',
        borrador: 'Borrador',
        finalizado: 'Procesado',
        enviado: 'Enviado',
    };

    return labels[value] ?? estado ?? '—';
};

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
        description: 'Resultados procesados',
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

    if (['pendiente', 'agendada', 'borrador', 'recepcionado', 'pendiente_resultado'].includes(value)) {
        return 'bg-amber-100 text-amber-700';
    }

    if (['muestra_tomada', 'en_laboratorio', 'en_proceso'].includes(value)) {
        return 'bg-blue-100 text-blue-700';
    }

    if (['cancelado', 'cancelada', 'fallido', 'inactivo'].includes(value)) {
        return 'bg-red-100 text-red-700';
    }

    return 'bg-slate-100 text-slate-700';
};

const pacientesFiltrados = computed(() => {
    const f = filtros.value.pacientes;

    return pacientes.value.filter((paciente) => {
        const texto = [
            paciente.nombres,
            paciente.apellidos,
            paciente.dui,
            paciente.telefono,
            paciente.usuario?.correo,
            paciente.usuario?.nombre,
            paciente.usuario?.apellido,
        ].join(' ');

        return (
            coincideFecha(paciente.created_at, f.fecha) &&
            coincideTexto(texto, f.usuario) &&
            coincideEstado(paciente.usuario?.estado ?? '', f.estado)
        );
    });
});

const citasFiltradas = computed(() => {
    const f = filtros.value.citas;

    return citas.value.filter((cita) => {
        const texto = [
            nombrePaciente(cita),
            cita.paciente?.usuario?.correo,
            cita.paciente?.usuario?.nombre,
            cita.paciente?.usuario?.apellido,
        ].join(' ');

        return (
            coincideFecha(cita.fecha_cita, f.fecha) &&
            coincideTexto(texto, f.usuario) &&
            coincideEstado(cita.estado, f.estado)
        );
    });
});

const examenesFiltrados = computed(() => {
    const f = filtros.value.examenes;

    return examenes.value.filter((examen) => {
        const texto = [examen.codigo, examen.nombre, examen.descripcion].join(' ');

        return (
            coincideFecha(examen.created_at, f.fecha) &&
            coincideTexto(texto, f.usuario) &&
            coincideEstado(examen.estado, f.estado)
        );
    });
});

const ordenesFiltradas = computed(() => {
    const f = filtros.value.ordenes;

    return ordenes.value.filter((orden) => {
        const texto = [
            orden.correlativo,
            nombrePaciente(orden),
            getExamenesOrden(orden),
            orden.paciente?.usuario?.correo,
            orden.paciente?.usuario?.nombre,
            orden.paciente?.usuario?.apellido,
        ].join(' ');

        return (
            coincideFecha(orden.fecha_orden, f.fecha) &&
            coincideTexto(texto, f.usuario) &&
            coincideEstado(orden.estado, f.estado)
        );
    });
});

const resultadosUnificados = computed(() => {
    if (esPaciente.value) {
        return resultados.value.map((resultado) => ({
            tipo: 'paciente_resultado',
            estado_visual: resultado.estado,
            fecha_visual: resultado.fecha_resultado,
            paciente_visual: nombrePaciente(resultado),
            examen_visual: resultado.detalle_orden?.examen?.nombre ?? resultado.detalleOrden?.examen?.nombre ?? '—',
            orden_visual: resultado.detalle_orden?.orden?.correlativo ?? resultado.detalleOrden?.orden?.correlativo ?? '—',
            raw: resultado,
        }));
    }

    if (!puedeRegistrarResultados.value) {
        return resultados.value.map((resultado) => ({
            tipo: 'registrado',
            estado_visual: resultado.estado,
            fecha_visual: resultado.fecha_resultado,
            paciente_visual: nombrePaciente(resultado),
            examen_visual: resultado.detalle_orden?.examen?.nombre ?? resultado.detalleOrden?.examen?.nombre ?? '—',
            orden_visual: resultado.detalle_orden?.orden?.correlativo ?? resultado.detalleOrden?.orden?.correlativo ?? '—',
            raw: resultado,
        }));
    }

    const pendientes = pendientesLaboratorio.value.map((detalle) => ({
        tipo: 'pendiente',
        estado_visual: 'pendiente_resultado',
        fecha_visual: detalle.fecha_muestra ?? detalle.orden?.fecha_orden,
        paciente_visual: nombrePaciente(detalle),
        examen_visual: detalle.examen?.nombre ?? '—',
        orden_visual: detalle.orden?.correlativo ?? '—',
        raw: detalle,
    }));

    const registrados = resultados.value.map((resultado) => ({
        tipo: 'registrado',
        estado_visual: resultado.estado,
        fecha_visual: resultado.fecha_resultado,
        paciente_visual: nombrePaciente(resultado),
        examen_visual: resultado.detalle_orden?.examen?.nombre ?? resultado.detalleOrden?.examen?.nombre ?? '—',
        orden_visual: resultado.detalle_orden?.orden?.correlativo ?? resultado.detalleOrden?.orden?.correlativo ?? '—',
        raw: resultado,
    }));

    return [...pendientes, ...registrados];
});

const resultadosFiltrados = computed(() => {
    const f = filtros.value.resultados;

    return resultadosUnificados.value.filter((item) => {
        const texto = [
            item.paciente_visual,
            item.examen_visual,
            item.orden_visual,
            item.raw?.archivo_pdf,
            item.raw?.detalle_orden?.orden?.paciente?.usuario?.correo,
            item.raw?.detalleOrden?.orden?.paciente?.usuario?.correo,
            item.raw?.orden?.paciente?.usuario?.correo,
        ].join(' ');

        return (
            coincideFecha(item.fecha_visual, f.fecha) &&
            coincideTexto(texto, f.usuario) &&
            coincideEstado(item.estado_visual, f.estado)
        );
    });
});

const correosFiltrados = computed(() => {
    const f = filtros.value.correos;

    return enviosCorreo.value.filter((envio) => {
        const texto = [envio.correo_destino, envio.archivo_adjunto, envio.estado_envio].join(' ');

        return (
            coincideFecha(envio.fecha_envio, f.fecha) &&
            coincideTexto(texto, f.usuario) &&
            coincideEstado(envio.estado_envio, f.estado)
        );
    });
});

const usuariosFiltrados = computed(() => {
    const f = filtros.value.usuarios;

    return usuarios.value.filter((usuario) => {
        const texto = [usuario.nombre, usuario.apellido, usuario.correo, usuario.rol].join(' ');

        return (
            coincideFecha(usuario.created_at, f.fecha) &&
            coincideTexto(texto, f.usuario) &&
            coincideEstado(usuario.estado, f.estado)
        );
    });
});

const puedeTomarMuestra = (cita) => {
    const estadoValido = ['agendada', 'confirmada'].includes(String(cita.estado ?? '').toLowerCase());
    return puedeGestionarMuestra.value && estadoValido;
};

const marcarMuestraTomada = async (cita) => {
    if (!confirm(`¿Confirmar que la muestra de ${nombrePaciente(cita)} ya fue tomada?`)) return;

    try {
        const response = await axios.put(`/api/recepcion/citas/${cita.id}/muestra-tomada`);

        if (response.data?.ok) {
            await fetchAll();
            alert(response.data?.mensaje || 'Muestra tomada correctamente.');
        } else {
            alert(response.data?.mensaje || 'No se pudo actualizar la cita.');
        }
    } catch (error) {
        console.error(error);
        alert(error.response?.data?.mensaje || 'No se pudo marcar la muestra como tomada.');
    }
};

const abrirModalResultado = async (detalle) => {
    resultadoModalVisible.value = true;
    resultadoLoading.value = true;
    resultadoSaving.value = false;
    resultadoError.value = '';
    resultadoMensaje.value = '';
    detalleSeleccionado.value = null;
    parametrosResultado.value = [];
    resultadoForm.value = {};
    observacionesResultado.value = '';

    try {
        const response = await axios.get(`/api/laboratorio/resultados-formulario/${detalle.id}`);
        const data = response.data?.data;

        detalleSeleccionado.value = data?.detalle_orden ?? null;
        parametrosResultado.value = data?.parametros ?? [];

        const valoresIniciales = {};

        parametrosResultado.value.forEach((parametro) => {
            valoresIniciales[parametro.nombre_parametro] = '';
        });

        resultadoForm.value = valoresIniciales;
    } catch (error) {
        console.error(error);
        resultadoError.value = error.response?.data?.mensaje || 'No se pudo cargar el formulario del resultado.';
    } finally {
        resultadoLoading.value = false;
    }
};

const cerrarModalResultado = () => {
    resultadoModalVisible.value = false;
};

const abrirModalResultadoPaciente = async (resultadoUnificado) => {
    resultadoPacienteVisible.value = true;
    resultadoPacienteLoading.value = true;
    resultadoPacienteError.value = '';
    resultadoPacienteSeleccionado.value = null;

    try {
        const resultadoId = resultadoUnificado?.raw?.id;
        const response = await axios.get(`/api/paciente/resultados/${resultadoId}`);
        resultadoPacienteSeleccionado.value = response.data?.data ?? null;
    } catch (error) {
        console.error(error);
        resultadoPacienteError.value = error.response?.data?.mensaje || 'No se pudo cargar el resultado.';
    } finally {
        resultadoPacienteLoading.value = false;
    }
};

const cerrarModalResultadoPaciente = () => {
    resultadoPacienteVisible.value = false;
};

const verPdfResultado = () => {
    alert('La vista PDF se conectará en el siguiente paso.');
};

const codigoExamenSeleccionado = computed(() => {
    return detalleSeleccionado.value?.examen?.codigo ?? '';
});

const opcionesPorParametro = {
    'ORI001:color': ['Amarillo', 'Ámbar', 'Pajizo', 'Rojizo', 'Marrón', 'Otro'],
    'ORI001:aspecto': ['Claro', 'Ligeramente turbio', 'Turbio'],
    'ORI001:proteinas': ['Negativo', 'Trazas', 'Positivo +', 'Positivo ++', 'Positivo +++'],
    'ORI001:glucosa': ['Negativo', 'Trazas', 'Positivo +', 'Positivo ++', 'Positivo +++'],
    'ORI001:cetonas': ['Negativo', 'Trazas', 'Positivo +', 'Positivo ++', 'Positivo +++'],
    'ORI001:nitritos': ['Negativo', 'Positivo'],
    'ORI001:leucocitos': ['Ausentes', 'Escasos', 'Moderados', 'Abundantes'],
    'ORI001:eritrocitos': ['Ausentes', 'Escasos', 'Moderados', 'Abundantes'],
    'ORI001:bacterias': ['Ausentes', 'Escasas', 'Moderadas', 'Abundantes'],

    'COP001:color': ['Café', 'Marrón', 'Amarillo', 'Verde', 'Negro', 'Rojizo', 'Otro'],
    'COP001:consistencia': ['Formada', 'Blanda', 'Pastosa', 'Líquida', 'Dura'],
    'COP001:moco': ['Ausente', 'Escaso', 'Moderado', 'Abundante'],
    'COP001:sangre_oculta': ['Negativo', 'Positivo'],
    'COP001:parasitos': ['No se observan', 'Quistes', 'Trofozoítos', 'Huevos', 'Larvas', 'Otros'],
    'COP001:leucocitos': ['Ausentes', 'Escasos', 'Moderados', 'Abundantes'],
    'COP001:eritrocitos': ['Ausentes', 'Escasos', 'Moderados', 'Abundantes'],
    'COP001:restos_alimenticios': ['Ausentes', 'Escasos', 'Moderados', 'Abundantes'],
};

const opcionesParametro = (parametro) => {
    const key = `${codigoExamenSeleccionado.value}:${parametro.nombre_parametro}`;
    return opcionesPorParametro[key] ?? [];
};

const tieneOpciones = (parametro) => {
    return opcionesParametro(parametro).length > 0;
};

const esObservacionParametro = (parametro) => {
    return String(parametro.nombre_parametro ?? '').startsWith('observacion');
};

const inputTypeParametro = (tipo) => {
    if (['numero', 'decimal'].includes(tipo)) return 'number';
    if (tipo === 'fecha') return 'date';
    return 'text';
};

const inputStepParametro = (tipo) => {
    if (tipo === 'decimal') return '0.01';
    if (tipo === 'numero') return '1';
    return undefined;
};

const guardarResultadoLaboratorio = async () => {
    resultadoError.value = '';
    resultadoMensaje.value = '';

    if (!detalleSeleccionado.value) {
        resultadoError.value = 'No hay examen seleccionado.';
        return;
    }

    for (const parametro of parametrosResultado.value) {
        const valor = resultadoForm.value[parametro.nombre_parametro];

        if (parametro.obligatorio && (valor === null || valor === undefined || String(valor).trim() === '')) {
            resultadoError.value = `El campo ${parametro.etiqueta} es obligatorio.`;
            return;
        }
    }

    resultadoSaving.value = true;

    try {
        const response = await axios.post('/api/laboratorio/resultados', {
            detalle_orden_id: detalleSeleccionado.value.id,
            resultado_json: resultadoForm.value,
            observaciones_generales: observacionesResultado.value,
        });

        resultadoMensaje.value = response.data?.mensaje || 'Resultado registrado correctamente.';

        await fetchAll();

        setTimeout(() => {
            resultadoModalVisible.value = false;
        }, 800);
    } catch (error) {
        console.error(error);
        resultadoError.value = error.response?.data?.mensaje || 'No se pudo guardar el resultado.';
    } finally {
        resultadoSaving.value = false;
    }
};

const pacienteSeleccionado = computed(() => {
    return detalleSeleccionado.value?.orden?.paciente ?? null;
});

const usuarioPacienteSeleccionado = computed(() => {
    return pacienteSeleccionado.value?.usuario ?? null;
});

const pacienteResultadoSeleccionado = computed(() => {
    return resultadoPacienteSeleccionado.value?.detalle_orden?.orden?.paciente
        ?? resultadoPacienteSeleccionado.value?.detalleOrden?.orden?.paciente
        ?? null;
});

const usuarioPacienteResultadoSeleccionado = computed(() => {
    return pacienteResultadoSeleccionado.value?.usuario ?? null;
});

const detalleResultadoSeleccionado = computed(() => {
    return resultadoPacienteSeleccionado.value?.detalle_orden
        ?? resultadoPacienteSeleccionado.value?.detalleOrden
        ?? null;
});

const examenResultadoSeleccionado = computed(() => {
    return detalleResultadoSeleccionado.value?.examen ?? null;
});

const ordenResultadoSeleccionado = computed(() => {
    return detalleResultadoSeleccionado.value?.orden ?? null;
});

const parametrosResultadoPaciente = computed(() => {
    return examenResultadoSeleccionado.value?.parametros_resultado
        ?? examenResultadoSeleccionado.value?.parametrosResultado
        ?? [];
});

const valorResultadoPaciente = (parametro) => {
    const json = resultadoPacienteSeleccionado.value?.resultado_json ?? {};
    const valor = json[parametro.nombre_parametro];

    if (valor === null || valor === undefined || valor === '') return '—';

    return valor;
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
                                <Column field="correlativo" header="Correlativo" />

                                <Column header="Examen">
                                    <template #body="{ data }">
                                        {{ getExamenesOrden(data) }}
                                    </template>
                                </Column>

                                <Column field="estado" header="Estado orden">
                                    <template #body="{ data }">
                                        <span
                                            class="rounded-full px-3 py-1 text-xs font-bold"
                                            :class="badgeClass(data.estado)"
                                        >
                                            {{ textoEstadoOrden(data.estado) }}
                                        </span>
                                    </template>
                                </Column>

                                <Column header="Estado examen">
                                    <template #body="{ data }">
                                        <span
                                            class="rounded-full px-3 py-1 text-xs font-bold"
                                            :class="badgeClass(getEstadoExamenOrden(data))"
                                        >
                                            {{ textoEstadoExamenOrden(getEstadoExamenOrden(data)) }}
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
                                    <p class="text-sm font-bold text-emerald-700">Resultados procesados</p>
                                    <p class="mt-1 text-3xl font-black text-emerald-800">{{ resultadosFinalizados }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </section>

            <section v-if="activeModule === 'pacientes'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Pacientes registrados</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">
                                    Filtra por fecha de registro, paciente/correo o estado.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <input v-model="filtros.pacientes.fecha" type="date" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <input v-model="filtros.pacientes.usuario" type="text" placeholder="Buscar paciente" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <select v-model="filtros.pacientes.estado" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                    <option value="">Todos</option>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                                <Button label="Limpiar" icon="pi pi-filter-slash" severity="secondary" class="rounded-2xl!" @click="limpiarFiltros('pacientes')" />
                            </div>
                        </div>
                    </template>

                    <template #content>
                        <DataTable :value="pacientesFiltrados" paginator :rows="10" stripedRows>
                            <Column field="nombres" header="Nombres" sortable />
                            <Column field="apellidos" header="Apellidos" sortable />
                            <Column field="dui" header="DUI" />
                            <Column field="telefono" header="Teléfono" />
                            <Column header="Correo">
                                <template #body="{ data }">
                                    {{ data.usuario?.correo ?? data.usuario?.email ?? '—' }}
                                </template>
                            </Column>
                            <Column header="Estado">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.usuario?.estado)">
                                        {{ data.usuario?.estado ?? '—' }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeModule === 'citas'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Citas clínicas</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">
                                    Filtra por fecha, paciente o estado. Recepción puede enviar la muestra a laboratorio.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <input v-model="filtros.citas.fecha" type="date" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <input v-model="filtros.citas.usuario" type="text" placeholder="Buscar paciente" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <select v-model="filtros.citas.estado" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                    <option value="">Todos</option>
                                    <option value="agendada">Agendada</option>
                                    <option value="confirmada">Confirmada</option>
                                    <option value="muestra_tomada">Muestra tomada</option>
                                    <option value="en_laboratorio">En laboratorio</option>
                                    <option value="finalizada">Finalizada</option>
                                    <option value="cancelada">Cancelada</option>
                                </select>
                                <Button label="Limpiar" icon="pi pi-filter-slash" severity="secondary" class="rounded-2xl!" @click="limpiarFiltros('citas')" />
                            </div>
                        </div>
                    </template>

                    <template #content>
                        <DataTable :value="citasFiltradas" paginator :rows="10" stripedRows>
                            <Column field="fecha_cita" header="Fecha" sortable>
                                <template #body="{ data }">{{ formatFecha(data.fecha_cita) }}</template>
                            </Column>
                            <Column field="hora_cita" header="Hora" />
                            <Column header="Paciente">
                                <template #body="{ data }">{{ data.paciente?.nombres }} {{ data.paciente?.apellidos }}</template>
                            </Column>
                            <Column field="estado" header="Estado">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado)">
                                        {{ data.estado }}
                                    </span>
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="{ data }">
                                    <Button
                                        v-if="puedeTomarMuestra(data)"
                                        label="Muestra tomada"
                                        icon="pi pi-check"
                                        size="small"
                                        class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!"
                                        @click="marcarMuestraTomada(data)"
                                    />
                                    <span v-else class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">
                                        Sin acción
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeModule === 'examenes'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Catálogo de exámenes</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">
                                    Este apartado muestra la disponibilidad del catálogo, no el estado del examen del paciente.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <input v-model="filtros.examenes.fecha" type="date" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <input v-model="filtros.examenes.usuario" type="text" placeholder="Buscar examen" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <select v-model="filtros.examenes.estado" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                    <option value="">Todos</option>
                                    <option value="activo">Disponible</option>
                                    <option value="inactivo">No disponible</option>
                                </select>
                                <Button label="Limpiar" icon="pi pi-filter-slash" severity="secondary" class="rounded-2xl!" @click="limpiarFiltros('examenes')" />
                            </div>
                        </div>
                    </template>

                    <template #content>
                        <DataTable :value="examenesFiltrados" paginator :rows="10" stripedRows>
                            <Column field="codigo" header="Código" sortable />
                            <Column field="nombre" header="Examen" sortable />
                            <Column field="precio" header="Precio">
                                <template #body="{ data }">${{ Number(data.precio ?? 0).toFixed(2) }}</template>
                            </Column>
                            <Column field="tiempo_entrega_horas" header="Entrega" />
                            <Column field="estado" header="Disponibilidad">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado)">
                                        {{ textoDisponibilidadExamen(data.estado) }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeModule === 'ordenes'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Órdenes clínicas</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">
                                    Filtra por fecha, paciente, correlativo, examen o estado.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <input v-model="filtros.ordenes.fecha" type="date" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <input v-model="filtros.ordenes.usuario" type="text" placeholder="Buscar paciente, orden o examen" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <select v-model="filtros.ordenes.estado" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                    <option value="">Todos</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="recepcionado">Recepcionado</option>
                                    <option value="en_laboratorio">En laboratorio</option>
                                    <option value="finalizado">Finalizado</option>
                                    <option value="entregado">Entregado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                                <Button label="Limpiar" icon="pi pi-filter-slash" severity="secondary" class="rounded-2xl!" @click="limpiarFiltros('ordenes')" />
                            </div>
                        </div>
                    </template>

                    <template #content>
                        <DataTable :value="ordenesFiltradas" paginator :rows="10" stripedRows>
                            <Column field="correlativo" header="Correlativo" sortable />

                            <Column header="Paciente">
                                <template #body="{ data }">{{ data.paciente?.nombres }} {{ data.paciente?.apellidos }}</template>
                            </Column>

                            <Column header="Examen">
                                <template #body="{ data }">
                                    {{ getExamenesOrden(data) }}
                                </template>
                            </Column>

                            <Column field="fecha_orden" header="Fecha" sortable>
                                <template #body="{ data }">{{ formatFecha(data.fecha_orden) }}</template>
                            </Column>

                            <Column field="total" header="Total">
                                <template #body="{ data }">${{ Number(data.total ?? 0).toFixed(2) }}</template>
                            </Column>

                            <Column field="estado" header="Estado orden">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado)">
                                        {{ textoEstadoOrden(data.estado) }}
                                    </span>
                                </template>
                            </Column>

                            <Column header="Estado examen">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(getEstadoExamenOrden(data))">
                                        {{ textoEstadoExamenOrden(getEstadoExamenOrden(data)) }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeModule === 'resultados'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Resultados clínicos</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">
                                    Laboratorio registra resultados de exámenes enviados desde recepción.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <input v-model="filtros.resultados.fecha" type="date" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <input v-model="filtros.resultados.usuario" type="text" placeholder="Buscar paciente, examen u orden" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <select v-model="filtros.resultados.estado" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                    <option value="">Todos</option>
                                    <option value="pendiente_resultado">Pendiente resultado</option>
                                    <option value="borrador">Borrador</option>
                                    <option value="finalizado">Procesado</option>
                                    <option value="enviado">Enviado</option>
                                </select>
                                <Button label="Limpiar" icon="pi pi-filter-slash" severity="secondary" class="rounded-2xl!" @click="limpiarFiltros('resultados')" />
                            </div>
                        </div>
                    </template>

                    <template #content>
                        <DataTable :value="resultadosFiltrados" paginator :rows="10" stripedRows>
                            <Column field="fecha_visual" header="Fecha" sortable>
                                <template #body="{ data }">{{ formatFecha(data.fecha_visual) }}</template>
                            </Column>
                            <Column field="orden_visual" header="Orden" />
                            <Column field="paciente_visual" header="Paciente" />
                            <Column field="examen_visual" header="Examen" />
                            <Column field="estado_visual" header="Estado">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado_visual)">
                                        {{ textoEstadoResultado(data.estado_visual) }}
                                    </span>
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="{ data }">
                                    <Button
                                        v-if="esPaciente && data.tipo === 'paciente_resultado'"
                                        label="Ver resultados"
                                        icon="pi pi-eye"
                                        size="small"
                                        class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!"
                                        @click="abrirModalResultadoPaciente(data)"
                                    />

                                    <Button
                                        v-else-if="puedeRegistrarResultados && data.tipo === 'pendiente'"
                                        label="Añadir resultado"
                                        icon="pi pi-plus"
                                        size="small"
                                        class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!"
                                        @click="abrirModalResultado(data.raw)"
                                    />

                                    <span v-else class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">
                                        {{ data.tipo === 'registrado' || data.tipo === 'paciente_resultado' ? 'Registrado' : 'Sin acción' }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <section v-if="activeModule === 'correos'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Envíos por correo</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">Filtra por fecha, correo/archivo o estado.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <input v-model="filtros.correos.fecha" type="date" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <input v-model="filtros.correos.usuario" type="text" placeholder="Buscar correo" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <select v-model="filtros.correos.estado" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                    <option value="">Todos</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="enviado">Enviado</option>
                                    <option value="fallido">Fallido</option>
                                </select>
                                <Button label="Limpiar" icon="pi pi-filter-slash" severity="secondary" class="rounded-2xl!" @click="limpiarFiltros('correos')" />
                            </div>
                        </div>
                    </template>

                    <template #content>
                        <DataTable :value="correosFiltrados" paginator :rows="10" stripedRows>
                            <Column field="correo_destino" header="Destino" sortable />
                            <Column field="fecha_envio" header="Fecha envío">
                                <template #body="{ data }">{{ formatFecha(data.fecha_envio) }}</template>
                            </Column>
                            <Column field="archivo_adjunto" header="Adjunto" />
                            <Column field="estado_envio" header="Estado">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado_envio)">
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
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Usuarios del sistema</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">Filtra por fecha de registro, nombre/correo o estado.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <input v-model="filtros.usuarios.fecha" type="date" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <input v-model="filtros.usuarios.usuario" type="text" placeholder="Buscar usuario" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <select v-model="filtros.usuarios.estado" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                    <option value="">Todos</option>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                                <Button label="Limpiar" icon="pi pi-filter-slash" severity="secondary" class="rounded-2xl!" @click="limpiarFiltros('usuarios')" />
                            </div>
                        </div>
                    </template>

                    <template #content>
                        <DataTable :value="usuariosFiltrados" paginator :rows="10" stripedRows>
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
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado)">
                                        {{ data.estado }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>
        </div>

        <Dialog
            v-model:visible="resultadoModalVisible"
            modal
            header="Registrar resultado del examen"
            :style="{ width: 'min(980px, 96vw)' }"
            class="rounded-3xl"
        >
            <div v-if="resultadoLoading" class="flex min-h-80 items-center justify-center">
                <div class="text-center">
                    <i class="pi pi-spin pi-spinner text-4xl text-cyan-500"></i>
                    <p class="mt-4 font-bold text-slate-600">Cargando formulario...</p>
                </div>
            </div>

            <div v-else class="space-y-5">
                <div v-if="resultadoError" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                    <i class="pi pi-exclamation-triangle mr-2"></i>
                    {{ resultadoError }}
                </div>

                <div v-if="resultadoMensaje" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                    <i class="pi pi-check-circle mr-2"></i>
                    {{ resultadoMensaje }}
                </div>

                <div v-if="detalleSeleccionado" class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                    <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <h3 class="text-lg font-black text-slate-950">Información del paciente</h3>

                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                            <input :value="`${pacienteSeleccionado?.nombres ?? ''} ${pacienteSeleccionado?.apellidos ?? ''}`" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" placeholder="Paciente" />
                            <input :value="usuarioPacienteSeleccionado?.correo ?? '—'" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" placeholder="Correo" />
                            <input :value="formatFecha(pacienteSeleccionado?.fecha_nacimiento)" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" placeholder="Fecha nacimiento" />
                            <input :value="pacienteSeleccionado?.genero ?? '—'" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" placeholder="Género" />
                            <input :value="pacienteSeleccionado?.dui ?? '—'" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" placeholder="DUI" />
                            <input :value="pacienteSeleccionado?.telefono ?? '—'" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" placeholder="Teléfono" />
                            <textarea :value="pacienteSeleccionado?.direccion ?? '—'" readonly rows="2" class="md:col-span-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600" placeholder="Dirección"></textarea>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-cyan-100 bg-cyan-50 p-5">
                        <h3 class="text-lg font-black text-cyan-950">Información del examen</h3>

                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                            <input :value="detalleSeleccionado?.orden?.correlativo ?? '—'" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" placeholder="Orden" />
                            <input :value="detalleSeleccionado?.examen?.nombre ?? '—'" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" placeholder="Examen" />
                            <input :value="formatFecha(detalleSeleccionado?.fecha_muestra)" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" placeholder="Fecha muestra" />
                            <input :value="textoEstadoExamenOrden(detalleSeleccionado?.estado)" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" placeholder="Estado" />
                        </div>
                    </section>
                </div>

                <section class="rounded-3xl border border-slate-200 bg-white p-5">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-lg font-black text-slate-950">Resultados del examen</h3>
                        <p class="text-sm text-slate-500">
                            Usa las opciones predeterminadas cuando estén disponibles. Los campos numéricos solo aceptan números.
                        </p>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="parametro in parametrosResultado"
                            :key="parametro.id"
                            class="rounded-2xl border border-slate-100 bg-slate-50 p-4"
                        >
                            <label class="mb-2 block text-sm font-black text-slate-700">
                                {{ parametro.etiqueta }}
                                <span v-if="parametro.obligatorio" class="text-red-500">*</span>
                            </label>

                            <select
                                v-if="tieneOpciones(parametro)"
                                v-model="resultadoForm[parametro.nombre_parametro]"
                                class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                            >
                                <option value="">Selecciona</option>
                                <option
                                    v-for="opcion in opcionesParametro(parametro)"
                                    :key="opcion"
                                    :value="opcion"
                                >
                                    {{ opcion }}
                                </option>
                            </select>

                            <select
                                v-else-if="parametro.tipo_dato === 'booleano'"
                                v-model="resultadoForm[parametro.nombre_parametro]"
                                class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                            >
                                <option value="">Selecciona</option>
                                <option value="Positivo">Positivo</option>
                                <option value="Negativo">Negativo</option>
                            </select>

                            <textarea
                                v-else-if="esObservacionParametro(parametro)"
                                v-model="resultadoForm[parametro.nombre_parametro]"
                                rows="3"
                                class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                            ></textarea>

                            <input
                                v-else
                                v-model="resultadoForm[parametro.nombre_parametro]"
                                :type="inputTypeParametro(parametro.tipo_dato)"
                                :step="inputStepParametro(parametro.tipo_dato)"
                                class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                            />

                            <p v-if="parametro.unidad_medida || parametro.valor_referencia" class="mt-2 text-xs font-bold text-slate-500">
                                <span v-if="parametro.unidad_medida">Unidad: {{ parametro.unidad_medida }}</span>
                                <span v-if="parametro.unidad_medida && parametro.valor_referencia"> · </span>
                                <span v-if="parametro.valor_referencia">Referencia: {{ parametro.valor_referencia }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="mb-2 block text-sm font-black text-slate-700">Observaciones generales</label>
                        <textarea
                            v-model="observacionesResultado"
                            maxlength="200"
                            rows="3"
                            class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                            placeholder="Observaciones adicionales del resultado..."
                        ></textarea>
                        <p class="mt-1 text-right text-xs font-bold text-slate-400">
                            {{ observacionesResultado.length }}/200
                        </p>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        severity="secondary"
                        class="rounded-2xl!"
                        :disabled="resultadoSaving"
                        @click="cerrarModalResultado"
                    />

                    <Button
                        label="Guardar resultado"
                        icon="pi pi-save"
                        class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!"
                        :loading="resultadoSaving"
                        @click="guardarResultadoLaboratorio"
                    />
                </div>
            </div>
        </Dialog>

        <Dialog
            v-model:visible="resultadoPacienteVisible"
            modal
            header="Resultado del examen"
            :style="{ width: 'min(980px, 96vw)' }"
            class="rounded-3xl"
        >
            <div v-if="resultadoPacienteLoading" class="flex min-h-80 items-center justify-center">
                <div class="text-center">
                    <i class="pi pi-spin pi-spinner text-4xl text-cyan-500"></i>
                    <p class="mt-4 font-bold text-slate-600">Cargando resultado...</p>
                </div>
            </div>

            <div v-else class="space-y-5">
                <div
                    v-if="resultadoPacienteError"
                    class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700"
                >
                    <i class="pi pi-exclamation-triangle mr-2"></i>
                    {{ resultadoPacienteError }}
                </div>

                <div v-if="resultadoPacienteSeleccionado" class="space-y-5">
                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                        <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <h3 class="text-lg font-black text-slate-950">Información del paciente</h3>

                            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                                <input
                                    :value="`${pacienteResultadoSeleccionado?.nombres ?? ''} ${pacienteResultadoSeleccionado?.apellidos ?? ''}`"
                                    readonly
                                    class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600"
                                    placeholder="Paciente"
                                />

                                <input
                                    :value="usuarioPacienteResultadoSeleccionado?.correo ?? '—'"
                                    readonly
                                    class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600"
                                    placeholder="Correo"
                                />

                                <input
                                    :value="formatFecha(pacienteResultadoSeleccionado?.fecha_nacimiento)"
                                    readonly
                                    class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600"
                                    placeholder="Fecha nacimiento"
                                />

                                <input
                                    :value="pacienteResultadoSeleccionado?.genero ?? '—'"
                                    readonly
                                    class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600"
                                    placeholder="Género"
                                />

                                <input
                                    :value="pacienteResultadoSeleccionado?.dui ?? '—'"
                                    readonly
                                    class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600"
                                    placeholder="DUI"
                                />

                                <input
                                    :value="pacienteResultadoSeleccionado?.telefono ?? '—'"
                                    readonly
                                    class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600"
                                    placeholder="Teléfono"
                                />
                            </div>
                        </section>

                        <section class="rounded-3xl border border-cyan-100 bg-cyan-50 p-5">
                            <h3 class="text-lg font-black text-cyan-950">Información del examen</h3>

                            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                                <input
                                    :value="ordenResultadoSeleccionado?.correlativo ?? '—'"
                                    readonly
                                    class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800"
                                    placeholder="Orden"
                                />

                                <input
                                    :value="examenResultadoSeleccionado?.nombre ?? '—'"
                                    readonly
                                    class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800"
                                    placeholder="Examen"
                                />

                                <input
                                    :value="formatFecha(resultadoPacienteSeleccionado?.fecha_resultado)"
                                    readonly
                                    class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800"
                                    placeholder="Fecha resultado"
                                />

                                <input
                                    :value="textoEstadoResultado(resultadoPacienteSeleccionado?.estado)"
                                    readonly
                                    class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800"
                                    placeholder="Estado"
                                />
                            </div>
                        </section>
                    </div>

                    <section class="rounded-3xl border border-slate-200 bg-white p-5">
                        <div class="flex flex-col gap-1">
                            <h3 class="text-lg font-black text-slate-950">Resultados del examen</h3>
                            <p class="text-sm text-slate-500">
                                Estos son los valores registrados por laboratorio.
                            </p>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div
                                v-for="parametro in parametrosResultadoPaciente"
                                :key="parametro.id"
                                class="rounded-2xl border border-slate-100 bg-slate-50 p-4"
                            >
                                <p class="text-sm font-black text-slate-700">
                                    {{ parametro.etiqueta }}
                                </p>

                                <p class="mt-2 text-lg font-black text-slate-950">
                                    {{ valorResultadoPaciente(parametro) }}
                                    <span
                                        v-if="parametro.unidad_medida"
                                        class="text-sm font-bold text-slate-500"
                                    >
                                        {{ parametro.unidad_medida }}
                                    </span>
                                </p>

                                <p
                                    v-if="parametro.valor_referencia"
                                    class="mt-2 text-xs font-bold text-slate-500"
                                >
                                    Referencia: {{ parametro.valor_referencia }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="resultadoPacienteSeleccionado?.observaciones_generales"
                            class="mt-5 rounded-2xl bg-cyan-50 p-4"
                        >
                            <p class="text-sm font-black text-cyan-900">Observaciones generales</p>
                            <p class="mt-1 text-sm font-bold text-cyan-800">
                                {{ resultadoPacienteSeleccionado.observaciones_generales }}
                            </p>
                        </div>
                    </section>

                    <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                        <Button
                            label="Cerrar"
                            icon="pi pi-times"
                            severity="secondary"
                            class="rounded-2xl!"
                            @click="cerrarModalResultadoPaciente"
                        />

                        <Button
                            label="Ver PDF"
                            icon="pi pi-file-pdf"
                            class="rounded-2xl! bg-slate-950! text-white! hover:bg-slate-800!"
                            @click="verPdfResultado"
                        />
                    </div>
                </div>
            </div>
        </Dialog>
    </AuthenticatedLayout>
</template>
