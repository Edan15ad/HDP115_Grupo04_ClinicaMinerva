<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    correo: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar sesión · Clínica Minerva" />

    <div style="min-height:100vh; background:#f1f5f9; font-family:'DM Sans','Segoe UI',sans-serif; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:24px;">

        <!-- Logo centrado -->
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:32px;">
            <div style="width:44px; height:44px; border-radius:14px; background:linear-gradient(135deg,#0891b2,#0e7490); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 14px rgba(8,145,178,0.35);">
                <i class="pi pi-heart-fill" style="color:white; font-size:18px;"></i>
            </div>
            <div>
                <div style="font-size:16px; font-weight:800; color:#0f172a; line-height:1.2;">Clínica Minerva</div>
                <div style="font-size:10px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:0.08em;">Sistema de exámenes clínicos</div>
            </div>
        </div>

        <!-- Card del formulario -->
        <div style="width:100%; max-width:420px; background:white; border-radius:24px; padding:36px; box-shadow:0 4px 24px rgba(0,0,0,0.08); border:1px solid #f1f5f9;">

            <!-- Encabezado -->
            <div style="margin-bottom:28px;">
                <h2 style="font-size:24px; font-weight:900; color:#0f172a; margin:0 0 6px; letter-spacing:-0.02em;">Iniciar sesión</h2>
                <p style="font-size:13px; color:#94a3b8; margin:0;">Ingresa con tu correo y contraseña.</p>
            </div>

            <!-- Error cuenta inactiva -->
            <div v-if="form.errors.correo && form.errors.correo.includes('inactiva')"
                style="margin-bottom:18px; padding:16px; border-radius:12px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; font-size:13px; font-weight:600;">
                <div style="font-weight:800; color:#b45309; margin-bottom:6px;">Cuenta inactiva</div>
                <p style="margin:0; line-height:1.6;">{{ form.errors.correo }}</p>
                <p style="margin:6px 0 0; font-size:12px; color:#b45309;">
                    Contacta al administrador en
                    <strong>mp21057@ues.edu.sv</strong>
                </p>
            </div>

            <!-- Error credenciales incorrectas -->
            <div v-else-if="form.errors.correo || form.errors.password"
                style="margin-bottom:18px; padding:12px 14px; border-radius:12px; background:#fff5f5; border:1px solid #fecaca; color:#dc2626; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                <i class="pi pi-exclamation-circle"></i>
                {{ form.errors.correo || form.errors.password }}
            </div>

            <form @submit.prevent="submit" style="display:flex; flex-direction:column; gap:16px;">

                <!-- Correo -->
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:7px;">Correo electrónico</label>
                    <input
                        v-model="form.correo"
                        type="email"
                        placeholder="tucorreo@dominio.com"
                        autocomplete="username"
                        style="width:100%; height:46px; border-radius:12px; border:1.5px solid #e2e8f0; background:white; color:#0f172a; padding:0 16px; font-size:14px; font-weight:600; outline:none; box-sizing:border-box; transition:border 0.2s, box-shadow 0.2s;"
                        onfocus="this.style.borderColor='#0891b2'; this.style.boxShadow='0 0 0 3px rgba(8,145,178,0.1)'"
                        onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'"
                    />
                </div>

                <!-- Contraseña -->
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:7px;">Contraseña</label>
                    <div style="position:relative;">
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            style="width:100%; height:46px; border-radius:12px; border:1.5px solid #e2e8f0; background:white; color:#0f172a; padding:0 50px 0 16px; font-size:14px; font-weight:600; outline:none; box-sizing:border-box; transition:border 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='#0891b2'; this.style.boxShadow='0 0 0 3px rgba(8,145,178,0.1)'"
                            onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            style="position:absolute; right:14px; top:50%; transform:translateY(-50%); border:none; background:transparent; cursor:pointer; color:#94a3b8; padding:0; transition:color 0.2s;"
                            onmouseover="this.style.color='#64748b'"
                            onmouseout="this.style.color='#94a3b8'"
                        >
                            <i :class="`pi ${showPassword ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:17px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Recordarme + olvidé contraseña -->
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input
                            type="checkbox"
                            v-model="form.remember"
                            style="width:16px; height:16px; border-radius:5px; accent-color:#0891b2; cursor:pointer;"
                        />
                        <span style="font-size:13px; font-weight:600; color:#64748b;">Recordarme</span>
                    </label>
                    <Link href="/forgot-password"
                        style="font-size:13px; font-weight:700; color:#0891b2; text-decoration:none; transition:color 0.2s;"
                        onmouseover="this.style.color='#0e7490'"
                        onmouseout="this.style.color='#0891b2'">
                        ¿Olvidaste tu contraseña?
                    </Link>
                </div>

                <!-- Botón -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    style="width:100%; height:48px; border-radius:13px; border:none; background:linear-gradient(135deg,#0891b2,#0e7490); color:white; font-size:15px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; box-shadow:0 4px 14px rgba(8,145,178,0.35); transition:all 0.2s; margin-top:4px;"
                    onmouseover="if(!this.disabled){ this.style.transform='translateY(-1px)'; this.style.boxShadow='0 8px 22px rgba(8,145,178,0.45)'; }"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 14px rgba(8,145,178,0.35)';"
                >
                    <i v-if="!form.processing" class="pi pi-arrow-right" style="font-size:15px;"></i>
                    <i v-else class="pi pi-spin pi-spinner" style="font-size:15px;"></i>
                    {{ form.processing ? 'Verificando...' : 'Entrar al sistema' }}
                </button>

                <!-- Registro -->
                <p style="text-align:center; font-size:13px; color:#94a3b8; margin:0;">
                    ¿No tienes cuenta?
                    <Link href="/register"
                        style="font-weight:700; color:#0891b2; text-decoration:none; margin-left:4px; transition:color 0.2s;"
                        onmouseover="this.style.color='#0e7490'"
                        onmouseout="this.style.color='#0891b2'">
                        Regístrate gratis
                    </Link>
                </p>
            </form>
        </div>

        <!-- Volver al inicio -->
        <div style="margin-top:20px;">
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