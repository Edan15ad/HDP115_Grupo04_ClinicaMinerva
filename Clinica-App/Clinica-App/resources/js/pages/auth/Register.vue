<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

const today = new Date().toISOString().split('T')[0];

const showPass    = ref(false);
const showConfirm = ref(false);

const passwordStrength = computed(() => {
    const p = form.password;
    if (!p) return 0;
    let s = 0;
    if (p.length >= 8)           s++;
    if (/[a-z]/.test(p))         s++;
    if (/[A-Z]/.test(p))         s++;
    if (/[0-9]/.test(p))         s++;
    if (/[^a-zA-Z0-9]/.test(p))  s++;
    return s;
});

const strengthLabel = computed(() => ['', 'Muy débil', 'Débil', 'Regular', 'Fuerte', 'Muy fuerte'][passwordStrength.value] ?? '');
const strengthColor = computed(() => ['', '#ef4444', '#f97316', '#eab308', '#22c55e', '#10b981'][passwordStrength.value] ?? '#e2e8f0');

const passwordsMatch = computed(() =>
    form.password && form.password_confirmation
        ? form.password === form.password_confirmation
        : null
);

const soloNumeros = (valor, max = 99) => {
    return String(valor ?? '').replace(/\D/g, '').slice(0, max);
};

const esCorreoValido = (correo) => {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(correo ?? '').trim());
};

const normalizarNumeros = () => {
    form.dui = soloNumeros(form.dui, 9);
    form.telefono = soloNumeros(form.telefono, 8);
};

const traducirErrorBackend = (mensaje) => {
    const texto = String(mensaje ?? '').toLowerCase();

    if (texto.includes('the correo has already been taken') || texto.includes('the email has already been taken')) {
        return 'El correo electrónico ingresado ya está registrado.';
    }

    if (texto.includes('the dui has already been taken')) {
        return 'El DUI ingresado ya está registrado.';
    }

    if (texto.includes('the nombres field is required')) return 'El campo nombres es obligatorio.';
    if (texto.includes('the apellidos field is required')) return 'El campo apellidos es obligatorio.';
    if (texto.includes('the correo field is required') || texto.includes('the email field is required')) return 'El correo electrónico es obligatorio.';
    if (texto.includes('the dui field is required')) return 'El DUI es obligatorio.';
    if (texto.includes('the telefono field is required')) return 'El teléfono es obligatorio.';
    if (texto.includes('the fecha nacimiento field is required') || texto.includes('the fecha_nacimiento field is required')) return 'La fecha de nacimiento es obligatoria.';
    if (texto.includes('the genero field is required')) return 'El género es obligatorio.';
    if (texto.includes('the direccion field is required')) return 'La dirección es obligatoria.';
    if (texto.includes('the password field is required')) return 'La contraseña es obligatoria.';
    if (texto.includes('the password confirmation field is required') || texto.includes('password confirmation')) return 'La confirmación de contraseña es obligatoria.';
    if (texto.includes('the password confirmation does not match')) return 'Las contraseñas no coinciden.';
    if (texto.includes('must be a valid email')) return 'Ingresa un correo electrónico válido.';

    return mensaje || 'Ocurrió un error al procesar el registro.';
};

const traducirErroresFormulario = () => {
    Object.entries(form.errors).forEach(([campo, mensaje]) => {
        form.setError(campo, traducirErrorBackend(mensaje));
    });
};

const validarRegistroAntesDeEnviar = () => {
    normalizarNumeros();
    form.clearErrors();

    if (!String(form.nombres ?? '').trim()) {
        form.setError('nombres', 'El campo nombres es obligatorio.');
        return false;
    }

    if (!String(form.apellidos ?? '').trim()) {
        form.setError('apellidos', 'El campo apellidos es obligatorio.');
        return false;
    }

    if (!String(form.fecha_nacimiento ?? '').trim()) {
        form.setError('fecha_nacimiento', 'La fecha de nacimiento es obligatoria.');
        return false;
    }

    if (form.fecha_nacimiento > today) {
        form.setError('fecha_nacimiento', 'La fecha de nacimiento no puede ser mayor a la fecha actual.');
        return false;
    }

    if (!String(form.genero ?? '').trim()) {
        form.setError('genero', 'El género es obligatorio.');
        return false;
    }

    if (!String(form.correo ?? '').trim()) {
        form.setError('correo', 'El correo electrónico es obligatorio.');
        return false;
    }

    if (!esCorreoValido(form.correo)) {
        form.setError('correo', 'Ingresa un correo electrónico válido.');
        return false;
    }

    if (!String(form.dui ?? '').trim()) {
        form.setError('dui', 'El DUI es obligatorio.');
        return false;
    }

    if (String(form.dui).length !== 9) {
        form.setError('dui', 'El DUI debe tener exactamente 9 números.');
        return false;
    }

    if (!String(form.telefono ?? '').trim()) {
        form.setError('telefono', 'El teléfono es obligatorio.');
        return false;
    }

    if (String(form.telefono).length !== 8) {
        form.setError('telefono', 'El teléfono debe tener exactamente 8 números.');
        return false;
    }

    if (!String(form.direccion ?? '').trim()) {
        form.setError('direccion', 'La dirección es obligatoria.');
        return false;
    }

    if (!String(form.password ?? '').trim()) {
        form.setError('password', 'La contraseña es obligatoria.');
        return false;
    }

    if (String(form.password).length < 8) {
        form.setError('password', 'La contraseña debe tener al menos 8 caracteres.');
        return false;
    }

    if (!String(form.password_confirmation ?? '').trim()) {
        form.setError('password_confirmation', 'La confirmación de contraseña es obligatoria.');
        return false;
    }

    if (form.password !== form.password_confirmation) {
        form.setError('password_confirmation', 'Las contraseñas no coinciden.');
        return false;
    }

    return true;
};

const submit = () => {
    if (!validarRegistroAntesDeEnviar()) return;

    form.post('/register', {
        preserveScroll: true,
        onError: () => traducirErroresFormulario(),
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

// Estilos reutilizables para inputs
const inputStyle = 'width:100%; height:44px; border-radius:11px; border:1.5px solid #e2e8f0; background:white; color:#0f172a; padding:0 14px; font-size:13.5px; font-weight:600; outline:none; box-sizing:border-box; transition:border 0.2s, box-shadow 0.2s;';
const inputFocus = "this.style.borderColor='#0891b2'; this.style.boxShadow='0 0 0 3px rgba(8,145,178,0.1)'";
const inputBlur  = "this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'";
const labelStyle = 'display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px;';
const errorStyle = 'display:block; margin-top:5px; font-size:11px; font-weight:700; color:#dc2626;';
</script>

<template>
    <Head title="Crear cuenta · Clínica Minerva" />

    <div style="min-height:100vh; background:#f1f5f9; font-family:'DM Sans','Segoe UI',sans-serif; padding:32px 20px;">

        <!-- Logo -->
        <div style="display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:28px;">
            <div style="width:40px; height:40px; border-radius:12px; background:linear-gradient(135deg,#0891b2,#0e7490); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(8,145,178,0.3);">
                <i class="pi pi-heart-fill" style="color:white; font-size:16px;"></i>
            </div>
            <div>
                <div style="font-size:15px; font-weight:800; color:#0f172a; line-height:1.2;">Clínica Minerva</div>
                <div style="font-size:10px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:0.08em;">Sistema de exámenes clínicos</div>
            </div>
        </div>

        <!-- Card principal -->
        <div style="max-width:680px; margin:0 auto; background:white; border-radius:24px; box-shadow:0 4px 24px rgba(0,0,0,0.08); border:1px solid #f1f5f9; overflow:hidden;">

            <!-- Header card -->
            <div style="padding:28px 32px 24px; border-bottom:1px solid #f1f5f9;">
                <div style="display:inline-flex; align-items:center; gap:8px; padding:5px 14px; border-radius:20px; background:#ecfeff; border:1px solid #a5f3fc; margin-bottom:14px;">
                    <i class="pi pi-user-plus" style="font-size:11px; color:#0e7490;"></i>
                    <span style="font-size:11px; font-weight:700; color:#0e7490; letter-spacing:0.04em;">Registro de nuevo paciente</span>
                </div>
                <h2 style="font-size:22px; font-weight:900; color:#0f172a; margin:0 0 4px; letter-spacing:-0.02em;">Crea tu cuenta</h2>
                <p style="font-size:13px; color:#94a3b8; margin:0;">Completa tu expediente clínico para acceder al sistema.</p>
            </div>

            <!-- Formulario -->
            <div style="padding:28px 32px 32px;">

                <!-- Error general -->
                <div v-if="form.errors.error"
                    style="margin-bottom:20px; padding:12px 14px; border-radius:12px; background:#fff5f5; border:1px solid #fecaca; color:#dc2626; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <i class="pi pi-exclamation-circle"></i> {{ form.errors.error }}
                </div>

                <form @submit.prevent="submit" style="display:flex; flex-direction:column; gap:0;">

                    <!-- Sección: Datos personales -->
                    <div style="margin-bottom:20px;">
                        <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#94a3b8; margin-bottom:14px; display:flex; align-items:center; gap:8px;">
                            <span style="flex:1; height:1px; background:#f1f5f9;"></span>
                            Datos personales
                            <span style="flex:1; height:1px; background:#f1f5f9;"></span>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                            <!-- Nombres -->
                            <div>
                                <label :style="labelStyle">Nombres <span style="color:#dc2626;">*</span></label>
                                <input v-model.trim="form.nombres" type="text" required autocomplete="given-name" placeholder="Tus nombres"
                                    :style="inputStyle + (form.errors.nombres ? 'border-color:#fca5a5;' : '')"
                                    :onfocus="inputFocus" :onblur="inputBlur" />
                                <small v-if="form.errors.nombres" :style="errorStyle">{{ form.errors.nombres }}</small>
                            </div>

                            <!-- Apellidos -->
                            <div>
                                <label :style="labelStyle">Apellidos <span style="color:#dc2626;">*</span></label>
                                <input v-model.trim="form.apellidos" type="text" required autocomplete="family-name" placeholder="Tus apellidos"
                                    :style="inputStyle + (form.errors.apellidos ? 'border-color:#fca5a5;' : '')"
                                    :onfocus="inputFocus" :onblur="inputBlur" />
                                <small v-if="form.errors.apellidos" :style="errorStyle">{{ form.errors.apellidos }}</small>
                            </div>

                            <!-- Fecha nacimiento -->
                            <div>
                                <label :style="labelStyle">Fecha de nacimiento <span style="color:#dc2626;">*</span></label>
                                <input
                                    v-model="form.fecha_nacimiento"
                                    type="date"
                                    required
                                    :max="today"
                                    :style="inputStyle + (form.errors.fecha_nacimiento ? 'border-color:#fca5a5;' : '')"
                                    :onfocus="inputFocus"
                                    :onblur="inputBlur"
                                />
                                <small v-if="form.errors.fecha_nacimiento" :style="errorStyle">
                                    {{ form.errors.fecha_nacimiento }}
                                </small>
                            </div>

                            <!-- Género -->
                            <div>
                                <label :style="labelStyle">Género <span style="color:#dc2626;">*</span></label>
                                <select v-model="form.genero" required
                                    :style="inputStyle + (form.errors.genero ? 'border-color:#fca5a5;' : '')"
                                    :onfocus="inputFocus" :onblur="inputBlur">
                                    <option value="" disabled>Selecciona tu género</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>
                                <small v-if="form.errors.genero" :style="errorStyle">{{ form.errors.genero }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Contacto -->
                    <div style="margin-bottom:20px;">
                        <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#94a3b8; margin-bottom:14px; display:flex; align-items:center; gap:8px;">
                            <span style="flex:1; height:1px; background:#f1f5f9;"></span>
                            Información de contacto
                            <span style="flex:1; height:1px; background:#f1f5f9;"></span>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:14px;">
                            <!-- Correo -->
                            <div>
                                <label :style="labelStyle">Correo electrónico <span style="color:#dc2626;">*</span></label>
                                <input v-model.trim="form.correo" type="email" required autocomplete="email" placeholder="ejemplo@minerva.com"
                                    :style="inputStyle + (form.errors.correo ? 'border-color:#fca5a5;' : '')"
                                    :onfocus="inputFocus" :onblur="inputBlur" />
                                <small v-if="form.errors.correo" :style="errorStyle">{{ form.errors.correo }}</small>
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                                <!-- DUI -->
                                <div>
                                    <label :style="labelStyle">DUI <span style="color:#dc2626;">*</span> <span style="color:#94a3b8; font-weight:500;">(solo números, sin guiones)</span></label>
                                    <input v-model="form.dui" type="text" required inputmode="numeric" pattern="[0-9]*" placeholder="123456789" maxlength="9" @input="form.dui = soloNumeros(form.dui, 9)"
                                        :style="inputStyle + 'font-family:monospace; letter-spacing:0.05em;' + (form.errors.dui ? 'border-color:#fca5a5;' : '')"
                                        :onfocus="inputFocus" :onblur="inputBlur" />
                                    <small v-if="form.errors.dui" :style="errorStyle">{{ form.errors.dui }}</small>
                                </div>

                                <!-- Teléfono -->
                                <div>
                                    <label :style="labelStyle">Teléfono <span style="color:#dc2626;">*</span></label>
                                    <input v-model="form.telefono" type="text" required inputmode="numeric" pattern="[0-9]*" placeholder="77665544" maxlength="8" @input="form.telefono = soloNumeros(form.telefono, 8)"
                                        :style="inputStyle + (form.errors.telefono ? 'border-color:#fca5a5;' : '')"
                                        :onfocus="inputFocus" :onblur="inputBlur" />
                                    <small v-if="form.errors.telefono" :style="errorStyle">{{ form.errors.telefono }}</small>
                                </div>
                            </div>

                            <!-- Dirección -->
                            <div>
                                <label :style="labelStyle">Dirección <span style="color:#dc2626;">*</span></label>
                                <input v-model.trim="form.direccion" type="text" required placeholder="Tu dirección completa"
                                    :style="inputStyle + (form.errors.direccion ? 'border-color:#fca5a5;' : '')"
                                    :onfocus="inputFocus" :onblur="inputBlur" />
                                <small v-if="form.errors.direccion" :style="errorStyle">{{ form.errors.direccion }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Contraseña -->
                    <div style="margin-bottom:24px;">
                        <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#94a3b8; margin-bottom:14px; display:flex; align-items:center; gap:8px;">
                            <span style="flex:1; height:1px; background:#f1f5f9;"></span>
                            Seguridad
                            <span style="flex:1; height:1px; background:#f1f5f9;"></span>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                            <!-- Contraseña -->
                            <div>
                                <label :style="labelStyle">Contraseña <span style="color:#dc2626;">*</span></label>
                                <div style="position:relative;">
                                    <input v-model="form.password"
                                        :type="showPass ? 'text' : 'password'"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                        placeholder="Mínimo 8 caracteres"
                                        :style="inputStyle + 'padding-right:44px;' + (form.errors.password ? 'border-color:#fca5a5;' : '')"
                                        :onfocus="inputFocus" :onblur="inputBlur" />
                                    <button type="button" @click="showPass = !showPass"
                                        style="position:absolute; right:12px; top:50%; transform:translateY(-50%); border:none; background:transparent; cursor:pointer; color:#94a3b8; padding:0;">
                                        <i :class="`pi ${showPass ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:16px;"></i>
                                    </button>
                                </div>

                                <!-- Barra de fuerza -->
                                <div v-if="form.password" style="margin-top:8px;">
                                    <div style="display:flex; gap:3px; margin-bottom:3px;">
                                        <div v-for="i in 5" :key="i"
                                            :style="`height:3px; flex:1; border-radius:3px; background:${i <= passwordStrength ? strengthColor : '#e2e8f0'}; transition:background 0.3s;`">
                                        </div>
                                    </div>
                                    <span style="font-size:10px; font-weight:700;" :style="`color:${strengthColor}`">{{ strengthLabel }}</span>
                                </div>
                                <small v-if="form.errors.password" :style="errorStyle">{{ form.errors.password }}</small>
                            </div>

                            <!-- Confirmar contraseña -->
                            <div>
                                <label :style="labelStyle">Confirmar contraseña <span style="color:#dc2626;">*</span></label>
                                <div style="position:relative;">
                                    <input v-model="form.password_confirmation"
                                        :type="showConfirm ? 'text' : 'password'"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                        placeholder="Repite la contraseña"
                                        :style="inputStyle + 'padding-right:44px;'"
                                        :onfocus="inputFocus" :onblur="inputBlur" />
                                    <button type="button" @click="showConfirm = !showConfirm"
                                        style="position:absolute; right:12px; top:50%; transform:translateY(-50%); border:none; background:transparent; cursor:pointer; color:#94a3b8; padding:0;">
                                        <i :class="`pi ${showConfirm ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:16px;"></i>
                                    </button>
                                </div>

                                <!-- Coincidencia -->
                                <div v-if="passwordsMatch !== null"
                                    style="margin-top:6px; font-size:10px; font-weight:700; display:flex; align-items:center; gap:4px;"
                                    :style="`color:${passwordsMatch ? '#16a34a' : '#dc2626'}`">
                                    <i :class="`pi ${passwordsMatch ? 'pi-check-circle' : 'pi-times-circle'}`"></i>
                                    {{ passwordsMatch ? 'Las contraseñas coinciden' : 'No coinciden' }}
                                </div>
                                <small v-if="form.errors.password_confirmation" :style="errorStyle">{{ form.errors.password_confirmation }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Botón submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        style="width:100%; height:50px; border-radius:14px; border:none; background:linear-gradient(135deg,#0891b2,#0e7490); color:white; font-size:15px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; box-shadow:0 4px 16px rgba(8,145,178,0.35); transition:all 0.2s;"
                        onmouseover="if(!this.disabled){ this.style.transform='translateY(-1px)'; this.style.boxShadow='0 8px 24px rgba(8,145,178,0.45)'; }"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(8,145,178,0.35)';"
                    >
                        <i v-if="!form.processing" class="pi pi-user-plus" style="font-size:16px;"></i>
                        <i v-else class="pi pi-spin pi-spinner" style="font-size:16px;"></i>
                        {{ form.processing ? 'Creando cuenta...' : 'Crear cuenta clínica' }}
                    </button>

                    <!-- Login -->
                    <p style="text-align:center; font-size:13px; color:#94a3b8; margin:16px 0 0;">
                        ¿Ya tienes cuenta?
                        <Link href="/login"
                            style="font-weight:700; color:#0891b2; text-decoration:none; margin-left:4px; transition:color 0.2s;"
                            onmouseover="this.style.color='#0e7490'"
                            onmouseout="this.style.color='#0891b2'">
                            Inicia sesión
                        </Link>
                    </p>
                </form>
            </div>
        </div>

        <!-- Volver al inicio -->
        <div style="text-align:center; margin-top:20px;">
            <Link href="/"
                style="font-size:12px; font-weight:600; color:#94a3b8; text-decoration:none; display:inline-flex; align-items:center; gap:5px; transition:color 0.2s;"
                onmouseover="this.style.color='#64748b'"
                onmouseout="this.style.color='#94a3b8'">
                <i class="pi pi-arrow-left" style="font-size:11px;"></i>
                Volver al inicio
            </Link>
        </div>
    </div>
</template>