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

// Datos por rol — cada rol solo carga lo que necesita
const usuarios = ref([]);
const pacientes = ref([]);
const examenes = ref([]);
const citas = ref([]);
const ordenes = ref([]);
const resultados = ref([]);           // Para paciente: sus resultados propios
const resultadosTodos = ref([]);      // Para recepcionista/admin/lab: todos los resultados
const enviosCorreo = ref([]);
const pendientesLaboratorio = ref([]); // Solo laboratorio

// Modales laboratorio
const resultadoModalVisible = ref(false);
const resultadoLoading = ref(false);
const resultadoSaving = ref(false);
const resultadoError = ref('');
const resultadoMensaje = ref('');
const detalleSeleccionado = ref(null);
const parametrosResultado = ref([]);
const resultadoForm = ref({});
const observacionesResultado = ref('');

// Modal paciente - ver resultado
const resultadoPacienteVisible = ref(false);
const resultadoPacienteLoading = ref(false);
const resultadoPacienteError = ref('');
const resultadoPacienteSeleccionado = ref(null);

// Estado reenvío correo
const reenvioLoading = ref(false);
const reenvioMensaje = ref('');
const reenvioError = ref('');

const filtros = ref({
    pacientes:  { fecha: '', usuario: '', estado: '' },
    citas:      { fecha: '', usuario: '', estado: '' },
    examenes:   { fecha: '', usuario: '', estado: '' },
    ordenes:    { fecha: '', usuario: '', estado: '' },
    resultados: { fecha: '', usuario: '', estado: '' },
    correos:    { fecha: '', usuario: '', estado: '' },
    usuarios:   { fecha: '', usuario: '', estado: '' },
});

// Roles
const userRole = computed(() => String(page.props.auth?.user?.rol ?? '').toLowerCase());
const esPaciente       = computed(() => userRole.value === 'paciente');
const esLaboratorio    = computed(() => userRole.value === 'laboratorio');
const esRecepcionista  = computed(() => userRole.value === 'recepcionista');
const esAdministrador  = computed(() => userRole.value === 'administrador');
const puedeVerPdfYReenviar = computed(() => esRecepcionista.value || esAdministrador.value);
const puedeRegistrarResultados = computed(() => esLaboratorio.value);
const puedeGestionarMuestra = computed(() => esRecepcionista.value || esAdministrador.value);

// Helpers
const unwrap = (payload) => {
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.data)) return payload.data;
    return [];
};

// Carga de datos por rol
const fetchAll = async () => {
    loading.value = true;
    try {
        if (esPaciente.value) {
            const res = await axios.get('/api/paciente/resultados');
            resultados.value = unwrap(res.data);
        } else if (esLaboratorio.value) {
            const [examenesRes, ordenesRes, resultadosRes, pendientesRes] = await Promise.all([
                axios.get('/api/examenes'),
                axios.get('/api/ordenes'),
                axios.get('/api/resultados'),
                axios.get('/api/laboratorio/resultados-pendientes'),
            ]);
            examenes.value = unwrap(examenesRes.data);
            ordenes.value  = unwrap(ordenesRes.data);
            resultadosTodos.value = unwrap(resultadosRes.data);
            pendientesLaboratorio.value = unwrap(pendientesRes.data);
        } else if (esRecepcionista.value) {
            const [pacientesRes, citasRes, ordenesRes, resultadosRes, correoRes] = await Promise.all([
                axios.get('/api/pacientes'),
                axios.get('/api/citas'),
                axios.get('/api/ordenes'),
                axios.get('/api/resultados'),
                axios.get('/api/envios-correo'),
            ]);
            pacientes.value    = unwrap(pacientesRes.data);
            citas.value        = unwrap(citasRes.data);
            ordenes.value      = unwrap(ordenesRes.data);
            resultadosTodos.value = unwrap(resultadosRes.data);
            enviosCorreo.value = unwrap(correoRes.data);
        } else if (esAdministrador.value) {
            const [usuariosRes, pacientesRes, examenesRes, citasRes, ordenesRes, resultadosRes, correoRes] = await Promise.all([
                axios.get('/api/usuarios'),
                axios.get('/api/pacientes'),
                axios.get('/api/examenes'),
                axios.get('/api/citas'),
                axios.get('/api/ordenes'),
                axios.get('/api/resultados'),
                axios.get('/api/envios-correo'),
            ]);
            usuarios.value     = unwrap(usuariosRes.data);
            pacientes.value    = unwrap(pacientesRes.data);
            examenes.value     = unwrap(examenesRes.data);
            citas.value        = unwrap(citasRes.data);
            ordenes.value      = unwrap(ordenesRes.data);
            resultadosTodos.value = unwrap(resultadosRes.data);
            enviosCorreo.value = unwrap(correoRes.data);
        }
    } catch (error) {
        console.error('Error cargando dashboard:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchAll);

// Formateo
const formatFecha = (value) => {
    if (!value) return '—';
    return String(value).slice(0, 10);
};

const textoNormalizado = (value) => String(value ?? '').toLowerCase().trim();
const coincideTexto = (base, filtro) => !filtro || textoNormalizado(base).includes(textoNormalizado(filtro));
const coincideFecha = (base, f) => !f || formatFecha(base) === f;
const coincideEstado = (base, f) => !f || textoNormalizado(base) === textoNormalizado(f);

const nombrePaciente = (item) => {
    const p =
        item?.paciente ??
        item?.orden?.paciente ??
        item?.detalle_orden?.orden?.paciente ??
        item?.detalleOrden?.orden?.paciente ??
        item;
    return `${p?.nombres ?? ''} ${p?.apellidos ?? ''}`.trim();
};

const limpiarFiltros = (modulo) => {
    filtros.value[modulo] = { fecha: '', usuario: '', estado: '' };
};

const getExamenesOrden = (orden) => {
    const detalles = orden?.detalles ?? [];
    if (!detalles.length) return '—';
    return detalles.map((d) => d.examen?.nombre ?? 'Examen').join(', ');
};

const getEstadoExamenOrden = (orden) => {
    const detalles = orden?.detalles ?? [];
    if (!detalles.length) return orden?.estado ?? '—';
    const estados = detalles.map((d) => String(d.estado ?? '').toLowerCase());
    if (estados.every((e) => e === 'finalizado')) return 'finalizado';
    if (estados.some((e) => e === 'en_proceso')) return 'en_proceso';
    if (estados.some((e) => e === 'muestra_tomada')) return 'muestra_tomada';
    if (estados.some((e) => e === 'pendiente')) return 'pendiente';
    if (estados.every((e) => e === 'cancelado')) return 'cancelado';
    return orden?.estado ?? '—';
};

const textoEstadoExamenOrden = (estado) => {
    const labels = {
        finalizado:     'Procesado',
        en_proceso:     'Pendiente de resultado',
        muestra_tomada: 'Muestra tomada',
        pendiente:      'Pendiente',
        cancelado:      'Cancelado',
        en_laboratorio: 'Pendiente de resultado',
    };
    return labels[String(estado ?? '').toLowerCase()] ?? estado ?? '—';
};

const textoEstadoOrden = (estado) => {
    const labels = {
        pendiente:      'Pendiente',
        recepcionado:   'Recepcionado',
        en_laboratorio: 'En laboratorio',
        finalizado:     'Finalizado',
        entregado:      'Entregado',
        cancelado:      'Cancelado',
    };
    return labels[String(estado ?? '').toLowerCase()] ?? estado ?? '—';
};

const textoDisponibilidadExamen = (estado) => {
    const v = String(estado ?? '').toLowerCase();
    if (v === 'activo') return 'Disponible';
    if (v === 'inactivo') return 'No disponible';
    return estado ?? '—';
};

const textoEstadoResultado = (estado) => {
    const labels = {
        pendiente_resultado: 'Pendiente resultado',
        borrador:   'Borrador',
        finalizado: 'Procesado',
        enviado:    'Enviado',
    };
    return labels[String(estado ?? '').toLowerCase()] ?? estado ?? '—';
};

// Computed estadísticas
const citasPendientes = computed(() =>
    citas.value.filter((c) => ['agendada', 'confirmada'].includes(String(c.estado).toLowerCase())).length
);
const ordenesPendientes = computed(() =>
    ordenes.value.filter((o) => ['pendiente', 'recepcionado', 'en_laboratorio'].includes(String(o.estado).toLowerCase())).length
);
const resultadosFinalizados = computed(() =>
    resultadosTodos.value.filter((r) => String(r.estado).toLowerCase() === 'finalizado').length
);
const correosPendientes = computed(() =>
    enviosCorreo.value.filter((e) => String(e.estado_envio).toLowerCase() === 'pendiente').length
);

// Cards para el inicio — adaptadas por rol
const cards = computed(() => {
    if (esPaciente.value) {
        const finalizados = resultados.value.filter((r) => ['finalizado','enviado'].includes(String(r.estado).toLowerCase())).length;
        return [
            { title: 'Mis resultados', value: resultados.value.length, icon: 'pi-file-check', color: 'teal', description: 'Resultados disponibles' },
            { title: 'Procesados', value: finalizados, icon: 'pi-check-circle', color: 'emerald', description: 'Resultados finalizados' },
        ];
    }
    if (esLaboratorio.value) {
        return [
            { title: 'Pendientes', value: pendientesLaboratorio.value.length, icon: 'pi-hourglass', color: 'amber', description: 'Exámenes por procesar' },
            { title: 'Registrados', value: resultadosTodos.value.length, icon: 'pi-file-check', color: 'teal', description: 'Resultados ingresados' },
            { title: 'Órdenes activas', value: ordenesPendientes.value, icon: 'pi-clipboard', color: 'blue', description: 'En laboratorio' },
        ];
    }
    return [
        { title: 'Pacientes', value: pacientes.value.length, icon: 'pi-users', color: 'cyan', description: 'Pacientes registrados' },
        { title: 'Citas pendientes', value: citasPendientes.value, icon: 'pi-calendar-clock', color: 'emerald', description: 'Agendadas o confirmadas' },
        { title: 'Órdenes activas', value: ordenesPendientes.value, icon: 'pi-clipboard', color: 'blue', description: 'Pendientes o en laboratorio' },
        { title: 'Resultados listos', value: resultadosFinalizados.value, icon: 'pi-file-check', color: 'teal', description: 'Resultados procesados' },
    ];
});

const moduleTitle = computed(() => {
    const titles = {
        inicio:      'Panel general',
        pacientes:   'Gestión de pacientes',
        citas:       'Gestión de citas',
        examenes:    'Catálogo de exámenes',
        ordenes:     'Órdenes clínicas',
        resultados:  'Resultados clínicos',
        correos:     'Envíos por correo',
        usuarios:    'Gestión de usuarios',
    };
    return titles[activeModule.value] ?? 'Panel general';
});

const badgeClass = (estado) => {
    const v = String(estado ?? '').toLowerCase();
    if (['activo','finalizado','enviado','entregado','confirmada'].includes(v))
        return 'bg-emerald-100 text-emerald-700';
    if (['pendiente','agendada','borrador','recepcionado','pendiente_resultado'].includes(v))
        return 'bg-amber-100 text-amber-700';
    if (['muestra_tomada','en_laboratorio','en_proceso'].includes(v))
        return 'bg-blue-100 text-blue-700';
    if (['cancelado','cancelada','fallido','inactivo'].includes(v))
        return 'bg-red-100 text-red-700';
    return 'bg-slate-100 text-slate-700';
};

// Filtros
const pacientesFiltrados = computed(() => {
    const f = filtros.value.pacientes;
    return pacientes.value.filter((p) => {
        const texto = [p.nombres, p.apellidos, p.dui, p.telefono, p.usuario?.correo].join(' ');
        return coincideFecha(p.created_at, f.fecha) && coincideTexto(texto, f.usuario) && coincideEstado(p.usuario?.estado ?? '', f.estado);
    });
});

const citasFiltradas = computed(() => {
    const f = filtros.value.citas;
    return citas.value.filter((c) => {
        const texto = [nombrePaciente(c), c.paciente?.usuario?.correo].join(' ');
        return coincideFecha(c.fecha_cita, f.fecha) && coincideTexto(texto, f.usuario) && coincideEstado(c.estado, f.estado);
    });
});

const examenesFiltrados = computed(() => {
    const f = filtros.value.examenes;
    return examenes.value.filter((e) => {
        const texto = [e.codigo, e.nombre, e.descripcion].join(' ');
        return coincideFecha(e.created_at, f.fecha) && coincideTexto(texto, f.usuario) && coincideEstado(e.estado, f.estado);
    });
});

const ordenesFiltradas = computed(() => {
    const f = filtros.value.ordenes;
    return ordenes.value.filter((o) => {
        const texto = [o.correlativo, nombrePaciente(o), getExamenesOrden(o)].join(' ');
        return coincideFecha(o.fecha_orden, f.fecha) && coincideTexto(texto, f.usuario) && coincideEstado(o.estado, f.estado);
    });
});

// Resultados unificados
const resultadosUnificados = computed(() => {
    if (esPaciente.value) {
        return resultados.value.map((r) => ({
            tipo: 'paciente_resultado',
            estado_visual: r.estado,
            fecha_visual: r.fecha_resultado,
            paciente_visual: nombrePaciente(r),
            examen_visual: r.detalle_orden?.examen?.nombre ?? r.detalleOrden?.examen?.nombre ?? '—',
            orden_visual:  r.detalle_orden?.orden?.correlativo ?? r.detalleOrden?.orden?.correlativo ?? '—',
            raw: r,
        }));
    }

    if (esLaboratorio.value) {
        const pendientes = pendientesLaboratorio.value.map((d) => ({
            tipo: 'pendiente',
            estado_visual: 'pendiente_resultado',
            fecha_visual: d.fecha_muestra ?? d.orden?.fecha_orden,
            paciente_visual: nombrePaciente(d),
            examen_visual: d.examen?.nombre ?? '—',
            orden_visual:  d.orden?.correlativo ?? '—',
            raw: d,
        }));
        const registrados = resultadosTodos.value.map((r) => ({
            tipo: 'registrado',
            estado_visual: r.estado,
            fecha_visual: r.fecha_resultado,
            paciente_visual: nombrePaciente(r),
            examen_visual: r.detalle_orden?.examen?.nombre ?? r.detalleOrden?.examen?.nombre ?? '—',
            orden_visual:  r.detalle_orden?.orden?.correlativo ?? r.detalleOrden?.orden?.correlativo ?? '—',
            raw: r,
        }));
        return [...pendientes, ...registrados];
    }

    return resultadosTodos.value.map((r) => ({
        tipo: 'registrado',
        estado_visual: r.estado,
        fecha_visual: r.fecha_resultado,
        paciente_visual: nombrePaciente(r),
        examen_visual: r.detalle_orden?.examen?.nombre ?? r.detalleOrden?.examen?.nombre ?? '—',
        orden_visual:  r.detalle_orden?.orden?.correlativo ?? r.detalleOrden?.orden?.correlativo ?? '—',
        raw: r,
    }));
});

const resultadosFiltrados = computed(() => {
    const f = filtros.value.resultados;
    return resultadosUnificados.value.filter((item) => {
        const texto = [item.paciente_visual, item.examen_visual, item.orden_visual].join(' ');
        return coincideFecha(item.fecha_visual, f.fecha) && coincideTexto(texto, f.usuario) && coincideEstado(item.estado_visual, f.estado);
    });
});

const correosFiltrados = computed(() => {
    const f = filtros.value.correos;
    return enviosCorreo.value.filter((e) => {
        const texto = [e.correo_destino, e.archivo_adjunto, e.estado_envio].join(' ');
        return coincideFecha(e.fecha_envio, f.fecha) && coincideTexto(texto, f.usuario) && coincideEstado(e.estado_envio, f.estado);
    });
});

const usuariosFiltrados = computed(() => {
    const f = filtros.value.usuarios;
    return usuarios.value.filter((u) => {
        const texto = [u.nombre, u.apellido, u.correo, u.rol].join(' ');
        return coincideFecha(u.created_at, f.fecha) && coincideTexto(texto, f.usuario) && coincideEstado(u.estado, f.estado);
    });
});

// Acciones citas
const puedeTomarMuestra = (cita) =>
    puedeGestionarMuestra.value && ['agendada','confirmada'].includes(String(cita.estado ?? '').toLowerCase());

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
        alert(error.response?.data?.mensaje || 'No se pudo marcar la muestra como tomada.');
    }
};

// Modal laboratorio
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
        const inicial = {};
        parametrosResultado.value.forEach((p) => { inicial[p.nombre_parametro] = ''; });
        resultadoForm.value = inicial;
    } catch (error) {
        resultadoError.value = error.response?.data?.mensaje || 'No se pudo cargar el formulario.';
    } finally {
        resultadoLoading.value = false;
    }
};

const cerrarModalResultado = () => { resultadoModalVisible.value = false; };

// Validaciones clínicas del modal de laboratorio
const esParametroNumerico = (parametro) => {
    return ['numero', 'decimal'].includes(String(parametro?.tipo_dato ?? '').toLowerCase());
};

const limpiarNumeroClinico = (valor) => {
    if (valor === null || valor === undefined) return null;

    const textoOriginal = String(valor).trim();
    if (textoOriginal === '') return null;

    const sinEspacios = textoOriginal.replace(/\s/g, '');
    const tienePunto = sinEspacios.includes('.');
    const tieneComa = sinEspacios.includes(',');

    let normalizado = sinEspacios;

    if (tienePunto && tieneComa) {
        normalizado = normalizado.replace(/,/g, '');
    } else if (tieneComa && !tienePunto) {
        const partes = normalizado.split(',');
        if (partes.length === 2 && partes[1].length <= 2) {
            normalizado = `${partes[0]}.${partes[1]}`;
        } else {
            normalizado = normalizado.replace(/,/g, '');
        }
    }

    const numero = Number(normalizado);
    return Number.isFinite(numero) ? numero : null;
};

const rangoParametro = (parametro) => {
    if (!esParametroNumerico(parametro)) return null;

    const referencia = String(parametro?.valor_referencia ?? '').trim();
    if (!referencia) return null;

    const numeros = referencia.match(/-?\d+(?:[.,]\d+)*/g);
    if (!numeros || numeros.length < 2) return null;

    const min = limpiarNumeroClinico(numeros[0]);
    const max = limpiarNumeroClinico(numeros[1]);

    if (min === null || max === null) return null;

    return {
        min: Math.min(min, max),
        max: Math.max(min, max),
    };
};

const inputMinParametro = (parametro) => {
    if (!esParametroNumerico(parametro)) return undefined;

    const rango = rangoParametro(parametro);

    if (rango?.min !== undefined && rango.min > 0) {
        return rango.min;
    }

    return parametro.tipo_dato === 'numero' ? 1 : 0.01;
};

const inputMaxParametro = (parametro) => {
    if (!esParametroNumerico(parametro)) return undefined;

    const rango = rangoParametro(parametro);
    return rango?.max ?? undefined;
};

const placeholderParametro = (parametro) => {
    if (esParametroNumerico(parametro) && parametro.valor_referencia) {
        return `Rango permitido: ${parametro.valor_referencia}`;
    }

    if (parametro.valor_referencia) {
        return `Referencia: ${parametro.valor_referencia}`;
    }

    if (parametro.tipo_dato === 'decimal' || parametro.tipo_dato === 'numero') {
        return 'Ingrese un valor mayor a 0';
    }

    return '';
};

const bloquearTeclasNumeroInvalido = (event, parametro) => {
    if (!esParametroNumerico(parametro)) return;

    const teclasInvalidas = ['-', '+', 'e', 'E'];

    if (teclasInvalidas.includes(event.key)) {
        event.preventDefault();
        return;
    }

    if (parametro.tipo_dato === 'numero' && ['.', ','].includes(event.key)) {
        event.preventDefault();
    }
};

const sanitizarEntradaNumerica = (parametro) => {
    if (!esParametroNumerico(parametro)) return;

    let valor = String(resultadoForm.value[parametro.nombre_parametro] ?? '');

    if (parametro.tipo_dato === 'numero') {
        valor = valor.replace(/[^\d]/g, '');
    } else {
        valor = valor.replace(/[^\d.]/g, '');
        const partes = valor.split('.');
        if (partes.length > 2) {
            valor = `${partes[0]}.${partes.slice(1).join('')}`;
        }
    }

    resultadoForm.value[parametro.nombre_parametro] = valor;
};

const validarCampoParametro = (parametro) => {
    const valor = resultadoForm.value[parametro.nombre_parametro];
    const valorTexto = String(valor ?? '').trim();

    if (parametro.obligatorio && valorTexto === '') {
        return `El campo ${parametro.etiqueta} es obligatorio.`;
    }

    if (!esParametroNumerico(parametro)) return null;

    if (valorTexto === '') return null;

    const numero = limpiarNumeroClinico(valorTexto);

    if (numero === null) {
        return `El campo ${parametro.etiqueta} debe ser un número válido.`;
    }

    if (numero <= 0) {
        return `El campo ${parametro.etiqueta} debe ser mayor a 0.`;
    }

    if (parametro.tipo_dato === 'numero' && !Number.isInteger(numero)) {
        return `El campo ${parametro.etiqueta} debe ser un número entero.`;
    }

    const rango = rangoParametro(parametro);

    if (rango && (numero < rango.min || numero > rango.max)) {
        return `El campo ${parametro.etiqueta} debe estar entre ${rango.min} y ${rango.max}. Referencia: ${parametro.valor_referencia}.`;
    }

    return null;
};

const guardarResultadoLaboratorio = async () => {
    resultadoError.value = '';
    resultadoMensaje.value = '';

    if (!detalleSeleccionado.value) {
        resultadoError.value = 'No hay examen seleccionado.';
        return;
    }

    for (const parametro of parametrosResultado.value) {
        const errorCampo = validarCampoParametro(parametro);
        if (errorCampo) {
            resultadoError.value = errorCampo;
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
        setTimeout(() => { resultadoModalVisible.value = false; }, 800);
    } catch (error) {
        resultadoError.value = error.response?.data?.mensaje || 'No se pudo guardar el resultado.';
    } finally {
        resultadoSaving.value = false;
    }
};

// Modal paciente – ver resultado
const abrirModalResultadoPaciente = async (resultadoUnificado) => {
    resultadoPacienteVisible.value = true;
    resultadoPacienteLoading.value = true;
    resultadoPacienteError.value = '';
    resultadoPacienteSeleccionado.value = null;
    try {
        const id = resultadoUnificado?.raw?.id;
        const response = await axios.get(`/api/paciente/resultados/${id}`);
        resultadoPacienteSeleccionado.value = response.data?.data ?? null;
    } catch (error) {
        resultadoPacienteError.value = error.response?.data?.mensaje || 'No se pudo cargar el resultado.';
    } finally {
        resultadoPacienteLoading.value = false;
    }
};

const cerrarModalResultadoPaciente = () => { resultadoPacienteVisible.value = false; };

// Ver PDF desde tabla de resultados
const verPdfResultado = (resultadoUnificado) => {
    const id = resultadoUnificado?.raw?.id;

    if (!id) {
        alert('No se encontró el resultado seleccionado.');
        return;
    }

    window.open(`/api/paciente/resultados/${id}/pdf`, '_blank');
};

// Ver PDF desde modal del paciente
const verPdfResultadoPaciente = () => {
    const id = resultadoPacienteSeleccionado.value?.id;

    if (!id) {
        alert('No se encontró el resultado seleccionado.');
        return;
    }

    window.open(`/api/paciente/resultados/${id}/pdf`, '_blank');
};

// Reenviar correo
const reenviarCorreo = async (resultadoUnificado) => {
    const id = resultadoUnificado?.raw?.id ?? null;
    if (!id) { alert('No se pudo identificar el resultado.'); return; }
    if (!confirm(`¿Reenviar correo con resultados a ${resultadoUnificado.paciente_visual}?`)) return;

    reenvioLoading.value = true;
    reenvioMensaje.value = '';
    reenvioError.value = '';
    try {
        const response = await axios.post(`/api/correos/reenviar/${id}`);
        if (response.data?.ok) {
            reenvioMensaje.value = response.data?.mensaje || 'Correo reenviado exitosamente.';
            await fetchAll();
            setTimeout(() => { reenvioMensaje.value = ''; }, 4000);
        } else {
            reenvioError.value = response.data?.mensaje || 'No se pudo reenviar el correo.';
        }
    } catch (error) {
        reenvioError.value = error.response?.data?.mensaje || 'Error al reenviar. Verifica las credenciales SMTP en el .env.';
    } finally {
        reenvioLoading.value = false;
    }
};

const tienePdf = (resultadoUnificado) => !!resultadoUnificado?.raw?.archivo_pdf;

// Helpers modal laboratorio
const codigoExamenSeleccionado = computed(() => detalleSeleccionado.value?.examen?.codigo ?? '');
const pacienteSeleccionado = computed(() => detalleSeleccionado.value?.orden?.paciente ?? null);
const usuarioPacienteSeleccionado = computed(() => pacienteSeleccionado.value?.usuario ?? null);

const pacienteResultadoSeleccionado = computed(() =>
    resultadoPacienteSeleccionado.value?.detalle_orden?.orden?.paciente ??
    resultadoPacienteSeleccionado.value?.detalleOrden?.orden?.paciente ?? null
);
const usuarioPacienteResultadoSeleccionado = computed(() => pacienteResultadoSeleccionado.value?.usuario ?? null);
const detalleResultadoSeleccionado = computed(() =>
    resultadoPacienteSeleccionado.value?.detalle_orden ?? resultadoPacienteSeleccionado.value?.detalleOrden ?? null
);
const examenResultadoSeleccionado = computed(() => detalleResultadoSeleccionado.value?.examen ?? null);
const ordenResultadoSeleccionado  = computed(() => detalleResultadoSeleccionado.value?.orden ?? null);
const parametrosResultadoPaciente = computed(() =>
    examenResultadoSeleccionado.value?.parametros_resultado ??
    examenResultadoSeleccionado.value?.parametrosResultado ?? []
);
const valorResultadoPaciente = (parametro) => {
    const v = (resultadoPacienteSeleccionado.value?.resultado_json ?? {})[parametro.nombre_parametro];
    return (v === null || v === undefined || v === '') ? '—' : v;
};

const opcionesPorParametro = {
    'ORI001:color':     ['Amarillo','Ámbar','Pajizo','Rojizo','Marrón','Otro'],
    'ORI001:aspecto':   ['Claro','Ligeramente turbio','Turbio'],
    'ORI001:proteinas': ['Negativo','Trazas','Positivo +','Positivo ++','Positivo +++'],
    'ORI001:glucosa':   ['Negativo','Trazas','Positivo +','Positivo ++','Positivo +++'],
    'ORI001:cetonas':   ['Negativo','Trazas','Positivo +','Positivo ++','Positivo +++'],
    'ORI001:nitritos':  ['Negativo','Positivo'],
    'ORI001:leucocitos':['Ausentes','Escasos','Moderados','Abundantes'],
    'ORI001:eritrocitos':['Ausentes','Escasos','Moderados','Abundantes'],
    'ORI001:bacterias': ['Ausentes','Escasas','Moderadas','Abundantes'],
    'COP001:color':     ['Café','Marrón','Amarillo','Verde','Negro','Rojizo','Otro'],
    'COP001:consistencia':['Formada','Blanda','Pastosa','Líquida','Dura'],
    'COP001:moco':      ['Ausente','Escaso','Moderado','Abundante'],
    'COP001:sangre_oculta':['Negativo','Positivo'],
    'COP001:parasitos': ['No se observan','Quistes','Trofozoítos','Huevos','Larvas','Otros'],
    'COP001:leucocitos':['Ausentes','Escasos','Moderados','Abundantes'],
    'COP001:eritrocitos':['Ausentes','Escasos','Moderados','Abundantes'],
    'COP001:restos_alimenticios':['Ausentes','Escasos','Moderados','Abundantes'],
};

const opcionesParametro = (p) => opcionesPorParametro[`${codigoExamenSeleccionado.value}:${p.nombre_parametro}`] ?? [];
const tieneOpciones = (p) => opcionesParametro(p).length > 0;
const esObservacionParametro = (p) => String(p.nombre_parametro ?? '').startsWith('observacion');
const inputTypeParametro = (tipo) => {
    if (['numero','decimal'].includes(tipo)) return 'number';
    if (tipo === 'fecha') return 'date';
    return 'text';
};
const inputStepParametro = (tipo) => tipo === 'decimal' ? '0.01' : tipo === 'numero' ? '1' : undefined;

// ─── Modal: Recepcionista registra paciente presencial ────────────────────────
const modalPacienteVisible  = ref(false);
const modalPacienteSaving   = ref(false);
const modalPacienteError    = ref('');
const modalPacienteMensaje  = ref('');
const showPassPaciente      = ref(false);
const showPassPacienteConf  = ref(false);

const formPaciente = ref({
    nombres: '', apellidos: '', correo: '', password: '', password_confirmation: '',
    dui: '', telefono: '', fecha_nacimiento: '', genero: '', direccion: '',
});

const abrirModalPaciente = () => {
    modalPacienteVisible.value = true;
    modalPacienteSaving.value  = false;
    modalPacienteError.value   = '';
    modalPacienteMensaje.value = '';
    showPassPaciente.value     = false;
    showPassPacienteConf.value = false;
    formPaciente.value = {
        nombres: '', apellidos: '', correo: '', password: '', password_confirmation: '',
        dui: '', telefono: '', fecha_nacimiento: '', genero: '', direccion: '',
    };
};

const guardarPaciente = async () => {
    modalPacienteSaving.value = true;
    modalPacienteError.value  = '';
    modalPacienteMensaje.value = '';
    try {
        const response = await axios.post('/api/recepcion/registrar-paciente', formPaciente.value);
        if (response.data?.ok) {
            modalPacienteMensaje.value = response.data?.mensaje || 'Paciente registrado correctamente.';
            await fetchAll();
            setTimeout(() => { modalPacienteVisible.value = false; }, 1200);
        } else {
            modalPacienteError.value = response.data?.mensaje || 'No se pudo registrar el paciente.';
        }
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            modalPacienteError.value = Object.values(errors).flat()[0];
        } else {
            modalPacienteError.value = error.response?.data?.mensaje || 'Error al registrar el paciente.';
        }
    } finally {
        modalPacienteSaving.value = false;
    }
};

// ─── Modal: Administrador crea usuario con cualquier rol ─────────────────────
const modalUsuarioVisible  = ref(false);
const modalUsuarioSaving   = ref(false);
const modalUsuarioError    = ref('');
const modalUsuarioMensaje  = ref('');
const showPassUsuario      = ref(false);
const showPassUsuarioConf  = ref(false);

const formUsuario = ref({
    nombre: '', apellido: '', correo: '', password: '', password_confirmation: '',
    rol: '', estado: 'activo',
    // Campos extra para cuando rol = paciente
    nombres: '', apellidos: '', dui: '', telefono: '', fecha_nacimiento: '', genero: '', direccion: '',
});

const today = computed(() => new Date().toISOString().split('T')[0]);

const abrirModalUsuario = () => {
    modalUsuarioVisible.value = true;
    modalUsuarioSaving.value  = false;
    modalUsuarioError.value   = '';
    modalUsuarioMensaje.value = '';
    showPassUsuario.value     = false;
    showPassUsuarioConf.value = false;
    formUsuario.value = {
        nombre: '', apellido: '', correo: '', password: '', password_confirmation: '',
        rol: '', estado: 'activo',
        nombres: '', apellidos: '', dui: '', telefono: '', fecha_nacimiento: '', genero: '', direccion: '',
    };
};

const guardarUsuario = async () => {
    modalUsuarioSaving.value = true;
    modalUsuarioError.value  = '';
    modalUsuarioMensaje.value = '';
    try {
        const response = await axios.post('/api/admin/registrar-usuario', formUsuario.value);
        if (response.data?.ok) {
            modalUsuarioMensaje.value = response.data?.mensaje || 'Usuario creado correctamente.';
            await fetchAll();
            setTimeout(() => { modalUsuarioVisible.value = false; }, 1200);
        } else {
            modalUsuarioError.value = response.data?.mensaje || 'No se pudo crear el usuario.';
        }
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            modalUsuarioError.value = Object.values(errors).flat()[0];
        } else {
            modalUsuarioError.value = error.response?.data?.mensaje || 'Error al crear el usuario.';
        }
    } finally {
        modalUsuarioSaving.value = false;
    }
};

const rolLabel = (rol) => ({
    paciente:      'Paciente',
    recepcionista: 'Recepcionista',
    laboratorio:   'Laboratorio',
    administrador: 'Administrador',
}[rol] ?? rol);

const rolColor = (rol) => ({
    paciente:      'bg-cyan-100 text-cyan-700',
    recepcionista: 'bg-emerald-100 text-emerald-700',
    laboratorio:   'bg-blue-100 text-blue-700',
    administrador: 'bg-violet-100 text-violet-700',
}[rol] ?? 'bg-slate-100 text-slate-700');
</script>


<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout v-model:activeModule="activeModule">

        <!-- ══ BANNER SUPERIOR ══ -->
        <div style="margin-bottom:28px;">
            <div style="
                border-radius:20px;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0c4a6e 100%);
                padding: 28px 32px; color:white;
                box-shadow: 0 8px 32px rgba(0,0,0,0.18);
                display:flex; align-items:center; justify-content:space-between;
                flex-wrap:wrap; gap:16px;
            ">
                <div>
                    <div style="font-size:11px; font-weight:800; letter-spacing:0.2em; text-transform:uppercase; color:#38bdf8; margin-bottom:8px;">
                        Clínica Minerva · SGE
                    </div>
                    <h1 style="font-size:28px; font-weight:900; margin:0 0 8px; line-height:1.2;">
                        {{ moduleTitle }}
                    </h1>
                    <p style="font-size:13.5px; color:#94a3b8; margin:0; max-width:520px; line-height:1.6;">
                        <span v-if="esPaciente">Consulta tus resultados y estado de tus exámenes clínicos.</span>
                        <span v-else-if="esLaboratorio">Registra resultados de los exámenes pendientes.</span>
                        <span v-else-if="esRecepcionista">Gestiona pacientes, citas, órdenes y resultados de la clínica.</span>
                        <span v-else>Panel de administración completo del sistema.</span>
                    </p>
                </div>
                <Button
                    label="Actualizar"
                    icon="pi pi-refresh"
                    :loading="loading"
                    @click="fetchAll"
                    style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:white; border-radius:12px; font-weight:700;"
                />
            </div>
        </div>

        <!-- ══ NOTIFICACIONES REENVÍO ══ -->
        <div v-if="reenvioMensaje" style="margin-bottom:14px; padding:12px 18px; border-radius:12px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
            <i class="pi pi-check-circle"></i>{{ reenvioMensaje }}
        </div>
        <div v-if="reenvioError" style="margin-bottom:14px; padding:12px 18px; border-radius:12px; background:#fff5f5; border:1px solid #fecaca; color:#991b1b; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
            <i class="pi pi-exclamation-triangle"></i>{{ reenvioError }}
        </div>

        <!-- ══ LOADING ══ -->
        <div v-if="loading" style="display:flex; align-items:center; justify-content:center; min-height:320px; background:white; border-radius:20px; box-shadow:0 2px 16px rgba(0,0,0,0.06);">
            <div style="text-align:center;">
                <i class="pi pi-spin pi-spinner" style="font-size:40px; color:#0891b2;"></i>
                <p style="margin-top:16px; font-weight:700; color:#64748b; font-size:14px;">Cargando información...</p>
            </div>
        </div>

        <div v-else>
            <!-- ════ INICIO ════ -->
            <section v-if="activeModule === 'inicio'" style="display:flex; flex-direction:column; gap:24px;">
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:16px;">
                    <div
                        v-for="card in cards"
                        :key="card.title"
                        style="background:white; border-radius:18px; padding:22px; box-shadow:0 2px 12px rgba(0,0,0,0.06); border:1px solid #f1f5f9; display:flex; align-items:flex-start; justify-content:space-between;"
                    >
                        <div>
                            <div style="font-size:12px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:0.05em;">{{ card.title }}</div>
                            <div style="font-size:36px; font-weight:900; color:#0f172a; margin:8px 0 6px; line-height:1;">{{ card.value }}</div>
                            <div style="font-size:12px; color:#94a3b8;">{{ card.description }}</div>
                        </div>
                        <div :style="`
                            width:46px; height:46px; border-radius:14px; flex-shrink:0;
                            display:flex; align-items:center; justify-content:center;
                            background: ${card.color === 'cyan' ? '#ecfeff' : card.color === 'emerald' ? '#ecfdf5' : card.color === 'blue' ? '#eff6ff' : card.color === 'teal' ? '#f0fdfa' : card.color === 'amber' ? '#fffbeb' : '#f5f3ff'};
                        `">
                            <i :class="`pi ${card.icon}`" :style="`font-size:20px; color:${card.color === 'cyan' ? '#0891b2' : card.color === 'emerald' ? '#059669' : card.color === 'blue' ? '#2563eb' : card.color === 'teal' ? '#0d9488' : card.color === 'amber' ? '#d97706' : '#7c3aed'}`"></i>
                        </div>
                    </div>
                </div>

                <div v-if="esPaciente" style="background:linear-gradient(135deg, #ecfeff, #f0f9ff); border:1px solid #bae6fd; border-radius:18px; padding:24px;">
                    <div style="font-size:16px; font-weight:800; color:#0e7490;">Bienvenido al portal del paciente</div>
                    <p style="font-size:13px; color:#0369a1; margin:8px 0 0; line-height:1.6;">Usa el menú lateral para consultar tus resultados, solicitar exámenes o revisar tus citas.</p>
                </div>

                <div v-if="esLaboratorio" style="background:linear-gradient(135deg, #eff6ff, #f0f9ff); border:1px solid #bfdbfe; border-radius:18px; padding:24px;">
                    <div style="font-size:16px; font-weight:800; color:#1d4ed8;">Exámenes pendientes de resultado</div>
                    <p style="font-size:13px; color:#2563eb; margin:8px 0 0; line-height:1.6;">Ve a la sección <strong>Resultados</strong> para registrar los valores clínicos de los exámenes pendientes.</p>
                </div>

                <div v-if="!esPaciente && !esLaboratorio" style="display:grid; grid-template-columns:2fr 1fr; gap:16px; flex-wrap:wrap;">
                    <Card class="rounded-3xl! shadow-sm">
                        <template #title>
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <span style="font-size:15px; font-weight:800; color:#0f172a;">Órdenes recientes</span>
                                <span style="background:#ecfeff; color:#0e7490; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700;">{{ ordenes.length }} registros</span>
                            </div>
                        </template>
                        <template #content>
                            <DataTable :value="ordenes.slice(0, 6)" size="small" stripedRows>
                                <Column field="correlativo" header="Correlativo" />
                                <Column header="Examen"><template #body="{ data }">{{ getExamenesOrden(data) }}</template></Column>
                                <Column header="Estado">
                                    <template #body="{ data }">
                                        <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado)">{{ textoEstadoOrden(data.estado) }}</span>
                                    </template>
                                </Column>
                                <Column field="total" header="Total"><template #body="{ data }">${{ Number(data.total ?? 0).toFixed(2) }}</template></Column>
                            </DataTable>
                        </template>
                    </Card>
                    <Card class="rounded-3xl! shadow-sm">
                        <template #title><span style="font-size:15px; font-weight:800; color:#0f172a;">Resumen envíos</span></template>
                        <template #content>
                            <div style="display:flex; flex-direction:column; gap:12px;">
                                <div style="padding:16px; border-radius:14px; background:linear-gradient(135deg,#fffbeb,#fef3c7); border:1px solid #fde68a;">
                                    <div style="font-size:11px; font-weight:700; color:#92400e; text-transform:uppercase; letter-spacing:0.05em;">Correos pendientes</div>
                                    <div style="font-size:32px; font-weight:900; color:#b45309; margin-top:4px;">{{ correosPendientes }}</div>
                                </div>
                                <div style="padding:16px; border-radius:14px; background:linear-gradient(135deg,#ecfdf5,#d1fae5); border:1px solid #a7f3d0;">
                                    <div style="font-size:11px; font-weight:700; color:#065f46; text-transform:uppercase; letter-spacing:0.05em;">Resultados listos</div>
                                    <div style="font-size:32px; font-weight:900; color:#047857; margin-top:4px;">{{ resultadosFinalizados }}</div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </section>

            <!-- ════ PACIENTES ════ -->
            <section v-if="activeModule === 'pacientes' && (esRecepcionista || esAdministrador)">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Pacientes registrados</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">Filtra por fecha, nombre/correo o estado.</p>
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
                                <Button v-if="esRecepcionista || esAdministrador" label="Nuevo paciente" icon="pi pi-user-plus" class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600! md:col-span-4 xl:col-span-1!" @click="abrirModalPaciente" />
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="pacientesFiltrados" paginator :rows="10" stripedRows>
                            <Column field="nombres" header="Nombres" sortable />
                            <Column field="apellidos" header="Apellidos" sortable />
                            <Column field="dui" header="DUI" />
                            <Column field="telefono" header="Teléfono" />
                            <Column header="Correo"><template #body="{ data }">{{ data.usuario?.correo ?? '—' }}</template></Column>
                            <Column header="Estado">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.usuario?.estado)">{{ data.usuario?.estado ?? '—' }}</span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <!-- ════ CITAS ════ -->
            <section v-if="activeModule === 'citas' && (esRecepcionista || esAdministrador)">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Citas clínicas</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">Recepción puede marcar la muestra como tomada.</p>
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
                            <Column field="fecha_cita" header="Fecha" sortable><template #body="{ data }">{{ formatFecha(data.fecha_cita) }}</template></Column>
                            <Column field="hora_cita" header="Hora" />
                            <Column header="Paciente"><template #body="{ data }">{{ data.paciente?.nombres }} {{ data.paciente?.apellidos }}</template></Column>
                            <Column field="estado" header="Estado">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado)">{{ data.estado }}</span>
                                </template>
                            </Column>
                            <Column header="Acción">
                                <template #body="{ data }">
                                    <Button v-if="puedeTomarMuestra(data)" label="Muestra tomada" icon="pi pi-check" size="small" class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!" @click="marcarMuestraTomada(data)" />
                                    <span v-else class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Sin acción</span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <!-- ════ EXÁMENES ════ -->
            <section v-if="activeModule === 'examenes' && (esLaboratorio || esAdministrador)">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Catálogo de exámenes</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">Exámenes disponibles en la clínica.</p>
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
                            <Column field="precio" header="Precio"><template #body="{ data }">${{ Number(data.precio ?? 0).toFixed(2) }}</template></Column>
                            <Column field="tiempo_entrega_horas" header="Entrega (hrs)" />
                            <Column field="estado" header="Disponibilidad">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado)">{{ textoDisponibilidadExamen(data.estado) }}</span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <!-- ════ ÓRDENES ════ -->
            <section v-if="activeModule === 'ordenes' && (esRecepcionista || esLaboratorio || esAdministrador)">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Órdenes clínicas</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">Filtra por fecha, paciente, correlativo o estado.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <input v-model="filtros.ordenes.fecha" type="date" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <input v-model="filtros.ordenes.usuario" type="text" placeholder="Buscar paciente u orden" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
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
                            <Column header="Paciente"><template #body="{ data }">{{ data.paciente?.nombres }} {{ data.paciente?.apellidos }}</template></Column>
                            <Column header="Examen"><template #body="{ data }">{{ getExamenesOrden(data) }}</template></Column>
                            <Column field="fecha_orden" header="Fecha" sortable><template #body="{ data }">{{ formatFecha(data.fecha_orden) }}</template></Column>
                            <Column field="total" header="Total"><template #body="{ data }">${{ Number(data.total ?? 0).toFixed(2) }}</template></Column>
                            <Column header="Estado orden">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado)">{{ textoEstadoOrden(data.estado) }}</span>
                                </template>
                            </Column>
                            <Column header="Estado examen">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(getEstadoExamenOrden(data))">{{ textoEstadoExamenOrden(getEstadoExamenOrden(data)) }}</span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <!-- ════ RESULTADOS ════ -->
            <section v-if="activeModule === 'resultados'">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">
                                    <span v-if="esPaciente">Mis resultados clínicos</span>
                                    <span v-else>Resultados clínicos</span>
                                </h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">
                                    <span v-if="esPaciente">Solo se muestran tus resultados personales.</span>
                                    <span v-else-if="esLaboratorio">Registra resultados pendientes o revisa los ya ingresados.</span>
                                    <span v-else>Visualiza el PDF o reenvía el correo al paciente.</span>
                                </p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <input v-model="filtros.resultados.fecha" type="date" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <input v-model="filtros.resultados.usuario" type="text" placeholder="Buscar paciente o examen" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <select v-model="filtros.resultados.estado" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                    <option value="">Todos</option>
                                    <option v-if="!esPaciente" value="pendiente_resultado">Pendiente resultado</option>
                                    <option value="finalizado">Procesado</option>
                                    <option value="enviado">Enviado</option>
                                </select>
                                <Button label="Limpiar" icon="pi pi-filter-slash" severity="secondary" class="rounded-2xl!" @click="limpiarFiltros('resultados')" />
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <DataTable :value="resultadosFiltrados" paginator :rows="10" stripedRows>
                            <Column field="fecha_visual" header="Fecha" sortable><template #body="{ data }">{{ formatFecha(data.fecha_visual) }}</template></Column>
                            <Column field="orden_visual" header="Orden" />
                            <Column v-if="!esPaciente" field="paciente_visual" header="Paciente" />
                            <Column field="examen_visual" header="Examen" />
                            <Column field="estado_visual" header="Estado">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado_visual)">{{ textoEstadoResultado(data.estado_visual) }}</span>
                                </template>
                            </Column>
                            <Column header="Acciones" style="min-width:230px;">
                                <template #body="{ data }">
                                    <div v-if="esPaciente" class="flex flex-wrap gap-2">
                                        <Button label="Ver resultado" icon="pi pi-eye" size="small" class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!" @click="abrirModalResultadoPaciente(data)" />
                                    </div>
                                    <div v-else-if="esLaboratorio && data.tipo === 'pendiente'" class="flex flex-wrap gap-2">
                                        <Button label="Añadir resultado" icon="pi pi-plus" size="small" class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!" @click="abrirModalResultado(data.raw)" />
                                    </div>
                                    <div v-else-if="puedeVerPdfYReenviar && data.tipo === 'registrado'" class="flex flex-wrap gap-2">
                                        <Button label="Ver PDF" icon="pi pi-file-pdf" size="small" :disabled="!tienePdf(data)" class="rounded-2xl! bg-slate-900! text-white! hover:bg-slate-700! disabled:opacity-40!" @click="verPdfResultado(data)" />
                                        <Button label="Reenviar correo" icon="pi pi-send" size="small" :disabled="!tienePdf(data) || reenvioLoading" :loading="reenvioLoading" class="rounded-2xl! bg-emerald-600! text-white! hover:bg-emerald-700! disabled:opacity-40!" @click="reenviarCorreo(data)" />
                                    </div>
                                    <div v-else-if="esLaboratorio && data.tipo === 'registrado'">
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Registrado</span>
                                    </div>
                                    <span v-else class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Sin acción</span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <!-- ════ CORREOS ════ -->
            <section v-if="activeModule === 'correos' && (esRecepcionista || esAdministrador)">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Envíos por correo</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">Historial de correos enviados a pacientes.</p>
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
                            <Column field="fecha_envio" header="Fecha"><template #body="{ data }">{{ formatFecha(data.fecha_envio) }}</template></Column>
                            <Column header="Examen"><template #body="{ data }">{{ data.resultado?.detalle_orden?.examen?.nombre ?? data.resultado?.detalleOrden?.examen?.nombre ?? '—' }}</template></Column>
                            <Column field="estado_envio" header="Estado">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado_envio)">{{ data.estado_envio }}</span>
                                </template>
                            </Column>
                            <Column header="Error">
                                <template #body="{ data }">
                                    <span v-if="data.error_detalle" style="font-size:11px; color:#dc2626;">{{ data.error_detalle }}</span>
                                    <span v-else style="font-size:11px; color:#94a3b8;">—</span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>

            <!-- ════ USUARIOS ════ -->
            <section v-if="activeModule === 'usuarios' && esAdministrador">
                <Card class="rounded-3xl! shadow-sm">
                    <template #title>
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">Usuarios del sistema</h2>
                                <p class="mt-1 text-sm font-normal text-slate-500">Filtra por nombre, correo o estado.</p>
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
                                <Button label="Nuevo usuario" icon="pi pi-plus" class="rounded-2xl! bg-violet-600! text-white! hover:bg-violet-700! md:col-span-4 xl:col-span-1!" @click="abrirModalUsuario" />
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
                                    <span class="rounded-full px-3 py-1 text-xs font-bold capitalize" :class="rolColor(data.rol)">{{ rolLabel(data.rol) }}</span>
                                </template>
                            </Column>
                            <Column field="estado" header="Estado">
                                <template #body="{ data }">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="badgeClass(data.estado)">{{ data.estado }}</span>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </section>
        </div>

        <!-- ════ MODAL: Registrar resultado (Laboratorio) ════ -->
        <Dialog v-model:visible="resultadoModalVisible" modal header="Registrar resultado del examen" :style="{ width: 'min(980px, 96vw)' }" class="rounded-3xl">
            <div v-if="resultadoLoading" class="flex min-h-80 items-center justify-center">
                <div class="text-center"><i class="pi pi-spin pi-spinner text-4xl text-cyan-500"></i><p class="mt-4 font-bold text-slate-600">Cargando formulario...</p></div>
            </div>
            <div v-else class="space-y-5">
                <div v-if="resultadoError" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700"><i class="pi pi-exclamation-triangle mr-2"></i>{{ resultadoError }}</div>
                <div v-if="resultadoMensaje" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700"><i class="pi pi-check-circle mr-2"></i>{{ resultadoMensaje }}</div>
                <div v-if="detalleSeleccionado" class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                    <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <h3 class="text-lg font-black text-slate-950">Información del paciente</h3>
                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                            <input :value="`${pacienteSeleccionado?.nombres ?? ''} ${pacienteSeleccionado?.apellidos ?? ''}`" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" placeholder="Paciente" />
                            <input :value="usuarioPacienteSeleccionado?.correo ?? '—'" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" placeholder="Correo" />
                            <input :value="formatFecha(pacienteSeleccionado?.fecha_nacimiento)" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" placeholder="Fecha nacimiento" />
                            <input :value="pacienteSeleccionado?.dui ?? '—'" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" placeholder="DUI" />
                        </div>
                    </section>
                    <section class="rounded-3xl border border-cyan-100 bg-cyan-50 p-5">
                        <h3 class="text-lg font-black text-cyan-950">Información del examen</h3>
                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                            <input :value="detalleSeleccionado?.orden?.correlativo ?? '—'" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" />
                            <input :value="detalleSeleccionado?.examen?.nombre ?? '—'" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" />
                            <input :value="formatFecha(detalleSeleccionado?.fecha_muestra)" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" />
                            <input :value="textoEstadoExamenOrden(detalleSeleccionado?.estado)" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" />
                        </div>
                    </section>
                </div>
                <section class="rounded-3xl border border-slate-200 bg-white p-5">
                    <h3 class="text-lg font-black text-slate-950">Parámetros del resultado</h3>
                    <p class="mt-1 text-sm text-slate-500">Los campos numéricos validan que el valor sea mayor a 0 y, si existe rango de referencia, que esté dentro del rango permitido.</p>
                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div v-for="parametro in parametrosResultado" :key="parametro.id" class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <label class="mb-2 block text-sm font-black text-slate-700">{{ parametro.etiqueta }}<span v-if="parametro.obligatorio" class="text-red-500">*</span></label>
                            <select v-if="tieneOpciones(parametro)" v-model="resultadoForm[parametro.nombre_parametro]" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                <option value="">Selecciona</option>
                                <option v-for="op in opcionesParametro(parametro)" :key="op" :value="op">{{ op }}</option>
                            </select>
                            <select v-else-if="parametro.tipo_dato === 'booleano'" v-model="resultadoForm[parametro.nombre_parametro]" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                <option value="">Selecciona</option><option value="Positivo">Positivo</option><option value="Negativo">Negativo</option>
                            </select>
                            <textarea v-else-if="esObservacionParametro(parametro)" v-model="resultadoForm[parametro.nombre_parametro]" rows="3" class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"></textarea>
                            <input
                                v-else
                                v-model="resultadoForm[parametro.nombre_parametro]"
                                :type="inputTypeParametro(parametro.tipo_dato)"
                                :step="inputStepParametro(parametro.tipo_dato)"
                                :min="inputMinParametro(parametro)"
                                :max="inputMaxParametro(parametro)"
                                :placeholder="placeholderParametro(parametro)"
                                :inputmode="esParametroNumerico(parametro) ? 'decimal' : undefined"
                                class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"
                                @keydown="bloquearTeclasNumeroInvalido($event, parametro)"
                                @input="sanitizarEntradaNumerica(parametro)"
                            />
                            <p v-if="parametro.unidad_medida || parametro.valor_referencia" class="mt-2 text-xs font-bold text-slate-500">
                                <span v-if="parametro.unidad_medida">Unidad: {{ parametro.unidad_medida }}</span>
                                <span v-if="parametro.unidad_medida && parametro.valor_referencia"> · </span>
                                <span v-if="parametro.valor_referencia">Rango permitido: {{ parametro.valor_referencia }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-5">
                        <label class="mb-2 block text-sm font-black text-slate-700">Observaciones generales</label>
                        <textarea v-model="observacionesResultado" maxlength="200" rows="3" class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100"></textarea>
                        <p class="mt-1 text-right text-xs font-bold text-slate-400">{{ observacionesResultado.length }}/200</p>
                    </div>
                </section>
                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <Button label="Cancelar" icon="pi pi-times" severity="secondary" class="rounded-2xl!" :disabled="resultadoSaving" @click="cerrarModalResultado" />
                    <Button label="Guardar resultado" icon="pi pi-save" class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!" :loading="resultadoSaving" @click="guardarResultadoLaboratorio" />
                </div>
            </div>
        </Dialog>

        <!-- ════ MODAL: Ver resultado paciente ════ -->
        <Dialog v-model:visible="resultadoPacienteVisible" modal header="Tu resultado clínico" :style="{ width: 'min(980px, 96vw)' }" class="rounded-3xl">
            <div v-if="resultadoPacienteLoading" class="flex min-h-80 items-center justify-center">
                <div class="text-center"><i class="pi pi-spin pi-spinner text-4xl text-cyan-500"></i><p class="mt-4 font-bold text-slate-600">Cargando resultado...</p></div>
            </div>
            <div v-else class="space-y-5">
                <div v-if="resultadoPacienteError" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700"><i class="pi pi-exclamation-triangle mr-2"></i>{{ resultadoPacienteError }}</div>
                <div v-if="resultadoPacienteSeleccionado" class="space-y-5">
                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                        <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <h3 class="text-lg font-black text-slate-950">Mis datos</h3>
                            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                                <input :value="`${pacienteResultadoSeleccionado?.nombres ?? ''} ${pacienteResultadoSeleccionado?.apellidos ?? ''}`" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" />
                                <input :value="usuarioPacienteResultadoSeleccionado?.correo ?? '—'" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" />
                                <input :value="formatFecha(pacienteResultadoSeleccionado?.fecha_nacimiento)" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" />
                                <input :value="pacienteResultadoSeleccionado?.dui ?? '—'" readonly class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600" />
                            </div>
                        </section>
                        <section class="rounded-3xl border border-cyan-100 bg-cyan-50 p-5">
                            <h3 class="text-lg font-black text-cyan-950">Detalle del examen</h3>
                            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                                <input :value="ordenResultadoSeleccionado?.correlativo ?? '—'" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" />
                                <input :value="examenResultadoSeleccionado?.nombre ?? '—'" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" />
                                <input :value="formatFecha(resultadoPacienteSeleccionado?.fecha_resultado)" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" />
                                <input :value="textoEstadoResultado(resultadoPacienteSeleccionado?.estado)" readonly class="h-11 rounded-2xl border border-cyan-100 bg-white px-4 text-sm font-bold text-cyan-800" />
                            </div>
                        </section>
                    </div>
                    <section class="rounded-3xl border border-slate-200 bg-white p-5">
                        <h3 class="text-lg font-black text-slate-950">Valores del resultado</h3>
                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div v-for="parametro in parametrosResultadoPaciente" :key="parametro.id" class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <p class="text-sm font-black text-slate-700">{{ parametro.etiqueta }}</p>
                                <p class="mt-2 text-lg font-black text-slate-950">
                                    {{ valorResultadoPaciente(parametro) }}
                                    <span v-if="parametro.unidad_medida" class="text-sm font-bold text-slate-500">{{ parametro.unidad_medida }}</span>
                                </p>
                                <p v-if="parametro.valor_referencia" class="mt-1 text-xs font-bold text-slate-500">Ref: {{ parametro.valor_referencia }}</p>
                            </div>
                        </div>
                        <div v-if="resultadoPacienteSeleccionado?.observaciones_generales" class="mt-5 rounded-2xl bg-cyan-50 p-4">
                            <p class="text-sm font-black text-cyan-900">Observaciones</p>
                            <p class="mt-1 text-sm font-bold text-cyan-800">{{ resultadoPacienteSeleccionado.observaciones_generales }}</p>
                        </div>
                    </section>
                    <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                        <Button label="Cerrar" icon="pi pi-times" severity="secondary" class="rounded-2xl!" @click="cerrarModalResultadoPaciente" />
                        <Button label="Ver PDF" icon="pi pi-file-pdf" :disabled="!resultadoPacienteSeleccionado?.archivo_pdf" class="rounded-2xl! bg-slate-900! text-white! hover:bg-slate-700! disabled:opacity-40!" @click="verPdfResultadoPaciente" />
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- ════ MODAL: Recepcionista registra paciente presencial ════ -->
        <Dialog v-model:visible="modalPacienteVisible" modal header="Registrar nuevo paciente" :style="{ width: 'min(680px, 95vw)' }" class="rounded-3xl">
            <div style="padding:4px 0;">
                <div v-if="modalPacienteMensaje" style="margin-bottom:16px; padding:12px 16px; border-radius:12px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <i class="pi pi-check-circle"></i> {{ modalPacienteMensaje }}
                </div>
                <div v-if="modalPacienteError" style="margin-bottom:16px; padding:12px 16px; border-radius:12px; background:#fff5f5; border:1px solid #fecaca; color:#991b1b; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <i class="pi pi-exclamation-triangle"></i> {{ modalPacienteError }}
                </div>

                <div style="margin-bottom:16px;">
                    <div class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-400">
                        <span class="h-px flex-1 bg-slate-100"></span>Datos personales<span class="h-px flex-1 bg-slate-100"></span>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">Nombres</label>
                            <input v-model="formPaciente.nombres" type="text" placeholder="Nombres del paciente" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" /></div>
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">Apellidos</label>
                            <input v-model="formPaciente.apellidos" type="text" placeholder="Apellidos del paciente" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" /></div>
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">Fecha de nacimiento</label>
                            <input v-model="formPaciente.fecha_nacimiento" type="date" :max="today" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" /></div>
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">Género</label>
                            <select v-model="formPaciente.genero" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100">
                                <option value="">Selecciona</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino">Femenino</option>
                                <option value="Otro">Otro</option>
                            </select></div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <div class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-400">
                        <span class="h-px flex-1 bg-slate-100"></span>Contacto<span class="h-px flex-1 bg-slate-100"></span>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="md:col-span-2"><label class="mb-2 block text-xs font-bold text-slate-700">Correo electrónico</label>
                            <input v-model="formPaciente.correo" type="email" placeholder="paciente@correo.com" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" /></div>
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">DUI <span class="font-normal text-slate-400">(sin guiones)</span></label>
                            <input v-model="formPaciente.dui" type="text" placeholder="123456789" maxlength="9" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 font-mono text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" /></div>
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">Teléfono</label>
                            <input v-model="formPaciente.telefono" type="text" placeholder="77665544" maxlength="8" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" /></div>
                        <div class="md:col-span-2"><label class="mb-2 block text-xs font-bold text-slate-700">Dirección</label>
                            <input v-model="formPaciente.direccion" type="text" placeholder="Dirección completa" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" /></div>
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <div class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-400">
                        <span class="h-px flex-1 bg-slate-100"></span>Acceso al sistema<span class="h-px flex-1 bg-slate-100"></span>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">Contraseña</label>
                            <div class="relative">
                                <input v-model="formPaciente.password" :type="showPassPaciente ? 'text' : 'password'" placeholder="Mínimo 8 caracteres" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 pr-12 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <button type="button" @click="showPassPaciente = !showPassPaciente" class="absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer border-none bg-transparent p-0 text-slate-400">
                                    <i :class="`pi ${showPassPaciente ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:16px;"></i></button></div></div>
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">Confirmar contraseña</label>
                            <div class="relative">
                                <input v-model="formPaciente.password_confirmation" :type="showPassPacienteConf ? 'text' : 'password'" placeholder="Repite la contraseña" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 pr-12 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <button type="button" @click="showPassPacienteConf = !showPassPacienteConf" class="absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer border-none bg-transparent p-0 text-slate-400">
                                    <i :class="`pi ${showPassPacienteConf ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:16px;"></i></button></div></div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Button label="Cancelar" icon="pi pi-times" severity="secondary" class="rounded-2xl!" :disabled="modalPacienteSaving" @click="modalPacienteVisible = false" />
                    <Button label="Registrar paciente" icon="pi pi-user-plus" :loading="modalPacienteSaving" class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!" @click="guardarPaciente" />
                </div>
            </div>
        </Dialog>

        <!-- ════ MODAL: Administrador crea nuevo usuario ════ -->
        <Dialog v-model:visible="modalUsuarioVisible" modal header="Crear nuevo usuario" :style="{ width: 'min(600px, 95vw)' }" class="rounded-3xl">
            <div style="padding:4px 0;">
                <div v-if="modalUsuarioMensaje" style="margin-bottom:16px; padding:12px 16px; border-radius:12px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <i class="pi pi-check-circle"></i> {{ modalUsuarioMensaje }}
                </div>
                <div v-if="modalUsuarioError" style="margin-bottom:16px; padding:12px 16px; border-radius:12px; background:#fff5f5; border:1px solid #fecaca; color:#991b1b; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <i class="pi pi-exclamation-triangle"></i> {{ modalUsuarioError }}
                </div>

                <div style="margin-bottom:16px;">
                    <div class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-400">
                        <span class="h-px flex-1 bg-slate-100"></span>Datos del usuario<span class="h-px flex-1 bg-slate-100"></span>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">Nombre</label>
                            <input v-model="formUsuario.nombre" type="text" placeholder="Nombre" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" /></div>
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">Apellido</label>
                            <input v-model="formUsuario.apellido" type="text" placeholder="Apellido" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" /></div>
                        <div class="md:col-span-2"><label class="mb-2 block text-xs font-bold text-slate-700">Correo electrónico</label>
                            <input v-model="formUsuario.correo" type="email" placeholder="correo@minerva.com" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" /></div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-xs font-bold text-slate-700">Rol del usuario</label>
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                                <button v-for="r in ['recepcionista','laboratorio','administrador']" :key="r" type="button" @click="formUsuario.rol = r"
                                    class="h-11 rounded-2xl border-2 text-xs font-bold capitalize transition-all"
                                    :class="formUsuario.rol === r ? (r === 'recepcionista' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : r === 'laboratorio' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-violet-500 bg-violet-50 text-violet-700') : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'">
                                    {{ r === 'laboratorio' ? 'Laboratorio' : r === 'recepcionista' ? 'Recepcionista' : r === 'administrador' ? 'Administrador' : 'Paciente' }}
                                </button>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-xs font-bold text-slate-700">Estado inicial</label>
                            <div class="flex gap-3">
                                <button type="button" @click="formUsuario.estado = 'activo'" class="h-11 flex-1 rounded-2xl border-2 text-xs font-bold transition-all" :class="formUsuario.estado === 'activo' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-500'">Activo</button>
                                <button type="button" @click="formUsuario.estado = 'inactivo'" class="h-11 flex-1 rounded-2xl border-2 text-xs font-bold transition-all" :class="formUsuario.estado === 'inactivo' ? 'border-red-400 bg-red-50 text-red-600' : 'border-slate-200 bg-white text-slate-500'">Inactivo</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <div class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-400">
                        <span class="h-px flex-1 bg-slate-100"></span>Contraseña de acceso<span class="h-px flex-1 bg-slate-100"></span>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">Contraseña</label>
                            <div class="relative">
                                <input v-model="formUsuario.password" :type="showPassUsuario ? 'text' : 'password'" placeholder="Mínimo 8 caracteres" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 pr-12 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <button type="button" @click="showPassUsuario = !showPassUsuario" class="absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer border-none bg-transparent p-0 text-slate-400">
                                    <i :class="`pi ${showPassUsuario ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:16px;"></i></button></div></div>
                        <div><label class="mb-2 block text-xs font-bold text-slate-700">Confirmar contraseña</label>
                            <div class="relative">
                                <input v-model="formUsuario.password_confirmation" :type="showPassUsuarioConf ? 'text' : 'password'" placeholder="Repite la contraseña" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 pr-12 text-sm font-bold text-slate-700 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-100" />
                                <button type="button" @click="showPassUsuarioConf = !showPassUsuarioConf" class="absolute right-4 top-1/2 -translate-y-1/2 cursor-pointer border-none bg-transparent p-0 text-slate-400">
                                    <i :class="`pi ${showPassUsuarioConf ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:16px;"></i></button></div></div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Button label="Cancelar" icon="pi pi-times" severity="secondary" class="rounded-2xl!" :disabled="modalUsuarioSaving" @click="modalUsuarioVisible = false" />
                    <Button label="Crear usuario" icon="pi pi-user-plus" :loading="modalUsuarioSaving" class="rounded-2xl! bg-violet-600! text-white! hover:bg-violet-700!" @click="guardarUsuario" />
                </div>
            </div>
        </Dialog>

    </AuthenticatedLayout>
</template>