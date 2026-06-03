<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ token: String, email: String });

const form = useForm({
    token: props.token,
    correo: props.email,
    password: '',
    password_confirmation: '',
});

const showPass   = ref({ nuevo: false, confirm: false });

const passwordStrength = computed(() => {
    const p = form.password;
    if (!p) return 0;
    let s = 0;
    if (p.length >= 8) s++;
    if (/[a-z]/.test(p)) s++;
    if (/[A-Z]/.test(p)) s++;
    if (/[0-9]/.test(p)) s++;
    if (/[^a-zA-Z0-9]/.test(p)) s++;
    return s;
});

const strengthLabel = computed(() => ['','Muy débil','Débil','Regular','Fuerte','Muy fuerte'][passwordStrength.value] ?? '');
const strengthColor = computed(() => ['','#ef4444','#f97316','#eab308','#22c55e','#10b981'][passwordStrength.value] ?? '#334155');

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Nueva contraseña" />

    <div style="min-height:100vh; background:#0f172a; font-family:'DM Sans','Segoe UI',sans-serif; display:flex; align-items:center; justify-content:center; padding:24px; position:relative; overflow:hidden;">

        <div style="position:absolute; top:-100px; right:-100px; width:400px; height:400px; border-radius:50%; background:radial-gradient(circle,rgba(8,145,178,0.15) 0%,transparent 70%); pointer-events:none;"></div>
        <div style="position:absolute; bottom:-80px; left:-80px; width:350px; height:350px; border-radius:50%; background:radial-gradient(circle,rgba(5,150,105,0.1) 0%,transparent 70%); pointer-events:none;"></div>

        <div style="width:100%; max-width:440px; position:relative; z-index:10;">

            <!-- Logo -->
            <div style="text-align:center; margin-bottom:32px;">
                <div style="width:56px; height:56px; border-radius:18px; background:linear-gradient(135deg,#0891b2,#0e7490); display:flex; align-items:center; justify-content:center; margin:0 auto 16px; box-shadow:0 6px 20px rgba(8,145,178,0.4);">
                    <i class="pi pi-key" style="color:white; font-size:22px;"></i>
                </div>
                <div style="font-size:22px; font-weight:900; color:white;">Nueva contraseña</div>
                <div style="font-size:13px; color:#64748b; margin-top:6px;">Escribe y confirma tu nueva contraseña.</div>
            </div>

            <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.09); border-radius:22px; padding:28px; backdrop-filter:blur(12px);">
                <form @submit.prevent="submit" style="display:flex; flex-direction:column; gap:18px;">

                    <!-- Correo (readonly) -->
                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#94a3b8; margin-bottom:8px;">Correo</label>
                        <input :value="form.correo" readonly type="email"
                            style="width:100%; height:44px; border-radius:12px; border:1.5px solid rgba(255,255,255,0.07); background:rgba(255,255,255,0.04); color:#64748b; padding:0 16px; font-size:14px; font-weight:600; outline:none; box-sizing:border-box;" />
                    </div>

                    <!-- Nueva contraseña -->
                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#cbd5e1; margin-bottom:8px;">Nueva contraseña</label>
                        <div style="position:relative;">
                            <input
                                v-model="form.password"
                                :type="showPass.nuevo ? 'text' : 'password'"
                                placeholder="Nueva contraseña"
                                style="width:100%; height:46px; border-radius:12px; border:1.5px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:white; padding:0 48px 0 16px; font-size:14px; font-weight:600; outline:none; box-sizing:border-box; transition:border 0.2s;"
                                onfocus="this.style.borderColor='#0891b2'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'"
                            />
                            <button type="button" @click="showPass.nuevo = !showPass.nuevo"
                                style="position:absolute; right:14px; top:50%; transform:translateY(-50%); border:none; background:transparent; cursor:pointer; color:#64748b; padding:0;">
                                <i :class="`pi ${showPass.nuevo ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:16px;"></i>
                            </button>
                        </div>
                        <!-- Barra fuerza -->
                        <div v-if="form.password" style="margin-top:8px;">
                            <div style="display:flex; gap:4px; margin-bottom:4px;">
                                <div v-for="i in 5" :key="i" :style="`height:4px; flex:1; border-radius:4px; background:${i <= passwordStrength ? strengthColor : 'rgba(255,255,255,0.1)'}; transition:background 0.3s;`"></div>
                            </div>
                            <div style="font-size:11px; font-weight:700;" :style="`color:${strengthColor}`">{{ strengthLabel }}</div>
                        </div>
                        <div v-if="form.errors.password" style="margin-top:6px; font-size:12px; color:#f87171; font-weight:600;">
                            <i class="pi pi-exclamation-circle"></i> {{ form.errors.password }}
                        </div>
                    </div>

                    <!-- Confirmar contraseña -->
                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:#cbd5e1; margin-bottom:8px;">Confirmar contraseña</label>
                        <div style="position:relative;">
                            <input
                                v-model="form.password_confirmation"
                                :type="showPass.confirm ? 'text' : 'password'"
                                placeholder="Repite la contraseña"
                                style="width:100%; height:46px; border-radius:12px; border:1.5px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.06); color:white; padding:0 48px 0 16px; font-size:14px; font-weight:600; outline:none; box-sizing:border-box; transition:border 0.2s;"
                                onfocus="this.style.borderColor='#0891b2'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'"
                            />
                            <button type="button" @click="showPass.confirm = !showPass.confirm"
                                style="position:absolute; right:14px; top:50%; transform:translateY(-50%); border:none; background:transparent; cursor:pointer; color:#64748b; padding:0;">
                                <i :class="`pi ${showPass.confirm ? 'pi-eye-slash' : 'pi-eye'}`" style="font-size:16px;"></i>
                            </button>
                        </div>
                        <!-- Indicador coincidencia -->
                        <div v-if="form.password_confirmation && form.password" style="margin-top:6px; font-size:11px; font-weight:700; display:flex; align-items:center; gap:4px;"
                            :style="`color:${form.password === form.password_confirmation ? '#22c55e' : '#f87171'}`">
                            <i :class="`pi ${form.password === form.password_confirmation ? 'pi-check-circle' : 'pi-times-circle'}`"></i>
                            {{ form.password === form.password_confirmation ? 'Las contraseñas coinciden' : 'No coinciden' }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        style="width:100%; height:48px; border-radius:14px; border:none; background:linear-gradient(135deg,#0891b2,#0e7490); color:white; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 16px rgba(8,145,178,0.4); transition:all 0.2s; margin-top:4px;"
                    >
                        <i v-if="!form.processing" class="pi pi-check" style="font-size:15px;"></i>
                        <i v-else class="pi pi-spin pi-spinner" style="font-size:15px;"></i>
                        {{ form.processing ? 'Guardando...' : 'Guardar nueva contraseña' }}
                    </button>
                </form>
            </div>

            <div style="text-align:center; margin-top:20px;">
                <Link href="/login" style="font-size:13px; font-weight:700; color:#38bdf8; text-decoration:none; display:inline-flex; align-items:center; gap:6px;"
                    onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                    <i class="pi pi-arrow-left" style="font-size:12px;"></i>
                    Volver al inicio de sesión
                </Link>
            </div>
        </div>
    </div>
</template>