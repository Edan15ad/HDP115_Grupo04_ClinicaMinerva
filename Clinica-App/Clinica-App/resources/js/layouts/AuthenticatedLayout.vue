<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    activeModule: { type: String, default: 'inicio' },
});
const emit = defineEmits(['update:activeModule']);

const page = usePage();
const sidebarOpen = ref(false);

const user = computed(() => page.props.auth?.user ?? {});
const userName  = computed(() => user.value.nombre || user.value.name || 'Usuario');
const userEmail = computed(() => user.value.correo || user.value.email || '');
const userRole  = computed(() => String(user.value.rol ?? '').toLowerCase() || 'usuario');

const userRoleLabel = computed(() => ({
    paciente:      'Paciente',
    recepcionista: 'Recepcionista',
    laboratorio:   'Laboratorio',
    administrador: 'Administrador',
}[userRole.value] ?? userRole.value));

const roleColor = computed(() => ({
    paciente:      { bg: '#0891b2', light: '#ecfeff', text: '#0e7490' },
    recepcionista: { bg: '#059669', light: '#ecfdf5', text: '#047857' },
    laboratorio:   { bg: '#2563eb', light: '#eff6ff', text: '#1d4ed8' },
    administrador: { bg: '#7c3aed', light: '#f5f3ff', text: '#6d28d9' },
}[userRole.value] ?? { bg: '#64748b', light: '#f8fafc', text: '#475569' }));

// ─── Módulos por rol ─────────────────────────────────────────────────────────
const modules = computed(() => {
    const all = [
        { key: 'inicio',       label: 'Inicio',         icon: 'pi-home',        roles: ['paciente','recepcionista','laboratorio','administrador'], route: '/dashboard' },
        { key: 'mis-examenes', label: 'Mis exámenes',   icon: 'pi-list-check',  roles: ['paciente'], route: '/paciente/mis-examenes' },
        { key: 'resultados',   label: 'Mis resultados', icon: 'pi-file-check',  roles: ['paciente'] },
        { key: 'pacientes',    label: 'Pacientes',      icon: 'pi-users',       roles: ['recepcionista','administrador'] },
        { key: 'citas',        label: 'Citas',          icon: 'pi-calendar',    roles: ['recepcionista','administrador'] },
        { key: 'ordenes',      label: 'Órdenes',        icon: 'pi-clipboard',   roles: ['recepcionista','laboratorio','administrador'] },
        { key: 'resultados',   label: 'Resultados',     icon: 'pi-file-check',  roles: ['recepcionista','laboratorio','administrador'] },
        { key: 'correos',      label: 'Envíos correo',  icon: 'pi-envelope',    roles: ['recepcionista','administrador'] },
        { key: 'examenes',     label: 'Exámenes',       icon: 'pi-list-check',  roles: ['laboratorio','administrador'] },
        { key: 'usuarios',     label: 'Usuarios',       icon: 'pi-user-edit',   roles: ['administrador'] },
    ];
    const filtered = all.filter((m) => m.roles.includes(userRole.value));
    const seen = new Set();
    return filtered.filter((m) => {
        if (seen.has(m.key)) return false;
        seen.add(m.key);
        return true;
    });
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

const logout = () => router.post('/logout');

// ─── Modal: editar perfil (paciente) ────────────────────────────────────────
const perfilVisible  = ref(false);
const perfilLoading  = ref(false);
const perfilSaving   = ref(false);
const perfilError    = ref('');
const perfilMensaje  = ref('');
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

const soloNumeros = (valor, max = 99) => {
    return String(valor ?? '').replace(/\D/g, '').slice(0, max);
};

const normalizarPerfilNumeros = () => {
    perfil.value.dui = soloNumeros(perfil.value.dui, 9);
    perfil.value.telefono = soloNumeros(perfil.value.telefono, 8);
};

const esCorreoValido = (correo) => {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(correo ?? '').trim());
};


const traducirErrorBackend = (mensaje) => {
    const original = String(mensaje ?? '').trim();
    const texto = original.toLowerCase();

    if (!original) return 'Ocurrió un error al procesar la solicitud.';

    if (texto.includes('the dui has already been taken')) {
        return 'El DUI ingresado ya está registrado por otro paciente.';
    }

    if (texto.includes('the correo has already been taken') || texto.includes('the email has already been taken')) {
        return 'El correo electrónico ingresado ya está registrado.';
    }

    if (texto.includes('the dui field is required')) {
        return 'El DUI es obligatorio.';
    }

    if (texto.includes('the correo field is required') || texto.includes('the email field is required')) {
        return 'El correo electrónico es obligatorio.';
    }

    if (texto.includes('the telefono field must not be greater than 8 characters')) {
        return 'El teléfono no debe tener más de 8 números.';
    }

    if (texto.includes('the dui field must not be greater than 9 characters')) {
        return 'El DUI no debe tener más de 9 números.';
    }

    if (texto.includes('the telefono field format is invalid')) {
        return 'El teléfono solo debe contener números.';
    }

    if (texto.includes('the dui field format is invalid')) {
        return 'El DUI solo debe contener números.';
    }

    if (texto.includes('the correo field must be a valid email address') || texto.includes('the email field must be a valid email address')) {
        return 'Ingresa un correo electrónico válido.';
    }

    if (texto.includes('the password actual field is required')) {
        return 'La contraseña actual es obligatoria.';
    }

    if (texto.includes('the password nuevo field is required')) {
        return 'La nueva contraseña es obligatoria.';
    }

    if (texto.includes('the password nuevo confirmation field is required')) {
        return 'La confirmación de la nueva contraseña es obligatoria.';
    }

    if (texto.includes('the password nuevo field confirmation does not match')) {
        return 'La confirmación de la nueva contraseña no coincide.';
    }

    return original;
};

const abrirPerfil = async () => {
    perfilVisible.value = true;
    perfilLoading.value = true;
    perfilError.value = '';
    perfilMensaje.value = '';

    try {
        const { data } = await axios.get('/api/paciente/perfil');
        const d = data?.data;

        perfil.value = {
            usuario_nombre: d?.usuario?.nombre ?? '',
            usuario_apellido: d?.usuario?.apellido ?? '',
            correo: d?.usuario?.correo ?? '',
            paciente_nombres: d?.paciente?.nombres ?? '',
            paciente_apellidos: d?.paciente?.apellidos ?? '',
            dui: soloNumeros(d?.paciente?.dui ?? '', 9),
            fecha_nacimiento: d?.paciente?.fecha_nacimiento ?? '',
            telefono: soloNumeros(d?.paciente?.telefono ?? '', 8),
            direccion: d?.paciente?.direccion ?? '',
        };
    } catch (e) {
        perfilError.value = traducirErrorBackend(e.response?.data?.mensaje || 'No se pudo cargar el perfil.');
    } finally {
        perfilLoading.value = false;
    }
};

const guardarPerfil = async () => {
    perfilError.value = '';
    perfilMensaje.value = '';

    normalizarPerfilNumeros();

    if (!String(perfil.value.correo ?? '').trim()) {
        perfilError.value = 'El correo electrónico es obligatorio.';
        return;
    }

    if (!esCorreoValido(perfil.value.correo)) {
        perfilError.value = 'Ingresa un correo electrónico válido.';
        return;
    }

    if (!String(perfil.value.dui ?? '').trim()) {
        perfilError.value = 'El DUI es obligatorio.';
        return;
    }

    if (String(perfil.value.dui).length !== 9) {
        perfilError.value = 'El DUI debe tener exactamente 9 números.';
        return;
    }

    if (perfil.value.telefono && String(perfil.value.telefono).length !== 8) {
        perfilError.value = 'El teléfono debe tener exactamente 8 números.';
        return;
    }

    perfilSaving.value = true;

    try {
        const { data } = await axios.put('/api/paciente/perfil', {
            correo: String(perfil.value.correo ?? '').trim(),
            dui: perfil.value.dui,
            telefono: perfil.value.telefono,
            direccion: perfil.value.direccion,
        });

        perfilMensaje.value = data?.mensaje || 'Perfil actualizado correctamente.';
    } catch (e) {
        const firstErr = e.response?.data?.errors ? Object.values(e.response.data.errors)[0]?.[0] : null;
        perfilError.value = traducirErrorBackend(firstErr || e.response?.data?.mensaje || 'Error al actualizar.');
    } finally {
        perfilSaving.value = false;
    }
};

// ─── Modal: cambiar contraseña ────────────────────────────────────────────────
const passVisible  = ref(false);
const passSaving   = ref(false);
const passError    = ref('');
const passMensaje  = ref('');
const passForm = ref({ password_actual: '', password_nuevo: '', password_nuevo_confirmation: '' });
const showPass = ref({ actual: false, nuevo: false, confirm: false });

const abrirCambiarPassword = () => {
    passVisible.value = true;
    passSaving.value = false;
    passError.value = '';
    passMensaje.value = '';
    passForm.value = { password_actual: '', password_nuevo: '', password_nuevo_confirmation: '' };
    showPass.value = { actual: false, nuevo: false, confirm: false };
};

const passwordStrength = computed(() => {
    const p = passForm.value.password_nuevo;
    if (!p) return 0;

    let score = 0;
    if (p.length >= 8) score++;
    if (/[a-z]/.test(p)) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^a-zA-Z0-9]/.test(p)) score++;
    return score;
});

const passwordStrengthLabel = computed(() => ['', 'Muy débil', 'Débil', 'Regular', 'Fuerte', 'Muy fuerte'][passwordStrength.value] ?? '');
const passwordStrengthColor = computed(() => ['', '#ef4444', '#f97316', '#eab308', '#22c55e', '#10b981'][passwordStrength.value] ?? '#e2e8f0');

const guardarPassword = async () => {
    passSaving.value = true;
    passError.value = '';
    passMensaje.value = '';

    try {
        const { data } = await axios.put('/api/usuario/cambiar-password', passForm.value);
        passMensaje.value = data?.mensaje || 'Contraseña actualizada correctamente.';
        passForm.value = { password_actual: '', password_nuevo: '', password_nuevo_confirmation: '' };
        setTimeout(() => {
            passVisible.value = false;
            passMensaje.value = '';
        }, 2000);
    } catch (e) {
        const firstErr = e.response?.data?.errors ? Object.values(e.response.data.errors)[0]?.[0] : null;
        passError.value = traducirErrorBackend(firstErr || e.response?.data?.mensaje || 'No se pudo cambiar la contraseña.');
    } finally {
        passSaving.value = false;
    }
};

// ─── Menú de usuario (dropdown) ──────────────────────────────────────────────
const userMenuOpen = ref(false);
const toggleUserMenu = () => {
    userMenuOpen.value = !userMenuOpen.value;
};
const closeUserMenu = () => {
    userMenuOpen.value = false;
};

const initials = computed(() => {
    const n = userName.value.trim().split(' ');
    return (n[0]?.[0] ?? '') + (n[1]?.[0] ?? '');
});
</script>

<template>
    <div class="min-h-screen" style="background: #f1f5f9; font-family: 'DM Sans', 'Segoe UI', sans-serif;">
        <!-- ══════════════════ HEADER ══════════════════ -->
        <header style="
            position: fixed; top: 0; left: 0; right: 0; z-index: 50;
            height: 64px;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(0,0,0,0.07);
            box-shadow: 0 1px 20px rgba(0,0,0,0.06);
        ">
            <div style="display:flex; align-items:center; justify-content:space-between; height:100%; padding: 0 20px;">
                <!-- Logo + Hamburguesa -->
                <div style="display:flex; align-items:center; gap:12px;">
                    <button
                        class="lg:hidden"
                        @click="sidebarOpen = true"
                        style="width:38px; height:38px; border-radius:10px; border:none; background:transparent; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#475569;"
                    >
                        <i class="pi pi-bars" style="font-size:18px;"></i>
                    </button>

                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="
                            width:40px; height:40px; border-radius:12px;
                            background: linear-gradient(135deg, #0891b2, #0e7490);
                            display:flex; align-items:center; justify-content:center;
                            box-shadow: 0 4px 12px rgba(8,145,178,0.35);
                        ">
                            <i class="pi pi-heart-fill" style="color:white; font-size:16px;"></i>
                        </div>
                        <div>
                            <div style="font-size:15px; font-weight:800; color:#0f172a; line-height:1.2;">Clínica Minerva</div>
                            <div style="font-size:11px; color:#94a3b8; font-weight:500;">SGE · Sistema de exámenes</div>
                        </div>
                    </div>
                </div>

                <!-- Derecha: badge rol + menú usuario -->
                <div style="display:flex; align-items:center; gap:10px;">
                    <div class="sm-show" :style="`
                        padding: 4px 12px; border-radius:20px; font-size:11px; font-weight:700;
                        background: ${roleColor.light}; color: ${roleColor.text};
                        border: 1px solid ${roleColor.bg}22;
                        letter-spacing: 0.04em; text-transform:uppercase;
                    `">
                        {{ userRoleLabel }}
                    </div>

                    <div style="position:relative;" v-click-outside="closeUserMenu">
                        <button
                            @click="toggleUserMenu"
                            :style="`
                                display:flex; align-items:center; gap:8px;
                                padding: 6px 10px 6px 6px;
                                border-radius: 24px; border: 1.5px solid #e2e8f0;
                                background: white; cursor:pointer;
                                box-shadow: 0 1px 4px rgba(0,0,0,0.06);
                                transition: all 0.2s;
                            `"
                        >
                            <div :style="`
                                width:32px; height:32px; border-radius:50%;
                                background: linear-gradient(135deg, ${roleColor.bg}, ${roleColor.text});
                                display:flex; align-items:center; justify-content:center;
                                font-size:12px; font-weight:800; color:white; text-transform:uppercase;
                            `">{{ initials }}</div>
                            <div class="sm-show" style="text-align:left;">
                                <div style="font-size:13px; font-weight:700; color:#0f172a; line-height:1.2;">{{ userName }}</div>
                                <div style="font-size:11px; color:#94a3b8;">{{ userEmail }}</div>
                            </div>
                            <i class="pi pi-chevron-down sm-show" style="font-size:10px; color:#94a3b8;"></i>
                        </button>

                        <div
                            v-if="userMenuOpen"
                            style="
                                position:absolute; top:calc(100% + 8px); right:0;
                                min-width:220px; background:white; border-radius:16px;
                                box-shadow: 0 8px 32px rgba(0,0,0,0.14); border:1px solid #f1f5f9;
                                overflow:hidden; z-index:100;
                            "
                        >
                            <div :style="`padding:16px; background: linear-gradient(135deg, ${roleColor.bg}15, ${roleColor.light}); border-bottom:1px solid #f1f5f9;`">
                                <div style="font-size:13px; font-weight:800; color:#0f172a;">{{ userName }}</div>
                                <div style="font-size:11px; color:#64748b; margin-top:2px;">{{ userEmail }}</div>
                                <div :style="`
                                    display:inline-block; margin-top:6px;
                                    padding:2px 8px; border-radius:8px; font-size:10px; font-weight:700;
                                    background: ${roleColor.bg}; color:white; text-transform:uppercase;
                                `">{{ userRoleLabel }}</div>
                            </div>

                            <div style="padding:8px;">
                                <button
                                    v-if="userRole === 'paciente'"
                                    @click="closeUserMenu(); abrirPerfil();"
                                    style="width:100%; display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; border:none; background:transparent; cursor:pointer; font-size:13px; font-weight:600; color:#374151; text-align:left; transition:background 0.15s;"
                                    onmouseover="this.style.background='#f8fafc'"
                                    onmouseout="this.style.background='transparent'"
                                >
                                    <span style="width:30px; height:30px; border-radius:8px; background:#f1f5f9; display:flex; align-items:center; justify-content:center;">
                                        <i class="pi pi-user" style="font-size:13px; color:#64748b;"></i>
                                    </span>
                                    Editar perfil
                                </button>

                                <button
                                    @click="closeUserMenu(); abrirCambiarPassword();"
                                    style="width:100%; display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; border:none; background:transparent; cursor:pointer; font-size:13px; font-weight:600; color:#374151; text-align:left; transition:background 0.15s;"
                                    onmouseover="this.style.background='#f8fafc'"
                                    onmouseout="this.style.background='transparent'"
                                >
                                    <span style="width:30px; height:30px; border-radius:8px; background:#f1f5f9; display:flex; align-items:center; justify-content:center;">
                                        <i class="pi pi-lock" style="font-size:13px; color:#64748b;"></i>
                                    </span>
                                    Cambiar contraseña
                                </button>

                                <div style="height:1px; background:#f1f5f9; margin:6px 0;"></div>

                                <button
                                    @click="closeUserMenu(); logout();"
                                    style="width:100%; display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; border:none; background:transparent; cursor:pointer; font-size:13px; font-weight:600; color:#ef4444; text-align:left; transition:background 0.15s;"
                                    onmouseover="this.style.background='#fff5f5'"
                                    onmouseout="this.style.background='transparent'"
                                >
                                    <span style="width:30px; height:30px; border-radius:8px; background:#fff5f5; display:flex; align-items:center; justify-content:center;">
                                        <i class="pi pi-sign-out" style="font-size:13px; color:#ef4444;"></i>
                                    </span>
                                    Cerrar sesión
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ══════════════════ OVERLAY MOBILE ══════════════════ -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            style="position:fixed; inset:0; z-index:40; background:rgba(15,23,42,0.5); backdrop-filter:blur(2px);"
        ></div>

        <!-- ══════════════════ SIDEBAR ══════════════════ -->
        <aside :style="`
            position: fixed; left:0; top:64px; z-index:50;
            width: 260px; height: calc(100vh - 64px);
            background: white;
            border-right: 1px solid #f1f5f9;
            display: flex; flex-direction: column;
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            transform: ${sidebarOpen ? 'translateX(0)' : 'translateX(-100%)'};
            box-shadow: 4px 0 24px rgba(0,0,0,0.06);
        `" class="lg-visible">
            <div style="padding: 20px 16px 12px;">
                <div style="font-size:10px; font-weight:800; letter-spacing:0.15em; text-transform:uppercase; color:#94a3b8;">Navegación</div>
            </div>

            <nav style="flex:1; overflow-y:auto; padding:0 10px 16px;">
                <button
                    v-for="item in modules"
                    :key="item.key"
                    @click="setModule(item)"
                    :style="`
                        width:100%; display:flex; align-items:center; gap:10px;
                        padding: 11px 14px; border-radius:12px; border:none;
                        cursor:pointer; font-size:13.5px; font-weight:600; text-align:left;
                        margin-bottom: 3px;
                        transition: all 0.2s;
                        background: ${props.activeModule === item.key ? `linear-gradient(135deg, ${roleColor.bg}, ${roleColor.text})` : 'transparent'};
                        color: ${props.activeModule === item.key ? 'white' : '#475569'};
                        box-shadow: ${props.activeModule === item.key ? `0 4px 12px ${roleColor.bg}40` : 'none'};
                    `"
                >
                    <span :style="`
                        width:30px; height:30px; border-radius:8px; flex-shrink:0;
                        display:flex; align-items:center; justify-content:center;
                        background: ${props.activeModule === item.key ? 'rgba(255,255,255,0.2)' : '#f8fafc'};
                    `">
                        <i :class="`pi ${item.icon}`" style="font-size:14px;"></i>
                    </span>
                    {{ item.label }}
                </button>
            </nav>

            <div style="padding:12px 10px;">
                <div :style="`
                    padding:14px 16px; border-radius:14px;
                    background: linear-gradient(135deg, #0f172a, #1e293b);
                    color:white;
                `">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div :style="`
                            width:34px; height:34px; border-radius:10px; flex-shrink:0;
                            background: ${roleColor.bg}; display:flex; align-items:center; justify-content:center;
                        `">
                            <i class="pi pi-shield" style="font-size:14px; color:white;"></i>
                        </div>
                        <div>
                            <div style="font-size:12px; font-weight:700;">Sesión segura</div>
                            <div style="font-size:10px; color:#94a3b8; margin-top:1px;">Control por roles activo</div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ══════════════════ CONTENIDO ══════════════════ -->
        <main style="min-height:100vh; padding-top:64px;" class="main-content">
            <div style="padding: 20px 20px 32px;">
                <slot />
            </div>
        </main>

        <!-- ══════════════════ MODAL: EDITAR PERFIL (Paciente) ══════════════════ -->
        <Dialog
            v-model:visible="perfilVisible"
            modal
            header="Editar mi perfil"
            :style="{ width: 'min(720px, 95vw)', borderRadius: '20px' }"
        >
            <div v-if="perfilLoading" class="flex min-h-60 items-center justify-center">
                <div class="text-center">
                    <i class="pi pi-spin pi-spinner text-4xl text-cyan-500"></i>
                    <p class="mt-4 font-bold text-slate-600">Cargando perfil...</p>
                </div>
            </div>
            <div v-else style="padding:4px 0;">
                <div v-if="perfilMensaje" style="margin-bottom:16px; padding:12px 16px; border-radius:12px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <i class="pi pi-check-circle"></i> {{ perfilMensaje }}
                </div>
                <div v-if="perfilError" style="margin-bottom:16px; padding:12px 16px; border-radius:12px; background:#fff5f5; border:1px solid #fecaca; color:#991b1b; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <i class="pi pi-exclamation-triangle"></i> {{ perfilError }}
                </div>

                <div style="margin-bottom:16px; padding:14px 16px; border-radius:14px; background:#f8fafc; border:1px solid #e2e8f0;">
                    <div style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-bottom:10px;">Información personal</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">Nombre completo</label>
                            <input :value="`${perfil.paciente_nombres} ${perfil.paciente_apellidos}`" disabled style="width:100%; height:40px; border-radius:10px; border:1px solid #e2e8f0; background:#f1f5f9; padding:0 12px; font-size:13px; font-weight:600; color:#94a3b8; box-sizing:border-box;" />
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">Fecha de nacimiento</label>
                            <input :value="perfil.fecha_nacimiento || '—'" disabled style="width:100%; height:40px; border-radius:10px; border:1px solid #e2e8f0; background:#f1f5f9; padding:0 12px; font-size:13px; font-weight:600; color:#94a3b8; box-sizing:border-box;" />
                        </div>
                    </div>
                </div>

                <div style="font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-bottom:10px;">Datos editables</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label style="font-size:12px; font-weight:700; color:#374151; display:block; margin-bottom:6px;">Correo electrónico <span style="color:#ef4444;">*</span></label>
                        <input
                            v-model="perfil.correo"
                            type="email"
                            style="width:100%; height:42px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; padding:0 12px; font-size:13px; font-weight:600; color:#0f172a; outline:none; box-sizing:border-box; transition:border 0.2s;"
                            onfocus="this.style.borderColor='#0891b2'"
                            onblur="this.style.borderColor='#e2e8f0'"
                        />
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:700; color:#374151; display:block; margin-bottom:6px;">DUI <span style="color:#ef4444;">*</span></label>
                        <input
                            v-model="perfil.dui"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="9"
                            placeholder="000000000"
                            style="width:100%; height:42px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; padding:0 12px; font-size:13px; font-weight:600; color:#0f172a; outline:none; box-sizing:border-box; transition:border 0.2s;"
                            onfocus="this.style.borderColor='#0891b2'"
                            onblur="this.style.borderColor='#e2e8f0'"
                            @input="perfil.dui = soloNumeros(perfil.dui, 9)"
                        />
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:700; color:#374151; display:block; margin-bottom:6px;">Teléfono</label>
                        <input
                            v-model="perfil.telefono"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="8"
                            placeholder="00000000"
                            style="width:100%; height:42px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; padding:0 12px; font-size:13px; font-weight:600; color:#0f172a; outline:none; box-sizing:border-box; transition:border 0.2s;"
                            onfocus="this.style.borderColor='#0891b2'"
                            onblur="this.style.borderColor='#e2e8f0'"
                            @input="perfil.telefono = soloNumeros(perfil.telefono, 8)"
                        />
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:700; color:#374151; display:block; margin-bottom:6px;">Dirección</label>
                        <input
                            v-model="perfil.direccion"
                            maxlength="150"
                            placeholder="Tu dirección"
                            style="width:100%; height:42px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; padding:0 12px; font-size:13px; font-weight:600; color:#0f172a; outline:none; box-sizing:border-box; transition:border 0.2s;"
                            onfocus="this.style.borderColor='#0891b2'"
                            onblur="this.style.borderColor='#e2e8f0'"
                        />
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <Button label="Cancelar" severity="secondary" icon="pi pi-times" class="rounded-2xl!" :disabled="perfilSaving" @click="perfilVisible = false" />
                    <Button label="Guardar cambios" icon="pi pi-save" :loading="perfilSaving" class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!" @click="guardarPerfil" />
                </div>
            </div>
        </Dialog>

        <!-- ══════════════════ MODAL: CAMBIAR CONTRASEÑA ══════════════════ -->
        <Dialog
            v-model:visible="passVisible"
            modal
            header="Cambiar contraseña"
            :style="{ width: 'min(460px, 95vw)', borderRadius: '20px' }"
        >
            <div style="padding:4px 0;">
                <div v-if="passMensaje" style="margin-bottom:16px; padding:12px 16px; border-radius:12px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <i class="pi pi-check-circle"></i> {{ passMensaje }}
                </div>
                <div v-if="passError" style="margin-bottom:16px; padding:12px 16px; border-radius:12px; background:#fff5f5; border:1px solid #fecaca; color:#991b1b; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <i class="pi pi-exclamation-triangle"></i> {{ passError }}
                </div>

                <div style="margin-bottom:18px; padding:12px 14px; border-radius:12px; background:#f0f9ff; border:1px solid #bae6fd;">
                    <div style="font-size:12px; font-weight:700; color:#0369a1; display:flex; align-items:center; gap:6px;">
                        <i class="pi pi-info-circle"></i> Requisitos de la nueva contraseña
                    </div>
                    <div style="font-size:11px; color:#0369a1; margin-top:6px; line-height:1.7;">
                        · Mínimo 8 caracteres &nbsp;·&nbsp; Al menos una letra &nbsp;·&nbsp; Al menos un número
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="font-size:12px; font-weight:700; color:#374151; display:block; margin-bottom:6px;">Contraseña actual</label>
                    <div style="position:relative;">
                        <input
                            v-model="passForm.password_actual"
                            :type="showPass.actual ? 'text' : 'password'"
                            placeholder="Tu contraseña actual"
                            style="width:100%; height:44px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; padding:0 44px 0 14px; font-size:13px; font-weight:600; color:#0f172a; outline:none; box-sizing:border-box; transition:border 0.2s;"
                            onfocus="this.style.borderColor='#0891b2'"
                            onblur="this.style.borderColor='#e2e8f0'"
                        />
                        <button type="button" @click="showPass.actual = !showPass.actual" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); border:none; background:transparent; cursor:pointer; color:#94a3b8; padding:0;">
                            <i :class="`pi ${showPass.actual ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:16px;"></i>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom:8px;">
                    <label style="font-size:12px; font-weight:700; color:#374151; display:block; margin-bottom:6px;">Nueva contraseña</label>
                    <div style="position:relative;">
                        <input
                            v-model="passForm.password_nuevo"
                            :type="showPass.nuevo ? 'text' : 'password'"
                            placeholder="Nueva contraseña"
                            style="width:100%; height:44px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; padding:0 44px 0 14px; font-size:13px; font-weight:600; color:#0f172a; outline:none; box-sizing:border-box; transition:border 0.2s;"
                            onfocus="this.style.borderColor='#0891b2'"
                            onblur="this.style.borderColor='#e2e8f0'"
                        />
                        <button type="button" @click="showPass.nuevo = !showPass.nuevo" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); border:none; background:transparent; cursor:pointer; color:#94a3b8; padding:0;">
                            <i :class="`pi ${showPass.nuevo ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:16px;"></i>
                        </button>
                    </div>
                </div>

                <div v-if="passForm.password_nuevo" style="margin-bottom:14px;">
                    <div style="display:flex; gap:4px; margin-bottom:4px;">
                        <div v-for="i in 5" :key="i" :style="`height:4px; flex:1; border-radius:4px; background: ${i <= passwordStrength ? passwordStrengthColor : '#e2e8f0'}; transition:background 0.3s;`"></div>
                    </div>
                    <div style="font-size:11px; font-weight:700;" :style="`color: ${passwordStrengthColor}`">
                        {{ passwordStrengthLabel }}
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="font-size:12px; font-weight:700; color:#374151; display:block; margin-bottom:6px;">Confirmar nueva contraseña</label>
                    <div style="position:relative;">
                        <input
                            v-model="passForm.password_nuevo_confirmation"
                            :type="showPass.confirm ? 'text' : 'password'"
                            placeholder="Repite la nueva contraseña"
                            style="width:100%; height:44px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; padding:0 44px 0 14px; font-size:13px; font-weight:600; color:#0f172a; outline:none; box-sizing:border-box; transition:border 0.2s;"
                            onfocus="this.style.borderColor='#0891b2'"
                            onblur="this.style.borderColor='#e2e8f0'"
                        />
                        <button type="button" @click="showPass.confirm = !showPass.confirm" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); border:none; background:transparent; cursor:pointer; color:#94a3b8; padding:0;">
                            <i :class="`pi ${showPass.confirm ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:16px;"></i>
                        </button>
                    </div>
                    <div
                        v-if="passForm.password_nuevo_confirmation && passForm.password_nuevo"
                        style="margin-top:5px; font-size:11px; font-weight:700; display:flex; align-items:center; gap:4px;"
                        :style="`color: ${passForm.password_nuevo === passForm.password_nuevo_confirmation ? '#16a34a' : '#dc2626'}`"
                    >
                        <i :class="`pi ${passForm.password_nuevo === passForm.password_nuevo_confirmation ? 'pi-check-circle' : 'pi-times-circle'}`"></i>
                        {{ passForm.password_nuevo === passForm.password_nuevo_confirmation ? 'Las contraseñas coinciden' : 'Las contraseñas no coinciden' }}
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <Button label="Cancelar" severity="secondary" icon="pi pi-times" class="rounded-2xl!" :disabled="passSaving" @click="passVisible = false" />
                    <Button
                        label="Actualizar contraseña"
                        icon="pi pi-lock"
                        :loading="passSaving"
                        :disabled="!passForm.password_actual || !passForm.password_nuevo || !passForm.password_nuevo_confirmation"
                        class="rounded-2xl! bg-cyan-500! text-white! hover:bg-cyan-600!"
                        @click="guardarPassword"
                    />
                </div>
            </div>
        </Dialog>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800;900&display=swap');

.sm-show { display: flex; }
.lg-visible { transform: translateX(-100%); }

@media (min-width: 1024px) {
    .lg-visible { transform: translateX(0) !important; }
    .main-content { padding-left: 260px; }
}
@media (max-width: 640px) {
    .sm-show { display: none !important; }
}
</style>
