<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ status: String });

const form = useForm({ correo: '' });

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <Head title="Recuperar contraseña" />

    <div style="min-height:100vh; background:#0f172a; font-family:'DM Sans','Segoe UI',sans-serif; display:flex; align-items:center; justify-content:center; padding:24px; position:relative; overflow:hidden;">

        <!-- Fondos decorativos -->
        <div style="position:absolute; top:-100px; right:-100px; width:400px; height:400px; border-radius:50%; background:radial-gradient(circle,rgba(8,145,178,0.15) 0%,transparent 70%); pointer-events:none;"></div>
        <div style="position:absolute; bottom:-80px; left:-80px; width:350px; height:350px; border-radius:50%; background:radial-gradient(circle,rgba(5,150,105,0.1) 0%,transparent 70%); pointer-events:none;"></div>

        <div style="width:100%; max-width:440px; position:relative; z-index:10;">

            <!-- Logo -->
            <div style="text-align:center; margin-bottom:32px;">
                <div style="width:56px; height:56px; border-radius:18px; background:linear-gradient(135deg,#0891b2,#0e7490); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; box-shadow:0 6px 20px rgba(8,145,178,0.4);">
                    <i class="pi pi-lock" style="color:white; font-size:22px;"></i>
                </div>
                <div style="font-size:22px; font-weight:900; color:white;">Recuperar contraseña</div>
                <div style="font-size:13px; color:#64748b; margin-top:6px; line-height:1.6;">
                    Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
                </div>
            </div>

            <!-- Mensaje de éxito -->
            <div v-if="status" style="margin-bottom:20px; padding:14px 18px; border-radius:14px; background:rgba(5,150,105,0.12); border:1px solid rgba(5,150,105,0.3); color:#34d399; font-size:13px; font-weight:600; display:flex; align-items:center; gap:10px;">
                <i class="pi pi-check-circle" style="font-size:16px;"></i>
                {{ status }}
            </div>

            <!-- Formulario -->
            <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.09); border-radius:22px; padding:28px; backdrop-filter:blur(12px);">

                <form @submit.prevent="submit">
                    <div style="margin-bottom:20px;">
                        <label style="display:block; font-size:12px; font-weight:700; color:#cbd5e1; margin-bottom:8px; letter-spacing:0.03em;">
                            Correo electrónico
                        </label>
                        <input
                            v-model="form.correo"
                            type="email"
                            placeholder="tucorreo@dominio.com"
                            autocomplete="username"
                            style="width:100%; height:46px; border-radius:12px; border:1.5px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:white; padding:0 16px; font-size:14px; font-weight:600; outline:none; box-sizing:border-box; transition:border 0.2s;"
                            :style="form.errors.correo ? 'border-color:#f87171;' : ''"
                            onfocus="this.style.borderColor='#0891b2'; this.style.background='rgba(8,145,178,0.06)'"
                            onblur="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.background='rgba(255,255,255,0.06)'"
                        />
                        <div v-if="form.errors.correo" style="margin-top:6px; font-size:12px; color:#f87171; font-weight:600; display:flex; align-items:center; gap:4px;">
                            <i class="pi pi-exclamation-circle"></i> {{ form.errors.correo }}
                        </div>
                    </div>

                    <!-- Aviso MAIL_MAILER=log -->
                    <div style="margin-bottom:20px; padding:12px 14px; border-radius:12px; background:rgba(234,179,8,0.1); border:1px solid rgba(234,179,8,0.25);">
                        <div style="font-size:11px; font-weight:700; color:#fbbf24; display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                            <i class="pi pi-info-circle"></i> Entorno en proceso de desarrollo
                        </div>
                        <div style="font-size:11px; color:#fbbf24; opacity:0.8; line-height:1.6;">
                            Este apartado se encuentra en etapa de desarrollo por limitaciones
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        style="width:100%; height:48px; border-radius:14px; border:none; background:linear-gradient(135deg,#0891b2,#0e7490); color:white; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 16px rgba(8,145,178,0.4); transition:all 0.2s;"
                        onmouseover="if(!this.disabled) this.style.opacity='0.9'"
                        onmouseout="this.style.opacity='1'"
                    >
                        <i v-if="!form.processing" class="pi pi-send" style="font-size:15px;"></i>
                        <i v-else class="pi pi-spin pi-spinner" style="font-size:15px;"></i>
                        {{ form.processing ? 'Enviando...' : 'Enviar enlace de recuperación' }}
                    </button>
                </form>
            </div>

            <!-- Volver al login -->
            <div style="text-align:center; margin-top:20px;">
                <Link href="/login" style="font-size:13px; font-weight:700; color:#38bdf8; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:opacity 0.2s;"
                    onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                    <i class="pi pi-arrow-left" style="font-size:12px;"></i>
                    Volver al inicio de sesión
                </Link>
            </div>
        </div>
    </div>
</template>