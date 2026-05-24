<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Clínica Minerva" />

    <div style="min-height:100vh; background:#f1f5f9; font-family:'DM Sans','Segoe UI',sans-serif;">

        <!-- Header -->
        <header style="background:white; border-bottom:1px solid #e2e8f0; padding:0 32px; height:64px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 8px rgba(0,0,0,0.05);">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:40px; height:40px; border-radius:12px; background:linear-gradient(135deg,#0891b2,#0e7490); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(8,145,178,0.3);">
                    <i class="pi pi-heart-fill" style="color:white; font-size:16px;"></i>
                </div>
                <div>
                    <div style="font-size:15px; font-weight:800; color:#0f172a; line-height:1.2;">Clínica Minerva</div>
                    <div style="font-size:10px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:0.08em;">Sistema de exámenes clínicos</div>
                </div>
            </div>
            <nav style="display:flex; align-items:center; gap:10px;">
                <Link href="/login"
                    style="padding:9px 22px; border-radius:24px; border:1.5px solid #e2e8f0; color:#475569; font-size:13px; font-weight:700; text-decoration:none; background:white; transition:all 0.2s;"
                    onmouseover="this.style.borderColor='#0891b2'; this.style.color='#0891b2'"
                    onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#475569'">
                    Iniciar sesión
                </Link>
                <Link href="/register"
                    style="padding:9px 22px; border-radius:24px; background:linear-gradient(135deg,#0891b2,#0e7490); color:white; font-size:13px; font-weight:700; text-decoration:none; box-shadow:0 4px 12px rgba(8,145,178,0.3); transition:all 0.2s;"
                    onmouseover="this.style.opacity='0.9'"
                    onmouseout="this.style.opacity='1'">
                    Registrarse
                </Link>
            </nav>
        </header>

        <!-- Hero -->
        <main style="max-width:900px; margin:0 auto; padding:80px 32px 60px; text-align:center;">

            <!-- Badge -->
            <div style="display:inline-flex; align-items:center; gap:8px; padding:6px 18px; border-radius:24px; background:#ecfeff; border:1px solid #a5f3fc; margin-bottom:28px;">
                <span style="width:7px; height:7px; border-radius:50%; background:#0891b2; display:inline-block;"></span>
                <span style="font-size:12px; font-weight:700; color:#0e7490; letter-spacing:0.04em;">Clínica Minerva</span>
            </div>

            <!-- Título -->
            <h1 style="font-size:56px; font-weight:900; color:#0f172a; line-height:1.08; margin:0 0 20px; letter-spacing:-0.03em;">
                Del examen al resultado,<br>
                <span style="color:#0891b2;">todo en un solo lugar.</span>
            </h1>

            <p style="font-size:17px; color:#64748b; line-height:1.75; margin:0 auto 44px; max-width:540px;">
                Agenda citas, registra órdenes de laboratorio, genera reportes PDF
                y entrega resultados por correo electrónico de forma automática.
            </p>

            <!-- CTAs -->
            <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:12px; margin-bottom:64px;">
                <Link href="/login"
                    style="display:inline-flex; align-items:center; gap:10px; padding:14px 32px; border-radius:14px; background:linear-gradient(135deg,#0891b2,#0e7490); color:white; font-size:15px; font-weight:700; text-decoration:none; box-shadow:0 4px 16px rgba(8,145,178,0.35); transition:all 0.2s;"
                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 8px 24px rgba(8,145,178,0.45)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(8,145,178,0.35)'">
                    <i class="pi pi-sign-in" style="font-size:15px;"></i>
                    Entrar al sistema
                </Link>
                <Link href="/register"
                    style="display:inline-flex; align-items:center; gap:10px; padding:14px 32px; border-radius:14px; background:white; border:1.5px solid #e2e8f0; color:#374151; font-size:15px; font-weight:700; text-decoration:none; box-shadow:0 2px 8px rgba(0,0,0,0.06); transition:all 0.2s;"
                    onmouseover="this.style.borderColor='#0891b2'; this.style.color='#0891b2'; this.style.transform='translateY(-1px)'"
                    onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#374151'; this.style.transform='translateY(0)'">
                    <i class="pi pi-user-plus" style="font-size:15px;"></i>
                    Crear cuenta
                </Link>
            </div>

            <!-- Feature cards -->
            <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px;">
                <div v-for="feat in [
                    { icon:'pi-users',    label:'Pacientes',    desc:'Registro y historial clínico',  color:'#0891b2', bg:'#ecfeff' },
                    { icon:'pi-calendar', label:'Citas',        desc:'Agenda con horarios',            color:'#059669', bg:'#ecfdf5' },
                    { icon:'pi-file-pdf', label:'Reportes PDF', desc:'Generación automática',          color:'#7c3aed', bg:'#f5f3ff' },
                    { icon:'pi-envelope', label:'Correo',       desc:'Entrega digital inmediata',      color:'#d97706', bg:'#fffbeb' },
                ]" :key="feat.label"
                    style="background:white; border-radius:18px; padding:22px 16px; text-align:center; border:1px solid #f1f5f9; box-shadow:0 2px 10px rgba(0,0,0,0.05); transition:all 0.2s; cursor:default;"
                    onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)'">
                    <div :style="`width:44px; height:44px; border-radius:13px; background:${feat.bg}; display:flex; align-items:center; justify-content:center; margin:0 auto 14px;`">
                        <i :class="`pi ${feat.icon}`" :style="`font-size:20px; color:${feat.color};`"></i>
                    </div>
                    <div style="font-size:13px; font-weight:800; color:#0f172a; margin-bottom:4px;">{{ feat.label }}</div>
                    <div style="font-size:11px; color:#94a3b8; line-height:1.5;">{{ feat.desc }}</div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer style="text-align:center; padding:20px 32px; border-top:1px solid #e2e8f0; background:white; margin-top:40px;">
            <span style="font-size:11px; color:#94a3b8;">© 2026 Clínica Minerva · Sistema de Gestión de Exámenes Clínicos · HDP115 </span>
        </footer>
    </div>
</template>